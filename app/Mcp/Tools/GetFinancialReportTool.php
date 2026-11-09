<?php

namespace App\Mcp\Tools;

use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Laporan Keuangan & Analisis Omzet: Menampilkan ringkasan omzet, laba kotor, jumlah transaksi, rata-rata transaksi, menu terlaris, serta rincian per channel penjualan.')]
class GetFinancialReportTool extends Tool
{
    public function handle(Request $request): Response
    {
        $period = $request->get('period', 'today'); // today, 7days, month, custom
        $query = Sale::where('status', Sale::STATUS_COMPLETED);

        switch ($period) {
            case 'today':
                $query->whereDate('ordered_at', Carbon::today());
                $periodLabel = 'Hari Ini ('.Carbon::today()->format('d M Y').')';
                break;
            case '7days':
                $query->whereDate('ordered_at', '>=', Carbon::now()->subDays(6)->startOfDay());
                $periodLabel = '7 Hari Terakhir';
                break;
            case 'month':
                $query->whereYear('ordered_at', Carbon::now()->year)->whereMonth('ordered_at', Carbon::now()->month);
                $periodLabel = 'Bulan Ini ('.Carbon::now()->format('F Y').')';
                break;
            case 'custom':
                if ($from = $request->get('date_from')) {
                    $query->whereDate('ordered_at', '>=', $from);
                }
                if ($to = $request->get('date_to')) {
                    $query->whereDate('ordered_at', '<=', $to);
                }
                $periodLabel = ($request->get('date_from') ?? 'Awal').' s/d '.($request->get('date_to') ?? 'Sekarang');
                break;
            default:
                $periodLabel = 'Semua Waktu';
                break;
        }

        $sales = $query->with('items')->get();

        $totalOmzet = (int) $sales->sum('total');
        $totalTransactions = $sales->count();
        $totalHpp = (int) $sales->sum(fn ($s) => $s->items->sum(fn ($it) => $it->unit_hpp * $it->qty));
        $grossProfit = $totalOmzet - $totalHpp;
        $avgBasket = $totalTransactions > 0 ? (int) round($totalOmzet / $totalTransactions) : 0;

        // Channel Breakdown
        $channelBreakdown = $sales->groupBy('channel')->map(fn ($group, $ch) => [
            'channel' => strtoupper($ch),
            'count' => $group->count(),
            'total' => (int) $group->sum('total'),
            'formatted_total' => 'Rp'.number_format($group->sum('total'), 0, ',', '.'),
        ])->values();

        // Payment Method Breakdown
        $paymentBreakdown = $sales->groupBy('payment_method')->map(fn ($group, $pm) => [
            'method' => strtoupper($pm),
            'count' => $group->count(),
            'total' => (int) $group->sum('total'),
            'formatted_total' => 'Rp'.number_format($group->sum('total'), 0, ',', '.'),
        ])->values();

        // Top Selling Menus in this period
        $saleIds = $sales->pluck('id');
        $topMenus = SaleItem::whereIn('sale_id', $saleIds)
            ->select('menu_name_snapshot', DB::raw('SUM(qty) as total_qty'), DB::raw('SUM(qty * unit_price) as total_revenue'))
            ->groupBy('menu_name_snapshot')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->menu_name_snapshot,
                'sold_qty' => (int) $row->total_qty,
                'revenue' => (int) $row->total_revenue,
                'formatted_revenue' => 'Rp'.number_format($row->total_revenue, 0, ',', '.'),
            ]);

        return Response::text(json_encode([
            'status' => 'success',
            'period' => $periodLabel,
            'summary' => [
                'total_omzet' => $totalOmzet,
                'formatted_omzet' => 'Rp'.number_format($totalOmzet, 0, ',', '.'),
                'total_transactions' => $totalTransactions,
                'total_hpp' => $totalHpp,
                'formatted_hpp' => 'Rp'.number_format($totalHpp, 0, ',', '.'),
                'gross_profit' => $grossProfit,
                'formatted_gross_profit' => 'Rp'.number_format($grossProfit, 0, ',', '.'),
                'profit_margin' => $totalOmzet > 0 ? round(($grossProfit / $totalOmzet) * 100, 1).'%' : '0%',
                'average_basket_size' => 'Rp'.number_format($avgBasket, 0, ',', '.'),
            ],
            'channels' => $channelBreakdown,
            'payment_methods' => $paymentBreakdown,
            'top_menus' => $topMenus,
        ], JSON_PRETTY_PRINT));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'period' => $schema->string()->description("Rentang waktu laporan: 'today', '7days', 'month', 'custom' (default: 'today')"),
            'date_from' => $schema->string()->description('Tanggal mulai untuk period custom (YYYY-MM-DD)'),
            'date_to' => $schema->string()->description('Tanggal akhir untuk period custom (YYYY-MM-DD)'),
        ];
    }
}
