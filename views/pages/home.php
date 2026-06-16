<?php
/**
 * @var array $tools
 * @var array $ticker
 * @var array $payment
 */
?>
<!-- ============ HERO ============ -->
<section class="hero" id="hero">
  <div class="container">
    <div class="hero__head">
      <span class="hero__eyebrow">پلتفرم تخصصی هوش مصنوعی</span>
      <h1 class="hero__title">اشتراک ابزارهای هوش مصنوعی را <span class="grad-text">ساده و امن</span> بخرید</h1>
      <p class="hero__subtitle">ChatGPT، Claude، Midjourney و ده‌ها ابزار دیگر — تحویل سریع، پرداخت امن و پشتیبانی ۲۴ ساعته.</p>
    </div>

    <?= $this->component('hero-carousel', ['tools' => $tools]) ?>

    <!-- Dynamic tool info (AJAX) -->
    <div class="tool-info" id="toolInfo" aria-live="polite">
      <div class="tool-info__logo skeleton" id="toolInfoLogo"></div>
      <div class="tool-info__text">
        <h2 class="tool-info__name" id="toolInfoName">&nbsp;</h2>
        <p class="tool-info__desc" id="toolInfoDesc">&nbsp;</p>
      </div>
    </div>
  </div>
</section>

<!-- ============ PLANS ============ -->
<section class="plans" id="plans">
  <div class="container">
    <div class="section-head">
      <h2 class="section-title">پلن‌های اشتراک</h2>
      <p class="section-sub">پلن مناسب خود را انتخاب کنید و بلافاصله وارد فرایند خرید شوید.</p>
    </div>
    <div class="plans__grid" id="plansGrid" aria-live="polite">
      <!-- skeletons -->
      <div class="plan-card skeleton-card"></div>
      <div class="plan-card skeleton-card"></div>
      <div class="plan-card skeleton-card"></div>
      <div class="plan-card skeleton-card"></div>
    </div>
  </div>
</section>

<!-- ============ USDT TICKER ============ -->
<?= $this->component('usdt-ticker', ['ticker' => $ticker]) ?>

<!-- ============ STEPS ============ -->
<section class="steps" id="steps">
  <div class="container">
    <div class="section-head">
      <h2 class="section-title">مراحل خرید</h2>
      <p class="section-sub">تنها در چند گام ساده اشتراک خود را فعال کنید.</p>
    </div>
    <div class="steps__grid">
      <?php foreach ([
        ['۱', 'تأیید موبایل', 'شماره خود را وارد و با کد پیامکی تأیید کنید.'],
        ['۲', 'اطلاعات شخصی', 'نام و نام خانوادگی خود را ثبت کنید.'],
        ['۳', 'اطلاعات حساب', 'ایمیل و رمز یا شناسه سازمانی را وارد کنید.'],
        ['۴', 'پرداخت', 'مبلغ را کارت‌به‌کارت کرده و رسید را بارگذاری کنید.'],
        ['۵', 'تحویل', 'سفارش طی ۲۴ ساعت بررسی و فعال می‌شود.'],
      ] as [$n, $t, $d]): ?>
      <article class="step-card glass">
        <span class="step-card__num"><?= e($n) ?></span>
        <h3 class="step-card__title"><?= e($t) ?></h3>
        <p class="step-card__desc"><?= e($d) ?></p>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ FAQ ============ -->
<section class="faq" id="faq">
  <div class="container">
    <div class="section-head">
      <h2 class="section-title">سوالات متداول</h2>
    </div>
    <div class="faq__list">
      <?php foreach ([
        ['اشتراک چقدر طول می‌کشد فعال شود؟', 'پس از ثبت سفارش و بارگذاری رسید، سفارش شما حداکثر طی ۲۴ ساعت بررسی و فعال می‌شود.'],
        ['روش پرداخت چگونه است؟', 'در حال حاضر پرداخت به‌صورت کارت‌به‌کارت انجام می‌شود. درگاه آنلاین به‌زودی اضافه خواهد شد.'],
        ['آیا اطلاعات من امن است؟', 'بله. تمام اطلاعات حساس به‌صورت رمزنگاری‌شده ذخیره و ارتباطات از طریق HTTPS محافظت می‌شوند.'],
        ['اگر مشکلی پیش بیاید چه کنم؟', 'از طریق دکمه پشتیبانی در تلگرام با ما در ارتباط باشید؛ پاسخ‌گوی شما هستیم.'],
      ] as [$q, $a]): ?>
      <details class="faq__item glass">
        <summary class="faq__q"><?= e($q) ?></summary>
        <p class="faq__a"><?= e($a) ?></p>
      </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ CHECKOUT MODAL ============ -->
<?= $this->component('checkout', ['payment' => $payment]) ?>

<script type="application/json" id="bootstrap-tools">
<?= json_encode(array_map(fn ($t) => [
    'id' => (int) $t['id'],
    'name' => $t['name'],
    'slug' => $t['slug'],
    'logo' => asset('images/' . $t['logo']),
    'description' => $t['description'],
], $tools), JSON_UNESCAPED_UNICODE) ?>
</script>
