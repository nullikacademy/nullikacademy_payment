<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Tool;
use App\Services\AuthService;

final class ToolController extends Controller
{
    private const PER_PAGE = 20;

    /** GET /admin/tools */
    public function index(Request $request): never
    {
        $page = max(1, (int) $request->query('page', 1));
        $search = trim((string) $request->query('search', ''));
        $result = Tool::paginate($page, self::PER_PAGE, $search);

        $this->view('admin/tools/index', [
            'title'       => 'مدیریت ابزارها',
            'admin'       => (new AuthService())->user(),
            'tools'       => $result['items'],
            'total'       => $result['total'],
            'page'        => $page,
            'total_pages' => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'search'      => $search,
        ], 'admin/layouts/admin');
    }

    /** POST /admin/tools */
    public function store(Request $request): never
    {
        $this->ensureCsrf($request);
        $data = $this->validateTool($request);

        if (Tool::slugExists($data['slug'])) {
            Response::error('این اسلاگ قبلاً استفاده شده است.', 422, ['slug' => ['اسلاگ تکراری است.']]);
        }

        $logo = $this->handleLogoUpload($request);
        $id = Tool::create([
            'name'        => $data['name'],
            'slug'        => $data['slug'],
            'description' => $data['description'] ?? '',
            'logo'        => $logo ?? 'tools/default.svg',
            'sort_order'  => (int) ($data['sort_order'] ?? 0),
            'status'      => $data['status'] ?? 'active',
        ]);

        Response::success(['id' => $id], 'ابزار ایجاد شد.');
    }

    /** PUT /admin/tools/{id} */
    public function update(Request $request, string $id): never
    {
        $this->ensureCsrf($request);
        $tool = Tool::find((int) $id);
        if (!$tool) {
            Response::error('ابزار یافت نشد.', 404);
        }

        $data = $this->validateTool($request);
        if (Tool::slugExists($data['slug'], (int) $id)) {
            Response::error('این اسلاگ قبلاً استفاده شده است.', 422, ['slug' => ['اسلاگ تکراری است.']]);
        }

        $payload = [
            'name'        => $data['name'],
            'slug'        => $data['slug'],
            'description' => $data['description'] ?? '',
            'sort_order'  => (int) ($data['sort_order'] ?? 0),
            'status'      => $data['status'] ?? 'active',
        ];
        $logo = $this->handleLogoUpload($request);
        if ($logo) {
            $payload['logo'] = $logo;
        }

        Tool::update((int) $id, $payload);
        Response::success([], 'ابزار به‌روزرسانی شد.');
    }

    /** POST /admin/tools/{id}/toggle */
    public function toggle(Request $request, string $id): never
    {
        $this->ensureCsrf($request);
        $tool = Tool::find((int) $id);
        if (!$tool) {
            Response::error('ابزار یافت نشد.', 404);
        }
        $new = $tool['status'] === 'active' ? 'inactive' : 'active';
        Tool::update((int) $id, ['status' => $new]);
        Response::success(['status' => $new], 'وضعیت تغییر کرد.');
    }

    /** DELETE /admin/tools/{id} */
    public function destroy(Request $request, string $id): never
    {
        $this->ensureCsrf($request);
        Tool::delete((int) $id);
        Response::success([], 'ابزار حذف شد.');
    }

    private function validateTool(Request $request): array
    {
        return $this->validate($request, [
            'name'        => 'required|max:120',
            'slug'        => 'required|max:140|regex:/^[a-z0-9\-]+$/',
            'description' => 'max:2000',
            'sort_order'  => 'integer',
            'status'      => 'in:active,inactive',
        ], [
            'slug.regex' => 'اسلاگ فقط می‌تواند شامل حروف کوچک انگلیسی، عدد و خط تیره باشد.',
        ]);
    }

    private function handleLogoUpload(Request $request): ?string
    {
        $file = $request->file('logo');
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($file['tmp_name']);
        $allowed = ['image/svg+xml' => 'svg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/jpeg' => 'jpg'];
        if (!isset($allowed[$mime])) {
            Response::error('فرمت لوگو مجاز نیست.', 422);
        }
        $dir = base_path('public/assets/images/tools');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $name = preg_replace('/[^a-z0-9\-]/', '', strtolower((string) $request->input('slug', 'tool')));
        $filename = $name . '-' . substr(bin2hex(random_bytes(4)), 0, 6) . '.' . $allowed[$mime];
        move_uploaded_file($file['tmp_name'], $dir . '/' . $filename);
        return 'tools/' . $filename;
    }
}
