<?php
/** @var array $tools @var int $total @var int $page @var int $total_pages @var string $search */
?>
<div class="page-head page-head--row">
  <div><h1 class="page-title">ابزارها</h1><p class="page-sub">مدیریت ابزارهای هوش مصنوعی</p></div>
  <button class="btn btn--primary btn--sm" id="newToolBtn">+ ابزار جدید</button>
</div>

<form class="filters glass" method="get" action="<?= e(url('admin/tools')) ?>">
  <input type="search" name="search" class="input" placeholder="جستجوی ابزار" value="<?= e($search) ?>">
  <button class="btn btn--primary btn--sm" type="submit">جستجو</button>
</form>

<div class="table-wrap glass">
  <table class="table">
    <thead><tr><th>لوگو</th><th>نام</th><th>اسلاگ</th><th>رنگ</th><th>ترتیب</th><th>وضعیت</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($tools as $t): $color = $t['color'] ?? '#0076FA'; ?>
      <tr data-tool='<?= e(json_encode([
          'id' => (int) $t['id'], 'name' => $t['name'], 'slug' => $t['slug'],
          'description' => $t['description'], 'color' => $color,
          'sort_order' => (int) $t['sort_order'], 'status' => $t['status'],
      ], JSON_UNESCAPED_UNICODE)) ?>'>
        <td><img src="<?= e(asset('images/' . ($t['logo'] ?: 'tools/default.svg'))) ?>" alt="" width="40" height="40" style="border-radius:10px"></td>
        <td><?= e($t['name']) ?></td>
        <td dir="ltr"><?= e($t['slug']) ?></td>
        <td><span style="display:inline-flex;align-items:center;gap:8px"><span style="width:18px;height:18px;border-radius:5px;border:1px solid var(--border);background:<?= e($color) ?>"></span><span dir="ltr" style="font-size:0.78rem;color:var(--text-mute)"><?= e($color) ?></span></span></td>
        <td><?= e(to_persian_digits((string) $t['sort_order'])) ?></td>
        <td>
          <button class="badge badge--toggle badge--<?= $t['status'] === 'active' ? 'approved' : 'rejected' ?>"
                  data-toggle-tool="<?= (int) $t['id'] ?>"><?= $t['status'] === 'active' ? 'فعال' : 'غیرفعال' ?></button>
        </td>
        <td>
          <button class="link-btn" data-edit-tool>ویرایش</button>
          <button class="link-btn link-btn--danger" data-delete-tool="<?= (int) $t['id'] ?>">حذف</button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if ($total_pages > 1): ?>
<nav class="pagination">
  <?php for ($i = 1; $i <= $total_pages; $i++): ?>
  <a href="<?= e(url('admin/tools') . '?page=' . $i . ($search ? '&search=' . urlencode($search) : '')) ?>" class="pagination__item <?= $i === $page ? 'is-active' : '' ?>"><?= e(to_persian_digits((string) $i)) ?></a>
  <?php endfor; ?>
</nav>
<?php endif; ?>

<!-- Modal -->
<div class="admin-modal" id="toolModal" aria-hidden="true">
  <div class="admin-modal__backdrop" data-modal-close></div>
  <div class="admin-modal__dialog glass">
    <button class="admin-modal__close" data-modal-close>&times;</button>
    <h2 class="admin-modal__title" id="toolModalTitle">ابزار جدید</h2>
    <form id="toolForm" enctype="multipart/form-data">
      <input type="hidden" name="id" id="toolId">
      <div class="field"><label class="field__label">نام</label><input class="input" name="name" id="toolName" required></div>
      <div class="field"><label class="field__label">اسلاگ (انگلیسی)</label><input class="input" name="slug" id="toolSlug" dir="ltr" required></div>
      <div class="field"><label class="field__label">توضیحات</label><textarea class="input" name="description" id="toolDesc" rows="3"></textarea></div>
      <div class="field">
        <label class="field__label">رنگ ابزار (برای درخشش کاروسل)</label>
        <div style="display:flex;gap:10px;align-items:center">
          <input type="color" id="toolColorPicker" value="#0076FA" style="width:48px;height:44px;padding:4px;border-radius:10px;background:var(--surface-2);border:1px solid var(--border);cursor:pointer">
          <input class="input" name="color" id="toolColor" dir="ltr" placeholder="#0076FA یا rgb(0,118,250)" value="#0076FA" style="flex:1">
        </div>
      </div>
      <div class="field-row">
        <div class="field"><label class="field__label">ترتیب</label><input class="input" type="number" name="sort_order" id="toolSort" value="0"></div>
        <div class="field"><label class="field__label">وضعیت</label>
          <select class="input" name="status" id="toolStatus"><option value="active">فعال</option><option value="inactive">غیرفعال</option></select>
        </div>
      </div>
      <div class="field"><label class="field__label">لوگو (SVG/PNG/WEBP)</label><input class="input" type="file" name="logo" accept="image/svg+xml,image/png,image/webp,image/jpeg"></div>
      <span class="field__error" id="toolError"></span>
      <button type="submit" class="btn btn--primary btn--block">ذخیره</button>
    </form>
  </div>
</div>
