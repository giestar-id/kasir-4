<?php

namespace App\Livewire;

use App\Models\Ingredient;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ScreenshotImport;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public string $period = 'today'; // today, 7_days, month, custom

    public ?string $startDate = null;

    public ?string $endDate = null;

    public string $chartMetric = 'omzet'; // omzet, profit, count

    public string $rightTab = 'critical_stock'; // critical_stock, pending_orders

    public function mount(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $this->startDate = Carbon::today()->format('Y-m-d');
        $this->endDate = Carbon::today()->format('Y-m-d');
    }

    public function setPeriod(string $period): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $this->period = $period;
        $today = Carbon::today();

        match ($period) {
            'today' => [
                $this->startDate = $today->format('Y-m-d'),
                $this->endDate = $today->format('Y-m-d'),
            ],
            '7_days' => [
                $this->startDate = $today->copy()->subDays(6)->format('Y-m-d'),
                $this->endDate = $today->format('Y-m-d'),
            ],
            'month' => [
                $this->startDate = $today->copy()->startOfMonth()->format('Y-m-d'),
                $this->endDate = $today->format('Y-m-d'),
            ],
            default => null,
        };
    }

    public function applyCustomPeriod(string $start, string $end): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $this->startDate = $start;
        $this->endDate = $end;
        $this->period = 'custom';
        $this->dispatch('close-modal', 'custom-date-modal');
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Filter Diterapkan',
            'message' => 'Rentang tanggal berhasil disesuaikan.',
        ]);
    }

    public function render()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        // 1. Base Query for Completed Sales
        $salesQuery = Sale::nonVoid()
            ->whereBetween('ordered_at', [$start, $end]);

        $totalOmzet = (int) (clone $salesQuery)->sum('total');
        $totalTransactions = (int) (clone $salesQuery)->count();

        // 2. Gross Profit Calculation (Omzet - Total HPP from SaleItems)
        $saleIdsSubquery = (clone $salesQuery)->select('id');
        $totalHpp = (int) SaleItem::whereIn('sale_id', $saleIdsSubquery)
            ->sum(DB::raw('qty * unit_hpp'));

        $grossProfit = max(0, $totalOmzet - $totalHpp);
        $profitMarginPct = $totalOmzet > 0 ? round(($grossProfit / $totalOmzet) * 100, 1) : 0;

        // Check if any sold menu had incomplete recipe (HPP = 0)
        $incompleteHppCount = SaleItem::whereIn('sale_id', $saleIdsSubquery)
            ->where('unit_hpp', '<=', 0)
            ->count();

        // 3. Pending Online Orders
        $pendingOrdersCount = ScreenshotImport::whereIn('status', [
            ScreenshotImport::STATUS_PENDING,
            ScreenshotImport::STATUS_PROCESSING,
            ScreenshotImport::STATUS_NEEDS_REVIEW,
            ScreenshotImport::STATUS_READY_TO_SAVE,
        ])->count();

        // 4. Channel Split
        $channelStats = Sale::nonVoid()
            ->whereBetween('ordered_at', [$start, $end])
            ->select('channel', DB::raw('SUM(total) as total_omzet'), DB::raw('COUNT(*) as count'))
            ->groupBy('channel')
            ->get()
            ->keyBy('channel');

        $channels = [
            'offline' => [
                'label' => 'POS Offline',
                'omzet' => (int) ($channelStats['offline']->total_omzet ?? 0),
                'count' => (int) ($channelStats['offline']->count ?? 0),
                'color' => '#5E63E8',
                'badge' => '#E1E0FF',
            ],
            'gofood' => [
                'label' => 'GoFood',
                'omzet' => (int) ($channelStats['gofood']->total_omzet ?? 0),
                'count' => (int) ($channelStats['gofood']->count ?? 0),
                'color' => '#0284C7',
                'badge' => '#E3F5FC',
            ],
            'shopeefood' => [
                'label' => 'ShopeeFood',
                'omzet' => (int) ($channelStats['shopeefood']->total_omzet ?? 0),
                'count' => (int) ($channelStats['shopeefood']->count ?? 0),
                'color' => '#EA580C',
                'badge' => '#FDEEE4',
            ],
        ];

        // 5. Top 5 Menus Sold
        $topMenus = SaleItem::whereIn('sale_id', $saleIdsSubquery)
            ->select('menu_name_snapshot', DB::raw('SUM(qty) as total_qty'), DB::raw('SUM(qty * unit_price) as total_revenue'))
            ->groupBy('menu_name_snapshot')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // 6. Critical Stock Ingredients
        $criticalStock = Ingredient::where('is_active', true)
            ->whereColumn('current_stock', '<=', 'min_stock')
            ->orderBy('current_stock', 'asc')
            ->limit(6)
            ->get();

        // 7. Recent Pending Imports
        $pendingImports = ScreenshotImport::whereIn('status', [
            ScreenshotImport::STATUS_PENDING,
            ScreenshotImport::STATUS_NEEDS_REVIEW,
            ScreenshotImport::STATUS_READY_TO_SAVE,
        ])
            ->latest()
            ->limit(5)
            ->get();

        return view('livewire.dashboard', [
            'totalOmzet' => $totalOmzet,
            'totalTransactions' => $totalTransactions,
            'grossProfit' => $grossProfit,
            'profitMarginPct' => $profitMarginPct,
            'incompleteHppCount' => $incompleteHppCount,
            'pendingOrdersCount' => $pendingOrdersCount,
            'channels' => $channels,
            'topMenus' => $topMenus,
            'criticalStock' => $criticalStock,
            'pendingImports' => $pendingImports,
            'aiEnabled' => Setting::get('ai_enabled', '1'),
            'aiApiKey' => Setting::get('ai_api_key'),
        ])->layout('layouts.app', ['title' => 'Dashboard Outlet']);
    }
}
