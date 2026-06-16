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

  <section class="glass detail-card">
    <h2 class="detail-card__title">نرخ USDT</h2>
    <p class="page-sub">نرخ فعلی: <strong><?= e(money_irt((float) ($settings['usdt_price_irt'] ?? 0))) ?></strong> تومان</p>
    <p class="page-sub">آخرین بروزرسانی: <?= e(to_persian_digits((string) ($settings['usdt_price_updated_at'] ?? '—'))) ?></p>
    <button class="btn btn--ghost btn--sm" id="refreshPriceBtn">بروزرسانی نرخ و قیمت پلن‌ها</button>
  </section>
</div>
