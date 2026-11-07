<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Categories\Index;
use App\Livewire\Dashboard;
use App\Livewire\Employees\Index as EmployeesIndex;
use App\Livewire\Ingredients\Index as IngredientsIndex;
use App\Livewire\Menu\Index as MenuIndex;
use App\Livewire\OnlineOrders\Index as OnlineOrdersIndex;
use App\Livewire\Pos\Cashier;
use App\Livewire\Purchases\Index as PurchasesIndex;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Livewire\Settings\Index as SettingsIndex;
use App\Livewire\StockOpname\Index as StockOpnameIndex;
use App\Livewire\Transactions\History as TransactionsHistory;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\Sale;
use App\Models\ScreenshotImport;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class FrontendLivewireButtonsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_buttons_cart_and_payment_modal_interactions(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier', 'can_give_discount' => true]);
        $this->actingAs($cashier);

        $cat1 = Category::create(['name' => 'Minuman', 'sort_order' => 1]);
        $cat2 = Category::create(['name' => 'Makanan', 'sort_order' => 2]);

        $menu1 = Menu::create([
            'category_id' => $cat1->id,
            'name' => 'Es Kopi Susu',
            'price' => 18000,
            'is_active' => true,
        ]);

        $menu2 = Menu::create([
            'category_id' => $cat2->id,
            'name' => 'Roti Bakar Cokelat',
            'price' => 15000,
            'is_active' => true,
        ]);

        // 1. Initial State & Category Filter Buttons
        $component = Livewire::test(Cashier::class)
            ->assertSee('Es Kopi Susu')
            ->assertSee('Roti Bakar Cokelat')
            ->set('selectedCategory', $cat1->id)
            ->assertSee('Es Kopi Susu')
            ->assertDontSee('Roti Bakar Cokelat')
            ->set('selectedCategory', null)
            ->assertSee('Roti Bakar Cokelat');

        // 2. Add to Cart Button Interactions
        $component->call('addToCart', $menu1->id)
            ->assertCount('cart', 1)
            ->assertSeeHtml('Rp18.000');

        // Increment Quantity Button (+)
        $component->call('increaseQty', $menu1->id);
        $this->assertEquals(2, $component->get('cart')[$menu1->id]['qty']);

        // Decrement Quantity Button (-)
        $component->call('decreaseQty', $menu1->id);
        $this->assertEquals(1, $component->get('cart')[$menu1->id]['qty']);

        // Add second item
        $component->call('addToCart', $menu2->id)
            ->assertCount('cart', 2);

        // Remove item button (trash)
        $component->call('removeFromCart', $menu2->id)
            ->assertCount('cart', 1);

        // 3. Clear Cart Button ("Kosongkan")
        $component->call('clearCart')
            ->assertCount('cart', 0);

        // 4. Payment Flow & Modal Buttons
        $component->call('addToCart', $menu1->id); // Subtotal 18.000
        $this->assertEquals(18000, $component->get('subtotal'));

        // Payment Method Selection buttons
        $component->set('paymentMethod', 'qris')
            ->assertSet('paymentMethod', 'qris')
            ->set('paymentMethod', 'cash')
            ->assertSet('paymentMethod', 'cash');

        // Discount buttons: fixed Rp vs percent %
        $component->set('discountType', 'fixed')
            ->set('discountAmount', 3000)
            ->set('discountReason', 'Promo Karyawan');
        $this->assertEquals(15000, $component->get('total'));

        // Quick Cash Buttons (Uang Pas, Nominal)
        $component->call('setUangPas')
            ->assertSet('paidAmount', 15000);
        $this->assertEquals(0, $component->get('change'));

        $component->call('setNominal', 50000)
            ->assertSet('paidAmount', 50000);
        $this->assertEquals(35000, $component->get('change'));

        // Insufficient Cash Validation
        $component->set('paidAmount', 10000)
            ->call('processSale')
            ->assertHasErrors(['paidAmount']);

        // Valid Payment Submit Button
        $component->set('paidAmount', 20000)
            ->call('processSale')
            ->assertHasNoErrors()
            ->assertCount('cart', 0)
            ->assertDispatched('close-modal', 'payment-modal')
            ->assertDispatched('open-modal', 'receipt-modal')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('sales', [
            'payment_method' => 'cash',
            'subtotal' => 18000,
            'discount' => 3000,
            'total' => 15000,
            'paid_amount' => 20000,
            'change_amount' => 5000,
        ]);
    }

    public function test_dashboard_period_buttons_and_custom_modal(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        $component = Livewire::test(Dashboard::class)
            ->assertSee('Dashboard')
            ->assertSee('Hari Ini')
            ->assertSee('7 Hari')
            ->assertSee('Bulan Ini')
            ->assertSee('Rentang Kustom');

        // 1. Period switcher buttons
        $component->call('setPeriod', 'today')
            ->assertSet('period', 'today')
            ->assertSet('startDate', Carbon::today()->format('Y-m-d'))
            ->assertSet('endDate', Carbon::today()->format('Y-m-d'));

        $component->call('setPeriod', '7_days')
            ->assertSet('period', '7_days')
            ->assertSet('startDate', Carbon::today()->subDays(6)->format('Y-m-d'))
            ->assertSet('endDate', Carbon::today()->format('Y-m-d'));

        $component->call('setPeriod', 'month')
            ->assertSet('period', 'month')
            ->assertSet('startDate', Carbon::today()->startOfMonth()->format('Y-m-d'))
            ->assertSet('endDate', Carbon::today()->format('Y-m-d'));

        // 2. Custom Date Modal Button & Submit
        $component->call('openCustomDateModal')
            ->assertDispatched('open-modal', 'custom-date-modal')
            ->set('customStartDate', '2026-03-01')
            ->set('customEndDate', '2026-03-15')
            ->call('applyCustomPeriod')
            ->assertSet('period', 'custom')
            ->assertSet('startDate', '2026-03-01')
            ->assertSet('endDate', '2026-03-15')
            ->assertDispatched('close-modal', 'custom-date-modal');

        // 3. Custom Date Modal Auto-Swap Inverted Dates
        $component->set('customStartDate', '2026-03-20')
            ->set('customEndDate', '2026-03-10')
            ->call('applyCustomPeriod')
            ->assertSet('startDate', '2026-03-10')
            ->assertSet('endDate', '2026-03-20');
    }

    public function test_transactions_history_filter_buttons_and_void_modal(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        $sale1 = Sale::create([
            'number' => 'TRX-20260101-0001',
            'channel' => 'offline',
            'status' => 'completed',
            'ordered_at' => Carbon::today(),
            'subtotal' => 20000,
            'discount' => 0,
            'total' => 20000,
            'paid_amount' => 20000,
            'change_amount' => 0,
            'payment_method' => 'cash',
            'idempotency_key' => (string) Str::uuid(),
            'user_id' => $owner->id,
        ]);

        $sale1->items()->create([
            'menu_name_snapshot' => 'Kopi Susu Gula Aren',
            'qty' => 1,
            'unit_price' => 20000,
            'unit_hpp' => 8000,
        ]);

        $sale2 = Sale::create([
            'number' => 'TRX-20260101-0002',
            'channel' => 'gofood',
            'status' => 'completed',
            'ordered_at' => Carbon::today(),
            'subtotal' => 35000,
            'discount' => 0,
            'total' => 35000,
            'paid_amount' => 35000,
            'change_amount' => 0,
            'payment_method' => 'qris',
            'idempotency_key' => (string) Str::uuid(),
            'user_id' => $owner->id,
        ]);

        $sale2->items()->create([
            'menu_name_snapshot' => 'Croissant Cokelat Crispy',
            'qty' => 2,
            'unit_price' => 17500,
            'unit_hpp' => 6000,
        ]);

        // 1. Channel Filter & Search (by number and item name)
        $component = Livewire::test(TransactionsHistory::class)
            ->assertSee('TRX-20260101-0001')
            ->assertSee('TRX-20260101-0002')
            ->assertSee('Kopi Susu Gula Aren')
            ->assertSee('Croissant Cokelat Crispy')
            ->set('channelFilter', 'gofood')
            ->assertDontSee('TRX-20260101-0001')
            ->assertSee('TRX-20260101-0002')
            ->set('search', '0001')
            ->assertDontSee('TRX-20260101-0002')
            // Search by Item/Barang name across channels
            ->set('channelFilter', 'all')
            ->set('search', 'Kopi Susu')
            ->assertSee('TRX-20260101-0001')
            ->assertDontSee('TRX-20260101-0002')
            ->set('search', 'Croissant')
            ->assertSee('TRX-20260101-0002')
            ->assertDontSee('TRX-20260101-0001');

        // Reset Filter Button
        $component->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('channelFilter', 'all')
            ->assertSee('TRX-20260101-0001')
            ->assertSee('TRX-20260101-0002');

        // 2. Detail Modal Button & Item Verification
        $component->call('openDetailModal', $sale1->id)
            ->assertSet('selectedSaleId', $sale1->id)
            ->assertDispatched('open-modal', 'detail-sale-modal')
            ->assertSee('Daftar Barang / Menu Yang Dibeli')
            ->assertSee('Kopi Susu Gula Aren');

        // 3. Void Modal Buttons & Validation
        $component->call('openVoidModal', $sale1->id)
            ->assertSet('voidSaleId', $sale1->id)
            ->assertDispatched('open-modal', 'void-sale-modal')
            ->set('voidReason', '')
            ->call('executeVoid')
            ->assertHasErrors(['voidReason'])
            ->set('voidReason', 'Pelanggan salah pesan dan minta refund')
            ->call('executeVoid')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', 'void-sale-modal')
            ->assertDispatched('toast');

        $this->assertEquals('void', $sale1->fresh()->status);
    }

    public function test_ingredients_buttons_modals_and_crud(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        // 1. Open Create Modal Button
        $component = Livewire::test(IngredientsIndex::class)
            ->call('openCreateModal')
            ->assertDispatched('open-modal', 'ingredient-form-modal')
            ->assertSet('baseUnit', 'gram')
            ->assertSet('purchaseUnit', 'kg');

        // Form Validation on save
        $component->set('ingredientName', '')
            ->call('saveIngredient')
            ->assertHasErrors(['ingredientName']);

        // Save successfully
        $component->set('ingredientName', 'Biji Kopi Arabika')
            ->set('baseUnit', 'gram')
            ->set('purchaseUnit', 'kg')
            ->set('unitFactor', 1000)
            ->set('minStock', 500)
            ->call('saveIngredient')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', 'ingredient-form-modal')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('ingredients', ['name' => 'Biji Kopi Arabika']);
        $ing = Ingredient::where('name', 'Biji Kopi Arabika')->first();

        // 2. Edit Modal Button
        $component->call('openEditModal', $ing->id)
            ->assertSet('editingIngredientId', $ing->id)
            ->assertSet('ingredientName', 'Biji Kopi Arabika')
            ->assertDispatched('open-modal', 'ingredient-form-modal')
            ->set('ingredientName', 'Biji Kopi Arabika Gayo')
            ->call('saveIngredient')
            ->assertDispatched('close-modal', 'ingredient-form-modal');

        $this->assertDatabaseHas('ingredients', ['name' => 'Biji Kopi Arabika Gayo']);

        // 3. Stock History Modal Button
        $component->call('openStockHistoryModal', $ing->id)
            ->assertSet('selectedIngredientId', $ing->id)
            ->assertDispatched('open-modal', 'stock-history-modal');

        // 4. Delete Ingredient Button
        $component->call('deleteIngredient', $ing->id);
        $this->assertDatabaseMissing('ingredients', ['id' => $ing->id]);
    }

    public function test_menu_buttons_recipe_modal_and_extra_costs(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        $cat = Category::create(['name' => 'Minuman', 'sort_order' => 1]);
        $ing = Ingredient::create([
            'name' => 'Susu UHT Full Cream',
            'base_unit' => 'ml',
            'purchase_unit' => 'liter',
            'unit_factor' => 1000,
            'current_stock' => 5000,
            'min_stock' => 1000,
            'avg_cost_per_base' => 18, // Rp18 / ml
            'is_active' => true,
        ]);

        // 1. Create Menu Button
        $component = Livewire::test(MenuIndex::class)
            ->call('openCreateModal')
            ->assertDispatched('open-modal', 'menu-form-modal')
            ->set('menuName', 'Es Latte Creamy')
            ->set('menuCategoryId', $cat->id)
            ->set('menuPrice', 22000)
            ->set('menuAliases', 'Latte, Es Latte, Creamy Latte')
            ->call('saveMenu')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', 'menu-form-modal')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('menus', ['name' => 'Es Latte Creamy', 'price' => 22000]);
        $menu = Menu::where('name', 'Es Latte Creamy')->first();

        // 2. Open Recipe Modal & Dynamic Ingredient Rows (+/- buttons)
        $component->call('openRecipeModal', $menu->id)
            ->assertSet('recipeMenuId', $menu->id)
            ->assertDispatched('open-modal', 'recipe-modal')
            // Add Ingredient button
            ->call('addRecipeIngredientRow')
            ->assertCount('recipeIngredients', 2); // Initialized with 1 empty row, now 2

        // Fill Recipe ingredient row
        $recipeRows = $component->get('recipeIngredients');
        $recipeRows[0]['ingredient_id'] = $ing->id;
        $recipeRows[0]['qty_per_portion'] = 150; // 150 ml * Rp18 = Rp2.700
        // Remove second row
        $component->set('recipeIngredients', $recipeRows)
            ->call('removeRecipeIngredientRow', 1)
            ->assertCount('recipeIngredients', 1);

        // Add Extra Cost button (+ Biaya Tambahan)
        $component->call('addExtraCostRow')
            ->assertCount('recipeExtraCosts', 1);

        $extraRows = $component->get('recipeExtraCosts');
        $extraRows[0]['label'] = 'Cup & Sedotan';
        $extraRows[0]['amount'] = 1200;
        $component->set('recipeExtraCosts', $extraRows);

        // Save Recipe Submit button
        $component->call('saveRecipe')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', 'recipe-modal')
            ->assertDispatched('toast');

        // Recalculated HPP should be 2.700 + 1.200 = 3.900
        $this->assertEquals(3900, $menu->calculateHpp());

        // 3. Delete Menu Modal Buttons
        $component->call('openDeleteModal', $menu->id)
            ->assertSet('deletingMenuId', $menu->id)
            ->assertDispatched('open-modal', 'delete-menu-modal')
            ->call('executeDelete')
            ->assertDispatched('close-modal', 'delete-menu-modal')
            ->assertDispatched('toast');

        $this->assertDatabaseMissing('menus', ['id' => $menu->id]);
    }

    public function test_purchases_buttons_and_dynamic_calculations(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        $ing = Ingredient::create([
            'name' => 'Sirup Karamel',
            'base_unit' => 'ml',
            'purchase_unit' => 'botol',
            'unit_factor' => 750, // 1 botol = 750 ml
            'current_stock' => 0,
            'min_stock' => 500,
            'avg_cost_per_base' => 0,
            'is_active' => true,
        ]);

        $component = Livewire::test(PurchasesIndex::class)
            ->call('openCreateModal')
            ->assertDispatched('open-modal', 'purchase-modal')
            ->set('purchaseIngredientId', $ing->id)
            ->set('qtyPurchaseUnit', 2) // 2 botol = 1500 ml
            ->set('totalCost', 90000) // Rp90.000 / 1500 = Rp60 / ml
            ->set('purchaseDate', Carbon::today()->format('Y-m-d'));

        // Computed dynamic properties
        $this->assertEquals(1500, $component->get('convertedBaseQty'));
        $this->assertEquals(60, $component->get('calculatedUnitCostBase'));

        // Save Purchase Submit Button
        $component->call('savePurchase')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', 'purchase-modal')
            ->assertDispatched('toast');

        $this->assertEquals(1500, $ing->fresh()->current_stock);
        $this->assertEquals(60, $ing->fresh()->avg_cost_per_base);
    }

    public function test_stock_opname_buttons_and_difference_calculation(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        $ing = Ingredient::create([
            'name' => 'Teh Melati',
            'base_unit' => 'gram',
            'purchase_unit' => 'kg',
            'unit_factor' => 1000,
            'current_stock' => 2000,
            'min_stock' => 500,
            'avg_cost_per_base' => 25,
            'is_active' => true,
        ]);

        $component = Livewire::test(StockOpnameIndex::class)
            ->call('openCreateModal')
            ->assertDispatched('open-modal', 'opname-modal')
            ->set('opnameIngredientId', $ing->id)
            ->set('correctionType', 'adjustment')
            ->set('actualStock', 1850) // Selisih: 1850 - 2000 = -150
            ->set('reason', 'Penyusutan dan tumpah saat racik');

        $this->assertEquals(-150, $component->get('difference'));

        $component->call('saveOpname')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', 'opname-modal')
            ->assertDispatched('toast');

        $this->assertEquals(1850, $ing->fresh()->current_stock);
    }

    public function test_online_orders_buttons_and_modals(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        $cat = Category::create(['name' => 'Makanan']);
        $menu = Menu::create([
            'category_id' => $cat->id,
            'name' => 'Mie Goreng Spesial',
            'price' => 20000,
            'is_active' => true,
        ]);

        // 1. Status Filter Tabs Buttons
        $component = Livewire::test(OnlineOrdersIndex::class)
            ->assertSet('statusFilter', 'all')
            ->call('setStatusFilter', 'needs_review')
            ->assertSet('statusFilter', 'needs_review')
            ->call('setStatusFilter', 'saved')
            ->assertSet('statusFilter', 'saved');

        // 2. Manual Input Modal Buttons & Dynamic Items (+/- item)
        $component->call('addManualItem')
            ->assertCount('manualItems', 2);

        $component->call('removeManualItem', 1)
            ->assertCount('manualItems', 1);

        $component->set('manualChannel', 'gofood')
            ->set('manualExternalId', 'GF-998877')
            ->set('manualPlatformTotal', 25000);

        // Fill item
        $manualItems = $component->get('manualItems');
        $manualItems[0]['menu_id'] = $menu->id;
        $manualItems[0]['qty'] = 1;
        $manualItems[0]['price'] = 20000;
        $component->set('manualItems', $manualItems);

        $component->call('saveManualOrder')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', 'manual-online-modal')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('sales', [
            'channel' => 'gofood',
            'external_order_id' => 'GF-998877',
        ]);
    }

    public function test_employees_buttons_and_toggle_actions(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        // 1. Add Employee Modal
        $component = Livewire::test(EmployeesIndex::class)
            ->call('openCreateModal')
            ->assertDispatched('open-modal', 'employee-form-modal')
            ->set('name', 'Karyawan Baru')
            ->set('email', 'karyawan_baru@outlet.id')
            ->set('password', 'password123')
            ->set('can_give_discount', true)
            ->call('saveEmployee')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', 'employee-form-modal')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('users', ['email' => 'karyawan_baru@outlet.id', 'role' => 'cashier']);
        $emp = User::where('email', 'karyawan_baru@outlet.id')->first();

        // 2. Toggle Active Status Button
        $component->call('toggleStatus', $emp->id);
        $this->assertFalse($emp->fresh()->is_active);

        $component->call('toggleStatus', $emp->id);
        $this->assertTrue($emp->fresh()->is_active);

        // 3. Edit Modal Button
        $component->call('openEditModal', $emp->id)
            ->assertSet('editingEmployeeId', $emp->id)
            ->assertSet('name', 'Karyawan Baru')
            ->set('name', 'Karyawan Senior')
            ->set('can_give_discount', false)
            ->call('saveEmployee')
            ->assertDispatched('close-modal', 'employee-form-modal');

        $this->assertEquals('Karyawan Senior', $emp->fresh()->name);
        $this->assertFalse($emp->fresh()->can_give_discount);

        // 4. Change Password Modal Button
        $component->call('openPasswordModal', $emp->id)
            ->assertSet('passwordEmployeeId', $emp->id)
            ->assertDispatched('open-modal', 'employee-password-modal')
            ->set('newEmployeePassword', 'newpass123')
            ->set('confirmEmployeePassword', 'newpass123')
            ->call('changeEmployeePassword')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', 'employee-password-modal')
            ->assertDispatched('toast');

        $this->assertTrue(Hash::check('newpass123', $emp->fresh()->password));

        // 5. Delete Employee Modal Button
        $component->call('openDeleteModal', $emp->id)
            ->assertSet('deletingEmployeeId', $emp->id)
            ->assertDispatched('open-modal', 'employee-delete-modal')
            ->call('deleteEmployee', $emp->id)
            ->assertDispatched('close-modal', 'employee-delete-modal')
            ->assertDispatched('toast');

        $this->assertDatabaseMissing('users', ['id' => $emp->id]);
    }

    public function test_settings_tab_buttons_and_profile_submission(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        // 1. Tab Switcher Buttons
        $component = Livewire::test(SettingsIndex::class)
            ->assertSet('activeTab', 'profile')
            ->set('activeTab', 'security')
            ->assertSet('activeTab', 'security')
            ->set('activeTab', 'ai')
            ->assertSet('activeTab', 'ai')
            ->set('activeTab', 'backup')
            ->assertSet('activeTab', 'backup')
            ->set('activeTab', 'profile');

        // 2. Profile Form Submission Button
        $component->set('storeName', 'Kedai Kopi Mantap')
            ->set('storeAddress', 'Jl. Merdeka No. 45')
            ->set('storePhone', '08123456789')
            ->set('receiptFooter', 'Terima kasih atas kunjungan Anda!')
            ->set('gofoodCommission', '20')
            ->set('shopeefoodCommission', '20')
            ->call('saveProfile')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertEquals('Kedai Kopi Mantap', Setting::get('store_name'));
        $this->assertEquals('Jl. Merdeka No. 45', Setting::get('store_address'));
    }

    public function test_categories_buttons_modals_and_deletion_protection(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        // 1. Open Create Modal Button
        $component = Livewire::test(Index::class)
            ->call('openCreateModal')
            ->assertDispatched('open-modal', 'category-form-modal')
            ->set('categoryName', '')
            ->call('saveCategory')
            ->assertHasErrors(['categoryName'])
            ->set('categoryName', 'Dessert & Manis')
            ->set('sortOrder', 10)
            ->call('saveCategory')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', 'category-form-modal')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('categories', ['name' => 'Dessert & Manis', 'sort_order' => 10]);
        $cat = Category::where('name', 'Dessert & Manis')->first();

        // 2. Open Edit Modal Button
        $component->call('openEditModal', $cat->id)
            ->assertSet('editingCategoryId', $cat->id)
            ->assertSet('categoryName', 'Dessert & Manis')
            ->set('categoryName', 'Aneka Dessert')
            ->call('saveCategory')
            ->assertDispatched('close-modal', 'category-form-modal')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('categories', ['name' => 'Aneka Dessert']);

        // 3. Deletion Protection Button: Cannot delete category if menu attached
        $menu = Menu::create([
            'category_id' => $cat->id,
            'name' => 'Puding Cokelat',
            'price' => 12000,
            'is_active' => true,
        ]);

        $component->call('openDeleteModal', $cat->id)
            ->assertSet('deletingCategoryId', $cat->id)
            ->assertDispatched('open-modal', 'delete-category-modal')
            ->call('executeDelete')
            ->assertDispatched('close-modal', 'delete-category-modal')
            ->assertDispatched('toast');

        // Still exists because menu was attached
        $this->assertDatabaseHas('categories', ['id' => $cat->id]);

        // Remove menu, now deletion succeeds
        $menu->delete();
        $component->call('openDeleteModal', $cat->id)
            ->call('executeDelete')
            ->assertDispatched('close-modal', 'delete-category-modal')
            ->assertDispatched('toast');

        $this->assertDatabaseMissing('categories', ['id' => $cat->id]);
    }

    public function test_reports_period_buttons_tabs_and_csv_export(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        $component = Livewire::test(ReportsIndex::class)
            ->assertSee('Laporan Penjualan')
            ->assertSee('Bulan Ini')
            ->assertSee('Ekspor CSV');

        // 1. Period Switcher Buttons
        $component->call('setPeriod', 'today')
            ->assertSet('period', 'today')
            ->assertSet('startDate', Carbon::today()->format('Y-m-d'))
            ->call('setPeriod', 'week')
            ->assertSet('period', 'week')
            ->call('setPeriod', 'month')
            ->assertSet('period', 'month');

        // 2. Custom Date Modal Buttons
        $component->call('openCustomDateModal')
            ->assertDispatched('open-modal', 'custom-date-modal')
            ->set('customStartDate', '2026-04-01')
            ->set('customEndDate', '2026-04-30')
            ->call('applyCustomPeriod')
            ->assertSet('period', 'custom')
            ->assertSet('startDate', '2026-04-01')
            ->assertSet('endDate', '2026-04-30')
            ->assertDispatched('close-modal', 'custom-date-modal');

        // 3. Tab Switcher Buttons (per_menu, per_channel, per_category, per_ingredient)
        $component->set('activeTab', 'per_channel')
            ->assertSet('activeTab', 'per_channel')
            ->set('activeTab', 'per_category')
            ->assertSet('activeTab', 'per_category')
            ->set('activeTab', 'per_menu')
            ->assertSet('activeTab', 'per_menu');

        // 4. Export CSV Action Button
        $response = $component->call('exportCsv');
        $this->assertNotNull($response);
    }

    public function test_login_buttons_and_one_click_demo_credentials(): void
    {
        $owner = User::factory()->create([
            'email' => 'owner@outlet.id',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $cashier = User::factory()->create([
            'email' => 'kasir@outlet.id',
            'password' => Hash::make('password123'),
            'role' => 'cashier',
            'is_active' => true,
        ]);

        // 1. Check Demo Card & Buttons rendered on login blade
        $component = Livewire::test(Login::class)
            ->assertSee('Akun Demo')
            ->assertSee('owner@outlet.id')
            ->assertSee('kasir@outlet.id')
            ->assertSee('password123');

        // 2. 1-click set Kasir and Login
        $component->set('identifier', 'kasir@outlet.id')
            ->set('password', 'password123')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('pos'));

        $this->assertAuthenticatedAs($cashier);
    }

    public function test_online_orders_ai_confirmation_modal_and_time_adjustment(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        $cat = Category::create(['name' => 'Kopi']);
        $menu = Menu::create([
            'category_id' => $cat->id,
            'name' => 'Kopi Tubruk Mantap',
            'price' => 10000,
            'is_active' => true,
        ]);

        $import = ScreenshotImport::create([
            'file_path' => 'screenshots/test_struk.jpg',
            'file_hash' => 'hash_test_struk_123',
            'status' => ScreenshotImport::STATUS_READY_TO_SAVE,
            'platform' => 'gofood',
            'source' => 'ai_local',
            'uploaded_by' => $owner->id,
            'confidence' => 95,
            'raw_result' => [
                'platform' => 'gofood',
                'external_order_id' => 'GF-112233',
                'order_time' => Carbon::now()->subYear()->format('Y-m-d H:i:s'),
                'total' => 20000,
                'items' => [
                    [
                        'raw_name' => 'Kopi Tubruk',
                        'menu_id' => $menu->id,
                        'name' => 'Kopi Tubruk Mantap',
                        'qty' => 2,
                        'price' => 10000,
                        'is_matched' => true,
                    ],
                ],
            ],
        ]);

        $component = Livewire::test(OnlineOrdersIndex::class)
            ->call('openConfirmModal', $import->id)
            ->assertSet('selectedImportId', $import->id)
            ->assertDispatched('open-modal', 'ai-confirmation-modal')
            ->assertCount('confirmItems', 1);

        // Click "Gunakan Waktu Sekarang" button
        $now = Carbon::now()->format('Y-m-d\TH:i');
        $component->call('useCurrentTime');
        $this->assertEquals($now, $component->get('confirmOrderTime'));

        // Match menu item
        $component->call('onConfirmMenuSelect', 0, $menu->id);
        $this->assertEquals($menu->id, $component->get('confirmItems')[0]['menu_id']);
        $this->assertTrue($component->get('confirmItems')[0]['is_matched']);

        // Click "Simpan ke Penjualan"
        $component->call('saveConfirmedOrder')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', 'ai-confirmation-modal')
            ->assertDispatched('toast');

        $this->assertEquals(ScreenshotImport::STATUS_SAVED, $import->fresh()->status);
        $this->assertNotNull($import->fresh()->sale_id);
    }

    public function test_settings_owner_password_change_and_database_export(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'password' => Hash::make('oldpassword123'),
        ]);
        $this->actingAs($owner);

        $component = Livewire::test(SettingsIndex::class)
            // 1. Password mismatch validation
            ->set('oldPassword', 'oldpassword123')
            ->set('newPassword', 'newpassword12345')
            ->set('confirmPassword', 'differentpassword')
            ->call('changePassword')
            ->assertHasErrors(['confirmPassword']);

        // 2. Wrong old password
        $component->set('oldPassword', 'wrongoldpassword')
            ->set('newPassword', 'newpassword12345')
            ->set('confirmPassword', 'newpassword12345')
            ->call('changePassword')
            ->assertDispatched('toast');

        $this->assertTrue(Hash::check('oldpassword123', $owner->fresh()->password));

        // 3. Successful password change
        $component->set('oldPassword', 'oldpassword123')
            ->set('newPassword', 'newpassword12345')
            ->set('confirmPassword', 'newpassword12345')
            ->call('changePassword')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertTrue(Hash::check('newpassword12345', $owner->fresh()->password));

        // 4. Save AI Settings
        $component->set('aiEnabled', true)
            ->set('aiProvider', 'gemini')
            ->set('aiModel', 'gemini-1.5-flash')
            ->set('aiBudgetLimit', '750000')
            ->call('saveAiSettings')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertEquals('750000', Setting::get('ai_budget_limit'));

        // 5. Export Database Stream
        $export = $component->call('exportDatabase');
        $this->assertNotNull($export);
    }

    public function test_employees_filters_by_status_and_discount(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        $activeDiscount = User::factory()->create([
            'name' => 'Budi Diskon',
            'email' => 'budi@outlet.id',
            'role' => 'cashier',
            'is_active' => true,
            'can_give_discount' => true,
        ]);

        $inactiveNoDiscount = User::factory()->create([
            'name' => 'Siti Nonaktif',
            'email' => 'siti@outlet.id',
            'role' => 'cashier',
            'is_active' => false,
            'can_give_discount' => false,
        ]);

        // Filter status: active
        Livewire::test(EmployeesIndex::class)
            ->assertSee('Budi Diskon')
            ->assertSee('Siti Nonaktif')
            ->set('statusFilter', 'active')
            ->assertSee('Budi Diskon')
            ->assertDontSee('Siti Nonaktif')
            ->set('statusFilter', 'inactive')
            ->assertSee('Siti Nonaktif')
            ->assertDontSee('Budi Diskon')
            // Filter discount: allowed vs not_allowed
            ->set('statusFilter', 'all')
            ->set('discountFilter', 'allowed')
            ->assertSee('Budi Diskon')
            ->assertDontSee('Siti Nonaktif')
            ->set('discountFilter', 'not_allowed')
            ->assertSee('Siti Nonaktif')
            ->assertDontSee('Budi Diskon')
            // Reset filters
            ->call('resetFilters')
            ->assertSet('statusFilter', 'all')
            ->assertSet('discountFilter', 'all')
            ->assertSee('Budi Diskon')
            ->assertSee('Siti Nonaktif');
    }

    public function test_dashboard_chart_metric_toggle(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        Livewire::test(Dashboard::class)
            ->assertSet('chartMetric', 'omzet')
            ->set('chartMetric', 'profit')
            ->assertSet('chartMetric', 'profit')
            ->set('chartMetric', 'omzet')
            ->assertSet('chartMetric', 'omzet');
    }

    public function test_transactions_history_item_summaries_and_multi_item_badge(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        $sale = Sale::create([
            'number' => 'TRX-MULTI-001',
            'channel' => 'offline',
            'status' => 'completed',
            'ordered_at' => Carbon::today(),
            'subtotal' => 60000,
            'discount' => 0,
            'total' => 60000,
            'paid_amount' => 60000,
            'change_amount' => 0,
            'payment_method' => 'cash',
            'idempotency_key' => (string) Str::uuid(),
            'user_id' => $owner->id,
        ]);

        $sale->items()->create(['menu_name_snapshot' => 'Nasi Goreng Ayam', 'qty' => 1, 'unit_price' => 25000, 'unit_hpp' => 10000]);
        $sale->items()->create(['menu_name_snapshot' => 'Es Jeruk Manis', 'qty' => 2, 'unit_price' => 10000, 'unit_hpp' => 3000]);
        $sale->items()->create(['menu_name_snapshot' => 'Kerupuk Kaleng', 'qty' => 3, 'unit_price' => 5000, 'unit_hpp' => 2000]);

        Livewire::test(TransactionsHistory::class)
            ->assertSee('TRX-MULTI-001')
            ->assertSee('1x Nasi Goreng Ayam')
            ->assertSee('2x Es Jeruk Manis')
            // Multi-item counter badge (+1 because 3 items total, first 2 shown)
            ->assertSee('+1')
            // Total portions sum (1 + 2 + 3 = 6)
            ->assertSee('Total 6 porsi/item');
    }
}
