<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Order;
use App\Services\AuthService;

final class DashboardController extends Controller
{
    /** GET /admin */
    public function index(Request $request): never
    {
        $byDay = Order::ordersByDay(14);
        $byTool = Order::ordersByTool();

        $this->view('admin/dashboard', [
            'title' => 'داشبورد | مدیریت نولیک آکادمی',
            'admin' => (new AuthService())->user(),
            'stats' => [
                'today'        => Order::countToday(),
                'pending'      => Order::countByStatus('pending_review') + Order::countByStatus('pending_payment'),
                'approved'     => Order::countByStatus('approved') + Order::countByStatus('delivered'),
                'revenue'      => Order::revenue(),
                'conversion'   => Order::conversionRate(),
            ],
            'chart_days'  => $byDay,
            'chart_tools' => $byTool,
        ], 'admin/layouts/admin');
    }
}
