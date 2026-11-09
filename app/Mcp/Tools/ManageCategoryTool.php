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
                    return Response::text(json_encode(['status' => 'error', 'message' => "Aksi '{$action}' tidak dikenal. Pilihan: list, create, update, delete"]));
            }
        } catch (\Throwable $e) {
            return Response::text(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->description("Aksi: 'list', 'create', 'update', 'delete'")->required(),
            'id' => $schema->integer()->description('ID Kategori (untuk update/delete)'),
            'name' => $schema->string()->description('Nama kategori'),
            'sort_order' => $schema->integer()->description('Urutan tampilan kategori (integer)'),
        ];
    }
}
