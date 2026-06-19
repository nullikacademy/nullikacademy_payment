<?php
/** @var array $order @var array $history @var array $notes @var array $receipt @var array $statuses */
?>
<div class="page-head page-head--row">
  <div>
    <h1 class="page-title">سفارش <span dir="ltr"><?= e($order['order_number']) ?></span></h1>
    <p class="page-sub"><span class="badge badge--<?= e($order['status']) ?>"><?= e($statuses[$order['status']] ?? $order['status']) ?></span></p>
  </div>
  <div style="display:flex;gap:10px">
    <button class="btn btn--ghost btn--sm" id="deleteOrderBtn" data-delete-order="<?= (int) $order['id'] ?>" style="color:var(--danger);border-color:rgba(239,68,68,0.4)">حذف سفارش</button>
    <a href="<?= e(url('admin/orders')) ?>" class="btn btn--ghost btn--sm">بازگشت</a>
  </div>
</div>

<div class="detail-grid">
  <section class="glass detail-card">
    <h2 class="detail-card__title">اطلاعات مشتری و سفارش</h2>
    <dl class="detail-list">
      <div><dt>نام</dt><dd><?= e($order['first_name'] . ' ' . $order['last_name']) ?></dd></div>
      <div><dt>موبایل</dt><dd dir="ltr"><?= e(to_persian_digits($order['mobile'])) ?></dd></div>
      <div><dt>ابزار</dt><dd><?= e($order['tool_name']) ?></dd></div>
      <div><dt>پلن</dt><dd><?= e($order['plan_name']) ?></dd></div>
      <div><dt>مبلغ</dt><dd><?= e(money_irt((int) $order['price_irt'])) ?> تومان (<?= e(to_persian_digits((string) $order['price_usdt'])) ?> USDT)</dd></div>
      <div><dt>ایمیل</dt><dd dir="ltr"><?= e($order['email'] ?? '—') ?></dd></div>
      <?php if ($order['mode_type'] === 'email_password'): ?>
      <div><dt>رمز عبور</dt><dd class="reveal" dir="ltr">
        <span class="reveal__masked" data-real="<?= e($order['password_plain']) ?>">••••••••</span>
        <button class="reveal__btn" type="button">نمایش</button>
      </dd></div>
      <?php else: ?>
      <div><dt>شناسه سازمانی</dt><dd dir="ltr"><?= e($order['organization_id'] ?? '—') ?></dd></div>
      <?php endif; ?>
      <div><dt>تاریخ ثبت</dt><dd><?= e(to_persian_digits($order['created_at'])) ?></dd></div>
      <div><dt>IP</dt><dd dir="ltr"><?= e($order['ip_address'] ?? '—') ?></dd></div>
    </dl>
  </section>

  <section class="glass detail-card">
    <h2 class="detail-card__title">رسید پرداخت</h2>
    <?php if ($receipt): ?>
    <a href="<?= e(url('admin/receipts/' . $receipt['token'])) ?>" target="_blank" rel="noopener" class="receipt-thumb">
      <img src="<?= e(url('admin/receipts/' . $receipt['token'])) ?>" alt="رسید پرداخت">
    </a>
    <?php else: ?>
    <p class="page-sub">رسیدی بارگذاری نشده است.</p>
    <?php endif; ?>
  </section>

  <section class="glass detail-card">
    <h2 class="detail-card__title">تغییر وضعیت</h2>
    <form id="statusForm" data-order="<?= (int) $order['id'] ?>">
      <select name="status" class="input">
        <?php foreach ($statuses as $key => $label): ?>
        <option value="<?= e($key) ?>" <?= $order['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="note" class="input" placeholder="یادداشت تغییر وضعیت (اختیاری)">
      <button type="submit" class="btn btn--primary btn--block">به‌روزرسانی وضعیت</button>
    </form>
  </section>

  <section class="glass detail-card">
    <h2 class="detail-card__title">یادداشت‌های داخلی</h2>
    <form id="noteForm" data-order="<?= (int) $order['id'] ?>">
      <textarea name="note" class="input" rows="3" placeholder="یادداشت جدید..."></textarea>
      <button type="submit" class="btn btn--ghost btn--sm">افزودن یادداشت</button>
    </form>
    <ul class="note-list" id="noteList">
      <?php foreach ($notes as $n): ?>
      <li class="note-item">
        <p><?= e($n['note']) ?></p>
        <span class="note-item__meta"><?= e($n['admin_name'] ?? 'ادمین') ?> · <?= e(to_persian_digits(substr($n['created_at'], 0, 16))) ?></span>
      </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <section class="glass detail-card detail-card--wide">
    <h2 class="detail-card__title">تاریخچه وضعیت</h2>
    <ul class="timeline">
      <?php foreach ($history as $h): ?>
      <li class="timeline__item">
        <span class="timeline__dot"></span>
        <div>
          <strong><?= e($statuses[$h['to_status']] ?? $h['to_status']) ?></strong>
          <?php if ($h['note']): ?><p class="timeline__note"><?= e($h['note']) ?></p><?php endif; ?>
          <span class="timeline__time"><?= e(to_persian_digits(substr($h['created_at'], 0, 16))) ?></span>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
  </section>
</div>
