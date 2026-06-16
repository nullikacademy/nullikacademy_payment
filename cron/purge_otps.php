<?php

declare(strict_types=1);

/**
 * Cron: purge expired OTP records (housekeeping).
 * Schedule daily:
 *   0 3 * * * /usr/bin/php /path/to/cron/purge_otps.php >> /path/to/storage/logs/cron.log 2>&1
 */

$basePath = dirname(__DIR__);
require $basePath . '/app/Core/Autoloader.php';
App\Core\Autoloader::register();

App\Core\Env::load($basePath . '/.env');
App\Core\Config::load($basePath . '/config');
date_default_timezone_set((string) config('app.timezone', 'Asia/Tehran'));

$deleted = App\Models\Otp::purgeExpired();
fwrite(STDOUT, sprintf("[%s] Purged %d expired OTP(s).\n", date('Y-m-d H:i:s'), $deleted));
