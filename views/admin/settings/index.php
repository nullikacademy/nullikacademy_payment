<?php /** @var array $settings */ ?>
<div class="page-head"><h1 class="page-title">تنظیمات</h1><p class="page-sub">پیکربندی فروشگاه</p></div>

<div class="detail-grid">
  <section class="glass detail-card detail-card--wide">
    <h2 class="detail-card__title">تنظیمات عمومی و سئو</h2>
    <form id="settingsForm">
      <div class="field"><label class="field__label">عنوان سایت</label>
        <input class="input" name="site_title" value="<?= e($settings['site_title'] ?? '') ?>"></div>
      <div class="field"><label class="field__label">توضیحات سایت (متا)</label>
        <textarea class="input" name="site_description" rows="3"><?= e($settings['site_description'] ?? '') ?></textarea></div>
      <div class="field">
        <label class="admin-login__remember">
          <input type="checkbox" name="online_gateway_enabled" value="1" <?= ($settings['online_gateway_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
          فعال‌سازی درگاه آنلاین
        </label>
      </div>
      <button type="submit" class="btn btn--primary">ذخیره تنظیمات</button>
    </form>
  </section>

  <section class="glass detail-card detail-card--wide">
    <h2 class="detail-card__title">پیامک‌ها (IPPanel)</h2>
    <p class="page-sub">کد پترن همان کدی است که برای ارسال استفاده می‌شود. «متن» صرفاً یادداشت شماست (متن واقعی در پنل IPPanel تعریف می‌شود).</p>
    <form id="smsForm">
      <div class="field"><label class="field__label">کد پترن کد تأیید (OTP)</label>
        <input class="input" dir="ltr" name="sms_pattern_otp" value="<?= e($settings['sms_pattern_otp'] ?? (string) config('services.ippanel.pattern_otp')) ?>"></div>
      <div class="field"><label class="field__label">متن کد تأیید (یادداشت)</label>
        <textarea class="input" name="sms_text_otp" rows="2"><?= e($settings['sms_text_otp'] ?? 'کد تأیید شما: %code%') ?></textarea></div>

      <div class="field"><label class="field__label">کد پترن ثبت سفارش (مشتری)</label>
        <input class="input" dir="ltr" name="sms_pattern_order_user" value="<?= e($settings['sms_pattern_order_user'] ?? (string) config('services.ippanel.pattern_order_user')) ?>"></div>
      <div class="field"><label class="field__label">متن ثبت سفارش مشتری (یادداشت)</label>
        <textarea class="input" name="sms_text_order_user" rows="2"><?= e($settings['sms_text_order_user'] ?? 'سفارش شما با شماره %order% ثبت شد.') ?></textarea></div>

      <div class="field"><label class="field__label">کد پترن سفارش جدید (ادمین)</label>
        <input class="input" dir="ltr" name="sms_pattern_order_admin" value="<?= e($settings['sms_pattern_order_admin'] ?? (string) config('services.ippanel.pattern_order_admin')) ?>"></div>
      <div class="field"><label class="field__label">متن اطلاع‌رسانی ادمین (یادداشت)</label>
        <textarea class="input" name="sms_text_order_admin" rows="2"><?= e($settings['sms_text_order_admin'] ?? 'سفارش جدید: %order% - %tool%') ?></textarea></div>

      <div class="field"><label class="field__label">موبایل ادمین برای دریافت پیامک</label>
        <input class="input" dir="ltr" name="sms_admin_mobile" placeholder="0912xxxxxxx" value="<?= e($settings['sms_admin_mobile'] ?? (string) config('services.ippanel.admin_mobile')) ?>"></div>

      <button type="submit" class="btn btn--primary">ذخیره پیامک‌ها</button>
    </form>
  </section>

  <section class="glass detail-card">
    <h2 class="detail-card__title">نرخ USDT</h2>
    <p class="page-sub">نرخ فعلی: <strong><?= e(money_irt((float) ($settings['usdt_price_irt'] ?? 0))) ?></strong> تومان</p>
    <p class="page-sub">آخرین بروزرسانی: <?= e(to_persian_digits((string) ($settings['usdt_price_updated_at'] ?? '—'))) ?></p>
    <button class="btn btn--ghost btn--sm" id="refreshPriceBtn">بروزرسانی نرخ و قیمت پلن‌ها</button>
  </section>
</div>
