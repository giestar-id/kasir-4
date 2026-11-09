<?php

namespace App\Mcp\Tools;

use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Buat Transaksi POS Baru: Mencatat transaksi penjualan kasir (offline/online) lengkap dengan item menu, metode bayar (tunai, QRIS, transfer), diskon, serta otomatis memotong stok bahan baku berdasarkan resep.')]
class CreateSaleTool extends Tool
{
    public function handle(Request $request, SaleService $saleService): Response
    {
        try {
            $rawItems = $request->get('items');
            if (empty($rawItems) || ! is_array($rawItems)) {
                return Response::error(json_encode(['status' => 'error', 'message' => 'Parameter items wajib diisi berupa array dari menu']));
            }

            // Normalize items so menu_id can be auto-resolved from name/menu if missing
            $items = [];
            foreach ($rawItems as $it) {
                $menuId = $it['menu_id'] ?? $it['id'] ?? null;
                if (! $menuId && (! empty($it['name']) || ! empty($it['menu']))) {
                    $searchName = $it['name'] ?? $it['menu'];
                    $foundMenu = \App\Models\Menu::where('name', 'like', "%{$searchName}%")->first();
                    $menuId = $foundMenu?->id;
                }
                if ($menuId) {
                    $items[] = [
                        'menu_id' => (int) $menuId,
                        'qty' => (int) ($it['qty'] ?? $it['quantity'] ?? 1),
                        'note' => $it['note'] ?? $it['notes'] ?? null,
                    ];
                }
            }

            if (empty($items)) {
                return Response::error(json_encode(['status' => 'error', 'message' => 'Tidak ada item menu valid yang ditemukan dalam pesanan']));
            }

            // Get acting user (default to first active user or owner)
            $user = User::where('role', 'owner')->first() ?? User::first();
            if (! $user) {
                return Response::error(json_encode(['status' => 'error', 'message' => 'Tidak ada user aktif dalam database']));
            }

            $channel = $request->get('channel', Sale::CHANNEL_OFFLINE);
            $paymentMethod = $request->get('payment_method', 'cash');
            $discount = (int) $request->get('discount', 0);
            $discountReason = $request->get('discount_reason');
            $notes = $request->get('notes');
            $customerName = $request->get('customer_name');

            $saleData = [
                'channel' => $channel,
                'external_order_id' => $request->get('external_order_id'),
                'discount' => $discount,
                'discount_reason' => $discountReason,
                'payment_method' => $paymentMethod,
                'paid_amount' => (int) $request->get('paid_amount', 0),
                'notes' => $customerName ? "Pelanggan: {$customerName}. ".($notes ?? '') : $notes,
                'ordered_at' => Carbon::now(),
            ];

            $sale = $saleService->createSale($saleData, $items, $user);

            return Response::text(json_encode([
                'status' => 'success',
                'message' => "Transaksi #{$sale->number} berhasil dibuat",
                'transaction_number' => $sale->number,
                'subtotal' => $sale->subtotal,
                'discount' => $sale->discount,
                'total' => $sale->total,
                'formatted_total' => 'Rp'.number_format($sale->total, 0, ',', '.'),
                'payment_method' => strtoupper($sale->payment_method),
                'channel' => strtoupper($sale->channel),
                'items_count' => $sale->items->count(),
                'items' => $sale->items->map(fn ($it) => [
                    'name' => $it->menu_name_snapshot,
                    'qty' => $it->qty,
                    'price' => $it->unit_price,
                    'subtotal' => $it->subtotal,
                ]),
            ], JSON_PRETTY_PRINT));

        } catch (\Throwable $e) {
            return Response::error(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'items' => $schema->array()->description('Daftar menu pesanan. Contoh: [{"menu_id": 1, "qty": 2, "note": "pedas"}, {"menu_id": 3, "qty": 1}]')->required(),
            'channel' => $schema->string()->description("Saluran penjualan: 'offline', 'gofood', 'shopeefood', 'grabfood'"),
            'payment_method' => $schema->string()->description("Metode pembayaran: 'cash', 'qris', 'transfer'"),
            'paid_amount' => $schema->integer()->description('Nominal uang tunai diterima dari pembeli (jika cash)'),
            'discount' => $schema->integer()->description('Nominal potongan diskon (Rp)'),
            'discount_reason' => $schema->string()->description('Alasan pemberian diskon jika ada diskon'),
            'customer_name' => $schema->string()->description('Nama pelanggan / nomor meja'),
            'notes' => $schema->string()->description('Catatan transaksi'),
            'external_order_id' => $schema->string()->description('Nomor ID pesanan ojol (jika online channel)'),
        ];
    }
}
