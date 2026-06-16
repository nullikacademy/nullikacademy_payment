<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Plan;
use App\Models\Tool;

final class ToolController extends Controller
{
    /** GET /api/tools — active tools for the carousel. */
    public function index(Request $request): never
    {
        $tools = array_map(static function (array $t): array {
            return [
                'id'          => (int) $t['id'],
                'name'        => $t['name'],
                'slug'        => $t['slug'],
                'logo'        => asset('images/' . ($t['logo'] ?? 'tools/default.svg')),
                'description' => $t['description'],
            ];
        }, Tool::active());

        Response::success(['tools' => $tools]);
    }

    /** GET /api/tools/{slug} — single tool detail. */
    public function show(Request $request, string $slug): never
    {
        $tool = Tool::findActiveBySlug($slug);
        if (!$tool) {
            Response::error('ابزار یافت نشد.', 404);
        }
        Response::success(['tool' => [
            'id'          => (int) $tool['id'],
            'name'        => $tool['name'],
            'slug'        => $tool['slug'],
            'logo'        => asset('images/' . $tool['logo']),
            'description' => $tool['description'],
        ]]);
    }

    /** GET /api/tools/{slug}/plans — active plans for a tool. */
    public function plans(Request $request, string $slug): never
    {
        $tool = Tool::findActiveBySlug($slug);
        if (!$tool) {
            Response::error('ابزار یافت نشد.', 404);
        }

        $plans = array_map(static function (array $p): array {
            return [
                'id'         => (int) $p['id'],
                'name'       => $p['name'],
                'badge'      => $p['badge'],
                'duration'   => $p['duration'],
                'price_usdt' => (float) $p['price_usdt'],
                'price_irt'  => (int) $p['price_irt'],
                'price_irt_formatted' => money_irt((int) $p['price_irt']),
                'mode_type'  => $p['mode_type'],
                'is_featured'=> (bool) $p['is_featured'],
            ];
        }, Plan::activeForTool((int) $tool['id']));

        Response::success([
            'tool'  => ['id' => (int) $tool['id'], 'name' => $tool['name'], 'slug' => $tool['slug']],
            'plans' => $plans,
        ]);
    }
}
