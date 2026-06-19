<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Setting;
use App\Services\AuthService;
use App\Services\UsdtPriceService;

final class SettingsController extends Controller
{
    /** GET /admin/settings */
    public function index(Request $request): never
    {
        $this->view('admin/settings/index', [
            'title'    => 'تنظیمات',
            'admin'    => (new AuthService())->user(),
            'settings' => Setting::map(),
        ], 'admin/layouts/admin');
    }

    /** POST /admin/settings */
    public function update(Request $request): never
    {
        $this->ensureCsrf($request);

        $editable = [
            'site_title'             => 'seo',
            'site_description'       => 'seo',
            'online_gateway_enabled' => 'payment',
            // SMS (IPPanel) pattern codes + reference texts + admin mobile
            'sms_pattern_otp'         => 'sms',
            'sms_text_otp'            => 'sms',
            'sms_pattern_order_user'  => 'sms',
            'sms_text_order_user'     => 'sms',
            'sms_pattern_order_admin' => 'sms',
            'sms_text_order_admin'    => 'sms',
            'sms_pattern_delivered'   => 'sms',
            'sms_text_delivered'      => 'sms',
            'sms_admin_mobile'        => 'sms',
            // USDT markup applied to the API price
            'usdt_markup_type'        => 'pricing',
            'usdt_markup_amount'      => 'pricing',
        ];

        foreach ($editable as $key => $group) {
            $value = $request->input($key);
            if ($value !== null) {
                Setting::set($key, (string) $value, $group);
            }
        }

        Response::success([], 'تنظیمات ذخیره شد.');
    }

    /** POST /admin/settings/refresh-price — manually refresh USDT price. */
    public function refreshPrice(Request $request): never
    {
        $this->ensureCsrf($request);
        $result = (new UsdtPriceService())->refreshAndRecalculate();
        Response::success([
            'price'         => $result['price'],
            'plans_updated' => $result['plans_updated'],
        ], 'قیمت USDT و پلن‌ها به‌روزرسانی شد.');
    }
}
