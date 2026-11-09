<?php

namespace App\Mcp\Tools;

use App\Models\Menu;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('CRUD Menu Kuliner: Melihat daftar menu, detail menu, menambah menu baru, mengedit menu, atau menghapus/menonaktifkan menu.')]
class ManageMenuTool extends Tool
{
    public function handle(Request $request): Response
    {
        $action = $request->get('action', 'list');
        $id = $request->get('id');

        try {
            switch ($action) {
                case 'list':
                    $query = Menu::with(['category', 'aliases', 'extraCosts']);
                    if ($categoryId = $request->get('category_id')) {
                        $query->where('category_id', $categoryId);
                    }
                    if ($search = $request->get('search')) {
                        $query->where('name', 'like', "%{$search}%");
                    }
                    $menus = $query->get()->map(fn ($m) => [
                        'id' => $m->id,
                        'name' => $m->name,
                        'category' => $m->category?->name,
                        'price' => $m->price,
                        'formatted_price' => 'Rp'.number_format($m->price, 0, ',', '.'),
                        'is_active' => $m->is_active,
                        'hpp' => $m->total_cost,
                        'formatted_hpp' => 'Rp'.number_format($m->total_cost, 0, ',', '.'),
                        'gross_margin' => $m->gross_margin,
                        'gross_margin_pct' => $m->gross_margin_percentage.'%',
                        'is_recipe_complete' => $m->isRecipeComplete(),
                    ]);

                    return Response::text(json_encode(['status' => 'success', 'count' => $menus->count(), 'menus' => $menus], JSON_PRETTY_PRINT));

                case 'get':
                    if (! $id) {
                        return Response::error(json_encode(['status' => 'error', 'message' => 'Parameter id wajib diisi']));
                    }
                    $menu = Menu::with(['category', 'recipes.ingredient', 'extraCosts', 'aliases'])->find($id);
                    if (! $menu) {
                        return Response::error(json_encode(['status' => 'error', 'message' => "Menu #{$id} tidak ditemukan"]));
                    }

                    return Response::text(json_encode(['status' => 'success', 'menu' => $menu], JSON_PRETTY_PRINT));

                case 'create':
                    // Auto-detect bulk if menus/items array is passed
                    if ($request->has('menus') || ($request->has('items') && is_array($request->get('items')))) {
                        // Forward to bulk_create
                        $items = $request->get('menus') ?? $request->get('items');
                        return $this->handleBulkCreate($items, (bool) $request->get('update_existing', true));
                    }

                    $name = $request->get('name') ?? $request->get('nama');
                    $price = $request->get('price') ?? $request->get('selling_price') ?? $request->get('harga');
                    $categoryId = $request->get('category_id') ?? $request->get('kategori_id') ?? $request->get('category') ?? $request->get('kategori');

                    if (is_string($price)) {
                        $price = (int) preg_replace('/[^0-9]/', '', $price);
                    }

                    if ($categoryId && ! is_numeric($categoryId)) {
                        $cat = \App\Models\Category::where('name', 'like', "%{$categoryId}%")->first();
                        $categoryId = $cat?->id;
                    }

                    if (! $categoryId) {
                        $categoryId = \App\Models\Category::first()?->id ?? 1;
                    }

                    if (! $name || ! $price) {
                        return Response::error(json_encode(['status' => 'error', 'message' => 'name dan price wajib diisi']));
                    }

                    $menu = Menu::create([
                        'name' => $name,
                        'price' => (int) $price,
                        'category_id' => (int) $categoryId,
                        'is_active' => $request->get('is_active', true),
                        'note' => $request->get('note') ?? $request->get('catatan'),
                    ]);

                    return Response::text(json_encode(['status' => 'success', 'message' => "Menu {$menu->name} berhasil dibuat", 'menu' => $menu], JSON_PRETTY_PRINT));

                case 'bulk_create':
                case 'batch_create':
                    $items = $request->get('menus') ?? $request->get('items') ?? $request->get('data');
                    if (empty($items) || ! is_array($items)) {
                        return Response::error(json_encode(['status' => 'error', 'message' => 'Parameter menus (array of objects) wajib diisi untuk aksi bulk_create']));
                    }
                    return $this->handleBulkCreate($items, (bool) $request->get('update_existing', true));

                case 'update':
                    if (! $id) {
                        return Response::text(json_encode(['status' => 'error', 'message' => 'Parameter id wajib diisi']));
                    }
                    $menu = Menu::find($id);
                    if (! $menu) {
                        return Response::error(json_encode(['status' => 'error', 'message' => "Menu #{$id} tidak ditemukan"]));
                    }

                    $data = [];
                    if ($request->has('name') || $request->has('nama')) {
                        $data['name'] = $request->get('name') ?? $request->get('nama');
                    }
                    if ($request->has('price') || $request->has('selling_price') || $request->has('harga')) {
                        $p = $request->get('price') ?? $request->get('selling_price') ?? $request->get('harga');
                        $data['price'] = is_string($p) ? (int) preg_replace('/[^0-9]/', '', $p) : (int) $p;
                    }
                    if ($request->has('category_id') || $request->has('category')) {
                        $c = $request->get('category_id') ?? $request->get('category');
                        if (! is_numeric($c)) {
                            $cat = \App\Models\Category::where('name', 'like', "%{$c}%")->first();
                            $c = $cat?->id;
                        }
                        if ($c) {
                            $data['category_id'] = (int) $c;
                        }
                    }
                    if ($request->has('is_active')) {
                        $data['is_active'] = (bool) $request->get('is_active');
                    }
                    if ($request->has('note') || $request->has('catatan')) {
                        $data['note'] = $request->get('note') ?? $request->get('catatan');
                    }

                    $menu->update($data);

                    return Response::text(json_encode(['status' => 'success', 'message' => "Menu #{$id} berhasil diperbarui", 'menu' => $menu], JSON_PRETTY_PRINT));

                case 'delete':
                    if (! $id) {
                        return Response::error(json_encode(['status' => 'error', 'message' => 'Parameter id wajib diisi']));
                    }
                    $menu = Menu::find($id);
                    if (! $menu) {
                        return Response::error(json_encode(['status' => 'error', 'message' => "Menu #{$id} tidak ditemukan"]));
                    }

                    // If menu has sales, deactivate instead of delete
                    if ($menu->saleItems()->exists()) {
                        $menu->update(['is_active' => false]);

                        return Response::text(json_encode(['status' => 'success', 'message' => "Menu #{$id} memiliki riwayat transaksi, status diubah menjadi nonaktif"]));
                    }

                    $menu->delete();

                    return Response::text(json_encode(['status' => 'success', 'message' => "Menu #{$id} berhasil dihapus permanen"]));

                default:
                    return Response::error(json_encode(['status' => 'error', 'message' => "Aksi '{$action}' tidak dikenal. Pilihan: list, get, create, bulk_create, update, delete"]));
            }
        } catch (\Throwable $e) {
            return Response::error(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    protected function handleBulkCreate(array $items, bool $updateExisting): Response
    {
        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $created = 0;
            $updated = 0;
            $categoryCache = [];

            foreach ($items as $it) {
                $name = trim($it['name'] ?? $it['nama'] ?? '');
                if (! $name) {
                    continue;
                }

                $rawPrice = $it['price'] ?? $it['harga'] ?? $it['selling_price'] ?? 0;
                $price = is_string($rawPrice) ? (int) preg_replace('/[^0-9]/', '', $rawPrice) : (int) $rawPrice;

                // Resolve category
                $c = $it['category_id'] ?? $it['category'] ?? $it['kategori'] ?? null;
                $catId = null;
                if ($c) {
                    if (is_numeric($c)) {
                        $catId = (int) $c;
                    } else {
                        $lowerC = strtolower(trim($c));
                        if (! isset($categoryCache[$lowerC])) {
                            $found = \App\Models\Category::whereRaw('LOWER(name) = ?', [$lowerC])->first();
                            if ($found) {
                                $categoryCache[$lowerC] = $found->id;
                            } else {
                                $newCat = \App\Models\Category::create(['name' => trim($c), 'sort_order' => (\App\Models\Category::max('sort_order') ?? 0) + 1]);
                                $categoryCache[$lowerC] = $newCat->id;
                            }
                        }
                        $catId = $categoryCache[$lowerC];
                    }
                }

                if (! $catId) {
                    $catId = \App\Models\Category::first()?->id ?? 1;
                }

                $isActive = isset($it['is_active']) ? (bool) $it['is_active'] : true;
                $note = $it['note'] ?? $it['catatan'] ?? null;

                $existing = Menu::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
                if ($existing) {
                    if ($updateExisting) {
                        $existing->update([
                            'price' => $price > 0 ? $price : $existing->price,
                            'category_id' => $catId,
                            'is_active' => $isActive,
                            'note' => $note ?? $existing->note,
                        ]);
                        $updated++;
                    }
                } else {
                    Menu::create([
                        'name' => $name,
                        'price' => $price,
                        'category_id' => $catId,
                        'is_active' => $isActive,
                        'note' => $note,
                    ]);
                    $created++;
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            return Response::text(json_encode([
                'status' => 'success',
                'message' => "Bulk import berhasil: {$created} menu baru dibuat, {$updated} menu diperbarui",
                'created_count' => $created,
                'updated_count' => $updated,
                'total_processed' => count($items),
            ], JSON_PRETTY_PRINT));

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return Response::error(json_encode(['status' => 'error', 'message' => 'Gagal bulk create menu: ' . $e->getMessage()]));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->description("Aksi yang ingin dilakukan: 'list', 'get', 'create', 'bulk_create', 'update', 'delete'")->required(),
            'id' => $schema->integer()->description('ID Menu (wajib untuk get, update, delete)'),
            'name' => $schema->string()->description('Nama menu kuliner (untuk create/update tunggal)'),
            'price' => $schema->integer()->description('Harga jual dalam Rupiah (integer)'),
            'category_id' => $schema->integer()->description('ID Kategori menu'),
            'menus' => $schema->array()->description('Array of objects menu untuk bulk_create: [{"name": "Kopi Susu", "price": 18000, "category": "Minuman"}, ...]'),
            'update_existing' => $schema->boolean()->description('Untuk bulk_create: jika menu sudah ada, perbarui datanya bukan error (default: true)'),
            'is_active' => $schema->boolean()->description('Status aktif menu'),
            'search' => $schema->string()->description('Pencarian kata kunci nama menu untuk action list'),
            'note' => $schema->string()->description('Catatan menu (opsional)'),
        ];
    }
}
