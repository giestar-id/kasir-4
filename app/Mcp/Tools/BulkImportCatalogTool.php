<?php

namespace App\Mcp\Tools;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\MenuExtraCost;
use App\Models\Recipe;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Import Massal Master Data Outlet (Batch/Bulk): Mengimpor sekaligus puluhan hingga ratusan Kategori, Bahan Baku, Menu, dan Resep HPP dalam SATU kali panggilan API atomik. Sangat cepat, efisien, dan mencegah timeout saat setup awal atau penambahan data massal.')]
class BulkImportCatalogTool extends Tool
{
    public function handle(Request $request): Response
    {
        try {
            $categoriesInput = $request->get('categories', []);
            $ingredientsInput = $request->get('ingredients', []);
            $menusInput = $request->get('menus', $request->get('items', []));
            $updateExisting = (bool) $request->get('update_existing', true);

            if (empty($categoriesInput) && empty($ingredientsInput) && empty($menusInput)) {
                return Response::error(json_encode([
                    'status' => 'error',
                    'message' => 'Harus menyertakan minimal salah satu dari parameter: categories, ingredients, atau menus (array of items)',
                ]));
            }

            $stats = [
                'categories_created' => 0,
                'categories_existing' => 0,
                'ingredients_created' => 0,
                'ingredients_updated' => 0,
                'menus_created' => 0,
                'menus_updated' => 0,
                'recipes_configured' => 0,
                'extra_costs_configured' => 0,
            ];

            $categoryMap = []; // lowercase name => id
            $ingredientMap = []; // lowercase name => id

            DB::beginTransaction();

            // 1. Process Categories
            if (! empty($categoriesInput) && is_array($categoriesInput)) {
                $maxOrder = Category::max('sort_order') ?? 0;
                foreach ($categoriesInput as $catItem) {
                    $name = is_string($catItem) ? trim($catItem) : trim($catItem['name'] ?? $catItem['nama'] ?? '');
                    if (! $name) {
                        continue;
                    }

                    $order = is_array($catItem) && isset($catItem['sort_order']) ? (int) $catItem['sort_order'] : ++$maxOrder;
                    $existing = Category::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
                    if ($existing) {
                        $categoryMap[strtolower($name)] = $existing->id;
                        $stats['categories_existing']++;
                    } else {
                        $newCat = Category::create([
                            'name' => $name,
                            'sort_order' => $order,
                        ]);
                        $categoryMap[strtolower($name)] = $newCat->id;
                        $stats['categories_created']++;
                    }
                }
            }

            // Load existing categories to map
            foreach (Category::all() as $c) {
                $categoryMap[strtolower($c->name)] = $c->id;
            }

            // 2. Process Ingredients
            if (! empty($ingredientsInput) && is_array($ingredientsInput)) {
                foreach ($ingredientsInput as $ingItem) {
                    $name = trim($ingItem['name'] ?? $ingItem['nama'] ?? '');
                    if (! $name) {
                        continue;
                    }

                    $baseUnit = $ingItem['base_unit'] ?? $ingItem['satuan_dasar'] ?? 'gram';
                    $purchaseUnit = $ingItem['purchase_unit'] ?? $ingItem['satuan_beli'] ?? 'kg';
                    $unitFactor = (float) ($ingItem['unit_factor'] ?? $ingItem['faktor_konversi'] ?? 1000);
                    $minStock = (float) ($ingItem['min_stock'] ?? $ingItem['stok_minimal'] ?? 0);
                    $initialStock = (float) ($ingItem['initial_stock'] ?? $ingItem['stok_awal'] ?? $ingItem['current_stock'] ?? $ingItem['stok'] ?? 0);
                    $avgCost = (int) ($ingItem['initial_avg_cost'] ?? $ingItem['avg_cost'] ?? $ingItem['harga_satuan'] ?? $ingItem['hpp'] ?? 0);

                    $existing = Ingredient::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
                    if ($existing) {
                        if ($updateExisting) {
                            $updateData = [];
                            if (isset($ingItem['base_unit'])) $updateData['base_unit'] = $baseUnit;
                            if (isset($ingItem['purchase_unit'])) $updateData['purchase_unit'] = $purchaseUnit;
                            if (isset($ingItem['unit_factor'])) $updateData['unit_factor'] = $unitFactor;
                            if (isset($ingItem['min_stock'])) $updateData['min_stock'] = $minStock;
                            if ($initialStock > 0 && $existing->current_stock == 0) $updateData['current_stock'] = $initialStock;
                            if ($avgCost > 0) $updateData['avg_cost_per_base'] = $avgCost;

                            if (! empty($updateData)) {
                                $existing->update($updateData);
                            }
                            $stats['ingredients_updated']++;
                        }
                        $ingredientMap[strtolower($name)] = $existing->id;
                    } else {
                        $newIng = Ingredient::create([
                            'name' => $name,
                            'base_unit' => $baseUnit,
                            'purchase_unit' => $purchaseUnit,
                            'unit_factor' => $unitFactor,
                            'min_stock' => $minStock,
                            'current_stock' => $initialStock,
                            'avg_cost_per_base' => $avgCost,
                        ]);
                        $ingredientMap[strtolower($name)] = $newIng->id;
                        $stats['ingredients_created']++;
                    }
                }
            }

            // Load existing ingredients to map
            foreach (Ingredient::all() as $ing) {
                $ingredientMap[strtolower($ing->name)] = $ing->id;
            }

            // 3. Process Menus
            if (! empty($menusInput) && is_array($menusInput)) {
                $defaultCategoryId = Category::first()?->id ?? 1;

                foreach ($menusInput as $mItem) {
                    $name = trim($mItem['name'] ?? $mItem['nama'] ?? '');
                    if (! $name) {
                        continue;
                    }

                    $rawPrice = $mItem['price'] ?? $mItem['harga'] ?? $mItem['selling_price'] ?? 0;
                    $price = is_string($rawPrice) ? (int) preg_replace('/[^0-9]/', '', $rawPrice) : (int) $rawPrice;

                    // Resolve category
                    $categoryId = null;
                    $catSpec = $mItem['category'] ?? $mItem['kategori'] ?? $mItem['category_id'] ?? null;
                    if ($catSpec) {
                        if (is_numeric($catSpec)) {
                            $categoryId = (int) $catSpec;
                        } else {
                            $lowerCat = strtolower(trim($catSpec));
                            if (isset($categoryMap[$lowerCat])) {
                                $categoryId = $categoryMap[$lowerCat];
                            } else {
                                // Auto-create category if not exists
                                $newC = Category::create(['name' => trim($catSpec), 'sort_order' => (Category::max('sort_order') ?? 0) + 1]);
                                $categoryMap[$lowerCat] = $newC->id;
                                $categoryId = $newC->id;
                                $stats['categories_created']++;
                            }
                        }
                    }

                    if (! $categoryId) {
                        $categoryId = $defaultCategoryId;
                    }

                    $isActive = isset($mItem['is_active']) ? (bool) $mItem['is_active'] : true;
                    $note = $mItem['note'] ?? $mItem['catatan'] ?? null;

                    $existingMenu = Menu::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
                    $targetMenu = null;

                    if ($existingMenu) {
                        if ($updateExisting) {
                            $existingMenu->update([
                                'price' => $price > 0 ? $price : $existingMenu->price,
                                'category_id' => $categoryId,
                                'is_active' => $isActive,
                                'note' => $note ?? $existingMenu->note,
                            ]);
                            $stats['menus_updated']++;
                        }
                        $targetMenu = $existingMenu;
                    } else {
                        $targetMenu = Menu::create([
                            'name' => $name,
                            'price' => $price,
                            'category_id' => $categoryId,
                            'is_active' => $isActive,
                            'note' => $note,
                        ]);
                        $stats['menus_created']++;
                    }

                    // Process recipes if supplied
                    $recipeItems = $mItem['recipe'] ?? $mItem['ingredients'] ?? $mItem['resep'] ?? null;
                    if (! empty($recipeItems) && is_array($recipeItems)) {
                        $targetMenu->recipes()->delete();
                        foreach ($recipeItems as $rItem) {
                            $qty = (float) ($rItem['qty'] ?? $rItem['qty_per_portion'] ?? $rItem['amount'] ?? 0);
                            $ingId = $rItem['ingredient_id'] ?? $rItem['id'] ?? null;

                            if (! $ingId) {
                                $ingName = strtolower(trim($rItem['name'] ?? $rItem['ingredient'] ?? $rItem['nama'] ?? ''));
                                if ($ingName && isset($ingredientMap[$ingName])) {
                                    $ingId = $ingredientMap[$ingName];
                                } elseif ($ingName) {
                                    // Auto-create missing ingredient on the fly!
                                    $createdIng = Ingredient::create([
                                        'name' => trim($rItem['name'] ?? $rItem['ingredient'] ?? $rItem['nama']),
                                        'base_unit' => $rItem['base_unit'] ?? 'gram',
                                        'purchase_unit' => $rItem['purchase_unit'] ?? 'kg',
                                        'unit_factor' => 1000,
                                        'current_stock' => 0,
                                        'avg_cost_per_base' => (int) ($rItem['unit_cost'] ?? $rItem['avg_cost'] ?? 0),
                                    ]);
                                    $ingredientMap[$ingName] = $createdIng->id;
                                    $ingId = $createdIng->id;
                                    $stats['ingredients_created']++;
                                }
                            }

                            if ($ingId && $qty > 0) {
                                Recipe::create([
                                    'menu_id' => $targetMenu->id,
                                    'ingredient_id' => (int) $ingId,
                                    'qty_per_portion' => $qty,
                                ]);
                                $stats['recipes_configured']++;
                            }
                        }
                    }

                    // Process extra packaging costs if supplied
                    $extraCosts = $mItem['extra_costs'] ?? $mItem['biaya_ekstra'] ?? null;
                    if (! empty($extraCosts) && is_array($extraCosts)) {
                        $targetMenu->extraCosts()->delete();
                        foreach ($extraCosts as $ec) {
                            $ecLabel = trim($ec['label'] ?? $ec['name'] ?? '');
                            $ecAmount = (int) ($ec['amount'] ?? $ec['nominal'] ?? 0);
                            if ($ecLabel && $ecAmount > 0) {
                                MenuExtraCost::create([
                                    'menu_id' => $targetMenu->id,
                                    'label' => $ecLabel,
                                    'amount' => $ecAmount,
                                ]);
                                $stats['extra_costs_configured']++;
                            }
                        }
                    }
                }
            }

            DB::commit();

            return Response::text(json_encode([
                'status' => 'success',
                'message' => 'Import massal katalog berhasil dieksekusi secara atomik',
                'summary' => $stats,
                'total_records_impacted' => array_sum($stats),
            ], JSON_PRETTY_PRINT));

        } catch (\Throwable $e) {
            DB::rollBack();
            return Response::error(json_encode([
                'status' => 'error',
                'message' => 'Gagal melakukan import massal: ' . $e->getMessage(),
            ]));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'categories' => $schema->array()->description('Daftar kategori menu yang ingin dibuat massal. Bisa berupa array of string ["Kopi", "Makanan"] atau array of object [{"name": "Kopi", "sort_order": 1}]'),
            'ingredients' => $schema->array()->description('Daftar bahan baku yang ingin dibuat massal. Contoh: [{"name": "Biji Kopi", "base_unit": "gram", "purchase_unit": "kg", "unit_factor": 1000, "initial_stock": 5000, "avg_cost": 250}]'),
            'menus' => $schema->array()->description('Daftar menu beserta resep & kemasannya yang ingin diimpor sekaligus. Contoh: [{"name": "Kopi Susu", "price": 20000, "category": "Kopi", "recipe": [{"name": "Biji Kopi", "qty": 18}], "extra_costs": [{"label": "Cup", "amount": 1000}]}]'),
            'update_existing' => $schema->boolean()->description('Jika true (default), jika nama menu/bahan sudah ada di database maka datanya akan diperbarui bukan error'),
        ];
    }
}

