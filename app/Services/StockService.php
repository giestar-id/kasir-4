<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Deduct ingredient stocks for a completed sale atomically.
     * Prevents deadlocks by sorting ingredient IDs and eliminates N+1 queries.
     */
    public function deductForSale(Sale $sale): void
    {
        $sale->loadMissing('items.menu.recipes.ingredient');

        // 1. Group deductions by ingredient_id to avoid redundant queries and repeated updates
        $deductions = [];
        foreach ($sale->items as $item) {
            $menu = $item->menu;
            if (! $menu) {
                continue;
            }

            foreach ($menu->recipes as $recipe) {
                if (! $recipe->ingredient) {
                    continue;
                }

                $qtyNeeded = (float) $recipe->qty_per_portion * (int) $item->qty;
                $ingId = $recipe->ingredient_id;

                if (! isset($deductions[$ingId])) {
                    $deductions[$ingId] = [
                        'total_qty' => 0.0,
                    ];
                }
                $deductions[$ingId]['total_qty'] += $qtyNeeded;
            }
        }

        if (empty($deductions)) {
            return;
        }

        // 2. Deadlock prevention: sort ingredient IDs ascending before acquiring pessimistic locks
        ksort($deductions);
        $ingredientIds = array_keys($deductions);

        $ingredients = Ingredient::whereIn('id', $ingredientIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        // 3. Apply updates & record stock movements
        foreach ($deductions as $ingId => $data) {
            $ingredient = $ingredients->get($ingId);
            if (! $ingredient) {
                continue;
            }

            $ingredient->current_stock = (float) $ingredient->current_stock - $data['total_qty'];
            $ingredient->save();

            StockMovement::create([
                'ingredient_id' => $ingredient->id,
                'type' => StockMovement::TYPE_SALE_USAGE,
                'qty_change' => -$data['total_qty'],
                'unit_cost' => $ingredient->avg_cost_per_base,
                'ref_type' => 'sale',
                'ref_id' => $sale->id,
                'reason' => 'Penjualan #'.$sale->number,
                'user_id' => $sale->user_id,
            ]);
        }
    }

    /**
     * Return ingredient stocks when a sale is voided.
     * Prevents deadlocks by sorting ingredient IDs and eliminates N+1 queries.
     */
    public function returnForVoid(Sale $sale, ?string $reason, ?int $userId = null): void
    {
        $sale->loadMissing('items.menu.recipes.ingredient');

        $returns = [];
        foreach ($sale->items as $item) {
            $menu = $item->menu;
            if (! $menu) {
                continue;
            }

            foreach ($menu->recipes as $recipe) {
                if (! $recipe->ingredient) {
                    continue;
                }

                $qtyReturned = (float) $recipe->qty_per_portion * (int) $item->qty;
                $ingId = $recipe->ingredient_id;

                if (! isset($returns[$ingId])) {
                    $returns[$ingId] = [
                        'total_qty' => 0.0,
                    ];
                }
                $returns[$ingId]['total_qty'] += $qtyReturned;
            }
        }

        if (empty($returns)) {
            return;
        }

        // Deadlock prevention: sort ingredient IDs ascending
        ksort($returns);
        $ingredientIds = array_keys($returns);

        $ingredients = Ingredient::whereIn('id', $ingredientIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($returns as $ingId => $data) {
            $ingredient = $ingredients->get($ingId);
            if (! $ingredient) {
                continue;
            }

            $ingredient->current_stock = (float) $ingredient->current_stock + $data['total_qty'];
            $ingredient->save();

            StockMovement::create([
                'ingredient_id' => $ingredient->id,
                'type' => StockMovement::TYPE_VOID_RETURN,
                'qty_change' => $data['total_qty'],
                'unit_cost' => $ingredient->avg_cost_per_base,
                'ref_type' => 'sale_void',
                'ref_id' => $sale->id,
                'reason' => 'Void #'.$sale->number.($reason ? ': '.$reason : ''),
                'user_id' => $userId ?? $sale->user_id,
            ]);
        }
    }

    /**
     * Record a new ingredient purchase and recalculate weighted average cost.
     */
    public function recordPurchase(
        int $ingredientId,
        float $qtyPurchaseUnit,
        int $totalCost,
        string $purchasedAt,
        ?string $note = null,
        ?int $userId = null
    ): Purchase {
        return DB::transaction(function () use (
            $ingredientId,
            $qtyPurchaseUnit,
            $totalCost,
            $purchasedAt,
            $note,
            $userId
        ) {
            $ingredient = Ingredient::where('id', $ingredientId)->lockForUpdate()->firstOrFail();
            $qtyBase = $qtyPurchaseUnit * (float) $ingredient->unit_factor;

            $unitCostPerBase = $qtyBase > 0 ? (int) round($totalCost / $qtyBase) : 0;

            // Weighted average cost formula
            $currentStock = (float) $ingredient->current_stock;
            $oldAvgCost = (int) $ingredient->avg_cost_per_base;

            if ($currentStock > 0 && ($currentStock + $qtyBase) > 0) {
                $newAvgCost = (int) round((($currentStock * $oldAvgCost) + $totalCost) / ($currentStock + $qtyBase));
            } else {
                $newAvgCost = $unitCostPerBase;
            }

            $ingredient->current_stock = $currentStock + $qtyBase;
            $ingredient->avg_cost_per_base = $newAvgCost;
            $ingredient->save();

            $purchase = Purchase::create([
                'ingredient_id' => $ingredient->id,
                'qty_purchase_unit' => $qtyPurchaseUnit,
                'qty_base' => $qtyBase,
                'total_cost' => $totalCost,
                'unit_cost_per_base' => $unitCostPerBase,
                'purchased_at' => $purchasedAt,
                'note' => $note,
                'user_id' => $userId,
            ]);

            StockMovement::create([
                'ingredient_id' => $ingredient->id,
                'type' => StockMovement::TYPE_PURCHASE,
                'qty_change' => $qtyBase,
                'unit_cost' => $unitCostPerBase,
                'ref_type' => 'purchase',
                'ref_id' => $purchase->id,
                'reason' => 'Pembelian bahan: '.$qtyPurchaseUnit.' '.$ingredient->purchase_unit,
                'user_id' => $userId,
            ]);

            return $purchase;
        });
    }

    /**
     * Record physical stock opname adjustment or waste.
     */
    public function recordAdjustment(
        int $ingredientId,
        string $type,
        float $actualStock,
        string $reason,
        ?int $userId = null
    ): StockMovement {
        return DB::transaction(function () use ($ingredientId, $type, $actualStock, $reason, $userId) {
            $ingredient = Ingredient::where('id', $ingredientId)->lockForUpdate()->firstOrFail();

            $currentStock = (float) $ingredient->current_stock;
            $diff = $actualStock - $currentStock;

            $ingredient->current_stock = $actualStock;
            $ingredient->save();

            return StockMovement::create([
                'ingredient_id' => $ingredient->id,
                'type' => in_array($type, [StockMovement::TYPE_WASTE, StockMovement::TYPE_ADJUSTMENT])
                    ? $type
                    : StockMovement::TYPE_ADJUSTMENT,
                'qty_change' => $diff,
                'unit_cost' => $ingredient->avg_cost_per_base,
                'ref_type' => 'stock_opname',
                'ref_id' => null,
                'reason' => $reason,
                'user_id' => $userId,
            ]);
        });
    }

    /**
     * Verify that current stock strictly equals the sum of all movements.
     */
    public function verifyConsistency(int $ingredientId): bool
    {
        $ingredient = Ingredient::findOrFail($ingredientId);
        $sumMovements = (float) StockMovement::where('ingredient_id', $ingredientId)->sum('qty_change');

        return abs((float) $ingredient->current_stock - $sumMovements) < 0.001;
    }
}
