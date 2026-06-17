<?php /** @var array $ticker */ ?>
<section class="ticker" id="usdtTicker" data-price="<?= e((string) $ticker['price']) ?>">
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

      <div class="ticker__now">
        <span class="ticker__value" id="tkPrice" data-value="<?= e((string) $ticker['price']) ?>">۰</span>
        <span class="ticker__unit">تومان</span>
      </div>
    </div>
  </div>
</section>
