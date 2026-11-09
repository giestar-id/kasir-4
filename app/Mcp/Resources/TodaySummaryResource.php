<?php

namespace App\Mcp\Resources;

use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('Ringkasan Penjualan Hari Ini')]
#[Description('Data keuangan real-time hari ini: omzet, total HPP, estimasi laba kotor, dan rincian metode pembayaran.')]
#[Uri('pos://reports/today')]
#[MimeType('application/json')]
class TodaySummaryResource extends Resource
{
    public function handle(): Response
    {
        $today = Carbon::today();

        $completedSales = Sale::whereDate('ordered_at', $today)
            ->where('status', Sale::STATUS_COMPLETED);

        $totalTransactions = (clone $completedSales)->count();
        $totalOmzet = (int) (clone $completedSales)->sum('total');

        $completedSaleIds = (clone $completedSales)->pluck('id');

        $totalHpp = (int) SaleItem::whereIn('sale_id', $completedSaleIds)
            ->sum(DB::raw('qty * unit_hpp'));

        $grossProfit = $totalOmzet - $totalHpp;
        $profitMargin = $totalOmzet > 0 ? round(($grossProfit / $totalOmzet) * 100, 1) : 0;

        $paymentMethods = (clone $completedSales)
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as total'))
            ->groupBy('payment_method')
            ->get()
            ->map(fn ($pm) => [
                'method' => strtoupper($pm->payment_method),
                'count' => (int) $pm->count,
                'total' => (int) $pm->total,
                'formatted_total' => 'Rp'.number_format((int) $pm->total, 0, ',', '.'),
            ]);

        $data = [
            'period' => Carbon::now()->format('d M Y'),
            'total_transactions' => $totalTransactions,
            'total_omzet' => $totalOmzet,
            'formatted_omzet' => 'Rp'.number_format($totalOmzet, 0, ',', '.'),
            'total_hpp' => $totalHpp,
            'formatted_hpp' => 'Rp'.number_format($totalHpp, 0, ',', '.'),
            'gross_profit' => $grossProfit,
            'formatted_gross_profit' => 'Rp'.number_format($grossProfit, 0, ',', '.'),
            'profit_margin' => $profitMargin.'%',
            'payment_breakdown' => $paymentMethods,
        ];

        return Response::text(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
