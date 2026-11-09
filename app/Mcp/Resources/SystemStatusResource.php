<?php

namespace App\Mcp\Resources;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\Sale;
use Carbon\Carbon;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('Status Sistem & Outlet')]
#[Description('Ringkasan status outlet, total menu aktif, daftar bahan baku yang di bawah stok minimum, dan jumlah transaksi hari ini.')]
#[Uri('pos://system/status')]
#[MimeType('application/json')]
class SystemStatusResource extends Resource
{
    public function handle(): Response
    {
        $today = Carbon::today();

        $activeMenusCount = Menu::where('is_active', true)->count();
        $totalCategoriesCount = Category::count();

        $lowStockIngredients = Ingredient::where('is_active', true)
            ->whereColumn('current_stock', '<=', 'min_stock')
            ->get(['id', 'name', 'current_stock', 'min_stock', 'base_unit'])
            ->map(fn ($ing) => [
                'id' => $ing->id,
                'name' => $ing->name,
                'current_stock' => $ing->current_stock.' '.$ing->base_unit,
                'min_stock' => $ing->min_stock.' '.$ing->base_unit,
                'is_depleted' => $ing->current_stock <= 0,
            ]);

        $todaySales = Sale::whereDate('ordered_at', $today)
            ->where('status', Sale::STATUS_COMPLETED);

        $todayCount = (clone $todaySales)->count();
        $todayTotal = (int) (clone $todaySales)->sum('total');

        $data = [
            'status' => 'online',
            'server' => config('mcp.server_name', 'POS Kuliner MCP Server'),
            'date' => Carbon::now()->isoFormat('dddd, D MMMM Y - HH:mm'),
            'outlet_metrics' => [
                'active_menus' => $activeMenusCount,
                'categories' => $totalCategoriesCount,
                'low_stock_count' => $lowStockIngredients->count(),
                'low_stock_alerts' => $lowStockIngredients,
            ],
            'today_sales' => [
                'completed_transactions' => $todayCount,
                'total_omzet' => $todayTotal,
                'formatted_omzet' => 'Rp'.number_format($todayTotal, 0, ',', '.'),
            ],
        ];

        return Response::text(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
