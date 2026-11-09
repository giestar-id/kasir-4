<?php

namespace Tests\Feature;

use App\Livewire\Settings\Index;
use App\Mcp\Prompts\CreateMenuWizardPrompt;
use App\Mcp\Prompts\DailyAnalysisPrompt;
use App\Mcp\Resources\ActiveCatalogResource;
use App\Mcp\Resources\SystemStatusResource;
use App\Mcp\Resources\TodaySummaryResource;
use App\Mcp\Servers\PosServer;
use App\Mcp\Tools\CreateSaleTool;
use App\Mcp\Tools\GetFinancialReportTool;
use App\Mcp\Tools\ManageCategoryTool;
use App\Mcp\Tools\ManageIngredientTool;
use App\Mcp\Tools\ManageMenuTool;
use App\Mcp\Tools\ManageRecipeTool;
use App\Mcp\Tools\ManageSaleTool;
use App\Mcp\Tools\ManageStockTool;
use App\Mcp\Tools\UniversalDatabaseTool;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Livewire\Livewire;
use ReflectionProperty;
use Tests\TestCase;

class McpServerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'role' => 'owner',
            'name' => 'Owner Test',
        ]);
        $this->actingAs($this->user);
    }

    protected function getResponseText($response): string
    {
        return (string) ($response->content()->toArray()['text'] ?? '');
    }

    public function test_pos_server_registers_all_tools(): void
    {
        $ref = new ReflectionProperty(PosServer::class, 'tools');
        $this->assertCount(9, $ref->getDefaultValue());
    }

    public function test_manage_category_tool_crud(): void
    {
        $tool = new ManageCategoryTool;

        // 1. Create
        $response = $tool->handle(new Request([
            'action' => 'create',
            'name' => 'Kopi Panas',
        ]));
        $this->assertStringContainsString('Kopi Panas berhasil dibuat', $this->getResponseText($response));
        $this->assertDatabaseHas('categories', ['name' => 'Kopi Panas']);

        $category = Category::where('name', 'Kopi Panas')->first();

        // 2. List
        $listResponse = $tool->handle(new Request(['action' => 'list']));
        $this->assertStringContainsString('Kopi Panas', $this->getResponseText($listResponse));

        // 3. Update
        $updateResponse = $tool->handle(new Request([
            'action' => 'update',
            'id' => $category->id,
            'name' => 'Kopi Hangat',
        ]));
        $this->assertStringContainsString('berhasil diperbarui', $this->getResponseText($updateResponse));
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Kopi Hangat']);

        // 4. Delete
        $deleteResponse = $tool->handle(new Request([
            'action' => 'delete',
            'id' => $category->id,
        ]));
        $this->assertStringContainsString('berhasil dihapus', $this->getResponseText($deleteResponse));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_manage_ingredient_tool_crud(): void
    {
        $tool = new ManageIngredientTool;

        // 1. Create
        $response = $tool->handle(new Request([
            'action' => 'create',
            'name' => 'Biji Kopi Arabika',
            'base_unit' => 'gram',
            'purchase_unit' => 'kg',
            'unit_factor' => 1000,
            'min_stock' => 500,
        ]));
        $this->assertStringContainsString('Biji Kopi Arabika berhasil ditambahkan', $this->getResponseText($response));
        $this->assertDatabaseHas('ingredients', ['name' => 'Biji Kopi Arabika']);

        $ingredient = Ingredient::where('name', 'Biji Kopi Arabika')->first();

        // 2. List & Get
        $listResponse = $tool->handle(new Request(['action' => 'list']));
        $this->assertStringContainsString('Biji Kopi Arabika', $this->getResponseText($listResponse));

        $getResponse = $tool->handle(new Request([
            'action' => 'get',
            'id' => $ingredient->id,
        ]));
        $this->assertStringContainsString('Biji Kopi Arabika', $this->getResponseText($getResponse));

        // 3. Update
        $updateResponse = $tool->handle(new Request([
            'action' => 'update',
            'id' => $ingredient->id,
            'min_stock' => 800,
        ]));
        $this->assertStringContainsString('berhasil diperbarui', $this->getResponseText($updateResponse));
        $this->assertDatabaseHas('ingredients', ['id' => $ingredient->id, 'min_stock' => 800]);
    }

    public function test_manage_menu_and_recipe_tools(): void
    {
        $category = Category::create(['name' => 'Coffee']);
        $menuTool = new ManageMenuTool;

        // 1. Create Menu
        $createResponse = $menuTool->handle(new Request([
            'action' => 'create',
            'name' => 'Espresso Single',
            'category_id' => $category->id,
            'price' => 18000,
        ]));
        $this->assertStringContainsString('Espresso Single berhasil dibuat', $this->getResponseText($createResponse));
        $this->assertDatabaseHas('menus', ['name' => 'Espresso Single', 'price' => 18000]);

        $menu = Menu::where('name', 'Espresso Single')->first();

        // 2. Set Recipe
        $ingredient = Ingredient::create([
            'name' => 'Biji Kopi',
            'base_unit' => 'gram',
            'purchase_unit' => 'kg',
            'unit_factor' => 1000,
            'avg_cost_per_base' => 150, // Rp 150 per gram
            'current_stock' => 5000,
            'min_stock' => 500,
        ]);

        $recipeTool = new ManageRecipeTool;
        $setRecipeResponse = $recipeTool->handle(new Request([
            'action' => 'set',
            'menu_id' => $menu->id,
            'ingredients' => [
                [
                    'ingredient_id' => $ingredient->id,
                    'qty_per_portion' => 18,
                ],
            ],
            'extra_costs' => [
                [
                    'label' => 'Cup & Packaging',
                    'amount' => 1000,
                ],
            ],
        ]));
        $this->assertStringContainsString('berhasil diperbarui', $this->getResponseText($setRecipeResponse));

        $menu->refresh();
        $this->assertEquals(3700, $menu->calculateHpp());

        // 3. Get Recipe
        $getRecipeResponse = $recipeTool->handle(new Request([
            'action' => 'get',
            'menu_id' => $menu->id,
        ]));
        $this->assertStringContainsString('Biji Kopi', $this->getResponseText($getRecipeResponse));

        // 4. Update Menu
        $menuTool->handle(new Request([
            'action' => 'update',
            'id' => $menu->id,
            'price' => 20000,
        ]));
        $this->assertDatabaseHas('menus', ['id' => $menu->id, 'price' => 20000]);

        // 5. Delete Menu
        $deleteMenuResponse = $menuTool->handle(new Request([
            'action' => 'delete',
            'id' => $menu->id,
        ]));
        $this->assertStringContainsString('berhasil dihapus', $this->getResponseText($deleteMenuResponse));
        $this->assertDatabaseMissing('menus', ['id' => $menu->id]);
    }

    public function test_sale_flow_and_inventory_deduction_and_void(): void
    {
        $category = Category::create(['name' => 'Minuman']);
        $ingredient = Ingredient::create([
            'name' => 'Teh Melati',
            'base_unit' => 'gram',
            'purchase_unit' => 'kg',
            'unit_factor' => 1000,
            'avg_cost_per_base' => 50,
            'current_stock' => 1000,
            'min_stock' => 100,
        ]);

        $menu = Menu::create([
            'name' => 'Es Teh Manis',
            'category_id' => $category->id,
            'price' => 5000,
            'is_active' => true,
        ]);

        $menu->recipes()->create([
            'ingredient_id' => $ingredient->id,
            'qty_per_portion' => 10, // 10 gram per porsi
        ]);

        // 1. Create Sale via CreateSaleTool
        $createSaleTool = new CreateSaleTool;
        $saleResponse = app()->call([$createSaleTool, 'handle'], [
            'request' => new Request([
                'customer_name' => 'Pelanggan Meja 1',
                'payment_method' => 'cash',
                'paid_amount' => 10000,
                'items' => [
                    [
                        'menu_id' => $menu->id,
                        'qty' => 2, // 2 * 10g = 20g
                    ],
                ],
            ]),
        ]);

        $this->assertStringContainsString('berhasil dibuat', $this->getResponseText($saleResponse));

        // Assert Stock Deducted (1000 - 20 = 980)
        $ingredient->refresh();
        $this->assertEquals(980, $ingredient->current_stock);

        $sale = Sale::latest('id')->first();
        $this->assertNotNull($sale);
        $this->assertEquals(10000, $sale->total);

        // 2. ManageSaleTool: detail
        $saleTool = new ManageSaleTool;
        $detailResponse = app()->call([$saleTool, 'handle'], [
            'request' => new Request([
                'action' => 'detail',
                'id' => $sale->id,
            ]),
        ]);
        $this->assertStringContainsString($sale->number, $this->getResponseText($detailResponse));

        // 3. ManageSaleTool: void (Stock should be restored!)
        $voidResponse = app()->call([$saleTool, 'handle'], [
            'request' => new Request([
                'action' => 'void',
                'id' => $sale->id,
                'notes' => 'Salah input',
            ]),
        ]);
        $this->assertStringContainsString('berhasil dibatalkan (VOID)', $this->getResponseText($voidResponse));

        $sale->refresh();
        $this->assertEquals('void', $sale->status);

        // Assert Stock Restored back to 1000
        $ingredient->refresh();
        $this->assertEquals(1000, $ingredient->current_stock);
    }

    public function test_manage_stock_tool_purchase_and_opname(): void
    {
        $ingredient = Ingredient::create([
            'name' => 'Gula Pasir',
            'base_unit' => 'gram',
            'purchase_unit' => 'kg',
            'unit_factor' => 1000,
            'avg_cost_per_base' => 15,
            'current_stock' => 2000,
            'min_stock' => 500,
        ]);

        $stockTool = new ManageStockTool;

        // 1. Purchase: beli 5 kg seharga Rp 75.000 (total +5000 gram)
        $purchaseResponse = app()->call([$stockTool, 'handle'], [
            'request' => new Request([
                'action' => 'purchase',
                'ingredient_id' => $ingredient->id,
                'purchase_qty' => 5,
                'total_cost' => 75000,
                'notes' => 'Beli dari supplier',
            ]),
        ]);
        $this->assertStringContainsString('Pembelian Gula Pasir', $this->getResponseText($purchaseResponse));

        $ingredient->refresh();
        $this->assertEquals(7000, $ingredient->current_stock);

        // 2. Opname: fisik ternyata 6500 gram (selisih -500 gram)
        $opnameResponse = app()->call([$stockTool, 'handle'], [
            'request' => new Request([
                'action' => 'opname',
                'ingredient_id' => $ingredient->id,
                'physical_stock' => 6500,
                'notes' => 'Tumpah 500g',
            ]),
        ]);
        $this->assertStringContainsString('berhasil dicatat', $this->getResponseText($opnameResponse));

        $ingredient->refresh();
        $this->assertEquals(6500, $ingredient->current_stock);

        // 3. Movements
        $movementsResponse = app()->call([$stockTool, 'handle'], [
            'request' => new Request([
                'action' => 'movements',
                'ingredient_id' => $ingredient->id,
            ]),
        ]);
        $this->assertStringContainsString('Gula Pasir', $this->getResponseText($movementsResponse));
    }

    public function test_get_financial_report_tool(): void
    {
        $tool = new GetFinancialReportTool;
        $response = $tool->handle(new Request([
            'start_date' => now()->startOfMonth()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]));

        $this->assertStringContainsString('"status": "success"', $this->getResponseText($response));
    }

    public function test_universal_database_tool_full_capabilities(): void
    {
        $dbTool = new UniversalDatabaseTool;

        // 1. Tables
        $tablesRes = $dbTool->handle(new Request(['action' => 'tables']));
        $this->assertStringContainsString('categories', $this->getResponseText($tablesRes));
        $this->assertStringContainsString('menus', $this->getResponseText($tablesRes));

        // 2. Describe
        $descRes = $dbTool->handle(new Request(['action' => 'describe', 'table' => 'categories']));
        $this->assertStringContainsString('name', $this->getResponseText($descRes));

        // 3. Insert
        $insertRes = $dbTool->handle(new Request([
            'action' => 'insert',
            'table' => 'categories',
            'data' => [
                'name' => 'Snack Spesial',
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s'),
            ],
        ]));
        $this->assertStringContainsString('Berhasil menambahkan data', $this->getResponseText($insertRes));
        $this->assertDatabaseHas('categories', ['name' => 'Snack Spesial']);

        // 4. Select
        $selectRes = $dbTool->handle(new Request([
            'action' => 'select',
            'query' => "SELECT id, name FROM categories WHERE name = 'Snack Spesial'",
        ]));
        $this->assertStringContainsString('Snack Spesial', $this->getResponseText($selectRes));

        // 5. Update
        $updateRes = $dbTool->handle(new Request([
            'action' => 'update',
            'table' => 'categories',
            'data' => ['name' => 'Snack Gurih'],
            'where' => ['name' => 'Snack Spesial'],
        ]));
        $this->assertStringContainsString('Berhasil memperbarui', $this->getResponseText($updateRes));
        $this->assertDatabaseHas('categories', ['name' => 'Snack Gurih']);

        // 6. Delete
        $deleteRes = $dbTool->handle(new Request([
            'action' => 'delete',
            'table' => 'categories',
            'where' => ['name' => 'Snack Gurih'],
        ]));
        $this->assertStringContainsString('Berhasil menghapus', $this->getResponseText($deleteRes));
        $this->assertDatabaseMissing('categories', ['name' => 'Snack Gurih']);
    }

    public function test_pos_server_registers_resources_and_prompts(): void
    {
        $resRef = new ReflectionProperty(PosServer::class, 'resources');
        $this->assertCount(3, $resRef->getDefaultValue());

        $promptRef = new ReflectionProperty(PosServer::class, 'prompts');
        $this->assertCount(2, $promptRef->getDefaultValue());
    }

    public function test_mcp_resources(): void
    {
        Category::create(['name' => 'Kopi']);
        $menu = Menu::create([
            'name' => 'Americano',
            'category_id' => 1,
            'price' => 15000,
            'is_active' => true,
        ]);

        Ingredient::create([
            'name' => 'Biji Kopi Robusta',
            'base_unit' => 'gram',
            'purchase_unit' => 'kg',
            'unit_factor' => 1000,
            'min_stock' => 500,
            'current_stock' => 100, // Trigger low stock alert!
        ]);

        // 1. SystemStatusResource
        $statusResource = new SystemStatusResource;
        $statusRes = $statusResource->handle();
        $this->assertStringContainsString('outlet_metrics', $this->getResponseText($statusRes));
        $this->assertStringContainsString('Biji Kopi Robusta', $this->getResponseText($statusRes));

        // 2. TodaySummaryResource
        $todayResource = new TodaySummaryResource;
        $todayRes = $todayResource->handle();
        $this->assertStringContainsString('total_omzet', $this->getResponseText($todayRes));

        // 3. ActiveCatalogResource
        $catalogResource = new ActiveCatalogResource;
        $catalogRes = $catalogResource->handle();
        $this->assertStringContainsString('Americano', $this->getResponseText($catalogRes));
    }

    public function test_mcp_prompts(): void
    {
        // 1. DailyAnalysisPrompt
        $dailyPrompt = new DailyAnalysisPrompt;
        $dailyRes = $dailyPrompt->handle(new Request(['date' => '2026-11-09']));
        $this->assertStringContainsString('get-financial-report-tool', $this->getResponseText($dailyRes));
        $this->assertStringContainsString('2026-11-09', $this->getResponseText($dailyRes));

        // 2. CreateMenuWizardPrompt
        $wizardPrompt = new CreateMenuWizardPrompt;
        $wizardRes = $wizardPrompt->handle(new Request(['menu_name' => 'Matcha Latte', 'target_margin_pct' => 70]));
        $this->assertStringContainsString('Matcha Latte', $this->getResponseText($wizardRes));
        $this->assertStringContainsString('70%', $this->getResponseText($wizardRes));
    }

    public function test_mcp_public_info_endpoint(): void
    {
        $response = $this->getJson('/mcp/info');

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'online');
        $response->assertJsonPath('server', 'POS Kuliner MCP Server');
        $response->assertJsonPath('capabilities.tools.count', 9);
        $response->assertJsonPath('capabilities.resources.count', 3);
        $response->assertJsonPath('capabilities.prompts.count', 2);
    }

    public function test_web_mcp_authentication_middleware(): void
    {
        $validKey = config('mcp.api_key');
        $payload = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
            'params' => [],
        ];

        // 1. Unauthenticated request -> must return 401
        $noAuthRes = $this->postJson('/mcp/pos', $payload);
        $noAuthRes->assertStatus(401);
        $noAuthRes->assertJsonPath('error.code', -32000);
        $noAuthRes->assertHeader('WWW-Authenticate');

        // 2. Invalid Bearer Token -> must return 401
        $invalidAuthRes = $this->withHeader('Authorization', 'Bearer invalid_key_123')
            ->postJson('/mcp/pos', $payload);
        $invalidAuthRes->assertStatus(401);

        // 3. Valid Bearer Token -> authentication passes (not 401)
        $bearerRes = $this->withHeader('Authorization', 'Bearer '.$validKey)
            ->postJson('/mcp/pos', $payload);
        $this->assertNotEquals(401, $bearerRes->status());

        // 4. Valid X-MCP-KEY Header -> authentication passes (not 401)
        $headerRes = $this->withHeader('X-MCP-KEY', $validKey)
            ->postJson('/mcp/pos', $payload);
        $this->assertNotEquals(401, $headerRes->status());

        // 5. Valid ?api_key= Query Parameter -> authentication passes (not 401)
        $queryRes = $this->postJson('/mcp/pos?api_key='.$validKey, $payload);
        $this->assertNotEquals(401, $queryRes->status());
    }

    public function test_artisan_mcp_credentials_command(): void
    {
        $this->artisan('mcp:credentials')
            ->expectsOutputToContain('POS KULINER - MCP SERVER CREDENTIALS & BASE URL')
            ->expectsOutputToContain(config('mcp.api_key'))
            ->assertSuccessful();
    }

    public function test_web_mcp_full_jsonrpc_protocol_standards(): void
    {
        $validKey = config('mcp.api_key');
        $authHeader = ['Authorization' => 'Bearer '.$validKey];

        // 1. MCP Standard: initialize handshake
        $initRes = $this->withHeaders($authHeader)->postJson('/mcp/pos', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => (object) [],
                'clientInfo' => ['name' => 'cursor', 'version' => '0.45.0'],
            ],
        ]);

        $initRes->assertStatus(200);
        $initRes->assertJsonPath('jsonrpc', '2.0');
        $initRes->assertJsonPath('id', 1);
        $initRes->assertJsonPath('result.serverInfo.name', 'POS Kuliner MCP Server');
        $initRes->assertJsonPath('result.serverInfo.version', '1.1.0');
        $this->assertNotNull($initRes->json('result.capabilities.tools'));
        $this->assertNotNull($initRes->json('result.capabilities.resources'));
        $this->assertNotNull($initRes->json('result.capabilities.prompts'));

        // 2. MCP Standard: ping
        $pingRes = $this->withHeaders($authHeader)->postJson('/mcp/pos', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'ping',
            'params' => (object) [],
        ]);
        $pingRes->assertStatus(200);
        $pingRes->assertJsonPath('jsonrpc', '2.0');
        $pingRes->assertJsonPath('id', 2);

        // 3. MCP Standard: tools/list
        $toolsRes = $this->withHeaders($authHeader)->postJson('/mcp/pos', [
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'tools/list',
            'params' => (object) [],
        ]);
        $toolsRes->assertStatus(200);
        $tools = $toolsRes->json('result.tools');
        $this->assertIsArray($tools);
        $this->assertCount(9, $tools);

        // Verify each tool has compliant name, description, and inputSchema with type object
        foreach ($tools as $t) {
            $this->assertArrayHasKey('name', $t);
            $this->assertArrayHasKey('description', $t);
            $this->assertArrayHasKey('inputSchema', $t);
            $this->assertEquals('object', $t['inputSchema']['type'] ?? null);
        }

        // 4. MCP Standard: tools/call
        $callRes = $this->withHeaders($authHeader)->postJson('/mcp/pos', [
            'jsonrpc' => '2.0',
            'id' => 4,
            'method' => 'tools/call',
            'params' => [
                'name' => 'manage-category-tool',
                'arguments' => ['action' => 'list'],
            ],
        ]);
        $callRes->assertStatus(200);
        $callRes->assertJsonPath('jsonrpc', '2.0');
        $callRes->assertJsonPath('id', 4);
        $callRes->assertJsonPath('result.isError', false);
        $this->assertNotEmpty($callRes->json('result.content'));
        $this->assertEquals('text', $callRes->json('result.content.0.type'));

        // 5. MCP Standard: resources/list
        $resListRes = $this->withHeaders($authHeader)->postJson('/mcp/pos', [
            'jsonrpc' => '2.0',
            'id' => 5,
            'method' => 'resources/list',
            'params' => (object) [],
        ]);
        $resListRes->assertStatus(200);
        $resources = $resListRes->json('result.resources');
        $this->assertIsArray($resources);
        $this->assertCount(3, $resources);

        // 6. MCP Standard: resources/read
        $readRes = $this->withHeaders($authHeader)->postJson('/mcp/pos', [
            'jsonrpc' => '2.0',
            'id' => 6,
            'method' => 'resources/read',
            'params' => ['uri' => 'pos://system/status'],
        ]);
        $readRes->assertStatus(200);
        $contents = $readRes->json('result.contents');
        $this->assertNotEmpty($contents);
        $this->assertEquals('pos://system/status', $contents[0]['uri']);

        // 7. MCP Standard: prompts/list
        $promptsRes = $this->withHeaders($authHeader)->postJson('/mcp/pos', [
            'jsonrpc' => '2.0',
            'id' => 7,
            'method' => 'prompts/list',
            'params' => (object) [],
        ]);
        $promptsRes->assertStatus(200);
        $prompts = $promptsRes->json('result.prompts');
        $this->assertIsArray($prompts);
        $this->assertCount(2, $prompts);

        // 8. MCP Standard: prompts/get
        $getPromptRes = $this->withHeaders($authHeader)->postJson('/mcp/pos', [
            'jsonrpc' => '2.0',
            'id' => 8,
            'method' => 'prompts/get',
            'params' => [
                'name' => 'pos-daily-analysis',
                'arguments' => ['date' => '2026-11-09'],
            ],
        ]);
        $getPromptRes->assertStatus(200);
        $messages = $getPromptRes->json('result.messages');
        $this->assertNotEmpty($messages);

        // 9. MCP Standard: tool error sets isError to true
        $errorCallRes = $this->withHeaders($authHeader)->postJson('/mcp/pos', [
            'jsonrpc' => '2.0',
            'id' => 9,
            'method' => 'tools/call',
            'params' => [
                'name' => 'manage-menu-tool',
                'arguments' => ['action' => 'get', 'id' => 999999], // Not found
            ],
        ]);
        $errorCallRes->assertStatus(200);
        $errorCallRes->assertJsonPath('result.isError', true);
        $this->assertStringContainsString('tidak ditemukan', $errorCallRes->json('result.content.0.text'));
    }

    public function test_settings_mcp_tab_renders_and_regenerates_key(): void
    {
        Livewire::test(Index::class)
            ->assertSee('Model Context Protocol (MCP) Server')
            ->assertSee(config('mcp.api_key'))
            ->call('regenerateMcpKey')
            ->assertDispatched('toast');

        $this->assertNotNull(Setting::get('mcp_api_key'));
    }

    public function test_claude_web_cors_and_get_connectivity_validation(): void
    {
        $validKey = Setting::get('mcp_api_key') ?: config('mcp.api_key');

        // 1. Browser CORS preflight (OPTIONS)
        $optionsRes = $this->options('/mcp/pos');
        $optionsRes->assertStatus(204);
        $optionsRes->assertHeader('Access-Control-Allow-Origin', '*');
        $optionsRes->assertHeader('Access-Control-Allow-Methods');

        // 2. Claude Web connectivity validation via GET with query param ?api_key=
        $getRes = $this->get('/mcp/pos?api_key='.$validKey);
        $getRes->assertStatus(200);
        $getRes->assertJsonPath('status', 'online');
        $getRes->assertJsonPath('authenticated', true);
        $getRes->assertHeader('Access-Control-Allow-Origin', '*');

        // 3. Unauthenticated GET returns 401
        $badGetRes = $this->get('/mcp/pos?api_key=invalid_key');
        $badGetRes->assertStatus(401);
    }
}
