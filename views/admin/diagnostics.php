<?php
/** @var array $configRows @var array $usdt @var ?array $smsResult @var string $smsMobile @var ?string $tgResult */
?>
<div class="page-head"><h1 class="page-title">عیب‌یابی سرویس‌ها</h1>
  <p class="page-sub">این صفحه پاسخ خام سرویس‌های بیرونی را نشان می‌دهد. خروجی را کپی و برای پشتیبانی فنی ارسال کنید.</p>
</div>

<section class="glass detail-card detail-card--wide">
  <h2 class="detail-card__title">۱) تنظیمات (.env)</h2>
  <dl class="detail-list">
    <?php foreach ($configRows as $k => $v): ?>
    <div><dt dir="ltr"><?= e($k) ?></dt><dd dir="ltr"><?= e((string) $v) ?></dd></div>
    <?php endforeach; ?>
  </dl>
</section>

<section class="glass detail-card detail-card--wide" style="margin-top:18px">
  <h2 class="detail-card__title">۲) قیمت تتر (Tabdeal) — پاسخ خام</h2>
  <p class="page-sub">HTTP status: <strong dir="ltr"><?= e((string) $usdt['status']) ?></strong>
     <?php if ($usdt['error']): ?> | curl error: <span dir="ltr"><?= e((string) $usdt['error']) ?></span><?php endif; ?>
     | length: <span dir="ltr"><?= e((string) $usdt['length']) ?></span> bytes</p>
  <p class="page-sub">پاسخ خام (۴۰۰۰ کاراکتر اول):</p>
  <pre class="diag-pre" dir="ltr"><?= e($usdt['body'] ?: '(خالی)') ?></pre>
  <p class="page-sub">مقدار استخراج‌شده توسط برنامه:</p>
  <pre class="diag-pre" dir="ltr"><?= e($usdt['parsed']) ?></pre>
</section>

<section class="glass detail-card detail-card--wide" style="margin-top:18px">
  <h2 class="detail-card__title">۳) تست پیامک (IPPanel)</h2>
  <form method="get" action="<?= e(url('admin/diagnostics')) ?>" class="filters" style="box-shadow:none;padding:0;margin-bottom:14px">
    <input type="text" name="sms" class="input" dir="ltr" placeholder="0912xxxxxxx" value="<?= e($smsMobile) ?>" style="max-width:260px">
    <button class="btn btn--primary btn--sm" type="submit">ارسال پیامک تست</button>
  </form>
  <?php if ($smsResult): ?>
  <p class="page-sub">به: <span dir="ltr"><?= e($smsResult['mobile']) ?></span> | نتیجه: <strong><?= e($smsResult['returned']) ?></strong> | sms_logs.status: <strong><?= e($smsResult['status']) ?></strong></p>
  <p class="page-sub">پاسخ خام IPPanel:</p>
  <pre class="diag-pre" dir="ltr"><?= e($smsResult['response'] ?: '(خالی)') ?></pre>
  <?php endif; ?>
</section>

<section class="glass detail-card detail-card--wide" style="margin-top:18px">
  <h2 class="detail-card__title">۳.۵) شناسایی خودکار آدرس صحیح IPPanel</h2>
  <p class="page-sub">همه‌ی نسخه‌های API را امتحان می‌کند تا مشخص شود کدام آدرس درست است (کدی که status آن ۴۰۴ نباشد).</p>
  <form method="get" action="<?= e(url('admin/diagnostics')) ?>" class="filters" style="box-shadow:none;padding:0;margin-bottom:14px">
    <input type="text" name="probe" class="input" dir="ltr" placeholder="0912xxxxxxx" value="<?= e($probeMobile ?? '') ?>" style="max-width:260px">
    <button class="btn btn--primary btn--sm" type="submit">شناسایی آدرس</button>
  </form>
  <?php if (!empty($probe)): ?>
  <?php foreach ($probe as $r): ?>
    <p class="page-sub" style="margin-top:10px"><strong dir="ltr"><?= e($r['name']) ?></strong> →
       status: <strong dir="ltr"><?= e((string) $r['status']) ?></strong>
       <?php if ($r['curl']): ?> | curl: <span dir="ltr"><?= e((string) $r['curl']) ?></span><?php endif; ?></p>
    <pre class="diag-pre" dir="ltr"><?= e($r['body'] ?: '(خالی)') ?></pre>
  <?php endforeach; ?>
  <?php endif; ?>
</section>

<section class="glass detail-card detail-card--wide" style="margin-top:18px">
  <h2 class="detail-card__title">۴) تست تلگرام</h2>
  <a class="btn btn--ghost btn--sm" href="<?= e(url('admin/diagnostics?telegram=1')) ?>">ارسال پیام تست به تلگرام</a>
  <?php if ($tgResult): ?><p class="page-sub" style="margin-top:12px"><?= e($tgResult) ?></p><?php endif; ?>
</section>

<style>
.diag-pre { background:#04070f; border:1px solid var(--border); border-radius:12px; padding:14px; overflow:auto; max-height:340px; font-size:0.8rem; line-height:1.6; white-space:pre-wrap; word-break:break-word; color:#9FB0CE; }
</style>
