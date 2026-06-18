<?php /** @var string $content @var string $title */ ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? 'ورود مدیریت') ?></title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="icon" type="image/png" href="<?= e(asset('images/cropped-favicon-1.png')) ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css">
  <link rel="stylesheet" href="<?= e(asset('css/main.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
  <script type="application/json" id="app-config">
    <?= json_encode(['csrf' => csrf_token(), 'baseUrl' => rtrim((string) config('app.url'), '/')], JSON_UNESCAPED_UNICODE) ?>
  </script>
</head>
<body class="admin-auth">
  <div class="bg-aurora" aria-hidden="true"></div>
  <?= $content ?>
  <div id="toast-root" class="toast-root" aria-live="polite"></div>
  <script src="<?= e(asset('js/config.js')) ?>"></script>
  <script src="<?= e(asset('js/api.js')) ?>"></script>
  <script src="<?= e(asset('js/ui.js')) ?>"></script>
  <script src="<?= e(asset('js/admin.js')) ?>"></script>
</body>
</html>
