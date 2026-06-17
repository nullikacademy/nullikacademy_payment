<?php

declare(strict_types=1);

/**
 * Database seeder. Populates tools, plans, default admin, and settings.
 * Usage: php database/seed.php
 */

$basePath = dirname(__DIR__);
require $basePath . '/app/Core/Autoloader.php';
App\Core\Autoloader::register();

use App\Core\Config;
use App\Core\Database;
use App\Core\Env;

Env::load($basePath . '/.env');
Config::load($basePath . '/config');
date_default_timezone_set((string) config('app.timezone', 'Asia/Tehran'));

fwrite(STDOUT, "Nullik Academy — Seeder\n" . str_repeat('=', 42) . "\n");

/* ---------------- Admin ---------------- */
$existingAdmin = Database::scalar('SELECT COUNT(*) FROM admins WHERE username = ?', ['admin']);
if (!$existingAdmin) {
    Database::insert(
        'INSERT INTO admins (username, full_name, password_hash, role, status) VALUES (?, ?, ?, ?, ?)',
        [
            'admin',
            'مدیر کل',
            password_hash('ChangeMeImmediately123!', PASSWORD_BCRYPT),
            'super_admin',
            'active',
        ]
    );
    fwrite(STDOUT, "✔ Default admin created (admin / ChangeMeImmediately123!)\n");
} else {
    fwrite(STDOUT, "• Admin already exists, skipped.\n");
}

/* ---------------- Settings ---------------- */
$settings = [
    ['usdt_price_irt', '60000', 'pricing'],
    ['usdt_price_updated_at', date('Y-m-d H:i:s'), 'pricing'],
    ['site_title', 'نالیک آکادمی | مارکت‌پلیس اشتراک هوش مصنوعی', 'seo'],
    ['site_description', 'خرید آسان و امن اشتراک ابزارهای هوش مصنوعی با تحویل سریع و پشتیبانی ۲۴ ساعته.', 'seo'],
    ['online_gateway_enabled', '0', 'payment'],
];
foreach ($settings as [$key, $value, $group]) {
    Database::statement(
        'INSERT INTO settings (`key`, `value`, `group`) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
        [$key, $value, $group]
    );
}
fwrite(STDOUT, "✔ Settings seeded.\n");

/* ---------------- Tools ---------------- */
$tools = [
    ['ChatGPT', 'chatgpt', 'دسترسی کامل به مدل‌های پیشرفته GPT برای نگارش، کدنویسی و تحلیل.', '#10A37F'],
    ['Claude', 'claude', 'دستیار هوش مصنوعی Anthropic با درک عمیق و پاسخ‌های دقیق و طولانی.', '#D97757'],
    ['Gemini', 'gemini', 'مدل چندوجهی گوگل برای متن، تصویر و استدلال پیشرفته.', '#1A73E8'],
    ['Higgsfield', 'higgsfield', 'تولید ویدیوهای سینمایی و خلاقانه با هوش مصنوعی.', '#7C3AED'],
    ['Midjourney', 'midjourney', 'خلق تصاویر هنری و فوق‌واقع‌گرایانه با کیفیت بی‌نظیر.', '#3B82F6'],
    ['Perplexity', 'perplexity', 'موتور پاسخ‌گوی هوشمند با ارجاع به منابع معتبر و به‌روز.', '#20808D'],
    ['Cursor', 'cursor', 'ویرایشگر کد مجهز به هوش مصنوعی برای توسعه‌دهندگان حرفه‌ای.', '#4B5563'],
    ['Bolt', 'bolt', 'ساخت اپلیکیشن‌های فول‌استک تنها با یک پرامپت.', '#2563EB'],
    ['Lovable', 'lovable', 'تبدیل ایده به وب‌اپلیکیشن کامل بدون نیاز به کدنویسی.', '#FF4D6D'],
    ['Runway', 'runway', 'استودیوی ویدیوی هوش مصنوعی برای ساخت و ویرایش حرفه‌ای.', '#22D3EE'],
    ['ElevenLabs', 'elevenlabs', 'تبدیل متن به گفتار طبیعی و کلون صدا با کیفیت استودیویی.', '#6366F1'],
    ['Suno', 'suno', 'ساخت موسیقی و آهنگ کامل با خواننده هوش مصنوعی.', '#F59E0B'],
    ['HeyGen', 'heygen', 'تولید ویدیوهای آواتار و دوبله چندزبانه با هوش مصنوعی.', '#5B5BFF'],
    ['Canva AI', 'canva-ai', 'ابزارهای طراحی هوشمند Canva برای ساخت محتوای حرفه‌ای.', '#00C4CC'],
    ['Notion AI', 'notion-ai', 'دستیار هوشمند Notion برای نوشتن، خلاصه‌سازی و سازماندهی.', '#9CA3AF'],
    ['DeepSeek', 'deepseek', 'مدل‌های زبانی قدرتمند و مقرون‌به‌صرفه برای کدنویسی و استدلال.', '#4D6BFE'],
];

$toolIds = [];
$order = 0;
foreach ($tools as [$name, $slug, $desc, $color]) {
    $existing = Database::selectOne('SELECT id FROM tools WHERE slug = ?', [$slug]);
    if ($existing) {
        $toolIds[$slug] = (int) $existing['id'];
        continue;
    }
    $id = Database::insert(
        'INSERT INTO tools (name, slug, logo, color, description, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$name, $slug, "tools/{$slug}.svg", $color, $desc, $order++, 'active']
    );
    $toolIds[$slug] = $id;
}
fwrite(STDOUT, "✔ " . count($toolIds) . " tools seeded.\n");

/* ---------------- Plans ---------------- */
$usdtPrice = (float) Database::scalar('SELECT `value` FROM settings WHERE `key` = ?', ['usdt_price_irt']);

/**
 * Plan template per tool: [name, badge, duration, days, price_usdt, mode, featured]
 */
$planTemplate = [
    ['اقتصادی', null, 'یک ماهه', 30, 9.0, 'email_password', 0],
    ['پرفروش', 'پرفروش', 'سه ماهه', 90, 24.0, 'email_password', 1],
    ['حرفه‌ای', 'پیشنهادی', 'شش ماهه', 180, 44.0, 'email_password', 0],
    ['ویژه', 'ویژه', 'یک ساله', 365, 79.0, 'organization_id', 0],
];

$plansSeeded = 0;
foreach ($toolIds as $slug => $toolId) {
    $has = Database::scalar('SELECT COUNT(*) FROM plans WHERE tool_id = ?', [$toolId]);
    if ($has) {
        continue;
    }
    $sort = 0;
    foreach ($planTemplate as [$pname, $badge, $duration, $days, $priceUsdt, $mode, $featured]) {
        $priceIrt = (int) round($priceUsdt * $usdtPrice);
        Database::insert(
            'INSERT INTO plans (tool_id, name, badge, duration, duration_days, price_usdt, price_irt, mode_type, is_featured, sort_order, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$toolId, $pname, $badge, $duration, $days, $priceUsdt, $priceIrt, $mode, $featured, $sort++, 'active']
        );
        $plansSeeded++;
    }
}
fwrite(STDOUT, "✔ {$plansSeeded} plans seeded.\n");
fwrite(STDOUT, str_repeat('=', 42) . "\nSeeding complete.\n");
