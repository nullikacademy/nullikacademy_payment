<header class="navbar" id="navbar">
  <div class="container navbar__inner">
    <a href="<?= e(url('/')) ?>" class="navbar__brand" aria-label="نولیک آکادمی">
      <img src="<?= e(asset('images/logo.svg')) ?>" alt="نولیک آکادمی" height="40">
    </a>

    <nav class="navbar__nav" aria-label="ناوبری اصلی">
      <a href="#hero" class="navbar__link">خانه</a>
      <a href="#plans" class="navbar__link">پلن‌ها</a>
      <a href="<?= e((string) config('app.support_url')) ?>" class="navbar__link" target="_blank" rel="noopener">پشتیبانی</a>
    </nav>

    <div class="navbar__actions">
      <a href="#plans" class="btn btn--primary btn--sm" data-scroll>شروع خرید</a>
      <button class="navbar__burger" id="navBurger" aria-label="منو" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</header>
