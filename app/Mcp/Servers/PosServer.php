<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CreateSaleTool;
use App\Mcp\Tools\GetFinancialReportTool;
use App\Mcp\Tools\ManageCategoryTool;
use App\Mcp\Tools\ManageIngredientTool;
use App\Mcp\Tools\ManageMenuTool;
use App\Mcp\Tools\ManageRecipeTool;
use App\Mcp\Tools\ManageSaleTool;
use App\Mcp\Tools\ManageStockTool;
use App\Mcp\Tools\UniversalDatabaseTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('POS Kuliner MCP Server')]
#[Version('1.0.0')]
#[Instructions('MCP Server resmi POS Kuliner Satu Outlet. Menyediakan kendali penuh (CRUD) atas menu, kategori, inventaris bahan baku, resep HPP, pencatatan transaksi kasir POS, stok opname, laporan keuangan, dan akses database universal.')]
class PosServer extends Server
{
    protected array $tools = [
        ManageMenuTool::class,
        ManageCategoryTool::class,
        ManageIngredientTool::class,
        ManageRecipeTool::class,
        CreateSaleTool::class,
        ManageSaleTool::class,
        ManageStockTool::class,
        GetFinancialReportTool::class,
        UniversalDatabaseTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
