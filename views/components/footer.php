<footer class="footer">
  <div class="container footer__inner">
    <div class="footer__brand">
      <img src="<?= e(asset('images/logo.svg')) ?>" alt="نالیک آکادمی" height="40">
      <p class="footer__tagline"><?= e(site_text('footer_tagline', 'مارکت‌پلیس اشتراک ابزارهای هوش مصنوعی، سریع و امن.')) ?></p>
    </div>

    <div class="footer__support">
      <a href="<?= e((string) config('app.support_url')) ?>" class="btn btn--ghost btn--sm" target="_blank" rel="noopener">
        <img src="<?= e(asset('images/telegram.svg')) ?>" alt="" width="20" height="20">
        <?= e(site_text('support_button', 'ارتباط با پشتیبانی')) ?>
      </a>
    </div>
  </div>
  <div class="footer__bottom container">
    <span>© <?= e(to_persian_digits((string) date('Y'))) ?> نالیک آکادمی — تمامی حقوق محفوظ است.</span>
  </div>
</footer>
