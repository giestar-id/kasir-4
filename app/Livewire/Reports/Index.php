<?php

namespace App\Livewire\Reports;

use App\Models\Ingredient;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Index extends Component
{
    public string $period = 'month'; // today, week, month, custom

    public ?string $startDate = null;

    public ?string $endDate = null;

    public string $customStartDate = '';

    public string $customEndDate = '';

    public string $activeTab = 'per_menu'; // per_period, per_channel, per_category, per_menu, per_ingredient

    public function mount(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $this->startDate = Carbon::today()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::today()->format('Y-m-d');
        $this->customStartDate = $this->startDate;
        $this->customEndDate = $this->endDate;
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
            'week' => [
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

    public function openCustomDateModal(): void
    {
        $this->customStartDate = $this->startDate ?: Carbon::today()->startOfMonth()->format('Y-m-d');
        $this->customEndDate = $this->endDate ?: Carbon::today()->format('Y-m-d');
        $this->dispatch('open-modal', 'custom-date-modal');
    }

    public function applyCustomPeriod(?string $start = null, ?string $end = null): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);

        $startDate = $start ?: $this->customStartDate;
        $endDate = $end ?: $this->customEndDate;

        if ($startDate && $endDate) {
            if ($startDate > $endDate) {
                $temp = $startDate;
                $startDate = $endDate;
                $endDate = $temp;
            }
            $this->startDate = $startDate;
            $this->endDate = $endDate;
            $this->customStartDate = $startDate;
            $this->customEndDate = $endDate;
            $this->period = 'custom';
        }

        $this->dispatch('close-modal', 'custom-date-modal');
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Filter Diterapkan',
            'message' => 'Laporan diperbarui berdasarkan rentang tanggal baru.',
        ]);
    }

    public function exportCsv(): StreamedResponse
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();
        $tab = $this->activeTab;

        $filename = "laporan_{$tab}_".date('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($start, $end, $tab) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM

            if ($tab === 'per_menu') {
                fputcsv($handle, ['Nama Menu', 'Terjual (Qty)', 'Omzet (Rp)', 'Total HPP (Rp)', 'Estimasi Laba Kotor (Rp)', 'Margin (%)']);

                $items = SaleItem::whereHas('sale', function ($q) use ($start, $end) {
                    $q->nonVoid()->whereBetween('ordered_at', [$start, $end]);
                })
                    ->select('menu_name_snapshot', DB::raw('SUM(qty) as total_qty'), DB::raw('SUM(qty * unit_price) as total_omzet'), DB::raw('SUM(qty * unit_hpp) as total_hpp'))
                    ->groupBy('menu_name_snapshot')
                    ->orderByDesc('total_omzet')
                    ->get();

                foreach ($items as $item) {
                    $profit = max(0, $item->total_omzet - $item->total_hpp);
                    $margin = $item->total_omzet > 0 ? round(($profit / $item->total_omzet) * 100, 1) : 0;
                    fputcsv($handle, [
                        $item->menu_name_snapshot,
                        $item->total_qty,
                        $item->total_omzet,
                        $item->total_hpp,
                        $profit,
                        $margin.'%',
                    ]);
                }
            } elseif ($tab === 'per_period') {
                fputcsv($handle, ['Tanggal', 'Jumlah Transaksi', 'Total Omzet (Rp)', 'Total Diskon (Rp)']);

                $daily = Sale::nonVoid()
                    ->whereBetween('ordered_at', [$start, $end])
                    ->select(DB::raw('DATE(ordered_at) as date'), DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as omzet'), DB::raw('SUM(discount) as discount'))
                    ->groupBy('date')
                    ->orderBy('date', 'desc')
                    ->get();

                foreach ($daily as $d) {
                    fputcsv($handle, [$d->date, $d->count, $d->omzet, $d->discount]);
                }
            } elseif ($tab === 'per_channel') {
                fputcsv($handle, ['Kanal Penjualan', 'Jumlah Transaksi', 'Penyesuaian Platform (Rp)', 'Total Omzet Bersih (Rp)']);

                $channels = Sale::nonVoid()
                    ->whereBetween('ordered_at', [$start, $end])
                    ->select('channel', DB::raw('COUNT(*) as count'), DB::raw('SUM(platform_adjustment) as adjustment'), DB::raw('SUM(total) as omzet'))
                    ->groupBy('channel')
                    ->get();

                foreach ($channels as $c) {
                    fputcsv($handle, [strtoupper($c->channel), $c->count, $c->adjustment, $c->omzet]);
                }
            } else {
                fputcsv($handle, ['Nama Bahan', 'Satuan', 'Stok Saat Ini', 'Harga Rata-rata (Rp)', 'Total Nilai Stok (Rp)']);
                $ings = Ingredient::where('is_active', true)->get();
                foreach ($ings as $ing) {
                    $val = (int) round((float) $ing->current_stock * (float) $ing->avg_cost_per_base);
                    fputcsv($handle, [$ing->name, $ing->base_unit, $ing->current_stock, $ing->avg_cost_per_base, $val]);
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function render()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        // High performance SQL Aggregations (Combined single query)
        $salesQuery = Sale::nonVoid()->whereBetween('ordered_at', [$start, $end]);

        $totalOmzet = (int) (clone $salesQuery)->sum('total');
        $saleIds = (clone $salesQuery)->pluck('id');

        $totalHpp = (int) SaleItem::whereIn('sale_id', $saleIds)
            ->sum(DB::raw('qty * unit_hpp'));

        $incompleteHppCount = SaleItem::whereIn('sale_id', $saleIds)
            ->where('unit_hpp', '<=', 0)
            ->count();

        $grossProfit = max(0, $totalOmzet - $totalHpp);
        $profitMarginPct = $totalOmzet > 0 ? round(($grossProfit / $totalOmzet) * 100, 1) : 0;

        // Lazy load tab data on demand to eliminate unnecessary database queries
        $menuReport = $this->activeTab === 'per_menu'
            ? SaleItem::whereIn('sale_id', $saleIds)
                ->select('menu_name_snapshot', DB::raw('SUM(qty) as total_qty'), DB::raw('SUM(qty * unit_price) as total_omzet'), DB::raw('SUM(qty * unit_hpp) as total_hpp'))
                ->groupBy('menu_name_snapshot')
                ->orderByDesc('total_omzet')
                ->get()
            : collect();

        $dailyReport = $this->activeTab === 'per_period'
            ? Sale::nonVoid()
                ->whereBetween('ordered_at', [$start, $end])
                ->select(DB::raw('DATE(ordered_at) as date'), DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as omzet'), DB::raw('SUM(discount) as discount'))
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->get()
            : collect();

        $channelReport = $this->activeTab === 'per_channel'
            ? Sale::nonVoid()
                ->whereBetween('ordered_at', [$start, $end])
                ->select('channel', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as omzet'), DB::raw('SUM(platform_adjustment) as adjustment'))
                ->groupBy('channel')
                ->get()
            : collect();

        $inventoryReport = $this->activeTab === 'per_ingredient'
            ? Ingredient::where('is_active', true)
                ->orderBy('name')
                ->get()
            : collect();

        $totalInventoryValue = $this->activeTab === 'per_ingredient'
            ? $inventoryReport->sum(function ($ing) {
                return (int) round((float) $ing->current_stock * (float) $ing->avg_cost_per_base);
            })
            : 0;

        return view('livewire.reports.index', [
            'totalOmzet' => $totalOmzet,
            'totalHpp' => $totalHpp,
            'grossProfit' => $grossProfit,
            'profitMarginPct' => $profitMarginPct,
            'incompleteHppCount' => $incompleteHppCount,
            'menuReport' => $menuReport,
            'dailyReport' => $dailyReport,
            'channelReport' => $channelReport,
            'inventoryReport' => $inventoryReport,
            'totalInventoryValue' => $totalInventoryValue,
        ])->layout('layouts.app', ['title' => 'Laporan Penjualan & Laba']);
    }
}
