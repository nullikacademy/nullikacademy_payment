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

    <!-- Dynamic tool description (AJAX, single line) -->
    <p class="tool-info" id="toolInfo" aria-live="polite"><span id="toolInfoDesc">&nbsp;</span></p>
  </div>
</section>

<!-- ============ PLANS ============ -->
<section class="plans" id="plans">
  <div class="container">
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

<!-- ============ CHECKOUT MODAL ============ -->
<?= $this->component('checkout', ['payment' => $payment]) ?>

<script type="application/json" id="bootstrap-tools">
<?= json_encode(array_map(fn ($t) => [
    'id' => (int) $t['id'],
    'name' => $t['name'],
    'slug' => $t['slug'],
    'logo' => asset('images/' . $t['logo']),
    'color' => $t['color'] ?? '#0076FA',
    'description' => $t['description'],
], $tools), JSON_UNESCAPED_UNICODE) ?>
</script>
