<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Minimal PHP template renderer with layout + component support.
 */
final class View
{
    private string $basePath;

    public function __construct()
    {
        $this->basePath = dirname(__DIR__, 2) . '/views';
    }

    public function render(string $template, array $data = []): string
    {
        $file = $this->basePath . '/' . ltrim($template, '/') . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("View not found: {$template}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }

    /** Render a page inside a layout. */
    public function renderWithLayout(string $template, array $data = [], string $layout = 'layouts/app'): string
    {
        $content = $this->render($template, $data);
        return $this->render($layout, array_merge($data, ['content' => $content]));
    }

    /** Render a reusable component. */
    public function component(string $name, array $data = []): string
    {
        return $this->render('components/' . $name, $data);
    }
}
