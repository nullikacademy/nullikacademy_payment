# راهنمای استقرار — Nullik Academy

## الف) Apache (هاست اشتراکی یا VPS)

### حالت ۱: Document Root روی `public/` (توصیه‌شده)
VirtualHost را چنین تنظیم کنید:

```apache
<VirtualHost *:443>
    ServerName nullikacademy.com
    DocumentRoot /var/www/nullik/public

    <Directory /var/www/nullik/public>
        AllowOverride All
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile      /etc/letsencrypt/live/nullikacademy.com/fullchain.pem
    SSLCertificateKeyFile   /etc/letsencrypt/live/nullikacademy.com/privkey.pem
</VirtualHost>
```

فایل `public/.htaccess` بازنویسی مسیرها و هدرهای امنیتی را انجام می‌دهد.

### حالت ۲: هاست اشتراکی بدون تغییر Document Root
کل پروژه را در ریشه آپلود کنید؛ `.htaccess` ریشه درخواست‌ها را به `public/`
هدایت می‌کند و دسترسی به فایل‌های حساس را مسدود می‌نماید.

---

## ب) Nginx

```nginx
server {
    listen 443 ssl http2;
    server_name nullikacademy.com;
    root /var/www/nullik/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/nullikacademy.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/nullikacademy.com/privkey.pem;

    client_max_body_size 12M;

    # Front controller
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # مسدودسازی مسیرهای حساس (خارج از public هستند ولی محکم‌کاری)
    location ~ /\.(env|git) { deny all; }
    location ~* /(storage|app|config|database|routes|vendor)/ { deny all; }

    # کش استاتیک
    location ~* \.(css|js|svg|png|jpg|jpeg|webp|woff2?)$ {
        expires 7d;
        add_header Cache-Control "public, immutable";
    }
}

# ریdirect HTTP -> HTTPS
server {
    listen 80;
    server_name nullikacademy.com;
    return 301 https://$host$request_uri;
}
```

---

## ج) مراحل استقرار

```bash
# 1) دریافت کد روی سرور
git clone <repo> /var/www/nullik && cd /var/www/nullik

# 2) محیط تولید
cp .env.example .env
# ویرایش .env: APP_ENV=production, APP_DEBUG=false, FORCE_HTTPS=true,
# APP_URL واقعی، APP_KEY تصادفی، DB_*، IPPANEL_*، TELEGRAM_*، PAYMENT_*
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"

# 3) وابستگی‌ها (در صورت استفاده از Composer)
composer install --no-dev --optimize-autoloader

# 4) دیتابیس
php database/migrate.php
php database/seed.php

# 5) دسترسی‌ها
chown -R www-data:www-data storage public/uploads public/assets/images/tools
chmod -R 750 storage

# 6) کرون
crontab -e   # خطوط بخش کرون را اضافه کنید
```

---

## د) کرون‌جاب‌ها

```cron
0,10,20,30,40,50 * * * * /usr/bin/php /var/www/nullik/cron/refresh_usdt_price.php >> /var/www/nullik/storage/logs/cron.log 2>&1
0 3 * * * /usr/bin/php /var/www/nullik/cron/purge_otps.php >> /var/www/nullik/storage/logs/cron.log 2>&1
```

---

## ه) پس از استقرار — چک‌لیست
- [ ] `https://yourdomain/` بدون خطا بالا می‌آید و کاروسل/پلن‌ها لود می‌شوند.
- [ ] `/admin` ورود می‌گیرد؛ رمز پیش‌فرض تغییر کرده.
- [ ] ارسال OTP و ثبت یک سفارش تستی کار می‌کند.
- [ ] اعلان تلگرام و پیامک دریافت می‌شود.
- [ ] هدرهای امنیتی با `curl -I` دیده می‌شوند.
- [ ] `storage/` و `.env` از طریق وب در دسترس نیستند (تست با مرورگر).
- [ ] کرون قیمت تتر هر ۱۰ دقیقه لاگ می‌زند.

---

## و) پشتیبان‌گیری و نگه‌داری
```bash
# پشتیبان روزانه دیتابیس
mysqldump -u USER -p nullik_academy | gzip > backup-$(date +%F).sql.gz
# پشتیبان رسیدها
tar czf receipts-$(date +%F).tgz storage/receipts
```
لاگ‌ها در `storage/logs/YYYY-MM-DD.log` نگه‌داری و در صورت نیاز چرخش (logrotate) شوند.
