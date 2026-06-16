<?php /** @var array $admins */ ?>
<div class="page-head page-head--row">
  <div><h1 class="page-title">مدیران سیستم</h1><p class="page-sub">مدیریت دسترسی‌ها و نقش‌ها</p></div>
  <button class="btn btn--primary btn--sm" id="newUserBtn">+ مدیر جدید</button>
</div>

<div class="table-wrap glass">
  <table class="table">
    <thead><tr><th>نام کاربری</th><th>نام کامل</th><th>نقش</th><th>وضعیت</th><th>آخرین ورود</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($admins as $a): ?>
      <tr data-user='<?= e(json_encode([
          'id' => (int) $a['id'], 'username' => $a['username'], 'full_name' => $a['full_name'],
          'role' => $a['role'], 'status' => $a['status'],
      ], JSON_UNESCAPED_UNICODE)) ?>'>
        <td dir="ltr"><?= e($a['username']) ?></td>
        <td><?= e($a['full_name'] ?: '—') ?></td>
        <td><?= $a['role'] === 'super_admin' ? 'مدیر کل' : 'مدیر' ?></td>
        <td><span class="badge badge--<?= $a['status'] === 'active' ? 'approved' : 'rejected' ?>"><?= $a['status'] === 'active' ? 'فعال' : 'غیرفعال' ?></span></td>
        <td><?= e($a['last_login_at'] ? to_persian_digits(substr($a['last_login_at'], 0, 16)) : '—') ?></td>
        <td>
          <button class="link-btn" data-edit-user>ویرایش</button>
          <button class="link-btn link-btn--danger" data-delete-user="<?= (int) $a['id'] ?>">حذف</button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="admin-modal" id="userModal" aria-hidden="true">
  <div class="admin-modal__backdrop" data-modal-close></div>
  <div class="admin-modal__dialog glass">
    <button class="admin-modal__close" data-modal-close>&times;</button>
    <h2 class="admin-modal__title" id="userModalTitle">مدیر جدید</h2>
    <form id="userForm">
      <input type="hidden" name="id" id="userId">
      <div class="field"><label class="field__label">نام کاربری</label><input class="input" name="username" id="userUsername" dir="ltr" required></div>
      <div class="field"><label class="field__label">نام کامل</label><input class="input" name="full_name" id="userFullName" required></div>
      <div class="field"><label class="field__label">رمز عبور <small id="userPwdHint">(در ویرایش خالی بگذارید)</small></label>
        <input class="input" type="password" name="password" id="userPassword" dir="ltr" autocomplete="new-password"></div>
      <div class="field-row">
        <div class="field"><label class="field__label">نقش</label>
          <select class="input" name="role" id="userRole"><option value="manager">مدیر</option><option value="super_admin">مدیر کل</option></select>
        </div>
        <div class="field"><label class="field__label">وضعیت</label>
          <select class="input" name="status" id="userStatus"><option value="active">فعال</option><option value="inactive">غیرفعال</option></select>
        </div>
      </div>
      <span class="field__error" id="userError"></span>
      <button type="submit" class="btn btn--primary btn--block">ذخیره</button>
    </form>
  </div>
</div>
