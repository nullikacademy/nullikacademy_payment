<header class="navbar" id="navbar">
  <div class="container navbar__inner">
    <a href="<?= e(url('/')) ?>" class="navbar__brand" aria-label="نالیک آکادمی">
      <img src="<?= e(asset('images/Nullik-Academy-Logo.svg')) ?>" alt="نالیک آکادمی" height="40">
    </a>

    <div class="navbar__actions">
      <a href="<?= e((string) config('app.support_url')) ?>" class="btn btn--primary btn--sm" target="_blank" rel="noopener">
        <img src="<?= e(asset('images/telegram.svg')) ?>" alt="" width="18" height="18">
        <?= e(site_text('support_button', 'ارتباط با پشتیبانی')) ?>
      </a>
    </div>
  </div>
</header>
