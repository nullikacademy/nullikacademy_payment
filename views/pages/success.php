<?php
/** @var array $order @var string $support_url */
?>
<section class="success">
  <div class="container">
    <div class="success__card glass">
      <div class="success__check" aria-hidden="true">
        <svg viewBox="0 0 80 80" width="96" height="96">
          <circle cx="40" cy="40" r="36" fill="none" stroke="#22C55E" stroke-width="4" opacity="0.25"/>
          <circle class="success__ring" cx="40" cy="40" r="36" fill="none" stroke="#22C55E" stroke-width="4" stroke-linecap="round"/>
          <path class="success__tick" d="M25 41l11 11 20-22" fill="none" stroke="#22C55E" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>

      <h1 class="success__title">سفارش با موفقیت ثبت شد</h1>
      <p class="success__msg">سفارش شما طی ۲۴ ساعت آینده بررسی می‌شود.</p>

      <div class="success__order">
        <span class="success__order-label">شماره سفارش</span>
        <span class="success__order-number" dir="ltr"><?= e($order['order_number']) ?></span>
      </div>

      <a href="<?= e($support_url) ?>" class="btn btn--primary btn--block" target="_blank" rel="noopener">
        <img src="<?= e(asset('images/telegram.svg')) ?>" alt="" width="22" height="22">
        ارتباط با پشتیبانی
      </a>
      <a href="<?= e(url('/')) ?>" class="link-btn success__home">بازگشت به صفحه اصلی</a>
    </div>
  </div>
</section>
