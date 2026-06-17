# نالیک آکادمی — Nullik Academy

مارکت‌پلیس پریمیوم اشتراک ابزارهای هوش مصنوعی. یک اپلیکیشن کامل و آمادهٔ تولید
(production-ready) با معماری MVC، REST API، پنل مدیریت، پرداخت کارت‌به‌کارت،
تأیید موبایل با OTP، و اعلان‌های پیامک و تلگرام.

> Premium AI subscription marketplace — full-stack PHP 8.3 / MySQL 8, RTL Persian,
> glassmorphism dark-luxury UI, vanilla JS (no build step), shared-hosting friendly.

---

## ✨ امکانات کلیدی

- **لندینگ تک‌صفحه‌ای پریمیوم** با کاروسل بی‌نهایت ابزارها، اطلاعات داینامیک، و تیکر زندهٔ قیمت تتر.
- **چک‌اوت ۵ مرحله‌ای AJAX** بدون رفرش صفحه: تأیید موبایل (OTP)، اطلاعات شخصی،
  اطلاعات حساب (دو حالته)، بررسی سفارش + پرداخت، بارگذاری رسید.
- **OTP امن**: کد ۴ رقمی، ذخیرهٔ هش‌شده، انقضای ۲ دقیقه، حداکثر ۳ تلاش، کول‌داون ۶۰ ثانیه.
- **پرداخت کارت‌به‌کارت** با کارت طراحی‌شدهٔ الهام‌گرفته از پاسارگاد و دکمه‌های کپی.
- **پنل مدیریت کامل**: داشبورد با نمودار، مدیریت سفارش‌ها (فیلتر/جستجو/خروجی CSV)،
  CRUD ابزارها و پلن‌ها، مدیران با نقش، تنظیمات.
- **محاسبهٔ خودکار قیمت**: مدیر فقط قیمت USDT را وارد می‌کند؛ قیمت تومان از نرخ
  زندهٔ تتر (Tabdeal) محاسبه و هر ۱۰ دقیقه با کرون به‌روزرسانی می‌شود.
- **اعلان‌ها**: پیامک با IPPanel Pattern API و اعلان تلگرام به مدیر پس از هر سفارش.
- **امنیت**: CSRF، محافظت XSS، Prepared Statements، اعتبارسنجی آپلود + MIME،
  Rate Limiting، هدرهای امنیتی + CSP، هش رمز، رمزنگاری AES-256-GCM، Audit Log.

---

## 🧱 ساختار پروژه

```
nullikacademy_payment/
├── app/
│   ├── Controllers/        # کنترلرها (Web, Api, Admin)
│   ├── Core/               # هستهٔ فریم‌ورک (Router, DB, Request/Response, ...)
│   ├── Helpers/            # توابع کمکی سراسری
│   ├── Middleware/         # CSRF, AdminAuth, RateLimit
│   ├── Models/             # مدل‌های داده
│   └── Services/           # سرویس‌ها (OTP, SMS, Telegram, USDT, Order, Auth, ...)
├── config/                 # پیکربندی (app, database, services)
├── cron/                   # اسکریپت‌های کرون
├── database/
│   ├── migrations/         # مهاجرت‌های SQL
│   ├── migrate.php         # اجرا‌کنندهٔ مهاجرت
│   ├── seed.php            # داده‌گذار اولیه
│   └── schema.sql          # اسکیمای کامل یکپارچه
├── docs/                   # مستندات نصب/امنیت/استقرار
├── public/                 # ریشهٔ وب (front controller + assets)
│   ├── assets/{css,js,images}
│   ├── index.php
│   └── .htaccess
├── routes/                 # web.php, api.php, admin.php
├── storage/                # رسیدها، لاگ‌ها، کش (خارج از دسترس وب)
├── views/                  # قالب‌ها (layouts, components, pages, admin)
├── .env.example
└── composer.json
```

---

## 🚀 نصب سریع

```bash
# ۱) وابستگی‌ها (اختیاری — autoloader داخلی هم موجود است)
composer install

# ۲) پیکربندی
cp .env.example .env
php -r "echo 'APP_KEY=base64:'.base64_encode(random_bytes(32)).PHP_EOL;"   # کلید را در .env قرار دهید
# مقادیر DB و سرویس‌ها را در .env تنظیم کنید

# ۳) دیتابیس
php database/migrate.php       # ساخت جداول
php database/seed.php          # ابزارها، پلن‌ها، ادمین پیش‌فرض

# ۴) اجرا (توسعه)
php -S 127.0.0.1:8000 -t public
```

سپس به `http://127.0.0.1:8000` و پنل مدیریت `http://127.0.0.1:8000/admin` بروید.

**ورود پیش‌فرض مدیر:** نام کاربری `admin` — رمز `ChangeMeImmediately123!`
> ⚠️ بلافاصله پس از اولین ورود، رمز را از بخش «مدیران» تغییر دهید.

راهنمای کامل: [`docs/INSTALLATION.md`](docs/INSTALLATION.md) ·
استقرار: [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) ·
امنیت: [`docs/SECURITY.md`](docs/SECURITY.md)

---

## 🔌 خلاصهٔ REST API

| متد | مسیر | توضیح |
|-----|------|-------|
| GET | `/api/tools` | فهرست ابزارهای فعال |
| GET | `/api/tools/{slug}` | اطلاعات یک ابزار |
| GET | `/api/tools/{slug}/plans` | پلن‌های یک ابزار |
| GET | `/api/usdt-price` | قیمت لحظه‌ای تتر |
| POST | `/api/send-otp` | ارسال کد تأیید |
| POST | `/api/verify-otp` | بررسی کد تأیید |
| POST | `/api/upload-receipt` | بارگذاری رسید |
| POST | `/api/order/create` | ثبت سفارش |
| POST | `/admin/login` | ورود مدیر |

همهٔ پاسخ‌ها JSON با ساختار `{ success, message, data | errors }` هستند.

---

## ⏱️ کرون‌جاب‌ها

```cron
# به‌روزرسانی قیمت USDT و قیمت پلن‌ها (هر ۱۰ دقیقه)
0,10,20,30,40,50 * * * * /usr/bin/php /path/to/cron/refresh_usdt_price.php >> /path/to/storage/logs/cron.log 2>&1

# پاک‌سازی OTPهای منقضی (روزانه)
0 3 * * * /usr/bin/php /path/to/cron/purge_otps.php >> /path/to/storage/logs/cron.log 2>&1
```

---

## 🛠️ تکنولوژی‌ها

PHP 8.3+ · MySQL 8+ · MVC + REST · Vanilla JS (Fetch API) · CSS3 (Glassmorphism) ·
RTL / Vazirmatn · Apache/Nginx · سازگار با هاست اشتراکی.

## 📄 لایسنس

اختصاصی (Proprietary) — © Nullik Academy.
