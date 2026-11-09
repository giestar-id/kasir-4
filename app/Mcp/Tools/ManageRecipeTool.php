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

        // Support bulk recipe setting
        if ($action === 'bulk_set' || ($request->has('recipes') && is_array($request->get('recipes')))) {
            $recipes = $request->get('recipes') ?? $request->get('items');
            return $this->handleBulkSet($recipes);
        }

        $menuId = $request->get('menu_id') ?? $request->get('id');

        if (! $menuId && ($request->has('menu_name') || $request->has('name') || $request->has('nama'))) {
            $search = $request->get('menu_name') ?? $request->get('name') ?? $request->get('nama');
            $menuId = Menu::where('name', 'like', "%{$search}%")->value('id');
        } elseif ($menuId && ! is_numeric($menuId)) {
            $menuId = Menu::where('name', 'like', "%{$menuId}%")->value('id');
        }

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
                                $ingId = $item['ingredient_id'] ?? $item['id'] ?? null;
                                if (! $ingId && (! empty($item['ingredient_name']) || ! empty($item['name']) || ! empty($item['nama']) || ! empty($item['ingredient']))) {
                                    $searchIng = $item['ingredient_name'] ?? $item['name'] ?? $item['nama'] ?? $item['ingredient'];
                                    $ingId = \App\Models\Ingredient::where('name', 'like', "%{$searchIng}%")->value('id');
                                }
                                if ($ingId && $qty > 0) {
                                    Recipe::create([
                                        'menu_id' => $menu->id,
                                        'ingredient_id' => (int) $ingId,
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
                    return Response::text(json_encode(['status' => 'error', 'message' => "Aksi '{$action}' tidak dikenal. Pilihan: get, set, bulk_set"]));
            }
        } catch (\Throwable $e) {
            return Response::text(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    protected function handleBulkSet(?array $recipes): Response
    {
        if (empty($recipes)) {
            return Response::text(json_encode(['status' => 'error', 'message' => 'Parameter recipes (array) wajib diisi untuk bulk_set']));
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();
            $configured = 0;

            foreach ($recipes as $item) {
                $menuId = $item['menu_id'] ?? $item['id'] ?? null;
                if (! $menuId && (! empty($item['menu']) || ! empty($item['name']) || ! empty($item['nama']))) {
                    $search = $item['menu'] ?? $item['name'] ?? $item['nama'];
                    $menuId = Menu::where('name', 'like', "%{$search}%")->value('id');
                }

                if (! $menuId) {
                    continue;
                }

                $targetMenu = Menu::find($menuId);
                if (! $targetMenu) {
                    continue;
                }

                // Process ingredients
                $ingredientsInput = $item['ingredients'] ?? $item['recipe'] ?? null;
                if (! empty($ingredientsInput) && is_array($ingredientsInput)) {
                    $targetMenu->recipes()->delete();
                    foreach ($ingredientsInput as $ing) {
                        $qty = (float) ($ing['qty_per_portion'] ?? $ing['qty'] ?? 0);
                        $ingId = $ing['ingredient_id'] ?? $ing['id'] ?? null;
                        if (! $ingId && (! empty($ing['name']) || ! empty($ing['ingredient']) || ! empty($ing['nama']))) {
                            $searchIng = $ing['name'] ?? $ing['ingredient'] ?? $ing['nama'];
                            $ingId = \App\Models\Ingredient::where('name', 'like', "%{$searchIng}%")->value('id');
                        }
                        if ($ingId && $qty > 0) {
                            Recipe::create([
                                'menu_id' => $targetMenu->id,
                                'ingredient_id' => (int) $ingId,
                                'qty_per_portion' => $qty,
                            ]);
                        }
                    }
                }

                // Process extra costs
                $extraCostsInput = $item['extra_costs'] ?? null;
                if (! empty($extraCostsInput) && is_array($extraCostsInput)) {
                    $targetMenu->extraCosts()->delete();
                    foreach ($extraCostsInput as $ec) {
                        $label = trim($ec['label'] ?? $ec['name'] ?? '');
                        $amount = (int) ($ec['amount'] ?? 0);
                        if ($label && $amount > 0) {
                            MenuExtraCost::create([
                                'menu_id' => $targetMenu->id,
                                'label' => $label,
                                'amount' => $amount,
                            ]);
                        }
                    }
                }

                $configured++;
            }

            \Illuminate\Support\Facades\DB::commit();

            return Response::text(json_encode([
                'status' => 'success',
                'message' => "Berhasil mengatur resep & HPP untuk {$configured} menu sekaligus secara massal",
                'configured_count' => $configured,
                'total_submitted' => count($recipes),
            ], JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return Response::text(json_encode(['status' => 'error', 'message' => 'Gagal bulk set resep: ' . $e->getMessage()]));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->description("Aksi: 'get' (lihat resep & kalkulasi HPP), 'set' (atur resep 1 menu), 'bulk_set' (atur resep banyak menu sekaligus)")->required(),
            'menu_id' => $schema->integer()->description('ID Menu (untuk get dan set tunggal)'),
            'ingredients' => $schema->array()->description('Daftar bahan per porsi untuk 1 menu: [{"name": "Biji Kopi", "qty": 18}]'),
            'extra_costs' => $schema->array()->description('Daftar biaya ekstra kemasan untuk 1 menu: [{"label": "Cup Box", "amount": 1500}]'),
            'recipes' => $schema->array()->description('Array of objects untuk bulk_set banyak menu sekaligus: [{"menu": "Kopi Susu", "ingredients": [{"name": "Biji Kopi", "qty": 18}], "extra_costs": [{"label": "Cup", "amount": 1000}]}, ...]'),
        ];
    }
}
