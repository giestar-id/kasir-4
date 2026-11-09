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
                    $name = $request->get('name');
                    $price = $request->get('price');
                    $categoryId = $request->get('category_id');

                    if (! $name || ! $price || ! $categoryId) {
                        return Response::error(json_encode(['status' => 'error', 'message' => 'name, price, dan category_id wajib diisi']));
                    }

                    $menu = Menu::create([
                        'name' => $name,
                        'price' => (int) $price,
                        'category_id' => (int) $categoryId,
                        'is_active' => $request->get('is_active', true),
                        'note' => $request->get('note'),
                    ]);

                    return Response::text(json_encode(['status' => 'success', 'message' => "Menu {$menu->name} berhasil dibuat", 'menu' => $menu], JSON_PRETTY_PRINT));

                case 'update':
                    if (! $id) {
                        return Response::text(json_encode(['status' => 'error', 'message' => 'Parameter id wajib diisi']));
                    }
                    $menu = Menu::find($id);
                    if (! $menu) {
                        return Response::error(json_encode(['status' => 'error', 'message' => "Menu #{$id} tidak ditemukan"]));
                    }

                    $data = [];
                    if ($request->has('name')) {
                        $data['name'] = $request->get('name');
                    }
                    if ($request->has('price')) {
                        $data['price'] = (int) $request->get('price');
                    }
                    if ($request->has('category_id')) {
                        $data['category_id'] = (int) $request->get('category_id');
                    }
                    if ($request->has('is_active')) {
                        $data['is_active'] = (bool) $request->get('is_active');
                    }
                    if ($request->has('note')) {
                        $data['note'] = $request->get('note');
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
                    return Response::error(json_encode(['status' => 'error', 'message' => "Aksi '{$action}' tidak dikenal. Pilihan: list, get, create, update, delete"]));
            }
        } catch (\Throwable $e) {
            return Response::error(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->description("Aksi yang ingin dilakukan: 'list', 'get', 'create', 'update', 'delete'")->required(),
            'id' => $schema->integer()->description('ID Menu (wajib untuk get, update, delete)'),
            'name' => $schema->string()->description('Nama menu kuliner'),
            'price' => $schema->integer()->description('Harga jual dalam Rupiah (integer)'),
            'category_id' => $schema->integer()->description('ID Kategori menu'),
            'is_active' => $schema->boolean()->description('Status aktif menu'),
            'search' => $schema->string()->description('Pencarian kata kunci nama menu untuk action list'),
            'note' => $schema->string()->description('Catatan menu (opsional)'),
        ];
    }
}
