<?php
/** @var array $plans @var array $tools @var float $usdt_price */
?>
<div class="page-head page-head--row">
  <div><h1 class="page-title">پلن‌ها</h1><p class="page-sub">قیمت تومان به‌صورت خودکار از نرخ USDT محاسبه می‌شود (نرخ فعلی: <?= e(money_irt($usdt_price)) ?> تومان)</p></div>
  <button class="btn btn--primary btn--sm" id="newPlanBtn">+ پلن جدید</button>
</div>

<div class="table-wrap glass">
  <table class="table">
    <thead><tr><th>ابزار</th><th>نام پلن</th><th>برچسب</th><th>مدت</th><th>USDT</th><th>تومان</th><th>حالت</th><th>وضعیت</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($plans as $p): ?>
      <tr data-plan='<?= e(json_encode([
          'id' => (int) $p['id'], 'tool_id' => (int) $p['tool_id'], 'name' => $p['name'], 'badge' => $p['badge'],
          'duration' => $p['duration'], 'duration_days' => (int) $p['duration_days'],
          'price_usdt' => (float) $p['price_usdt'], 'mode_type' => $p['mode_type'],
          'is_featured' => (int) $p['is_featured'], 'sort_order' => (int) $p['sort_order'], 'status' => $p['status'],
      ], JSON_UNESCAPED_UNICODE)) ?>'>
        <td><?= e($p['tool_name']) ?></td>
        <td><?= e($p['name']) ?></td>
        <td><?= e($p['badge'] ?: '—') ?></td>
        <td><?= e($p['duration']) ?></td>
        <td dir="ltr"><?= e(to_persian_digits((string) $p['price_usdt'])) ?></td>
        <td><?= e(money_irt((int) $p['price_irt'])) ?></td>
        <td><?= $p['mode_type'] === 'email_password' ? 'ایمیل/رمز' : 'شناسه سازمانی' ?></td>
        <td><span class="badge badge--<?= $p['status'] === 'active' ? 'approved' : 'rejected' ?>"><?= $p['status'] === 'active' ? 'فعال' : 'غیرفعال' ?></span></td>
        <td>
          <button class="link-btn" data-edit-plan>ویرایش</button>
          <button class="link-btn link-btn--danger" data-delete-plan="<?= (int) $p['id'] ?>">حذف</button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="admin-modal" id="planModal" aria-hidden="true">
  <div class="admin-modal__backdrop" data-modal-close></div>
  <div class="admin-modal__dialog glass">
    <button class="admin-modal__close" data-modal-close>&times;</button>
    <h2 class="admin-modal__title" id="planModalTitle">پلن جدید</h2>
    <form id="planForm">
      <input type="hidden" name="id" id="planId">
      <div class="field"><label class="field__label">ابزار</label>
        <select class="input" name="tool_id" id="planTool" required>
          <?php foreach ($tools as $t): ?><option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field-row">
        <div class="field"><label class="field__label">نام پلن</label><input class="input" name="name" id="planName" required></div>
        <div class="field"><label class="field__label">برچسب</label><input class="input" name="badge" id="planBadge" placeholder="پرفروش"></div>
      </div>
      <div class="field-row">
        <div class="field"><label class="field__label">مدت (نمایشی)</label><input class="input" name="duration" id="planDuration" placeholder="یک ماهه" required></div>
        <div class="field"><label class="field__label">تعداد روز</label><input class="input" type="number" name="duration_days" id="planDays" value="30"></div>
      </div>
      <div class="field-row">
        <div class="field"><label class="field__label">قیمت USDT</label><input class="input" type="number" step="0.01" name="price_usdt" id="planUsdt" required></div>
        <div class="field"><label class="field__label">معادل تومان (خودکار)</label><input class="input" id="planIrtPreview" readonly></div>
      </div>
      <div class="field-row">
        <div class="field"><label class="field__label">حالت حساب</label>
          <select class="input" name="mode_type" id="planMode">
            <option value="email_password">ایمیل و رمز عبور</option>
            <option value="organization_id">شناسه سازمانی</option>
          </select>
        </div>
        <div class="field"><label class="field__label">وضعیت</label>
          <select class="input" name="status" id="planStatus"><option value="active">فعال</option><option value="inactive">غیرفعال</option></select>
        </div>
      </div>
      <div class="field-row">
        <div class="field"><label class="field__label">ترتیب</label><input class="input" type="number" name="sort_order" id="planSort" value="0"></div>
        <label class="admin-login__remember"><input type="checkbox" name="is_featured" id="planFeatured" value="1"> پلن ویژه</label>
      </div>
      <span class="field__error" id="planError"></span>
      <button type="submit" class="btn btn--primary btn--block">ذخیره</button>
    </form>
  </div>
</div>

<script type="application/json" id="usdt-rate"><?= json_encode(['rate' => $usdt_price]) ?></script>
