<?php
/** @var string $content @var string $title @var array $admin */
$admin = $admin ?? [];
$role = $admin['role'] ?? 'manager';
$uri = $_SERVER['REQUEST_URI'] ?? '';
if (!function_exists('navActive')) {
  function navActive(string $path, string $uri): string {
    $p = parse_url($uri, PHP_URL_PATH) ?? '';
    if ($path === '/admin') return $p === '/admin' ? 'is-active' : '';
    return str_starts_with($p, $path) ? 'is-active' : '';
  }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? 'مدیریت') ?></title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="icon" type="image/png" href="<?= e(asset('images/cropped-favicon-1.png')) ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css">
  <link rel="stylesheet" href="<?= e(asset('css/main.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
  <script type="application/json" id="app-config">
    <?= json_encode(['csrf' => csrf_token(), 'baseUrl' => rtrim((string) config('app.url'), '/')], JSON_UNESCAPED_UNICODE) ?>
  </script>
</head>
<body class="admin">
  <div class="bg-aurora" aria-hidden="true"></div>

  <aside class="admin-sidebar" id="adminSidebar">
    <a href="<?= e(url('admin')) ?>" class="admin-sidebar__brand">
      <img src="<?= e(asset('images/Nullik-Academy-Logo.svg')) ?>" alt="نالیک آکادمی" height="36">
    </a>
    <nav class="admin-nav">
      <a href="<?= e(url('admin')) ?>" class="admin-nav__link <?= navActive('/admin', $uri) ?>">داشبورد</a>
      <a href="<?= e(url('admin/orders')) ?>" class="admin-nav__link <?= navActive('/admin/orders', $uri) ?>">سفارش‌ها</a>
      <a href="<?= e(url('admin/tools')) ?>" class="admin-nav__link <?= navActive('/admin/tools', $uri) ?>">ابزارها</a>
      <a href="<?= e(url('admin/plans')) ?>" class="admin-nav__link <?= navActive('/admin/plans', $uri) ?>">پلن‌ها</a>
      <?php if ($role === 'super_admin'): ?>
      <a href="<?= e(url('admin/users')) ?>" class="admin-nav__link <?= navActive('/admin/users', $uri) ?>">مدیران</a>
      <?php endif; ?>
      <a href="<?= e(url('admin/content')) ?>" class="admin-nav__link <?= navActive('/admin/content', $uri) ?>">متن‌های سایت</a>
      <a href="<?= e(url('admin/settings')) ?>" class="admin-nav__link <?= navActive('/admin/settings', $uri) ?>">تنظیمات</a>
      <a href="<?= e(url('admin/diagnostics')) ?>" class="admin-nav__link <?= navActive('/admin/diagnostics', $uri) ?>">عیب‌یابی</a>
    </nav>
  </aside>

  <div class="admin-main">
    <header class="admin-topbar">
      <button class="admin-topbar__burger" id="adminBurger" aria-label="منو">☰</button>
      <div class="admin-topbar__user">
        <span class="admin-topbar__name"><?= e($admin['full_name'] ?? $admin['username'] ?? 'مدیر') ?></span>
        <span class="admin-topbar__role"><?= e($role === 'super_admin' ? 'مدیر کل' : 'مدیر') ?></span>
        <button class="btn btn--ghost btn--sm" id="logoutBtn">خروج</button>
      </div>
    </header>

    <main class="admin-content">
      <?= $content ?>
    </main>
  </div>

  <div id="toast-root" class="toast-root" aria-live="polite"></div>
  <script src="<?= e(asset('js/config.js')) ?>"></script>
  <script src="<?= e(asset('js/api.js')) ?>"></script>
  <script src="<?= e(asset('js/ui.js')) ?>"></script>
  <script src="<?= e(asset('js/admin.js')) ?>"></script>
</body>
</html>
