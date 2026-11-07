<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Livewire\OnlineOrders\Index as OnlineOrdersIndex;
use App\Livewire\Pos\Cashier;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\Recipe;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\CostingService;
use App\Services\SaleService;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class TransactionBalanceAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected StockService $stockService;

    protected SaleService $saleService;

    protected Ingredient $kopi;

    protected Ingredient $susu;

    protected Menu $kopiSusu;

    protected Menu $espresso;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($this->owner);

        $this->stockService = app(StockService::class);
        $this->saleService = app(SaleService::class);

        // 1. Setup Ingredients
        // Kopi: unit_factor 1000 (1kg = 1000g), Rp200.000 / kg = Rp200 / gram
        $this->kopi = Ingredient::create([
            'name' => 'Biji Kopi Arabika',
            'base_unit' => 'gram',
            'purchase_unit' => 'kg',
            'unit_factor' => 1000,
            'current_stock' => 0,
            'avg_cost_per_base' => 0,
            'min_stock' => 500,
            'is_active' => true,
        ]);
        $this->stockService->recordPurchase(
            $this->kopi->id,
            2.0, // 2 kg
            400000, // Rp400.000 => Rp200/gram
            Carbon::now()->format('Y-m-d H:i:s'),
            'Pembelian biji kopi',
            $this->owner->id
        );

        // Susu: unit_factor 1000 (1L = 1000ml), Rp25.000 / Liter = Rp25 / ml
        $this->susu = Ingredient::create([
            'name' => 'Susu UHT Fresh',
            'base_unit' => 'ml',
            'purchase_unit' => 'liter',
            'unit_factor' => 1000,
            'current_stock' => 0,
            'avg_cost_per_base' => 0,
            'min_stock' => 1000,
            'is_active' => true,
        ]);
        $this->stockService->recordPurchase(
            $this->susu->id,
            5.0, // 5 Liter
            125000, // Rp125.000 => Rp25/ml
            Carbon::now()->format('Y-m-d H:i:s'),
            'Pembelian susu segar',
            $this->owner->id
        );

        // 2. Setup Menus & Recipes
        $category = Category::create(['name' => 'Kopi', 'sort_order' => 1]);

        // Kopi Susu: Price Rp22.000, Recipe: 18g kopi (18 * 200 = 3600) + 120ml susu (120 * 25 = 3000) => HPP = Rp6.600
        $this->kopiSusu = Menu::create([
            'category_id' => $category->id,
            'name' => 'Kopi Susu Gula Aren',
            'price' => 22000,
            'is_active' => true,
        ]);
        Recipe::create([
            'menu_id' => $this->kopiSusu->id,
            'ingredient_id' => $this->kopi->id,
            'qty_per_portion' => 18,
        ]);
        Recipe::create([
            'menu_id' => $this->kopiSusu->id,
            'ingredient_id' => $this->susu->id,
            'qty_per_portion' => 120,
        ]);

        // Espresso: Price Rp15.000, Recipe: 18g kopi (18 * 200 = 3600) => HPP = Rp3.600
        $this->espresso = Menu::create([
            'category_id' => $category->id,
            'name' => 'Espresso Single',
            'price' => 15000,
            'is_active' => true,
        ]);
        Recipe::create([
            'menu_id' => $this->espresso->id,
            'ingredient_id' => $this->kopi->id,
            'qty_per_portion' => 18,
        ]);
    }

    public function test_pos_transaction_math_and_balance_integrity(): void
    {
        $initialKopiStock = (float) $this->kopi->fresh()->current_stock; // 2000g
        $initialSusuStock = (float) $this->susu->fresh()->current_stock; // 5000ml

        // Test POS Cashier component with cart: 2x Kopi Susu (44.000), 1x Espresso (15.000)
        // Subtotal = 59.000. Discount = 5.000. Total = 54.000. Cash Paid = 100.000. Change = 46.000.
        $component = Livewire::test(Cashier::class)
            ->call('addToCart', $this->kopiSusu->id)
            ->call('increaseQty', $this->kopiSusu->id) // 2x Kopi Susu
            ->call('addToCart', $this->espresso->id) // 1x Espresso
            ->assertSet('subtotal', 59000)
            ->set('discountType', 'fixed')
            ->set('discountAmount', 5000)
            ->set('discountReason', 'Promo Grand Opening')
            ->assertSet('discountValue', 5000)
            ->assertSet('total', 54000)
            ->set('paymentMethod', 'cash')
            ->set('paidAmount', 100000)
            ->assertSet('change', 46000)
            ->call('processSale');

        $component->assertHasNoErrors();

        // 1. Verify Sale Record
        $sale = Sale::latest('id')->first();
        $this->assertNotNull($sale);
        $this->assertEquals(Sale::CHANNEL_OFFLINE, $sale->channel);
        $this->assertEquals(59000, $sale->subtotal);
        $this->assertEquals(5000, $sale->discount);
        $this->assertEquals(54000, $sale->total);
        $this->assertEquals(100000, $sale->paid_amount);
        $this->assertEquals(46000, $sale->change_amount);
        $this->assertEquals(Sale::STATUS_COMPLETED, $sale->status);

        // Fundamental Balance Check: paid - change == total
        $this->assertEquals($sale->total, $sale->paid_amount - $sale->change_amount);
        // Subtotal balance: subtotal - discount + platform_adjustment == total
        $this->assertEquals($sale->total, $sale->subtotal - $sale->discount + $sale->platform_adjustment);

        // 2. Verify SaleItems & HPP Snapshot
        $items = $sale->items;
        $this->assertCount(2, $items);

        $kopiSusuItem = $items->firstWhere('menu_id', $this->kopiSusu->id);
        $this->assertEquals(2, $kopiSusuItem->qty);
        $this->assertEquals(22000, $kopiSusuItem->unit_price);
        $this->assertEquals(6600, $kopiSusuItem->unit_hpp);

        $espressoItem = $items->firstWhere('menu_id', $this->espresso->id);
        $this->assertEquals(1, $espressoItem->qty);
        $this->assertEquals(15000, $espressoItem->unit_price);
        $this->assertEquals(3600, $espressoItem->unit_hpp);

        // Total HPP for this sale = (2 * 6600) + (1 * 3600) = 13.200 + 3.600 = 16.800
        $totalHpp = $kopiSusuItem->total_hpp + $espressoItem->total_hpp;
        $this->assertEquals(16800, $totalHpp);

        // 3. Verify Stock Deduction
        // Kopi usage: (2 * 18g) + (1 * 18g) = 54g
        $this->assertEquals($initialKopiStock - 54, (float) $this->kopi->fresh()->current_stock);
        // Susu usage: 2 * 120ml = 240ml
        $this->assertEquals($initialSusuStock - 240, (float) $this->susu->fresh()->current_stock);

        // Verify stock movement mathematical consistency
        $this->assertTrue($this->stockService->verifyConsistency($this->kopi->id));
        $this->assertTrue($this->stockService->verifyConsistency($this->susu->id));
    }

    public function test_online_order_math_and_platform_adjustment_balance(): void
    {
        $initialKopiStock = (float) $this->kopi->fresh()->current_stock;
        $initialSusuStock = (float) $this->susu->fresh()->current_stock;

        // Manual GoFood Order:
        // 2x Kopi Susu (2 * 22.000 = 44.000)
        // Platform price markup / total received: Rp 50.000
        // Platform adjustment = 50.000 - 44.000 = +6.000
        Livewire::test(OnlineOrdersIndex::class)
            ->set('manualChannel', 'gofood')
            ->set('manualExternalId', 'GF-998877')
            ->set('manualOrderTime', Carbon::now()->format('Y-m-d\TH:i'))
            ->set('manualItems', [
                [
                    'menu_id' => $this->kopiSusu->id,
                    'qty' => 2,
                    'price' => 22000,
                ],
            ])
            ->set('manualPlatformTotal', 50000)
            ->assertSet('manualSubtotal', 44000)
            ->assertSet('manualAdjustment', 6000)
            ->call('saveManualOrder')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', 'manual-online-modal');

        $sale = Sale::where('external_order_id', 'GF-998877')->first();
        $this->assertNotNull($sale);
        $this->assertEquals(Sale::CHANNEL_GOFOOD, $sale->channel);
        $this->assertEquals(44000, $sale->subtotal);
        $this->assertEquals(6000, $sale->platform_adjustment);
        $this->assertEquals(50000, $sale->total);
        $this->assertEquals(50000, $sale->paid_amount);
        $this->assertEquals(0, $sale->change_amount);

        // Accounting Equation Balance: subtotal + platform_adjustment == total
        $this->assertEquals($sale->total, $sale->subtotal + $sale->platform_adjustment);

        // Stock deduction: 2 * 18g kopi = 36g, 2 * 120ml susu = 240ml
        $this->assertEquals($initialKopiStock - 36, (float) $this->kopi->fresh()->current_stock);
        $this->assertEquals($initialSusuStock - 240, (float) $this->susu->fresh()->current_stock);
        $this->assertTrue($this->stockService->verifyConsistency($this->kopi->id));
        $this->assertTrue($this->stockService->verifyConsistency($this->susu->id));
    }

    public function test_void_sale_full_reversal_and_accounting_isolation(): void
    {
        $initialKopiStock = (float) $this->kopi->fresh()->current_stock;
        $initialSusuStock = (float) $this->susu->fresh()->current_stock;

        // 1. Create a sale
        $sale = $this->saleService->createSale([
            'channel' => Sale::CHANNEL_OFFLINE,
            'subtotal' => 44000,
            'discount' => 0,
            'total' => 44000,
            'payment_method' => 'cash',
            'paid_amount' => 50000,
            'change_amount' => 6000,
            'ordered_at' => Carbon::now(),
        ], [
            ['menu_id' => $this->kopiSusu->id, 'qty' => 2],
        ], $this->owner);

        // Stock after sale
        $this->assertEquals($initialKopiStock - 36, (float) $this->kopi->fresh()->current_stock);
        $this->assertEquals($initialSusuStock - 240, (float) $this->susu->fresh()->current_stock);

        // 2. Void the sale
        $this->saleService->voidSale($sale, 'Pelanggan membatalkan pesanan', $this->owner);

        $sale->refresh();
        $this->assertEquals(Sale::STATUS_VOID, $sale->status);
        $this->assertNotNull($sale->voided_at);
        $this->assertEquals('Pelanggan membatalkan pesanan', $sale->void_reason);
        $this->assertEquals($this->owner->id, $sale->voided_by);

        // 3. Stock must be returned to 100% exact pre-sale amount!
        $this->assertEquals($initialKopiStock, (float) $this->kopi->fresh()->current_stock);
        $this->assertEquals($initialSusuStock, (float) $this->susu->fresh()->current_stock);
        $this->assertTrue($this->stockService->verifyConsistency($this->kopi->id));
        $this->assertTrue($this->stockService->verifyConsistency($this->susu->id));

        // 4. Non-void scope must exclude this void sale
        $this->assertEquals(0, Sale::nonVoid()->where('id', $sale->id)->count());
    }

    public function test_dashboard_and_reports_multi_channel_balance(): void
    {
        // 1. Offline POS Sale: 2x Kopi Susu = 44.000, HPP = 13.200
        $this->saleService->createSale([
            'channel' => Sale::CHANNEL_OFFLINE,
            'subtotal' => 44000,
            'discount' => 4000,
            'total' => 40000,
            'payment_method' => 'cash',
            'paid_amount' => 40000,
            'ordered_at' => Carbon::today()->setHour(10),
        ], [
            ['menu_id' => $this->kopiSusu->id, 'qty' => 2],
        ], $this->owner);

        // 2. GoFood Sale: 1x Kopi Susu + 1x Espresso = 37.000, Adj = +3000 => Total = 40.000, HPP = 6.600 + 3.600 = 10.200
        $this->saleService->createSale([
            'channel' => Sale::CHANNEL_GOFOOD,
            'external_order_id' => 'GF-AUDIT-1',
            'subtotal' => 37000,
            'platform_adjustment' => 3000,
            'total' => 40000,
            'payment_method' => 'transfer',
            'ordered_at' => Carbon::today()->setHour(12),
        ], [
            ['menu_id' => $this->kopiSusu->id, 'qty' => 1],
            ['menu_id' => $this->espresso->id, 'qty' => 1],
        ], $this->owner);

        // 3. ShopeeFood Sale: 2x Espresso = 30.000, Adj = 0 => Total = 30.000, HPP = 7.200
        $this->saleService->createSale([
            'channel' => Sale::CHANNEL_SHOPEEFOOD,
            'external_order_id' => 'SF-AUDIT-2',
            'subtotal' => 30000,
            'platform_adjustment' => 0,
            'total' => 30000,
            'payment_method' => 'transfer',
            'ordered_at' => Carbon::today()->setHour(14),
        ], [
            ['menu_id' => $this->espresso->id, 'qty' => 2],
        ], $this->owner);

        // 4. Void Sale (Should NOT appear in calculations): Total = 100.000
        $voidedSale = $this->saleService->createSale([
            'channel' => Sale::CHANNEL_OFFLINE,
            'subtotal' => 100000,
            'total' => 100000,
            'payment_method' => 'cash',
            'ordered_at' => Carbon::today()->setHour(15),
        ], [
            ['menu_id' => $this->kopiSusu->id, 'qty' => 4],
        ], $this->owner);
        $this->saleService->voidSale($voidedSale, 'Pesanan Dibatalkan Kasir', $this->owner);

        // Financial Math Totals:
        // Total Omzet = 40.000 + 40.000 + 30.000 = 110.000
        // Total HPP = 13.200 + 10.200 + 7.200 = 30.600
        // Total Laba Bersih = 110.000 - 30.600 = 79.400
        // Channel Split: Offline = 40.000, GoFood = 40.000, ShopeeFood = 30.000. Sum = 110.000.

        // Verify Dashboard
        Livewire::test(Dashboard::class)
            ->call('setPeriod', 'today')
            ->assertViewHas('totalOmzet', 110000)
            ->assertViewHas('totalTransactions', 3)
            ->assertViewHas('grossProfit', 79400)
            ->assertSee('Rp110.000')
            ->assertSee('Rp79.400');

        // Verify Reports
        Livewire::test(ReportsIndex::class)
            ->call('setPeriod', 'today')
            ->assertViewHas('totalOmzet', 110000)
            ->assertViewHas('totalHpp', 30600)
            ->assertViewHas('grossProfit', 79400)
            ->assertSee('Rp110.000')
            ->assertSee('Rp79.400');
    }

    public function test_create_sale_rejects_empty_items_and_supports_custom_prices(): void
    {
        // 1. Must reject sale with 0 items
        $this->expectException(ValidationException::class);
        $this->saleService->createSale([
            'channel' => Sale::CHANNEL_OFFLINE,
            'subtotal' => 0,
            'total' => 0,
        ], [], $this->owner);
    }

    public function test_create_sale_respects_custom_or_platform_item_price(): void
    {
        // Normal price is 22.000, but platform markup price is 25.000
        $sale = $this->saleService->createSale([
            'channel' => Sale::CHANNEL_GOFOOD,
            'external_order_id' => 'GF-CUSTOM-PRICE-1',
            'subtotal' => 25000,
            'platform_adjustment' => 0,
            'total' => 25000,
            'payment_method' => 'transfer',
            'ordered_at' => Carbon::now(),
        ], [
            [
                'menu_id' => $this->kopiSusu->id,
                'qty' => 1,
                'unit_price' => 25000,
            ],
        ], $this->owner);

        $this->assertEquals(25000, $sale->subtotal);
        $this->assertEquals(25000, $sale->total);
        $this->assertEquals(25000, $sale->items->first()->unit_price);
        $this->assertEquals(6600, $sale->items->first()->unit_hpp);
    }

    public function test_cash_underpayment_is_strictly_rejected_at_service_layer(): void
    {
        $this->expectException(ValidationException::class);

        // Subtotal = 22.000, Paid = 20.000 (Underpaid by 2.000)
        $this->saleService->createSale([
            'channel' => Sale::CHANNEL_OFFLINE,
            'subtotal' => 22000,
            'total' => 22000,
            'payment_method' => 'cash',
            'paid_amount' => 20000,
            'ordered_at' => Carbon::now(),
        ], [
            ['menu_id' => $this->kopiSusu->id, 'qty' => 1],
        ], $this->owner);
    }

    public function test_cashless_payment_strictly_enforces_zero_change_and_exact_paid_amount(): void
    {
        // Total = 44.000, Non-cash (QRIS)
        $sale = $this->saleService->createSale([
            'channel' => Sale::CHANNEL_OFFLINE,
            'subtotal' => 44000,
            'total' => 44000,
            'payment_method' => 'qris',
            'paid_amount' => 50000, // Even if caller accidentally passes 50.000
            'ordered_at' => Carbon::now(),
        ], [
            ['menu_id' => $this->kopiSusu->id, 'qty' => 2],
        ], $this->owner);

        // Strictly: paid_amount = total, change_amount = 0, paid - change == total
        $this->assertEquals(44000, $sale->total);
        $this->assertEquals(44000, $sale->paid_amount);
        $this->assertEquals(0, $sale->change_amount);
        $this->assertEquals($sale->total, $sale->paid_amount - $sale->change_amount);
    }

    public function test_hpp_formula_includes_recipe_ingredients_and_extra_packaging_costs(): void
    {
        // Create menu with packaging extra cost:
        // Recipe: 18g Kopi Arabika (18 * 200 = 3600) + Extra Cup & Paper Bag (1500)
        // Total HPP = 3600 + 1500 = Rp 5.100
        $category = Category::firstOrCreate(['name' => 'Kopi']);
        $kopiTakeaway = Menu::create([
            'category_id' => $category->id,
            'name' => 'Kopi Arabika Takeaway Box',
            'price' => 18000,
            'is_active' => true,
        ]);

        Recipe::create([
            'menu_id' => $kopiTakeaway->id,
            'ingredient_id' => $this->kopi->id,
            'qty_per_portion' => 18,
        ]);

        $kopiTakeaway->extraCosts()->create([
            'label' => 'Cup + Straw + Bag',
            'amount' => 1500,
        ]);

        // 1. Verify model calculateHpp()
        $this->assertEquals(5100, $kopiTakeaway->calculateHpp());

        // 2. Verify CostingService breakdown
        $costingService = app(CostingService::class);
        $breakdown = $costingService->getRecipeBreakdown($kopiTakeaway);
        $this->assertEquals(3600, $breakdown['ingredients_cost']);
        $this->assertEquals(1500, $breakdown['extra_cost_total']);
        $this->assertEquals(5100, $breakdown['total_hpp']);
        $this->assertEquals(12900, $breakdown['margin_rp']); // 18000 - 5100

        // 3. Verify Sale records accurate unit_hpp snapshot
        $sale = $this->saleService->createSale([
            'channel' => Sale::CHANNEL_OFFLINE,
            'subtotal' => 36000,
            'total' => 36000,
            'payment_method' => 'cash',
        ], [
            ['menu_id' => $kopiTakeaway->id, 'qty' => 2],
        ], $this->owner);

        $item = $sale->items->first();
        $this->assertEquals(5100, $item->unit_hpp);
        $this->assertEquals(10200, $item->total_hpp);
    }

    public function test_moving_weighted_average_cost_formula_on_consecutive_purchases(): void
    {
        $teh = Ingredient::create([
            'name' => 'Daun Teh Hijau',
            'base_unit' => 'gram',
            'purchase_unit' => 'kg',
            'unit_factor' => 1000,
            'current_stock' => 0,
            'avg_cost_per_base' => 0,
            'min_stock' => 500,
            'is_active' => true,
        ]);

        // Purchase 1: 1 kg (1000g) at Rp 100.000 => Rp 100 / gram
        $this->stockService->recordPurchase(
            $teh->id,
            1.0,
            100000,
            Carbon::now()->format('Y-m-d H:i:s'),
            'Batch 1',
            $this->owner->id
        );
        $teh->refresh();
        $this->assertEquals(1000, (float) $teh->current_stock);
        $this->assertEquals(100, (int) $teh->avg_cost_per_base);
        $this->assertTrue($this->stockService->verifyConsistency($teh->id));

        // Purchase 2: 2 kg (2000g) at Rp 400.000 => Unit cost Rp 200 / gram
        // Old stock = 1000g @ Rp 100 = Rp 100.000
        // New incoming = Rp 400.000
        // Total value = 500.000 / 3000g = 166.666 => Rp 167 / gram
        $this->stockService->recordPurchase(
            $teh->id,
            2.0,
            400000,
            Carbon::now()->format('Y-m-d H:i:s'),
            'Batch 2',
            $this->owner->id
        );
        $teh->refresh();
        $this->assertEquals(3000, (float) $teh->current_stock);
        $this->assertEquals(167, (int) $teh->avg_cost_per_base);
        $this->assertTrue($this->stockService->verifyConsistency($teh->id));

        // Purchase 3: 1 kg (1000g) at Rp 300.000 => Unit cost Rp 300 / gram
        // Old stock = 3000g @ Rp 167 = Rp 501.000
        // New incoming = Rp 300.000
        // Total value = 801.000 / 4000g = 200.25 => Rp 200 / gram
        $this->stockService->recordPurchase(
            $teh->id,
            1.0,
            300000,
            Carbon::now()->format('Y-m-d H:i:s'),
            'Batch 3',
            $this->owner->id
        );
        $teh->refresh();
        $this->assertEquals(4000, (float) $teh->current_stock);
        $this->assertEquals(200, (int) $teh->avg_cost_per_base);
        $this->assertTrue($this->stockService->verifyConsistency($teh->id));
    }

    public function test_stock_consistency_across_full_lifecycle(): void
    {
        // 1. Initial State: verify initial consistency
        $this->assertTrue($this->stockService->verifyConsistency($this->kopi->id));
        $this->assertTrue($this->stockService->verifyConsistency($this->susu->id));

        // 2. POS Sale: deducts 2 portions of Kopi Susu (36g kopi, 240ml susu)
        $sale1 = $this->saleService->createSale([
            'channel' => Sale::CHANNEL_OFFLINE,
            'subtotal' => 44000,
            'total' => 44000,
            'payment_method' => 'cash',
        ], [
            ['menu_id' => $this->kopiSusu->id, 'qty' => 2],
        ], $this->owner);

        $this->assertTrue($this->stockService->verifyConsistency($this->kopi->id));
        $this->assertTrue($this->stockService->verifyConsistency($this->susu->id));

        // 3. Online Order: deducts 1 portion of Kopi Susu (18g kopi, 120ml susu)
        $sale2 = $this->saleService->createSale([
            'channel' => Sale::CHANNEL_GOFOOD,
            'external_order_id' => 'GF-LIFECYCLE-1',
            'subtotal' => 22000,
            'total' => 25000,
            'platform_adjustment' => 3000,
            'payment_method' => 'transfer',
        ], [
            ['menu_id' => $this->kopiSusu->id, 'qty' => 1],
        ], $this->owner);

        $this->assertTrue($this->stockService->verifyConsistency($this->kopi->id));
        $this->assertTrue($this->stockService->verifyConsistency($this->susu->id));

        // 4. Stock Opname Adjustment: Waste due to expired milk
        $susuCurrent = (float) $this->susu->fresh()->current_stock;
        $this->stockService->recordAdjustment(
            $this->susu->id,
            'waste',
            $susuCurrent - 500, // 500ml spoiled
            'Susu basi rusak',
            $this->owner->id
        );
        $this->assertTrue($this->stockService->verifyConsistency($this->susu->id));

        // 5. Void Sale 1: Returned 36g kopi and 240ml susu
        $this->saleService->voidSale($sale1, 'Pembatalan transaksi', $this->owner);
        $this->assertTrue($this->stockService->verifyConsistency($this->kopi->id));
        $this->assertTrue($this->stockService->verifyConsistency($this->susu->id));
    }

    public function test_void_sale_concurrency_double_void_rejection(): void
    {
        $sale = $this->saleService->createSale([
            'channel' => Sale::CHANNEL_OFFLINE,
            'subtotal' => 22000,
            'total' => 22000,
            'payment_method' => 'cash',
        ], [
            ['menu_id' => $this->kopiSusu->id, 'qty' => 1],
        ], $this->owner);

        // First void succeeds
        $this->saleService->voidSale($sale, 'Void pertama', $this->owner);
        $this->assertTrue($sale->fresh()->isVoid());

        // Second void must throw exception
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Transaksi ini sudah dibatalkan sebelumnya.');
        $this->saleService->voidSale($sale, 'Void kedua duplicate', $this->owner);
    }

    public function test_sale_creation_database_rollback_on_failure(): void
    {
        $initialKopiStock = (float) $this->kopi->fresh()->current_stock;
        $initialSalesCount = Sale::count();
        $initialItemsCount = SaleItem::count();
        $initialMovementsCount = StockMovement::count();

        // Create a failing StockService simulator
        $failingStockService = new class extends StockService
        {
            public function deductForSale(Sale $sale): void
            {
                throw new \RuntimeException('Database disk full / stock service failure simulation');
            }
        };

        $saleServiceWithFailure = new SaleService($failingStockService);

        try {
            $saleServiceWithFailure->createSale([
                'channel' => Sale::CHANNEL_OFFLINE,
                'subtotal' => 22000,
                'total' => 22000,
                'payment_method' => 'cash',
                'paid_amount' => 22000,
            ], [
                ['menu_id' => $this->kopiSusu->id, 'qty' => 1],
            ], $this->owner);

            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Database disk full / stock service failure simulation', $e->getMessage());
        }

        // Database transaction rollback assertions: Zero partial writes!
        $this->assertEquals($initialSalesCount, Sale::count(), 'Sale record must NOT be persisted if deduction fails.');
        $this->assertEquals($initialItemsCount, SaleItem::count(), 'SaleItem records must be rolled back.');
        $this->assertEquals($initialMovementsCount, StockMovement::count(), 'StockMovements must be rolled back.');
        $this->assertEquals($initialKopiStock, (float) $this->kopi->fresh()->current_stock, 'Ingredient stock must not be modified.');
    }

    public function test_void_sale_database_rollback_on_failure(): void
    {
        $sale = $this->saleService->createSale([
            'channel' => Sale::CHANNEL_OFFLINE,
            'subtotal' => 22000,
            'total' => 22000,
            'payment_method' => 'cash',
            'paid_amount' => 22000,
        ], [
            ['menu_id' => $this->kopiSusu->id, 'qty' => 1],
        ], $this->owner);

        $postSaleKopiStock = (float) $this->kopi->fresh()->current_stock;
        $postSaleMovementsCount = StockMovement::count();
        $postSaleAuditLogsCount = AuditLog::count();

        // Create failing StockService simulator for returnForVoid
        $failingVoidStockService = new class extends StockService
        {
            public function returnForVoid(Sale $sale, ?string $reason, ?int $userId = null): void
            {
                throw new \RuntimeException('Deadlock or failure during void stock return');
            }
        };

        $saleServiceVoidFailure = new SaleService($failingVoidStockService);

        try {
            $saleServiceVoidFailure->voidSale($sale, 'Alasan void gagal', $this->owner);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Deadlock or failure during void stock return', $e->getMessage());
        }

        // Assert sale status was NOT changed to void
        $saleFresh = $sale->fresh();
        $this->assertEquals(Sale::STATUS_COMPLETED, $saleFresh->status, 'Sale status must remain completed if void fails.');
        $this->assertNull($saleFresh->voided_at, 'voided_at must remain null on rollback.');
        $this->assertNull($saleFresh->void_reason, 'void_reason must remain null on rollback.');

        // Assert audit log and stock movement were rolled back
        $this->assertEquals($postSaleAuditLogsCount, AuditLog::count(), 'Audit log must not be committed on void failure.');
        $this->assertEquals($postSaleMovementsCount, StockMovement::count(), 'Void stock movement must not be committed on void failure.');
        $this->assertEquals($postSaleKopiStock, (float) $this->kopi->fresh()->current_stock, 'Stock must remain unchanged.');
    }

    public function test_multi_item_same_ingredient_atomic_grouping_and_hpp_snapshot(): void
    {
        $kopiBefore = (float) $this->kopi->fresh()->current_stock;
        $susuBefore = (float) $this->susu->fresh()->current_stock;

        // Order contains:
        // - 2 portions of Kopi Susu (each: 18g kopi + 120ml susu) => 36g kopi, 240ml susu. unit_hpp = 6600
        // - 3 portions of Espresso (each: 18g kopi + 0ml susu) => 54g kopi, 0ml susu. unit_hpp = 3600
        // Total expected deduction: 36g + 54g = 90g kopi; 240ml susu.
        $sale = $this->saleService->createSale([
            'channel' => Sale::CHANNEL_OFFLINE,
            'subtotal' => (2 * 22000) + (3 * 15000), // 44000 + 45000 = 89000
            'total' => 89000,
            'payment_method' => 'cash',
            'paid_amount' => 100000,
        ], [
            ['menu_id' => $this->kopiSusu->id, 'qty' => 2],
            ['menu_id' => $this->espresso->id, 'qty' => 3],
        ], $this->owner);

        // 1. Check line item HPP snapshots
        $items = $sale->items()->orderBy('menu_id')->get();
        $this->assertCount(2, $items);
        $this->assertEquals(6600, $items[0]->unit_hpp);
        $this->assertEquals(2, $items[0]->qty);
        $this->assertEquals(13200, $items[0]->total_hpp);

        $this->assertEquals(3600, $items[1]->unit_hpp);
        $this->assertEquals(3, $items[1]->qty);
        $this->assertEquals(10800, $items[1]->total_hpp);

        // Total HPP for this sale = 13200 + 10800 = 24000
        $this->assertEquals(24000, $items->sum('total_hpp'));

        // 2. Check ingredient stocks: Exactly 90g kopi and 240ml susu deducted
        $this->assertEquals($kopiBefore - 90, (float) $this->kopi->fresh()->current_stock);
        $this->assertEquals($susuBefore - 240, (float) $this->susu->fresh()->current_stock);

        // 3. Check stock movements: Exactly ONE consolidated movement for kopi and ONE for susu
        $movements = StockMovement::where('ref_type', 'sale')
            ->where('ref_id', $sale->id)
            ->get();
        $this->assertCount(2, $movements, 'Stock deductions for same ingredient must be atomically consolidated into one movement.');

        $kopiMovement = $movements->firstWhere('ingredient_id', $this->kopi->id);
        $this->assertEquals(-90, (float) $kopiMovement->qty_change);

        $susuMovement = $movements->firstWhere('ingredient_id', $this->susu->id);
        $this->assertEquals(-240, (float) $susuMovement->qty_change);

        // 4. Verify consistency
        $this->assertTrue($this->stockService->verifyConsistency($this->kopi->id));
        $this->assertTrue($this->stockService->verifyConsistency($this->susu->id));
    }
}
