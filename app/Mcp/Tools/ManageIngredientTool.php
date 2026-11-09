<?php

namespace App\Mcp\Tools;

use App\Models\Ingredient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('CRUD Bahan Baku & Stok: Melihat inventaris bahan baku, stok saat ini, satuan konversi, menambah bahan baru, atau mengedit data bahan.')]
class ManageIngredientTool extends Tool
{
    public function handle(Request $request): Response
    {
        $action = $request->get('action', 'list');
        $id = $request->get('id');

        try {
            switch ($action) {
                case 'list':
                    $query = Ingredient::query();
                    if ($status = $request->get('stock_status')) {
                        if ($status === 'low') {
                            $query->whereColumn('current_stock', '<=', 'min_stock');
                        }
                        if ($status === 'negative') {
                            $query->where('current_stock', '<', 0);
                        }
                    }
                    if ($search = $request->get('search')) {
                        $query->where('name', 'like', "%{$search}%");
                    }
                    $items = $query->orderBy('name')->get()->map(fn ($ing) => [
                        'id' => $ing->id,
                        'name' => $ing->name,
                        'base_unit' => $ing->base_unit,
                        'purchase_unit' => $ing->purchase_unit,
                        'unit_factor' => $ing->unit_factor,
                        'current_stock' => $ing->current_stock,
                        'formatted_current_stock' => number_format($ing->current_stock, 1, ',', '.').' '.$ing->base_unit,
                        'min_stock' => $ing->min_stock,
                        'avg_cost_per_base' => $ing->avg_cost_per_base,
                        'formatted_avg_cost' => 'Rp'.number_format($ing->avg_cost_per_base, 0, ',', '.').' / '.$ing->base_unit,
                        'stock_status' => $ing->stock_status,
                    ]);

                    return Response::text(json_encode(['status' => 'success', 'count' => $items->count(), 'ingredients' => $items], JSON_PRETTY_PRINT));

                case 'get':
                    if (! $id) {
                        return Response::text(json_encode(['status' => 'error', 'message' => 'Parameter id wajib diisi']));
                    }
                    $ing = Ingredient::with(['stockMovements' => fn ($q) => $q->latest()->limit(10)])->find($id);
                    if (! $ing) {
                        return Response::text(json_encode(['status' => 'error', 'message' => "Bahan baku #{$id} tidak ditemukan"]));
                    }

                    return Response::text(json_encode(['status' => 'success', 'ingredient' => $ing], JSON_PRETTY_PRINT));

                case 'create':
                    $name = $request->get('name') ?? $request->get('nama');
                    $baseUnit = $request->get('base_unit') ?? $request->get('satuan_dasar') ?? 'gram';
                    $purchaseUnit = $request->get('purchase_unit') ?? $request->get('satuan_beli') ?? 'kg';
                    $unitFactor = (float) ($request->get('unit_factor') ?? $request->get('faktor_konversi') ?? 1000);
                    $minStock = (float) ($request->get('min_stock') ?? $request->get('stok_minimal') ?? 0);
                    $initialStock = (float) ($request->get('initial_stock') ?? $request->get('stok_awal') ?? $request->get('current_stock') ?? $request->get('stok') ?? 0);
                    $initialAvgCost = (int) ($request->get('initial_avg_cost') ?? $request->get('harga_satuan') ?? $request->get('avg_cost_per_base') ?? $request->get('hpp') ?? 0);

                    if (! $name) {
                        return Response::text(json_encode(['status' => 'error', 'message' => 'Parameter name wajib diisi']));
                    }

                    $ing = Ingredient::create([
                        'name' => $name,
                        'base_unit' => $baseUnit,
                        'purchase_unit' => $purchaseUnit,
                        'unit_factor' => $unitFactor,
                        'min_stock' => $minStock,
                        'current_stock' => $initialStock,
                        'avg_cost_per_base' => $initialAvgCost,
                    ]);

                    return Response::text(json_encode(['status' => 'success', 'message' => "Bahan baku {$ing->name} berhasil ditambahkan", 'ingredient' => $ing], JSON_PRETTY_PRINT));

                case 'update':
                    if (! $id) {
                        return Response::text(json_encode(['status' => 'error', 'message' => 'Parameter id wajib diisi']));
                    }
                    $ing = Ingredient::find($id);
                    if (! $ing) {
                        return Response::text(json_encode(['status' => 'error', 'message' => "Bahan baku #{$id} tidak ditemukan"]));
                    }

                    $data = [];
                    if ($request->has('name') || $request->has('nama')) {
                        $data['name'] = $request->get('name') ?? $request->get('nama');
                    }
                    if ($request->has('base_unit') || $request->has('satuan_dasar')) {
                        $data['base_unit'] = $request->get('base_unit') ?? $request->get('satuan_dasar');
                    }
                    if ($request->has('purchase_unit') || $request->has('satuan_beli')) {
                        $data['purchase_unit'] = $request->get('purchase_unit') ?? $request->get('satuan_beli');
                    }
                    if ($request->has('unit_factor') || $request->has('faktor_konversi')) {
                        $data['unit_factor'] = (float) ($request->get('unit_factor') ?? $request->get('faktor_konversi'));
                    }
                    if ($request->has('min_stock') || $request->has('stok_minimal')) {
                        $data['min_stock'] = (float) ($request->get('min_stock') ?? $request->get('stok_minimal'));
                    }

                    $ing->update($data);

                    return Response::text(json_encode(['status' => 'success', 'message' => "Bahan baku #{$id} berhasil diperbarui", 'ingredient' => $ing], JSON_PRETTY_PRINT));

                case 'delete':
                    if (! $id) {
                        return Response::text(json_encode(['status' => 'error', 'message' => 'Parameter id wajib diisi']));
                    }
                    $ing = Ingredient::find($id);
                    if (! $ing) {
                        return Response::text(json_encode(['status' => 'error', 'message' => "Bahan baku #{$id} tidak ditemukan"]));
                    }
                    if ($ing->recipes()->exists()) {
                        return Response::text(json_encode(['status' => 'error', 'message' => "Bahan baku #{$id} ({$ing->name}) tidak dapat dihapus karena masih digunakan dalam resep menu"]));
                    }
                    $ing->delete();

                    return Response::text(json_encode(['status' => 'success', 'message' => "Bahan baku #{$id} ({$ing->name}) berhasil dihapus"]));

                default:
                    return Response::text(json_encode(['status' => 'error', 'message' => "Aksi '{$action}' tidak dikenal. Pilihan: list, get, create, update, delete"]));
            }
        } catch (\Throwable $e) {
            return Response::text(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->description("Aksi: 'list', 'get', 'create', 'update'")->required(),
            'id' => $schema->integer()->description('ID Bahan Baku'),
            'name' => $schema->string()->description('Nama bahan baku (misal: Beras, Ayam, Minyak Goreng)'),
            'base_unit' => $schema->string()->description('Satuan dasar pemakaian (misal: gram, ml, butir, lembar)'),
            'purchase_unit' => $schema->string()->description('Satuan saat belanja (misal: kg, liter, dus)'),
            'unit_factor' => $schema->number()->description('Faktor konversi dari satuan beli ke satuan dasar (misal 1 kg = 1000 gram)'),
            'min_stock' => $schema->number()->description('Batas minimum stok untuk peringatan menipis'),
            'initial_stock' => $schema->number()->description('Stok awal saat membuat bahan baru'),
            'initial_avg_cost' => $schema->integer()->description('Harga pokok rata-rata per satuan dasar'),
            'stock_status' => $schema->string()->description("Filter kondisi stok: 'all', 'low', 'negative'"),
            'search' => $schema->string()->description('Kata kunci pencarian nama bahan'),
        ];
    }
}
