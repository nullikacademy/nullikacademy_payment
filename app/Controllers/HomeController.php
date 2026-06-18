<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Tool;
use App\Services\UsdtPriceService;

final class HomeController extends Controller
{
    /** GET / — premium single-page landing + checkout. */
    public function index(Request $request): never
    {
        $tools = Tool::active();
        $ticker = (new UsdtPriceService())->ticker();
        $payment = Config::get('services.payment');

        $this->view('pages/home', [
            'title'       => (string) Setting::get('site_title', 'نالیک آکادمی | مارکت‌پلیس اشتراک هوش مصنوعی'),
            'description' => (string) Setting::get('site_description', 'خرید آسان و امن اشتراک ابزارهای هوش مصنوعی مانند ChatGPT، Claude، Midjourney و … با تحویل سریع و پشتیبانی ۲۴ ساعته.'),
            'tools'       => $tools,
            'ticker'      => $ticker,
            'payment'     => $payment,
        ]);
    }

    /** GET /success/{number} — order success screen. */
    public function success(Request $request, string $number): never
    {
        $order = Order::findByNumber($number);
        if (!$order) {
            Response::notFound();
        }

        $this->view('pages/success', [
            'title'       => 'سفارش ثبت شد | نالیک آکادمی',
            'description' => 'سفارش شما با موفقیت ثبت شد.',
            'order'       => $order,
            'support_url' => Config::get('app.support_url'),
        ]);
    }
}
