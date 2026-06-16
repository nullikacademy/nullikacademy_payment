<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Admin;
use App\Services\AuthService;

final class AdminUserController extends Controller
{
    /** GET /admin/users */
    public function index(Request $request): never
    {
        $this->guardSuperAdmin();
        $this->view('admin/users/index', [
            'title' => 'مدیران سیستم',
            'admin' => (new AuthService())->user(),
            'admins'=> Admin::all('id ASC'),
        ], 'admin/layouts/admin');
    }

    /** POST /admin/users */
    public function store(Request $request): never
    {
        $this->ensureCsrf($request);
        $this->guardSuperAdmin();

        $data = $this->validate($request, [
            'username'  => 'required|max:60|regex:/^[a-zA-Z0-9_\.]+$/',
            'full_name' => 'required|max:120',
            'password'  => 'required|min:8|max:190',
            'role'      => 'required|in:super_admin,manager',
        ]);

        if (Admin::usernameExists((string) $data['username'])) {
            Response::error('این نام کاربری قبلاً ثبت شده است.', 422);
        }

        $id = Admin::create([
            'username'      => $data['username'],
            'full_name'     => $data['full_name'],
            'password_hash' => password_hash((string) $data['password'], PASSWORD_BCRYPT),
            'role'          => $data['role'],
            'status'        => 'active',
        ]);

        Response::success(['id' => $id], 'مدیر جدید ایجاد شد.');
    }

    /** PUT /admin/users/{id} */
    public function update(Request $request, string $id): never
    {
        $this->ensureCsrf($request);
        $this->guardSuperAdmin();

        $admin = Admin::find((int) $id);
        if (!$admin) {
            Response::error('مدیر یافت نشد.', 404);
        }

        $data = $this->validate($request, [
            'full_name' => 'required|max:120',
            'role'      => 'required|in:super_admin,manager',
            'status'    => 'required|in:active,inactive',
        ]);

        $payload = [
            'full_name' => $data['full_name'],
            'role'      => $data['role'],
            'status'    => $data['status'],
        ];
        $password = (string) $request->input('password', '');
        if ($password !== '') {
            if (mb_strlen($password) < 8) {
                Response::error('رمز عبور باید حداقل ۸ کاراکتر باشد.', 422);
            }
            $payload['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        }

        Admin::update((int) $id, $payload);
        Response::success([], 'مدیر به‌روزرسانی شد.');
    }

    /** DELETE /admin/users/{id} */
    public function destroy(Request $request, string $id): never
    {
        $this->ensureCsrf($request);
        $this->guardSuperAdmin();

        $auth = new AuthService();
        if ($auth->id() === (int) $id) {
            Response::error('نمی‌توانید حساب خود را حذف کنید.', 422);
        }
        Admin::delete((int) $id);
        Response::success([], 'مدیر حذف شد.');
    }

    private function guardSuperAdmin(): void
    {
        if (!(new AuthService())->isSuperAdmin()) {
            Response::error('فقط مدیر کل به این بخش دسترسی دارد.', 403);
        }
    }
}
