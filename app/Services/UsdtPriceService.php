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
        if ($row === null) {
            Logger::warning('USDT entry not found in price feed.');
            return null;
        }

        $price = (float) ($row['price'] ?? $row['last'] ?? 0);
        if ($price <= 0) {
            return null;
        }

        // Tabdeal prices are typically in Rial; normalize to Toman.
        $priceToman = $price >= 100000 ? $price / 10 : $price;
        $high = (float) ($row['high_24'] ?? $row['high'] ?? $price);
        $low = (float) ($row['low_24'] ?? $row['low'] ?? $price);

        return [
            'price'             => round($priceToman),
            'high_24'           => round($high >= 100000 ? $high / 10 : $high),
            'low_24'            => round($low >= 100000 ? $low / 10 : $low),
            'change_percent_24' => round((float) ($row['change_percent_24'] ?? $row['change'] ?? 0), 2),
            'updated_at'        => date('Y-m-d H:i:s'),
        ];
    }

    /** Recursively locate the USDT/IRT entry within the feed payload. */
    private function locateUsdt(array $data): ?array
    {
        // Direct keyed structures
        foreach (['usdt', 'USDT', 'usdt_irt', 'USDTIRT'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return $data[$key];
            }
        }

        // List of currency objects
        foreach ($data as $value) {
            if (is_array($value)) {
                $symbol = strtolower((string) ($value['symbol'] ?? $value['currency'] ?? $value['name'] ?? ''));
                if (str_contains($symbol, 'usdt') || str_contains($symbol, 'tether')) {
                    return $value;
                }
                // Recurse one level for nested containers
                $nested = $this->locateUsdt($value);
                if ($nested !== null) {
                    return $nested;
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
