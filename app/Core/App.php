<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Application kernel. Bootstraps environment, config, session,
 * security headers and dispatches the router.
 */
final class App
{
    private Router $router;

    public function __construct(private string $basePath)
    {
        Env::load($this->basePath . '/.env');
        Config::load($this->basePath . '/config');
        date_default_timezone_set((string) Config::get('app.timezone', 'Asia/Tehran'));

        $this->configureErrorHandling();
        $this->enforceHttps();
        Session::start();
        $this->sendSecurityHeaders();

        $this->router = new Router();
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function loadRoutes(array $files): void
    {
        $router = $this->router;
        foreach ($files as $file) {
            if (is_file($file)) {
                require $file;
            }
        }
    }

    public function run(): void
    {
        $request = new Request();
        try {
            $this->router->dispatch($request);
        } catch (Throwable $e) {
            $this->handleException($e, $request);
        }
    }

    private function configureErrorHandling(): void
    {
        $debug = (bool) Config::get('app.debug', false);
        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');

        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
    }

    private function handleException(Throwable $e, Request $request): void
    {
        Logger::error($e->getMessage(), [
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
            'uri'   => $request->uri(),
            'trace' => substr($e->getTraceAsString(), 0, 2000),
        ]);

        $debug = (bool) Config::get('app.debug', false);

        if ($request->isAjax() || str_starts_with($request->uri(), '/api')) {
            Response::error(
                $debug ? $e->getMessage() : 'خطای داخلی سرور رخ داده است.',
                500
            );
        }

        http_response_code(500);
        if ($debug) {
            echo '<pre style="direction:ltr;padding:20px;background:#0b1220;color:#f87171;">';
            echo e($e->getMessage()) . "\n\n" . e($e->getTraceAsString());
            echo '</pre>';
        } else {
            echo '<h1 style="font-family:sans-serif;text-align:center;margin-top:80px;">خطای داخلی سرور</h1>';
        }
        exit;
    }

    private function enforceHttps(): void
    {
        if (!Config::get('app.force_https', false) || PHP_SAPI === 'cli') {
            return;
        }
        $request = new Request();
        if (!$request->isSecure()) {
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            Response::redirect('https://' . $host . $uri, 301);
        }
    }

    private function sendSecurityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-XSS-Protection: 1; mode=block');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header_remove('X-Powered-By');

        if (Config::get('app.force_https', false)) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }

        $csp = "default-src 'self'; "
            . "img-src 'self' data: https:; "
            . "font-src 'self' data:; "
            . "style-src 'self' 'unsafe-inline'; "
            . "script-src 'self'; "
            . "connect-src 'self' https://api-web.tabdeal.org; "
            . "frame-ancestors 'self'; base-uri 'self'; form-action 'self'";
        header("Content-Security-Policy: {$csp}");
    }
}
