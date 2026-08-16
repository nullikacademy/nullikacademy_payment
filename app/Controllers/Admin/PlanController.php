<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Tool;
use App\Services\AuthService;
use App\Services\UsdtPriceService;

final class PlanController extends Controller
{
    /** GET /admin/plans */
    public function index(Request $request): never
    {
        $this->view('admin/plans/index', [
            'title'        => 'مدیریت پلن‌ها',
            'admin'        => (new AuthService())->user(),
            'plans'        => Plan::allWithTool(),
            'tools'        => Tool::all('name ASC'),
            // Market rate is what visitors see; pricing rate includes the
            // markup and is what plan prices are actually built from.
            'market_rate'  => (float) Setting::get('usdt_price_irt', 0),
            'pricing_rate' => (new UsdtPriceService())->pricingRate(),
        ], 'admin/layouts/admin');
    }

    /** POST /admin/plans */
    public function store(Request $request): never
    {
        $this->ensureCsrf($request);
        $data = $this->validatePlan($request);

        $priceIrt = $this->irtFromUsdt((float) $data['price_usdt']);
        $id = Plan::create([
            'tool_id'       => (int) $data['tool_id'],
            'name'          => $data['name'],
            'badge'         => $data['badge'] ?: null,
            'duration'      => $data['duration'],
            'duration_days' => (int) ($data['duration_days'] ?? 30),
            'price_usdt'    => (float) $data['price_usdt'],
            'price_irt'     => $priceIrt,
            'mode_type'     => $data['mode_type'],
            'is_featured'   => (int) ($request->input('is_featured') ? 1 : 0),
            'sort_order'    => (int) ($data['sort_order'] ?? 0),
            'status'        => $data['status'] ?? 'active',
        ]);

        Response::success(['id' => $id, 'price_irt' => $priceIrt], 'پلن ایجاد شد.');
    }

    /** PUT /admin/plans/{id} */
    public function update(Request $request, string $id): never
    {
        $this->ensureCsrf($request);
        if (!Plan::find((int) $id)) {
            Response::error('پلن یافت نشد.', 404);
        }
        $data = $this->validatePlan($request);
        $priceIrt = $this->irtFromUsdt((float) $data['price_usdt']);

        Plan::update((int) $id, [
            'tool_id'       => (int) $data['tool_id'],
            'name'          => $data['name'],
            'badge'         => $data['badge'] ?: null,
            'duration'      => $data['duration'],
            'duration_days' => (int) ($data['duration_days'] ?? 30),
            'price_usdt'    => (float) $data['price_usdt'],
            'price_irt'     => $priceIrt,
            'mode_type'     => $data['mode_type'],
            'is_featured'   => (int) ($request->input('is_featured') ? 1 : 0),
            'sort_order'    => (int) ($data['sort_order'] ?? 0),
            'status'        => $data['status'] ?? 'active',
        ]);

        Response::success(['price_irt' => $priceIrt], 'پلن به‌روزرسانی شد.');
    }

    /** DELETE /admin/plans/{id} */
    public function destroy(Request $request, string $id): never
    {
        $this->ensureCsrf($request);
        Plan::delete((int) $id);
        Response::success([], 'پلن حذف شد.');
    }

    private function validatePlan(Request $request): array
    {
        return $this->validate($request, [
            'tool_id'       => 'required|integer',
            'name'          => 'required|max:120',
            'badge'         => 'max:60',
            'duration'      => 'required|max:60',
            'duration_days' => 'integer',
            'price_usdt'    => 'required|numeric|min:0',
            'mode_type'     => 'required|in:email_password,organization_id',
            'sort_order'    => 'integer',
            'status'        => 'in:active,inactive',
        ]);
    }

    /**
     * price_irt = plan usdt price * the marked-up pricing rate.
     * The markup lives in UsdtPriceService so plan prices and the public
     * ticker cannot drift apart.
     */
    private function irtFromUsdt(float $priceUsdt): int
    {
        return (int) round($priceUsdt * (new UsdtPriceService())->pricingRate());
    }
}
