<?php
/**
 * @var string $content
 * @var string $title
 * @var string $description
 */
$title = $title ?? 'نالیک آکادمی';
$description = $description ?? 'مارکت‌پلیس اشتراک هوش مصنوعی';
$appUrl = rtrim((string) config('app.url'), '/');
$canonical = $appUrl . ($_SERVER['REQUEST_URI'] ?? '/');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <meta name="theme-color" content="#071226">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <meta name="robots" content="index, follow">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="نالیک آکادمی">
    <meta property="og:title" content="<?= e($title) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:image" content="<?= e(asset('images/logo-mark.svg')) ?>">
    <meta property="og:locale" content="fa_IR">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($title) ?>">
    <meta name="twitter:description" content="<?= e($description) ?>">
    <meta name="twitter:image" content="<?= e(asset('images/logo-mark.svg')) ?>">

    <link rel="icon" type="image/svg+xml" href="<?= e(asset('images/favicon.svg')) ?>">
    <link rel="apple-touch-icon" href="<?= e(asset('images/logo-mark.svg')) ?>">

    <!-- Vazirmatn font -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css">
    <link rel="stylesheet" href="<?= e(asset('css/main.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/checkout.css')) ?>">

    <!-- Schema.org -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "نالیک آکادمی",
      "url": "<?= e($appUrl) ?>",
      "logo": "<?= e(asset('images/logo-mark.svg')) ?>",
      "sameAs": ["<?= e((string) config('app.support_url')) ?>"]
    }
    </script>

    <!-- Front-end runtime config (non-executable JSON) -->
    <script type="application/json" id="app-config">
    <?= json_encode([
        'csrf'      => csrf_token(),
        'apiBase'   => $appUrl . '/api',
        'baseUrl'   => $appUrl,
        'supportUrl'=> config('app.support_url'),
        'otp'       => [
            'length'   => (int) config('services.otp.length', 4),
            'expiry'   => (int) config('services.otp.expiry', 120),
            'cooldown' => (int) config('services.otp.resend_cooldown', 60),
        ],
        'payment'   => [
            'card'     => config('services.payment.card_number'),
            'iban'     => config('services.payment.iban'),
            'name'     => config('services.payment.account_name'),
            'bank'     => config('services.payment.bank_name'),
        ],
    ], JSON_UNESCAPED_UNICODE) ?>
    </script>
</head>
<body>
    <div class="bg-aurora" aria-hidden="true"></div>
    <?= $this->component('navbar') ?>

    <main id="app">
        <?= $content ?>
    </main>

    <?= $this->component('footer') ?>

    <div id="toast-root" class="toast-root" aria-live="polite"></div>

    <script src="<?= e(asset('js/config.js')) ?>"></script>
    <script src="<?= e(asset('js/api.js')) ?>"></script>
    <script src="<?= e(asset('js/ui.js')) ?>"></script>
    <script src="<?= e(asset('js/ticker.js')) ?>"></script>
    <script src="<?= e(asset('js/carousel.js')) ?>"></script>
    <script src="<?= e(asset('js/checkout.js')) ?>"></script>
    <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
