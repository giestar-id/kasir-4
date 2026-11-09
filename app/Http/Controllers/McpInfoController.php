<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class McpInfoController extends Controller
{
    public function index(): JsonResponse
    {
        $baseUrl = config('mcp.base_url', url('/mcp/pos'));
        $apiKey = Setting::get('mcp_api_key') ?: config('mcp.api_key');
        // Mask API key for safety on public info endpoint (show first 11 chars + ***)
        $maskedKey = strlen((string) $apiKey) > 12
            ? substr((string) $apiKey, 0, 11).'...'.substr((string) $apiKey, -4)
            : 'pos_sec_***';

        return response()->json([
            'status' => config('mcp.enabled', true) ? 'online' : 'disabled',
            'server' => config('mcp.server_name', 'POS Kuliner MCP Server'),
            'version' => config('mcp.version', '1.2.0'),
            'protocol' => 'Model Context Protocol (JSON-RPC 2.0)',
            'base_url' => $baseUrl,
            'authentication' => [
                'type' => 'Bearer Token or API Key Header',
                'supported_headers' => [
                    'Authorization' => 'Bearer <MCP_API_KEY>',
                    'X-MCP-KEY' => '<MCP_API_KEY>',
                ],
                'supported_query_param' => '?api_key=<MCP_API_KEY>',
                'credential_hint' => $maskedKey,
            ],
            'capabilities' => [
                'tools' => [
                    'count' => 10,
                    'items' => [
                        'bulk-import-catalog-tool',
                        'manage-menu-tool',
                        'manage-category-tool',
                        'manage-ingredient-tool',
                        'manage-recipe-tool',
                        'create-sale-tool',
                        'manage-sale-tool',
                        'manage-stock-tool',
                        'get-financial-report-tool',
                        'universal-database-tool',
                    ],
                ],
                'resources' => [
                    'count' => 3,
                    'uris' => [
                        'pos://system/status',
                        'pos://reports/today',
                        'pos://catalog/menus',
                    ],
                ],
                'prompts' => [
                    'count' => 2,
                    'names' => [
                        'pos-daily-analysis',
                        'pos-menu-wizard',
                    ],
                ],
            ],
            'documentation' => url('/MCP.md'),
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
