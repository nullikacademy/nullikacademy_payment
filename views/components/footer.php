<footer class="footer">
  <div class="container footer__inner">
    <div class="footer__brand">
      <img src="<?= e(asset('images/logo.svg')) ?>" alt="نولیک آکادمی" height="40">
      <p class="footer__tagline">مارکت‌پلیس اشتراک ابزارهای هوش مصنوعی، سریع و امن.</p>
    </div>

    <nav class="footer__links" aria-label="پیوندها">
      <a href="#hero">خانه</a>
      <a href="#plans">پلن‌ها</a>
      <a href="<?= e((string) config('app.support_url')) ?>" target="_blank" rel="noopener">پشتیبانی تلگرام</a>
    </nav>

    <div class="footer__support">
      <a href="<?= e((string) config('app.support_url')) ?>" class="btn btn--ghost btn--sm" target="_blank" rel="noopener">
        <img src="<?= e(asset('images/telegram.svg')) ?>" alt="" width="20" height="20">
        ارتباط با پشتیبانی
      </a>
    </div>
  </div>
  <div class="footer__bottom container">
    <span>© <?= e(to_persian_digits((string) date('Y'))) ?> نولیک آکادمی — تمامی حقوق محفوظ است.</span>
  </div>
</footer>
