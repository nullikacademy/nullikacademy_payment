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
  $sv = static fn (string $p): string =>
    '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $p . '</svg>';
  $icons = [
    'box'   => $sv('<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>'),
    'clock' => $sv('<circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/>'),
    'check' => $sv('<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'),
    'money' => $sv('<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/>'),
    'trend' => $sv('<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>'),
  ];
  $cards = [
    ['سفارش‌های امروز', $num($stats['today']), $icons['box'], 'primary'],
    ['در انتظار بررسی', $num($stats['pending']), $icons['clock'], 'warning'],
    ['تأییدشده / تحویل', $num($stats['approved']), $icons['check'], 'success'],
    ['درآمد (تومان)', money_irt($stats['revenue']), $icons['money'], 'accent'],
    ['نرخ تبدیل', to_persian_digits((string) $stats['conversion']) . '٪', $icons['trend'], 'primary'],
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
