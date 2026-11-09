<?php

namespace App\Mcp\Tools;

use App\Models\Menu;
use App\Models\MenuExtraCost;
use App\Models\Recipe;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Kelola Resep HPP & Biaya Tambahan: Melihat atau mengatur bahan baku per porsi dan biaya ekstra (kemasan) suatu menu.')]
class ManageRecipeTool extends Tool
{
    public function handle(Request $request): Response
    {
        $action = $request->get('action', 'get');
        $menuId = $request->get('menu_id');

        if (! $menuId) {
            return Response::text(json_encode(['status' => 'error', 'message' => 'Parameter menu_id wajib diisi']));
        }

        $menu = Menu::with(['recipes.ingredient', 'extraCosts'])->find($menuId);
        if (! $menu) {
            return Response::text(json_encode(['status' => 'error', 'message' => "Menu #{$menuId} tidak ditemukan"]));
        }

        try {
            switch ($action) {
                case 'get':
                    $marginData = $menu->margin;
                    $ingredientsList = $menu->recipes->map(fn ($r) => [
                        'ingredient_id' => $r->ingredient_id,
                        'name' => $r->ingredient?->name,
                        'qty_per_portion' => (float) $r->qty_per_portion,
                        'base_unit' => $r->ingredient?->base_unit,
                        'unit_cost' => $r->ingredient?->avg_cost_per_base,
                        'subtotal_cost' => (int) round($r->qty_per_portion * ($r->ingredient?->avg_cost_per_base ?? 0)),
                    ]);

                    $extraCostsList = $menu->extraCosts->map(fn ($ec) => [
                        'id' => $ec->id,
                        'label' => $ec->label,
                        'amount' => (int) $ec->amount,
                    ]);

                    return Response::text(json_encode([
                        'status' => 'success',
                        'menu_id' => $menu->id,
                        'menu_name' => $menu->name,
                        'selling_price' => $menu->price,
                        'total_hpp' => $marginData['hpp'],
                        'gross_margin' => $marginData['margin_rp'],
                        'gross_margin_pct' => $marginData['margin_pct'].'%',
                        'ingredients' => $ingredientsList,
                        'extra_costs' => $extraCostsList,
                    ], JSON_PRETTY_PRINT));

                case 'set':
                    // ingredients: array of {ingredient_id, qty_per_portion}
                    if ($request->has('ingredients')) {
                        $ingredientsInput = $request->get('ingredients');
                        if (is_array($ingredientsInput)) {
                            // Delete existing and reinsert
                            $menu->recipes()->delete();
                            foreach ($ingredientsInput as $item) {
                                $qty = (float) ($item['qty_per_portion'] ?? $item['amount_needed'] ?? $item['qty'] ?? 0);
                                if (! empty($item['ingredient_id']) && $qty > 0) {
                                    Recipe::create([
                                        'menu_id' => $menu->id,
                                        'ingredient_id' => (int) $item['ingredient_id'],
                                        'qty_per_portion' => $qty,
                                    ]);
                                }
                            }
                        }
                    }

                    // extra_costs: array of {label, amount}
                    if ($request->has('extra_costs')) {
                        $extraCostsInput = $request->get('extra_costs');
                        if (is_array($extraCostsInput)) {
                            $menu->extraCosts()->delete();
                            foreach ($extraCostsInput as $ec) {
                                if (! empty($ec['label']) && isset($ec['amount'])) {
                                    MenuExtraCost::create([
                                        'menu_id' => $menu->id,
                                        'label' => $ec['label'],
                                        'amount' => (int) $ec['amount'],
                                    ]);
                                }
                            }
                        }
                    }

                    $menu->refresh();

                    $marginData = $menu->margin;

                    return Response::text(json_encode([
                        'status' => 'success',
                        'message' => "Resep & HPP untuk menu {$menu->name} berhasil diperbarui",
                        'new_total_hpp' => $marginData['hpp'],
                        'gross_margin' => $marginData['margin_rp'],
                        'gross_margin_pct' => $marginData['margin_pct'].'%',
                    ], JSON_PRETTY_PRINT));

                default:
                    return Response::text(json_encode(['status' => 'error', 'message' => "Aksi '{$action}' tidak dikenal. Pilihan: get, set"]));
            }
        } catch (\Throwable $e) {
            return Response::text(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->description("Aksi: 'get' (lihat resep & kalkulasi HPP) atau 'set' (atur resep baru)")->required(),
            'menu_id' => $schema->integer()->description('ID Menu yang akan dikelola resepnya')->required(),
            'ingredients' => $schema->array()->description('Daftar bahan per porsi, array dari object: [{"ingredient_id": 1, "qty_per_portion": 150}]'),
            'extra_costs' => $schema->array()->description('Daftar biaya ekstra kemasan, array dari object: [{"label": "Kotak Box", "amount": 1500}]'),
        ];
    }
}
