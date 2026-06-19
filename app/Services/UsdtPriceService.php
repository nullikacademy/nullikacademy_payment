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
     * Return current ticker data (cached). Shape:
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

        return [
            'price'             => round($this->applyMarkup($price)),
            'high_24'           => round($this->applyMarkup((float) ($row['high_24'] ?? $price))),
            'low_24'            => round($this->applyMarkup((float) ($row['low_24'] ?? $price))),
            'change_percent_24' => round((float) ($row['change_percent_24'] ?? 0), 2),
            'updated_at'        => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Apply the admin-configured markup to the raw API price.
     * Settings: usdt_markup_type (value|percent), usdt_markup_amount.
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
     * Refresh the price, persist it, and recalculate all plan IRT prices.
     * Invoked by cron every 10 minutes.
     */
    public function refreshAndRecalculate(): array
    {
        $ticker = $this->ticker(true);
        $price = (float) $ticker['price'];

        if ($price > 0) {
            Setting::set('usdt_price_irt', (string) $price, 'pricing');
            Setting::set('usdt_price_updated_at', date('Y-m-d H:i:s'), 'pricing');
            $updated = Plan::recalculatePrices($price);
            Logger::info('USDT price refreshed.', ['price' => $price, 'plans_updated' => $updated]);
            return ['price' => $price, 'plans_updated' => $updated];
        }

        return ['price' => 0, 'plans_updated' => 0];
    }
}
