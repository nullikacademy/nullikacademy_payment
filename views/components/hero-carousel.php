<?php /** @var array $tools */ ?>
<div class="carousel" id="heroCarousel" aria-roledescription="carousel" aria-label="ابزارهای هوش مصنوعی">
  <div class="carousel__track" id="carouselTrack">
    <?php foreach ($tools as $i => $tool): ?>
    <button type="button"
            class="carousel__item<?= $i === 0 ? ' is-active' : '' ?>"
            data-index="<?= (int) $i ?>"
            data-slug="<?= e($tool['slug']) ?>"
            style="--tool-color: <?= e($tool['color'] ?? '#0076FA') ?>"
            aria-label="<?= e($tool['name']) ?>">
      <span class="carousel__logo">
        <img src="<?= e(asset('images/' . $tool['logo'])) ?>" alt="<?= e($tool['name']) ?>" loading="lazy" width="96" height="96">
      </span>
      <span class="carousel__label"><?= e($tool['name']) ?></span>
    </button>
    <?php endforeach; ?>
  </div>

  <div class="carousel__progress" aria-hidden="true">
    <span class="carousel__bar" id="carouselBar"></span>
  </div>
</div>
