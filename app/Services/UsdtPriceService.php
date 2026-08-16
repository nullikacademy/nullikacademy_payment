<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Http;
use App\Core\Logger;
use App\Models\Plan;
use App\Models\Setting;

/**
 * Fetches live USDT->IRT price from Tabdeal and recalculates plan
 * prices. Cached to disk to limit external calls.
 *
 * The admin markup applies to plan pricing only. Everything the public
 * sees — the landing ticker and GET /api/usdt-price — reports the raw
 * market rate, so the quoted Tether price stays truthful.
 */
final class UsdtPriceService
{
    private function cacheFile(): string
    {
        $dir = base_path('storage/cache');
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        return $dir . '/usdt_price.json';
    }

    /**
     * Return current ticker data (cached), at the raw market rate with
     * no markup applied. Shape:
     * ['price','high_24','low_24','change_percent_24','updated_at']
     */
    public function ticker(bool $force = false): array
    {
        $ttl = (int) Config::get('services.usdt.cache_ttl', 600);
        $file = $this->cacheFile();

        if (!$force && is_file($file)) {
            $cached = json_decode((string) file_get_contents($file), true);
            if (is_array($cached) && (time() - ($cached['cached_at'] ?? 0)) < $ttl) {
                return $cached['data'];
            }
        }

        $fresh = $this->fetch();
        if ($fresh !== null) {
            file_put_contents($file, json_encode(['cached_at' => time(), 'data' => $fresh]), LOCK_EX);
            return $fresh;
        }

        // Fall back to last cached value, then to stored setting.
        if (is_file($file)) {
            $cached = json_decode((string) file_get_contents($file), true);
            if (is_array($cached)) {
                return $cached['data'];
            }
        }

        $stored = (float) Setting::get('usdt_price_irt', 60000);
        return [
            'price'              => $stored,
            'high_24'            => $stored,
            'low_24'             => $stored,
            'change_percent_24'  => 0.0,
            'updated_at'         => Setting::get('usdt_price_updated_at', date('Y-m-d H:i:s')),
        ];
    }

    /**
     * Fetch + parse the Tabdeal dynamic-info feed for USDT/IRT.
     */
    private function fetch(): ?array
    {
        $url = (string) Config::get('services.usdt.api');
        $response = Http::get($url);
        if (!$response['ok'] || !is_array($response['json'])) {
            Logger::warning('USDT price fetch failed.', ['status' => $response['status']]);
            return null;
        }

        $row = $this->locateUsdt($response['json']);
        if ($row === null || !isset($row['price'])) {
            Logger::warning('USDT entry not found in price feed.');
            return null;
        }

        // Tabdeal returns IRT values already in Toman.
        $price = (float) $row['price'];
        if ($price <= 0) {
            return null;
        }

        // Raw market values — the markup belongs to plan pricing only.
        return [
            'price'             => round($price),
            'high_24'           => round((float) ($row['high_24'] ?? $price)),
            'low_24'            => round((float) ($row['low_24'] ?? $price)),
            'change_percent_24' => round((float) ($row['change_percent_24'] ?? 0), 2),
            'updated_at'        => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * The rate used to convert a plan's USDT price into Toman: the
     * market rate plus the admin-configured markup. This is the only
     * place the markup is applied.
     *
     * Reads settings only — no outbound request — so it is safe to call
     * on every plan save.
     *
     * @param float|null $marketRate Defaults to the stored market rate.
     */
    public function pricingRate(?float $marketRate = null): float
    {
        $rate = $marketRate ?? (float) Setting::get('usdt_price_irt', 0);
        // Rounded like the market rate, so percentage markups do not leave
        // floating-point noise in the rate shown to the admin.
        return $rate > 0 ? round($this->applyMarkup($rate)) : 0.0;
    }

    /**
     * Reprice every plan from the stored market rate, without hitting
     * the price API. Used when the markup settings change.
     */
    public function recalculatePlans(): int
    {
        $rate = $this->pricingRate();
        return $rate > 0 ? Plan::recalculatePrices($rate) : 0;
    }

    /**
     * Apply the admin-configured markup to a market rate.
     * Settings: usdt_markup_type (value|percent), usdt_markup_amount.
     *
     * Note that in 'value' mode the amount is added to the rate, so each
     * plan gains (price_usdt * amount) Toman — a per-USDT spread rather
     * than a flat fee per order.
     */
    private function applyMarkup(float $price): float
    {
        $type = (string) Setting::get('usdt_markup_type', 'value');
        $amount = (float) Setting::get('usdt_markup_amount', 0);
        if ($amount === 0.0) {
            return $price;
        }
        return $type === 'percent'
            ? $price * (1 + $amount / 100)
            : $price + $amount;
    }

    /**
     * Locate the USDT/IRT price node within the Tabdeal feed.
     * Expected shape: {"currencies":{"USDT":{"IRT":{"price","high_24","low_24","change_percent_24"}}}}
     */
    private function locateUsdt(array $data): ?array
    {
        // Primary: exact Tabdeal path.
        $node = $data['currencies']['USDT']['IRT'] ?? null;
        if (is_array($node) && isset($node['price'])) {
            return $node;
        }

        // Fallbacks for alternative shapes.
        $node = $data['USDT']['IRT'] ?? $data['usdt']['irt'] ?? null;
        if (is_array($node) && isset($node['price'])) {
            return $node;
        }

        // Generic recursive search for a node that has a numeric "price".
        return $this->searchPriceNode($data, 0);
    }

    private function searchPriceNode(array $data, int $depth): ?array
    {
        if ($depth > 4) {
            return null;
        }
        // A USDT node keyed directly.
        foreach (['USDT', 'usdt'] as $key) {
            if (isset($data[$key]['IRT']['price'])) {
                return $data[$key]['IRT'];
            }
            if (isset($data[$key]['price'])) {
                return $data[$key];
            }
        }
        foreach ($data as $value) {
            if (is_array($value)) {
                $found = $this->searchPriceNode($value, $depth + 1);
                if ($found !== null) {
                    return $found;
                }
            }
        }
        return null;
    }

    /**
     * Refresh the market rate, persist it, and recalculate all plan IRT
     * prices at the marked-up rate. Invoked by cron every 10 minutes.
     *
     * usdt_price_irt stores the raw market rate — the markup is applied
     * to plan prices only and is never persisted into that setting.
     */
    public function refreshAndRecalculate(): array
    {
        $ticker = $this->ticker(true);
        $marketRate = (float) $ticker['price'];

        if ($marketRate > 0) {
            Setting::set('usdt_price_irt', (string) $marketRate, 'pricing');
            Setting::set('usdt_price_updated_at', date('Y-m-d H:i:s'), 'pricing');

            $pricingRate = $this->pricingRate($marketRate);
            $updated = Plan::recalculatePrices($pricingRate);

            Logger::info('USDT price refreshed.', [
                'market_rate'   => $marketRate,
                'pricing_rate'  => $pricingRate,
                'plans_updated' => $updated,
            ]);

            return [
                'price'         => $marketRate,
                'pricing_rate'  => $pricingRate,
                'plans_updated' => $updated,
            ];
        }

        return ['price' => 0, 'pricing_rate' => 0, 'plans_updated' => 0];
    }
}
