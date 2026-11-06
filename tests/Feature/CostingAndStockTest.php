<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\Recipe;
use App\Models\User;
use App\Services\CostingService;
use App\Services\SaleService;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostingAndStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_and_recipe_hpp_calculation(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $stockService = app(StockService::class);
        $costingService = app(CostingService::class);

        // 1. Create ingredient Ayam (base unit: gram, purchase unit: kg, factor: 1000)
        $ayam = Ingredient::create([
            'name' => 'Ayam Broiler',
            'base_unit' => 'gram',
            'purchase_unit' => 'kg',
            'unit_factor' => 1000,
            'avg_cost_per_base' => 0,
            'current_stock' => 0,
            'min_stock' => 500,
            'is_active' => true,
        ]);

        // 2. Buy 1 kg at Rp36.000
        $stockService->recordPurchase(
            $ayam->id,
            1.0, // 1 kg
            36000, // Rp 36.000
            Carbon::now()->format('Y-m-d H:i:s'),
            'Beli di pasar',
            $owner->id
        );

        $ayam->refresh();
        $this->assertEquals(1000, $ayam->current_stock);
        $this->assertEquals(36, $ayam->avg_cost_per_base); // Rp36 / gram

        // 3. Create Menu with recipe 150 gram ayam
        $category = Category::create(['name' => 'Makanan', 'sort_order' => 1]);
        $menu = Menu::create([
            'category_id' => $category->id,
            'name' => 'Ayam Goreng Spesial',
            'price' => 20000,
            'is_active' => true,
        ]);

        Recipe::create([
            'menu_id' => $menu->id,
            'ingredient_id' => $ayam->id,
            'qty_per_portion' => 150, // 150 gram
        ]);

        // PRD Section 5.5 criterion: 150g * Rp36/g = Rp5.400
        $hpp = $costingService->calculateMenuHpp($menu);
        $this->assertEquals(5400, $hpp);

        $margin = $menu->margin;
        $this->assertEquals(14600, $margin['margin_rp']); // Rp20.000 - Rp5.400
        $this->assertEquals(73.0, $margin['margin_pct']);
        $this->assertTrue($margin['is_complete']);
    }

    public function test_stock_deduction_on_sale_and_return_on_void(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $stockService = app(StockService::class);
        $saleService = app(SaleService::class);

        $beras = Ingredient::create([
            'name' => 'Beras Pandan Wangi',
            'base_unit' => 'gram',
            'purchase_unit' => 'kg',
            'unit_factor' => 1000,
            'avg_cost_per_base' => 12,
            'current_stock' => 0,
            'min_stock' => 1000,
            'is_active' => true,
        ]);

        // Initialize 5000g stock through official movement
        $stockService->recordAdjustment($beras->id, 'adjustment', 5000, 'Saldo Awal Inventaris', $owner->id);
        $beras->refresh();

        $cat = Category::create(['name' => 'Makanan', 'sort_order' => 1]);
        $nasi = Menu::create([
            'category_id' => $cat->id,
            'name' => 'Nasi Putih Pulen',
            'price' => 5000,
            'is_active' => true,
        ]);

        Recipe::create([
            'menu_id' => $nasi->id,
            'ingredient_id' => $beras->id,
            'qty_per_portion' => 200, // 200g per portion
        ]);

        // Sell 3 portions of Nasi (requires 600g beras)
        $sale = $saleService->createSale([
            'channel' => 'offline',
            'subtotal' => 15000,
            'total' => 15000,
            'payment_method' => 'cash',
        ], [
            ['menu_id' => $nasi->id, 'qty' => 3],
        ], $owner);

        $beras->refresh();
        $this->assertEquals(4400, $beras->current_stock); // 5000 - 600 = 4400

        // Verify stock consistency
        $this->assertTrue($stockService->verifyConsistency($beras->id));

        // Void transaction
        $saleService->voidSale($sale, 'Salah input pesanan', $owner);

        $beras->refresh();
        $this->assertEquals(5000, $beras->current_stock); // returned +600

        // Verify stock consistency remains intact
        $this->assertTrue($stockService->verifyConsistency($beras->id));
    }
}
