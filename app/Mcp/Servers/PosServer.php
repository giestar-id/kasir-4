<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\CreateMenuWizardPrompt;
use App\Mcp\Prompts\DailyAnalysisPrompt;
use App\Mcp\Resources\ActiveCatalogResource;
use App\Mcp\Resources\SystemStatusResource;
use App\Mcp\Resources\TodaySummaryResource;
use App\Mcp\Tools\BulkImportCatalogTool;
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
#[Version('1.2.0')]
#[Instructions('MCP Server resmi POS Kuliner Satu Outlet. Menyediakan kendali penuh (CRUD & Bulk/Batch Import) atas menu, kategori, inventaris bahan baku, resep HPP, pencatatan transaksi kasir POS, stok opname, laporan keuangan, dan akses database universal.')]
class PosServer extends Server
{
    protected array $tools = [
        BulkImportCatalogTool::class,
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
        SystemStatusResource::class,
        TodaySummaryResource::class,
        ActiveCatalogResource::class,
    ];

    protected array $prompts = [
        DailyAnalysisPrompt::class,
        CreateMenuWizardPrompt::class,
    ];
}
