<?php

namespace App\Mcp\Tools;

use App\Models\Category;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('CRUD Kategori Menu: Melihat daftar kategori, membuat kategori baru, mengedit, atau menghapus kategori.')]
class ManageCategoryTool extends Tool
{
    public function handle(Request $request): Response
    {
        $action = $request->get('action', 'list');
        $id = $request->get('id');

        try {
            switch ($action) {
                case 'list':
                    $categories = Category::withCount('menus')->orderBy('sort_order')->get();

                    return Response::text(json_encode(['status' => 'success', 'count' => $categories->count(), 'categories' => $categories], JSON_PRETTY_PRINT));

                case 'create':
                    if ($request->has('categories') || ($request->has('items') && is_array($request->get('items')))) {
                        $items = $request->get('categories') ?? $request->get('items');
                        return $this->handleBulkCreate($items);
                    }

                    $name = $request->get('name') ?? $request->get('nama');
                    if (! $name) {
                        return Response::text(json_encode(['status' => 'error', 'message' => 'Parameter name wajib diisi']));
                    }

                    $maxOrder = Category::max('sort_order') ?? 0;
                    $cat = Category::create([
                        'name' => $name,
                        'sort_order' => $request->get('sort_order', $maxOrder + 1),
                    ]);

                    return Response::text(json_encode(['status' => 'success', 'message' => "Kategori {$name} berhasil dibuat", 'category' => $cat], JSON_PRETTY_PRINT));

                case 'bulk_create':
                case 'batch_create':
                    $items = $request->get('categories') ?? $request->get('items') ?? $request->get('data');
                    if (empty($items) || ! is_array($items)) {
                        return Response::text(json_encode(['status' => 'error', 'message' => 'Parameter categories (array) wajib diisi untuk bulk_create']));
                    }
                    return $this->handleBulkCreate($items);

                case 'update':
                    if (! $id) {
                        return Response::text(json_encode(['status' => 'error', 'message' => 'Parameter id wajib diisi']));
                    }
                    $cat = Category::find($id);
                    if (! $cat) {
                        return Response::text(json_encode(['status' => 'error', 'message' => "Kategori #{$id} tidak ditemukan"]));
                    }

                    $data = [];
                    if ($request->has('name') || $request->has('nama')) {
                        $data['name'] = $request->get('name') ?? $request->get('nama');
                    }
                    if ($request->has('sort_order')) {
                        $data['sort_order'] = (int) $request->get('sort_order');
                    }
                    $cat->update($data);

                    return Response::text(json_encode(['status' => 'success', 'message' => "Kategori #{$id} berhasil diperbarui", 'category' => $cat], JSON_PRETTY_PRINT));

                case 'delete':
                    if (! $id) {
                        return Response::text(json_encode(['status' => 'error', 'message' => 'Parameter id wajib diisi']));
                    }
                    $cat = Category::withCount('menus')->find($id);
                    if (! $cat) {
                        return Response::text(json_encode(['status' => 'error', 'message' => "Kategori #{$id} tidak ditemukan"]));
                    }

                    if ($cat->menus_count > 0) {
                        return Response::text(json_encode(['status' => 'error', 'message' => "Kategori tidak dapat dihapus karena masih memiliki {$cat->menus_count} menu"]));
                    }

                    $cat->delete();

                    return Response::text(json_encode(['status' => 'success', 'message' => "Kategori #{$id} berhasil dihapus"]));

                default:
                    return Response::text(json_encode(['status' => 'error', 'message' => "Aksi '{$action}' tidak dikenal. Pilihan: list, create, bulk_create, update, delete"]));
            }
        } catch (\Throwable $e) {
            return Response::text(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    protected function handleBulkCreate(array $items): Response
    {
        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $created = 0;
            $existing = 0;
            $maxOrder = Category::max('sort_order') ?? 0;

            foreach ($items as $it) {
                $name = is_string($it) ? trim($it) : trim($it['name'] ?? $it['nama'] ?? '');
                if (! $name) {
                    continue;
                }

                $order = is_array($it) && isset($it['sort_order']) ? (int) $it['sort_order'] : ++$maxOrder;

                $found = Category::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
                if ($found) {
                    $existing++;
                } else {
                    Category::create([
                        'name' => $name,
                        'sort_order' => $order,
                    ]);
                    $created++;
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            return Response::text(json_encode([
                'status' => 'success',
                'message' => "Bulk create kategori selesai: {$created} baru dibuat, {$existing} sudah ada",
                'created_count' => $created,
                'existing_count' => $existing,
                'total_processed' => count($items),
            ], JSON_PRETTY_PRINT));

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return Response::text(json_encode(['status' => 'error', 'message' => 'Gagal bulk create kategori: ' . $e->getMessage()]));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->description("Aksi: 'list', 'create', 'bulk_create', 'update', 'delete'")->required(),
            'id' => $schema->integer()->description('ID Kategori (untuk update/delete)'),
            'name' => $schema->string()->description('Nama kategori (untuk create tunggal)'),
            'categories' => $schema->array()->description('Array kategori untuk bulk_create: ["Makanan", "Minuman"] atau [{"name": "Snack", "sort_order": 3}]'),
            'sort_order' => $schema->integer()->description('Urutan tampilan kategori (integer)'),
        ];
    }
}
