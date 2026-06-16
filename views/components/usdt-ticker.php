<?php /** @var array $ticker */ ?>
<section class="ticker" id="usdtTicker"
         data-price="<?= e((string) $ticker['price']) ?>"
         data-high="<?= e((string) $ticker['high_24']) ?>"
         data-low="<?= e((string) $ticker['low_24']) ?>"
         data-change="<?= e((string) $ticker['change_percent_24']) ?>">
  <div class="container">
    <div class="ticker__card glass">
      <div class="ticker__head">
        <img src="<?= e(asset('images/usdt.svg')) ?>" alt="USDT" width="40" height="40">
        <div>
          <h3 class="ticker__title">قیمت لحظه‌ای تتر</h3>
          <span class="ticker__sub">USDT / تومان</span>
        </div>
        <span class="ticker__live"><span class="dot"></span> زنده</span>
      </div>

      <div class="ticker__grid">
        <div class="ticker__cell">
          <span class="ticker__label">قیمت فعلی</span>
          <span class="ticker__value" id="tkPrice" data-value="<?= e((string) $ticker['price']) ?>">۰</span>
          <span class="ticker__unit">تومان</span>
        </div>
        <div class="ticker__cell">
          <span class="ticker__label">بیشترین ۲۴ساعت</span>
          <span class="ticker__value ticker__value--sm" id="tkHigh" data-value="<?= e((string) $ticker['high_24']) ?>">۰</span>
        </div>
        <div class="ticker__cell">
          <span class="ticker__label">کمترین ۲۴ساعت</span>
          <span class="ticker__value ticker__value--sm" id="tkLow" data-value="<?= e((string) $ticker['low_24']) ?>">۰</span>
        </div>
        <div class="ticker__cell">
          <span class="ticker__label">تغییر ۲۴ساعت</span>
          <span class="ticker__change" id="tkChange">۰٪</span>
        </div>
      </div>
    </div>
  </div>
</section>
