<?php

namespace App\Mcp\Tools;

use App\Models\Ingredient;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Operasional Stok & Pembelian Bahan: Mencatat pembelian/belanja bahan (otomatis hitung moving average HPP), mencatat stok opname fisik/waste, atau melihat kartu riwayat pergerakan stok.')]
class ManageStockTool extends Tool
{
    public function handle(Request $request, StockService $stockService): Response
    {
        $action = $request->get('action');
        $ingredientId = (int) $request->get('ingredient_id');

        if (! $ingredientId) {
            return Response::text(json_encode(['status' => 'error', 'message' => 'Parameter ingredient_id wajib diisi']));
        }

        $ing = Ingredient::find($ingredientId);
        if (! $ing) {
            return Response::text(json_encode(['status' => 'error', 'message' => "Bahan baku #{$ingredientId} tidak ditemukan"]));
        }

        $user = User::where('role', 'owner')->first() ?? User::first();

        try {
            switch ($action) {
                case 'purchase':
                    $qtyPurchaseUnit = (float) ($request->get('qty_purchase_unit') ?? $request->get('purchase_qty'));
                    $totalCost = (int) $request->get('total_cost');
                    $purchasedAt = $request->get('purchased_at', Carbon::now()->format('Y-m-d'));
                    $note = $request->get('note', $request->get('notes'));

                    if ($qtyPurchaseUnit <= 0 || $totalCost <= 0) {
                        return Response::text(json_encode(['status' => 'error', 'message' => 'qty_purchase_unit dan total_cost harus bernilai lebih dari 0']));
                    }

                    $purchase = $stockService->recordPurchase(
                        $ingredientId,
                        $qtyPurchaseUnit,
                        $totalCost,
                        $purchasedAt,
                        $note,
                        $user?->id
                    );

                    $ing->refresh();

                    return Response::text(json_encode([
                        'status' => 'success',
                        'message' => "Pembelian {$ing->name} sebanyak {$qtyPurchaseUnit} {$ing->purchase_unit} berhasil dicatat",
                        'purchase_id' => $purchase->id,
                        'total_cost' => $purchase->total_cost,
                        'formatted_total_cost' => 'Rp'.number_format($purchase->total_cost, 0, ',', '.'),
                        'new_current_stock' => $ing->current_stock.' '.$ing->base_unit,
                        'new_avg_cost_per_base' => 'Rp'.number_format($ing->avg_cost_per_base, 0, ',', '.').' / '.$ing->base_unit,
                    ], JSON_PRETTY_PRINT));

                case 'opname':
                case 'adjustment':
                    $type = $action === 'opname' ? 'opname' : $request->get('type', 'opname');
                    $actualStock = $request->get('actual_stock', $request->get('physical_stock'));
                    $reason = $request->get('reason', $request->get('notes', 'Stok Opname'));

                    if ($actualStock === null || ! $reason) {
                        return Response::text(json_encode(['status' => 'error', 'message' => 'Parameter actual_stock (stok fisik hasil hitung) dan reason wajib diisi']));
                    }

                    $movement = $stockService->recordAdjustment(
                        $ingredientId,
                        $type,
                        (float) $actualStock,
                        $reason,
                        $user?->id
                    );

                    $ing->refresh();

                    return Response::text(json_encode([
                        'status' => 'success',
                        'message' => "Penyesuaian stok ({$type}) untuk {$ing->name} berhasil dicatat",
                        'difference' => $movement->qty_change.' '.$ing->base_unit,
                        'new_current_stock' => $ing->current_stock.' '.$ing->base_unit,
                    ], JSON_PRETTY_PRINT));

                case 'movements':
                case 'history':
                    $movements = StockMovement::where('ingredient_id', $ingredientId)
                        ->with('user')
                        ->latest('created_at')
                        ->limit(20)
                        ->get()
                        ->map(fn ($m) => [
                            'id' => $m->id,
                            'date' => $m->created_at->format('Y-m-d H:i:s'),
                            'type' => $m->type,
                            'qty_change' => ($m->qty_change > 0 ? '+' : '').$m->qty_change.' '.$ing->base_unit,
                            'stock_after' => $m->stock_after.' '.$ing->base_unit,
                            'reason' => $m->reason ?: '-',
                            'recorded_by' => $m->user?->name ?? 'System',
                        ]);

                    return Response::text(json_encode([
                        'status' => 'success',
                        'ingredient_name' => $ing->name,
                        'current_stock' => $ing->current_stock.' '.$ing->base_unit,
                        'history' => $movements,
                    ], JSON_PRETTY_PRINT));

                default:
                    return Response::text(json_encode(['status' => 'error', 'message' => "Aksi '{$action}' tidak dikenal. Pilihan: purchase, adjustment, history"]));
            }
        } catch (\Throwable $e) {
            return Response::text(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->description("Aksi: 'purchase' (catat belanja bahan), 'adjustment' (penyesuaian stok opname / waste), 'history' (kartu stok)")->required(),
            'ingredient_id' => $schema->integer()->description('ID Bahan Baku')->required(),
            'qty_purchase_unit' => $schema->number()->description('Jumlah belanja dalam satuan beli (misal 5 kg)'),
            'total_cost' => $schema->integer()->description('Total uang yang dibayarkan untuk belanja (Rp)'),
            'purchased_at' => $schema->string()->description('Tanggal belanja (YYYY-MM-DD, default hari ini)'),
            'actual_stock' => $schema->number()->description('Jumlah stok fisik hasil opname di lapangan (untuk action adjustment)'),
            'type' => $schema->string()->description("Tipe penyesuaian: 'opname' atau 'waste'"),
            'reason' => $schema->string()->description('Alasan penyesuaian stok (wajib untuk adjustment)'),
            'note' => $schema->string()->description('Catatan opsional belanja bahan'),
        ];
    }
}
