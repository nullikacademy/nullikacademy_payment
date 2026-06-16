<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\UsdtPriceService;

final class PriceController extends Controller
{
    /** GET /api/usdt-price — live USDT/IRT ticker. */
    public function usdt(Request $request): never
    {
        $ticker = (new UsdtPriceService())->ticker();
        Response::success(['ticker' => [
            'price'             => (float) $ticker['price'],
            'price_formatted'   => money_irt((float) $ticker['price']),
            'high_24'           => (float) $ticker['high_24'],
            'low_24'            => (float) $ticker['low_24'],
            'change_percent_24' => (float) $ticker['change_percent_24'],
            'updated_at'        => $ticker['updated_at'],
        ]]);
    }
}
