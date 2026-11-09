<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Universal Database CRUD: Memungkinkan AI melakukan operasi database apapun (melihat tabel, cek schema kolom, SELECT query, INSERT, UPDATE, dan DELETE data secara langsung pada tabel manapun).')]
class UniversalDatabaseTool extends Tool
{
    public function handle(Request $request): Response
    {
        $action = $request->get('action', 'tables');
        $table = $request->get('table');

        try {
            switch ($action) {
                case 'tables':
                    $tables = Schema::getTableListing();

                    return Response::text(json_encode([
                        'status' => 'success',
                        'count' => count($tables),
                        'tables' => $tables,
                    ], JSON_PRETTY_PRINT));

                case 'describe':
                    if (! $table) {
                        return Response::error(json_encode(['status' => 'error', 'message' => 'Parameter table wajib diisi']));
                    }
                    $columns = Schema::getColumnListing($table);
                    $columnDetails = [];
                    foreach ($columns as $col) {
                        $columnDetails[] = [
                            'name' => $col,
                            'type' => Schema::getColumnType($table, $col),
                        ];
                    }

                    return Response::text(json_encode([
                        'status' => 'success',
                        'table' => $table,
                        'columns' => $columnDetails,
                    ], JSON_PRETTY_PRINT));

                case 'select':
                    if (! $table) {
                        $rawQuery = $request->get('raw_query', $request->get('query'));
                        if (! $rawQuery) {
                            return Response::error(json_encode(['status' => 'error', 'message' => 'table atau raw_query wajib diisi']));
                        }
                        // Only allow SELECT statements in raw query for safety
                        if (! str_starts_with(trim(strtolower($rawQuery)), 'select')) {
                            return Response::error(json_encode(['status' => 'error', 'message' => 'raw_query hanya mengizinkan perintah SELECT']));
                        }
                        $rows = DB::select($rawQuery);

                        return Response::text(json_encode(['status' => 'success', 'count' => count($rows), 'data' => $rows], JSON_PRETTY_PRINT));
                    }

                    $query = DB::table($table);
                    if ($where = $request->get('where')) {
                        if (is_array($where)) {
                            foreach ($where as $k => $v) {
                                $query->where($k, $v);
                            }
                        }
                    }

                    $limit = min(100, (int) $request->get('limit', 25));
                    $data = $query->limit($limit)->get();

                    return Response::text(json_encode([
                        'status' => 'success',
                        'table' => $table,
                        'count' => $data->count(),
                        'data' => $data,
                    ], JSON_PRETTY_PRINT));

                case 'insert':
                    if (! $table) {
                        return Response::error(json_encode(['status' => 'error', 'message' => 'Parameter table wajib diisi']));
                    }
                    $data = $request->get('data');
                    if (empty($data) || ! is_array($data)) {
                        return Response::error(json_encode(['status' => 'error', 'message' => 'Parameter data (array key-value) wajib diisi']));
                    }

                    // Auto timestamp if exists in schema
                    if (Schema::hasColumn($table, 'created_at') && ! isset($data['created_at'])) {
                        $data['created_at'] = now();
                    }
                    if (Schema::hasColumn($table, 'updated_at') && ! isset($data['updated_at'])) {
                        $data['updated_at'] = now();
                    }

                    $insertedId = DB::table($table)->insertGetId($data);

                    return Response::text(json_encode([
                        'status' => 'success',
                        'message' => "Berhasil menambahkan data ke tabel {$table}",
                        'inserted_id' => $insertedId,
                    ], JSON_PRETTY_PRINT));

                case 'update':
                    if (! $table) {
                        return Response::error(json_encode(['status' => 'error', 'message' => 'Parameter table wajib diisi']));
                    }
                    $id = $request->get('id');
                    $where = $request->get('where');
                    $data = $request->get('data');

                    if (empty($data) || ! is_array($data)) {
                        return Response::error(json_encode(['status' => 'error', 'message' => 'Parameter data (array key-value yang akan diubah) wajib diisi']));
                    }

                    if (Schema::hasColumn($table, 'updated_at') && ! isset($data['updated_at'])) {
                        $data['updated_at'] = now();
                    }

                    $query = DB::table($table);
                    if ($id) {
                        $query->where('id', $id);
                    } elseif (! empty($where) && is_array($where)) {
                        foreach ($where as $k => $v) {
                            $query->where($k, $v);
                        }
                    } else {
                        return Response::error(json_encode(['status' => 'error', 'message' => 'Harus menyertakan id atau where clause untuk update data']));
                    }

                    $affected = $query->update($data);

                    return Response::text(json_encode([
                        'status' => 'success',
                        'message' => "Berhasil memperbarui data di tabel {$table}",
                        'affected_rows' => $affected,
                    ], JSON_PRETTY_PRINT));

                case 'delete':
                    if (! $table) {
                        return Response::error(json_encode(['status' => 'error', 'message' => 'Parameter table wajib diisi']));
                    }
                    $id = $request->get('id');
                    $where = $request->get('where');

                    $query = DB::table($table);
                    if ($id) {
                        $query->where('id', $id);
                    } elseif (! empty($where) && is_array($where)) {
                        foreach ($where as $k => $v) {
                            $query->where($k, $v);
                        }
                    } else {
                        return Response::error(json_encode(['status' => 'error', 'message' => 'Harus menyertakan id atau where clause untuk menghapus data']));
                    }

                    $deleted = $query->delete();

                    return Response::text(json_encode([
                        'status' => 'success',
                        'message' => "Berhasil menghapus {$deleted} baris data dari tabel {$table}",
                    ], JSON_PRETTY_PRINT));

                default:
                    return Response::error(json_encode(['status' => 'error', 'message' => "Aksi '{$action}' tidak dikenal. Pilihan: tables, describe, select, insert, update, delete"]));
            }
        } catch (\Throwable $e) {
            return Response::error(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->description("Aksi database: 'tables', 'describe', 'select', 'insert', 'update', 'delete'")->required(),
            'table' => $schema->string()->description('Nama tabel database yang akan diakses'),
            'id' => $schema->integer()->description('ID record untuk operasi update atau delete'),
            'where' => $schema->array()->description('Kondisi filter (object key-value). Contoh: {"channel": "gofood", "status": "completed"}'),
            'data' => $schema->array()->description('Data baris (object key-value) untuk operasi insert atau update'),
            'raw_query' => $schema->string()->description('Perintah SQL SELECT mentah jika membutuhkan join/grouping kompleks'),
            'limit' => $schema->integer()->description('Limit baris untuk select (default: 25, max: 100)'),
        ];
    }
}
