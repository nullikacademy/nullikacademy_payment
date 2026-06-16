# مستندات امنیتی — Nullik Academy

این سند کنترل‌های امنیتی پیاده‌سازی‌شده و توصیه‌های سخت‌سازی را شرح می‌دهد.

## ۱. تزریق SQL (SQL Injection)
- تمام کوئری‌ها از طریق `App\Core\Database` و **Prepared Statements** با پارامترهای
  bind اجرا می‌شوند (`PDO::ATTR_EMULATE_PREPARES = false`).
- نام جداول/ستون‌ها هرگز از ورودی کاربر ساخته نمی‌شوند.

## ۲. CSRF
- توکن per-session در `App\Core\Csrf` تولید و با `hash_equals` (timing-safe) بررسی می‌شود.
- `CsrfMiddleware` روی تمام درخواست‌های `POST/PUT/DELETE` اعمال می‌شود؛ کنترلرها نیز
  `ensureCsrf()` را فراخوانی می‌کنند. توکن از طریق هدر `X-CSRF-TOKEN` یا فیلد `_token` می‌آید.

## ۳. XSS
- خروجی‌ها با تابع `e()` (`htmlspecialchars` با `ENT_QUOTES`) اسکیپ می‌شوند.
- در سمت کلاینت، داده‌های داینامیک با `escapeHtml` اسکیپ و کانفیگ به‌صورت
  JSON **غیرقابل‌اجرا** (`<script type="application/json">`) تزریق می‌شود.
- **Content-Security-Policy** سخت‌گیرانه: `script-src 'self'` (هیچ اسکریپت inline اجرا نمی‌شود).

## ۴. هدرهای امنیتی
در `App\Core\App::sendSecurityHeaders()`:
- `Content-Security-Policy` (default-src 'self'، اتصال‌های مجاز محدود)
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN` + `frame-ancestors 'self'`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Strict-Transport-Security` (در صورت فعال بودن HTTPS)
- حذف `X-Powered-By`

## ۵. اجبار HTTPS
- با `FORCE_HTTPS=true`، درخواست‌های غیرامن با ۳۰۱ به HTTPS هدایت می‌شوند
  (با احترام به `X-Forwarded-Proto` پشت پراکسی/CDN).

## ۶. نشست (Session) سخت‌شده
- کوکی‌ها: `HttpOnly`, `SameSite=Lax`, و `Secure` (در تولید).
- `session.use_strict_mode=1`, `use_only_cookies=1`.
- چرخش شناسهٔ نشست (regenerate) هنگام ورود و هر ۳۰ دقیقه.

## ۷. رمز عبور و رمزنگاری
- رمز مدیران: `password_hash` با **bcrypt**؛ بررسی با `password_verify`.
- در ورود ادمین، حتی برای کاربر ناموجود یک hash ساختگی verify می‌شود تا از
  **افشای کاربر از طریق timing** جلوگیری شود.
- رمز عبور حساب مشتری (که مدیر باید برای فعال‌سازی بخواند) با **AES-256-GCM**
  (`App\Services\Crypto`، احراز اصالت‌شده) رمزنگاری و ذخیره می‌شود — کلید از `APP_KEY`.

## ۸. OTP و محافظت Brute-force
- کد ۴ رقمی، ذخیرهٔ **هش‌شده** (bcrypt)، انقضای ۱۲۰ ثانیه.
- حداکثر **۳ تلاش** برای هر کد؛ کول‌داون **۶۰ ثانیه** برای ارسال مجدد.
- محدودیت سقف **۸ درخواست در ساعت** برای هر شماره (`RateLimiter`).
- شمارهٔ موبایلِ ثبتِ سفارش با شمارهٔ تأییدشده در نشست تطبیق داده می‌شود (anti-tamper).

## ۹. Rate Limiting
- `RateLimiter` فایل‌محور (مناسب هاست اشتراکی، بدون Redis).
- API: ۶۰ درخواست/دقیقه به ازای IP (`RateLimitMiddleware`).
- ورود ادمین: ۵ تلاش/۱۵ دقیقه به ازای IP (قفل موقت).

## ۱۰. آپلود امن فایل
- اعتبارسنجی **MIME واقعی** با `finfo` (نه هدر کلاینت)، پسوند، و سقف **۱۰MB**.
- بررسی تصویر بودن با `getimagesize`.
- نام فایل **تصادفی** (`random_bytes`)، ذخیره **خارج از public** در `storage/receipts`.
- سرو رسید فقط از طریق مسیر احراز هویت‌شدهٔ `/admin/receipts/{token}` با هدر `no-store`.

## ۱۱. اعتبارسنجی ورودی
- `App\Core\Validator` با قواعد: required, email, mobile_ir, persian, in, regex, min/max و ….
- قیمت‌ها همیشه **سمت سرور** از روی پلن محاسبه می‌شوند؛ قیمت ارسالی کلاینت نادیده گرفته می‌شود.

## ۱۲. Audit Logging
- ورود موفق/ناموفق ادمین، ساخت سفارش و رویدادهای حساس در `storage/logs/` ثبت می‌شوند
  (`App\Core\Logger::audit`).

## ۱۳. محافظت فایل‌سیستم
- `.htaccess` ریشه و `storage/.htaccess` دسترسی وب به `.env`، لاگ‌ها، رسیدها و
  composer را مسدود می‌کنند. در Nginx رول‌های معادل اعمال شود (به DEPLOYMENT مراجعه کنید).

---

## چک‌لیست سخت‌سازی تولید
- [ ] `APP_DEBUG=false` و `APP_ENV=production`
- [ ] `APP_KEY` یکتا و تصادفی تنظیم شده
- [ ] رمز ادمین پیش‌فرض تغییر کرده
- [ ] `FORCE_HTTPS=true` و گواهی TLS معتبر
- [ ] Document Root روی `public/`
- [ ] دسترسی وب به `storage/`, `.env`, `vendor/`, `database/` مسدود است
- [ ] کرون‌جاب‌ها فعال‌اند
- [ ] پشتیبان‌گیری منظم دیتابیس
- [ ] مقادیر واقعی IPPanel و Telegram تنظیم شده‌اند
