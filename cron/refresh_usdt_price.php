<?php

declare(strict_types=1);

/**
 * Cron: refresh USDT->IRT price and recalculate all plan IRT prices.
 * Schedule every 10 minutes (crontab):
 *   0,10,20,30,40,50 * * * * /usr/bin/php /path/to/cron/refresh_usdt_price.php >> /path/to/storage/logs/cron.log 2>&1
 */

$basePath = dirname(__DIR__);
require $basePath . '/app/Core/Autoloader.php';
App\Core\Autoloader::register();

App\Core\Env::load($basePath . '/.env');
App\Core\Config::load($basePath . '/config');
date_default_timezone_set((string) config('app.timezone', 'Asia/Tehran'));

$result = (new App\Services\UsdtPriceService())->refreshAndRecalculate();

fwrite(STDOUT, sprintf(
    "[%s] USDT price: %s IRT | plans updated: %d\n",
    date('Y-m-d H:i:s'),
    number_format((float) $result['price']),
    (int) $result['plans_updated']
));
