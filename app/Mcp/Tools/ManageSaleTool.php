<?php

namespace App\Mcp\Tools;

use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Kelola & Lihat Riwayat Transaksi: Menampilkan daftar transaksi, detail isi struk transaksi, atau membatalkan (Void) transaksi berisiko dengan pengembalian stok otomatis.')]
class ManageSaleTool extends Tool
{
    public function handle(Request $request, SaleService $saleService): Response
    {
        $action = $request->get('action', 'list');
        $id = $request->get('id');

        try {
            switch ($action) {
                case 'list':
                    $query = Sale::with(['items', 'user'])->latest('ordered_at');

                    if ($channel = $request->get('channel')) {
                        $query->where('channel', $channel);
                    }
                    if ($paymentMethod = $request->get('payment_method')) {
                        $query->where('payment_method', $paymentMethod);
                    }
                    if ($dateFrom = $request->get('date_from')) {
                        $query->whereDate('ordered_at', '>=', $dateFrom);
                    }
                    if ($dateTo = $request->get('date_to')) {
                        $query->whereDate('ordered_at', '<=', $dateTo);
                    }
                    if ($status = $request->get('status')) {
                        $query->where('status', $status);
                    }

                    $limit = min(50, (int) $request->get('limit', 20));
                    $sales = $query->limit($limit)->get()->map(fn ($s) => [
                        'id' => $s->id,
                        'number' => $s->number,
                        'ordered_at' => $s->ordered_at->format('Y-m-d H:i:s'),
                        'channel' => strtoupper($s->channel),
                        'payment_method' => strtoupper($s->payment_method),
                        'status' => $s->status,
                        'total' => $s->total,
                        'formatted_total' => 'Rp'.number_format($s->total, 0, ',', '.'),
                        'cashier' => $s->user?->name,
                        'items_summary' => $s->items->map(fn ($it) => "{$it->qty}x {$it->menu_name_snapshot}")->implode(', '),
                    ]);

                    return Response::text(json_encode(['status' => 'success', 'count' => $sales->count(), 'sales' => $sales], JSON_PRETTY_PRINT));

                case 'detail':
                    if (! $id) {
                        return Response::text(json_encode(['status' => 'error', 'message' => 'Parameter id wajib diisi']));
                    }
                    $sale = Sale::with(['items', 'user', 'voidedBy'])->find($id);
                    if (! $sale) {
                        return Response::text(json_encode(['status' => 'error', 'message' => "Transaksi #{$id} tidak ditemukan"]));
                    }

                    return Response::text(json_encode(['status' => 'success', 'sale' => $sale], JSON_PRETTY_PRINT));

                case 'void':
                    if (! $id) {
                        return Response::text(json_encode(['status' => 'error', 'message' => 'Parameter id wajib diisi']));
                    }
                    $reason = $request->get('void_reason', $request->get('reason', $request->get('notes')));
                    if (! $reason) {
                        return Response::text(json_encode(['status' => 'error', 'message' => 'Parameter void_reason wajib diisi untuk membatalkan transaksi']));
                    }

                    $sale = Sale::find($id);
                    if (! $sale) {
                        return Response::text(json_encode(['status' => 'error', 'message' => "Transaksi #{$id} tidak ditemukan"]));
                    }

                    $user = User::where('role', 'owner')->first() ?? User::first();
                    $voidedSale = $saleService->voidSale($sale, $reason, $user);

                    return Response::text(json_encode([
                        'status' => 'success',
                        'message' => "Transaksi #{$voidedSale->number} berhasil dibatalkan (VOID). Seluruh stok bahan baku telah dikembalikan ke inventaris.",
                        'void_reason' => $voidedSale->void_reason,
                        'voided_at' => $voidedSale->voided_at->format('Y-m-d H:i:s'),
                    ], JSON_PRETTY_PRINT));

                default:
                    return Response::text(json_encode(['status' => 'error', 'message' => "Aksi '{$action}' tidak dikenal. Pilihan: list, detail, void"]));
            }
        } catch (\Throwable $e) {
            return Response::text(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->description("Aksi: 'list' (daftar transaksi), 'detail' (rincian lengkap), 'void' (batalkan transaksi)")->required(),
            'id' => $schema->integer()->description('ID Transaksi (untuk detail & void)'),
            'date_from' => $schema->string()->description('Filter tanggal awal (YYYY-MM-DD)'),
            'date_to' => $schema->string()->description('Filter tanggal akhir (YYYY-MM-DD)'),
            'channel' => $schema->string()->description("Filter saluran: 'offline', 'gofood', 'shopeefood', 'grabfood'"),
            'payment_method' => $schema->string()->description("Filter metode: 'cash', 'qris', 'transfer'"),
            'status' => $schema->string()->description("Filter status: 'completed' atau 'void'"),
            'void_reason' => $schema->string()->description('Alasan pembatalan/void (wajib untuk action void)'),
            'limit' => $schema->integer()->description('Maksimum baris yang ditampilkan (default: 20, max: 50)'),
        ];
    }
}
