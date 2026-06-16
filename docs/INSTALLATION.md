# راهنمای نصب — Nullik Academy

## پیش‌نیازها

- PHP **8.3+** با اکستنشن‌های: `pdo_mysql`, `openssl`, `curl`, `fileinfo`, `mbstring`, `json`
- MySQL **8.0+** (یا MariaDB 10.6+)
- Composer (اختیاری — autoloader داخلی نیز ارائه شده)
- وب‌سرور Apache (mod_rewrite) یا Nginx

بررسی اکستنشن‌ها:

```bash
php -m | grep -iE 'pdo_mysql|openssl|curl|fileinfo|mbstring'
```

---

## ۱. دریافت کد و وابستگی‌ها

```bash
git clone <repo-url> nullikacademy
cd nullikacademy
composer install      # اگر Composer ندارید این مرحله را رد کنید
```

> بدون Composer هم برنامه کار می‌کند؛ `public/index.php` در صورت نبود
> `vendor/autoload.php` از autoloader داخلی (`app/Core/Autoloader.php`) استفاده می‌کند.

---

## ۲. پیکربندی محیط

```bash
cp .env.example .env
```

سپس کلید اپلیکیشن را تولید و در `.env` قرار دهید:

```bash
php -r "echo 'APP_KEY=base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

مقادیر کلیدی `.env`:

| متغیر | توضیح |
|-------|-------|
| `APP_URL` | آدرس کامل سایت (مثلاً `https://nullikacademy.com`) |
| `APP_KEY` | کلید رمزنگاری ۳۲ بایتی (الزامی برای رمزنگاری رمز عبور سفارش‌ها) |
| `DB_*` | اطلاعات اتصال دیتابیس |
| `IPPANEL_*` | کلید و کدهای پترن پنل پیامک |
| `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID` | اعلان تلگرام مدیر |
| `PAYMENT_*` | شمارهٔ کارت، شبا و نام صاحب حساب |

---

## ۳. ساخت دیتابیس

ابتدا دیتابیس را بسازید:

```sql
CREATE DATABASE nullik_academy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

سپس مهاجرت و داده‌گذاری:

```bash
php database/migrate.php        # ساخت تمام جداول
php database/seed.php           # ابزارها، پلن‌ها، تنظیمات، ادمین پیش‌فرض
```

برای ساخت مجدد کامل (حذف و ساخت دوباره):

```bash
php database/migrate.php fresh && php database/seed.php
```

> روش جایگزین: فایل یکپارچهٔ `database/schema.sql` را مستقیماً در phpMyAdmin/CLI ایمپورت کنید،
> سپس فقط `php database/seed.php` را اجرا نمایید.

---

## ۴. دسترسی‌های پوشه‌ها

```bash
chmod -R 750 storage
chmod -R 755 public/uploads public/assets/images/tools
# وب‌سرور باید مالک storage باشد (مثلاً www-data)
chown -R www-data:www-data storage public/uploads
```

پوشهٔ `storage/` نباید از طریق وب قابل دسترسی باشد (با `.htaccess` محافظت شده؛
در Nginx رول معادل در `docs/DEPLOYMENT.md` آمده است).

---

## ۵. اجرا

**توسعه (built-in server):**

```bash
php -S 127.0.0.1:8000 -t public
```

**تولید:** Document Root را روی پوشهٔ `public/` تنظیم کنید. اگر امکان تغییر
Document Root نیست (هاست اشتراکی)، فایل `.htaccess` ریشه به‌صورت خودکار
درخواست‌ها را به `public/` هدایت می‌کند.

---

## ۶. کرون‌جاب‌ها

```cron
0,10,20,30,40,50 * * * * /usr/bin/php /path/to/cron/refresh_usdt_price.php >> /path/to/storage/logs/cron.log 2>&1
0 3 * * * /usr/bin/php /path/to/cron/purge_otps.php >> /path/to/storage/logs/cron.log 2>&1
```

---

## ۷. ورود به پنل مدیریت

به `/admin` بروید و با `admin` / `ChangeMeImmediately123!` وارد شوید،
سپس **بلافاصله** رمز را از بخش «مدیران» تغییر دهید.

---

## رفع اشکال

- **صفحهٔ سفید/خطای ۵۰۰:** `APP_DEBUG=true` را موقتاً فعال کنید و `storage/logs/` را ببینید.
- **خطای اتصال دیتابیس:** مقادیر `DB_*` و در دسترس بودن MySQL را بررسی کنید.
- **پیامک ارسال نمی‌شود:** کلید و کد پترن IPPanel را چک کنید؛ در حالت `APP_DEBUG=true`
  کد OTP در پاسخ API و توست نمایش داده می‌شود (برای تست).
- **آپلود رسید رد می‌شود:** `upload_max_filesize` و `post_max_size` در PHP باید ≥ ۱۲MB باشند.
