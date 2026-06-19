<?php
/** @var array $orders @var int $total @var int $page @var int $total_pages
 *  @var array $filters @var array $tools @var array $statuses */
$qs = function (array $overrides) use ($filters, $page) {
    $params = array_merge($filters, ['page' => $page], $overrides);
    return '?' . http_build_query(array_filter($params, fn ($v) => $v !== '' && $v !== null));
};
?>
<div class="page-head page-head--row">
  <div>
    <h1 class="page-title">سفارش‌ها</h1>
    <p class="page-sub">مجموع: <?= e(to_persian_digits((string) $total)) ?> سفارش</p>
  </div>
  <a href="<?= e(url('admin/orders/export') . $qs([])) ?>" class="btn btn--ghost btn--sm">خروجی CSV</a>
</div>

<form class="filters glass" method="get" action="<?= e(url('admin/orders')) ?>">
  <input type="search" name="search" class="input" placeholder="جستجو: شماره، نام، موبایل، ایمیل" value="<?= e($filters['search']) ?>">
  <select name="status" class="input">
    <option value="">همه وضعیت‌ها</option>
    <?php foreach ($statuses as $key => $label): ?>
    <option value="<?= e($key) ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="tool_id" class="input">
    <option value="">همه ابزارها</option>
    <?php foreach ($tools as $t): ?>
    <option value="<?= (int) $t['id'] ?>" <?= (string) $filters['tool_id'] === (string) $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <input type="date" name="date_from" class="input" value="<?= e($filters['date_from']) ?>">
  <input type="date" name="date_to" class="input" value="<?= e($filters['date_to']) ?>">
  <button type="submit" class="btn btn--primary btn--sm">فیلتر</button>
</form>

<div class="table-wrap glass">
  <table class="table">
    <thead>
      <tr>
        <th>شماره</th><th>مشتری</th><th>موبایل</th><th>ابزار</th><th>پلن</th><th>مبلغ</th><th>وضعیت</th><th>تاریخ</th><th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$orders): ?>
      <tr><td colspan="9" class="table__empty">سفارشی یافت نشد.</td></tr>
      <?php endif; ?>
      <?php foreach ($orders as $o): ?>
      <tr>
        <td dir="ltr"><?= e($o['order_number']) ?></td>
        <td><?= e($o['first_name'] . ' ' . $o['last_name']) ?></td>
        <td dir="ltr"><?= e(to_persian_digits($o['mobile'])) ?></td>
        <td><?= e($o['tool_name']) ?></td>
        <td><?= e($o['plan_name']) ?></td>
        <td><?= e(money_irt((int) $o['price_irt'])) ?></td>
        <td><span class="badge badge--<?= e($o['status']) ?>"><?= e($statuses[$o['status']] ?? $o['status']) ?></span></td>
        <td><?= e(to_persian_digits(substr($o['created_at'], 0, 16))) ?></td>
        <td>
          <a class="link-btn" href="<?= e(url('admin/orders/' . $o['id'])) ?>">جزئیات</a>
          <button class="link-btn link-btn--danger" data-delete-order="<?= (int) $o['id'] ?>">حذف</button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if ($total_pages > 1): ?>
<nav class="pagination">
  <?php for ($i = 1; $i <= $total_pages; $i++): ?>
  <a href="<?= e(url('admin/orders') . $qs(['page' => $i])) ?>" class="pagination__item <?= $i === $page ? 'is-active' : '' ?>"><?= e(to_persian_digits((string) $i)) ?></a>
  <?php endfor; ?>
</nav>
<?php endif; ?>
