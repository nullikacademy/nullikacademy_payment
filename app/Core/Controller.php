<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base controller with view rendering and validation helpers.
 */
abstract class Controller
{
    protected View $view;

    public function __construct()
    {
        $this->view = new View();
    }

    protected function view(string $template, array $data = [], string $layout = 'layouts/app'): never
    {
        $html = $this->view->renderWithLayout($template, $data, $layout);
        Response::html($html);
    }

    protected function validate(Request $request, array $rules, array $messages = []): array
    {
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            Response::error('داده‌های ارسالی نامعتبر است.', 422, $validator->errors());
        }
        return $validator->validated();
    }

    protected function ensureCsrf(Request $request): void
    {
        if (!Csrf::verify($request)) {
            Response::error('توکن امنیتی نامعتبر است. لطفاً صفحه را تازه‌سازی کنید.', 419);
        }
    }
}
