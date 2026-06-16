<?php

declare(strict_types=1);

/**
 * Public web routes.
 * @var \App\Core\Router $router
 */

use App\Controllers\HomeController;
use App\Core\Request;

$router->get('/', [HomeController::class, 'index']);
$router->get('/success/{number}', [HomeController::class, 'success']);

// Dynamic sitemap
$router->get('/sitemap.xml', function (Request $request): void {
    $url = rtrim((string) config('app.url'), '/');
    $today = date('Y-m-d');
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    echo "  <url><loc>{$url}/</loc><lastmod>{$today}</lastmod><changefreq>daily</changefreq><priority>1.0</priority></url>\n";
    echo '</urlset>';
    exit;
});
