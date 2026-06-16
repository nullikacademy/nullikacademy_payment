<?php
/** @var array $stats @var array $chart_days @var array $chart_tools */
?>
<div class="page-head">
  <h1 class="page-title">داشبورد</h1>
  <p class="page-sub">نمای کلی عملکرد فروشگاه</p>
</div>

<div class="stat-grid">
  <?php
  $num = fn ($n) => to_persian_digits(number_format((int) $n));
  $cards = [
    ['سفارش‌های امروز', $num($stats['today']), '📦', 'primary'],
    ['در انتظار بررسی', $num($stats['pending']), '⏳', 'warning'],
    ['تأییدشده / تحویل', $num($stats['approved']), '✅', 'success'],
    ['درآمد (تومان)', money_irt($stats['revenue']), '💰', 'accent'],
    ['نرخ تبدیل', to_persian_digits((string) $stats['conversion']) . '٪', '📈', 'primary'],
  ];
  foreach ($cards as [$label, $value, $icon, $variant]): ?>
  <article class="stat-card glass stat-card--<?= $variant ?>">
    <span class="stat-card__icon"><?= $icon ?></span>
    <div>
      <span class="stat-card__label"><?= e($label) ?></span>
      <span class="stat-card__value"><?= e($value) ?></span>
    </div>
  </article>
  <?php endforeach; ?>
</div>

<div class="chart-grid">
  <section class="chart-card glass">
    <h2 class="chart-card__title">سفارش‌ها در ۱۴ روز اخیر</h2>
    <canvas id="chartDays" height="260"></canvas>
  </section>
  <section class="chart-card glass">
    <h2 class="chart-card__title">سفارش‌ها به تفکیک ابزار</h2>
    <canvas id="chartTools" height="260"></canvas>
  </section>
</div>

<script type="application/json" id="chart-data">
<?= json_encode([
    'days'  => array_map(fn ($r) => ['label' => $r['day'], 'value' => (int) $r['total']], $chart_days),
    'tools' => array_map(fn ($r) => ['label' => $r['tool_name'], 'value' => (int) $r['total']], $chart_tools),
], JSON_UNESCAPED_UNICODE) ?>
</script>
