<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Categories\Index as CategoriesIndex;
use App\Livewire\Dashboard;
use App\Livewire\Ingredients\Index as IngredientsIndex;
use App\Livewire\Menu\Index as MenuIndex;
use App\Livewire\Pos\Cashier;
use App\Livewire\Purchases\Index as PurchasesIndex;
use App\Livewire\Reports\Index;
use App\Livewire\Settings\Index as SettingsIndex;
use App\Livewire\StockOpname\Index as StockOpnameIndex;
use App\Livewire\Transactions\History;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class LivewirePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_livewire_component(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@outlet.id',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        Livewire::test(Login::class)
            ->set('identifier', 'owner@outlet.id')
            ->set('password', 'password123')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_pos_cashier_livewire_component_flow(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        $cat = Category::create(['name' => 'Makanan', 'sort_order' => 1]);
        $menu = Menu::create([
            'category_id' => $cat->id,
            'name' => 'Nasi Goreng Spesial',
            'price' => 25000,
            'is_active' => true,
        ]);

        Livewire::test(Cashier::class)
            ->call('addToCart', $menu->id)
            ->assertCount('cart', 1)
            ->set('paidAmount', 50000)
            ->call('processSale')
            ->assertCount('cart', 0)
            ->assertDispatched('toast');
    }

    public function test_category_crud_flow(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        // 1. Create
        $component = Livewire::test(CategoriesIndex::class)
            ->call('openCreateModal')
            ->assertDispatched('open-modal', 'category-form-modal')
            ->set('categoryName', 'Snack & Camilan')
            ->set('sortOrder', 5)
            ->call('saveCategory')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', 'category-form-modal')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('categories', ['name' => 'Snack & Camilan', 'sort_order' => 5]);
        $cat = Category::where('name', 'Snack & Camilan')->first();

        // 2. Edit
        $component->call('openEditModal', $cat->id)
            ->assertDispatched('open-modal', 'category-form-modal')
            ->assertSet('categoryName', 'Snack & Camilan')
            ->set('categoryName', 'Camilan Gurih')
            ->call('saveCategory')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('categories', ['id' => $cat->id, 'name' => 'Camilan Gurih']);

        // 3. Delete
        $component->call('openDeleteModal', $cat->id)
            ->assertDispatched('open-modal', 'delete-category-modal')
            ->call('executeDelete')
            ->assertDispatched('close-modal', 'delete-category-modal')
            ->assertDispatched('toast');

        $this->assertDatabaseMissing('categories', ['id' => $cat->id]);
    }

    public function test_menu_livewire_component_creation(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        $cat = Category::create(['name' => 'Minuman', 'sort_order' => 1]);
        $ing = Ingredient::create([
            'name' => 'Bubuk Kopi',
            'base_unit' => 'gram',
            'purchase_unit' => 'kg',
            'unit_factor' => 1000,
            'current_stock' => 5000,
            'min_stock' => 1000,
            'avg_cost_per_base' => 100,
            'is_active' => true,
        ]);

        $component = Livewire::test(MenuIndex::class)
            ->call('openCreateModal')
            ->assertDispatched('open-modal', 'menu-form-modal')
            ->set('menuName', 'Kopi Susu Gula Aren')
            ->set('menuCategoryId', $cat->id)
            ->set('menuPrice', 18000)
            ->set('menuAliases', 'Kopsus, Es Kopi Aren')
            ->call('saveMenu')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', 'menu-form-modal')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('menus', ['name' => 'Kopi Susu Gula Aren', 'price' => 18000]);
        $this->assertDatabaseHas('menu_aliases', ['alias' => 'Kopsus']);
        $menu = Menu::where('name', 'Kopi Susu Gula Aren')->first();

        // Test Recipe modal & save
        $component->call('openRecipeModal', $menu->id)
            ->assertDispatched('open-modal', 'recipe-modal')
            ->set('recipeIngredients', [
                ['ingredient_id' => $ing->id, 'qty_per_portion' => 15],
            ])
            ->set('recipeExtraCosts', [
                ['label' => 'Cup & Straw', 'amount' => 1200],
            ])
            ->call('saveRecipe')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', 'recipe-modal')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('recipes', ['menu_id' => $menu->id, 'ingredient_id' => $ing->id, 'qty_per_portion' => 15]);
        $this->assertDatabaseHas('menu_extra_costs', ['menu_id' => $menu->id, 'amount' => 1200]);
    }

    public function test_ingredient_crud_flow(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        Livewire::test(IngredientsIndex::class)
            ->call('openCreateModal')
            ->assertDispatched('open-modal', 'ingredient-form-modal')
            ->set('ingredientName', 'Biji Kopi Arabika')
            ->set('baseUnit', 'gram')
            ->set('purchaseUnit', 'kg')
            ->set('unitFactor', 1000)
            ->set('minStock', 500)
            ->call('saveIngredient')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', 'ingredient-form-modal')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('ingredients', ['name' => 'Biji Kopi Arabika', 'base_unit' => 'gram']);
    }

    public function test_purchase_crud_flow(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        $ing = Ingredient::create([
            'name' => 'Susu UHT',
            'base_unit' => 'ml',
            'purchase_unit' => 'liter',
            'unit_factor' => 1000,
            'current_stock' => 0,
            'min_stock' => 1000,
            'avg_cost_per_base' => 0,
            'is_active' => true,
        ]);

        Livewire::test(PurchasesIndex::class)
            ->call('openCreateModal')
            ->assertDispatched('open-modal', 'purchase-modal')
            ->set('purchaseIngredientId', $ing->id)
            ->set('qtyPurchaseUnit', 10)
            ->set('totalCost', 180000)
            ->set('purchaseDate', now()->format('Y-m-d'))
            ->call('savePurchase')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', 'purchase-modal')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('purchases', ['ingredient_id' => $ing->id, 'total_cost' => 180000]);
        $this->assertEquals(10000, $ing->fresh()->current_stock);
    }

    public function test_stock_opname_flow(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        $ing = Ingredient::create([
            'name' => 'Gula Pasir',
            'base_unit' => 'gram',
            'purchase_unit' => 'kg',
            'unit_factor' => 1000,
            'current_stock' => 5000,
            'min_stock' => 1000,
            'avg_cost_per_base' => 15,
            'is_active' => true,
        ]);

        Livewire::test(StockOpnameIndex::class)
            ->call('openCreateModal')
            ->assertDispatched('open-modal', 'opname-modal')
            ->set('opnameIngredientId', $ing->id)
            ->set('correctionType', 'adjustment')
            ->set('actualStock', 4800)
            ->set('reason', 'Opname mingguan selisih timbangan')
            ->call('saveOpname')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', 'opname-modal')
            ->assertDispatched('toast');

        $this->assertEquals(4800, $ing->fresh()->current_stock);
    }

    public function test_settings_cashier_crud_flow(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        Livewire::test(SettingsIndex::class)
            ->call('openCreateCashierModal')
            ->assertDispatched('open-modal', 'cashier-form-modal')
            ->set('cashierName', 'Kasir Siang')
            ->set('cashierEmail', 'kasir_siang@outlet.id')
            ->set('cashierPassword', 'password123')
            ->set('cashierCanDiscount', true)
            ->set('cashierIsActive', true)
            ->call('saveCashier')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', 'cashier-form-modal')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('users', ['email' => 'kasir_siang@outlet.id', 'role' => 'cashier']);
    }

    public function test_dashboard_custom_date_filter_modal_and_apply(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        Livewire::test(Dashboard::class)
            ->call('openCustomDateModal')
            ->assertDispatched('open-modal', 'custom-date-modal')
            ->set('customStartDate', '2026-01-01')
            ->set('customEndDate', '2026-01-15')
            ->call('applyCustomPeriod')
            ->assertSet('period', 'custom')
            ->assertSet('startDate', '2026-01-01')
            ->assertSet('endDate', '2026-01-15')
            ->assertDispatched('close-modal', 'custom-date-modal');
    }

    public function test_reports_custom_date_filter_modal_and_apply(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        Livewire::test(Index::class)
            ->call('openCustomDateModal')
            ->assertDispatched('open-modal', 'custom-date-modal')
            ->set('customStartDate', '2026-02-01')
            ->set('customEndDate', '2026-02-10')
            ->call('applyCustomPeriod')
            ->assertSet('period', 'custom')
            ->assertSet('startDate', '2026-02-01')
            ->assertSet('endDate', '2026-02-10')
            ->assertDispatched('close-modal', 'custom-date-modal');
    }

    public function test_transactions_history_filter_and_reset(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        Livewire::test(History::class)
            ->set('search', 'TRX-123')
            ->set('channelFilter', 'gofood')
            ->set('statusFilter', 'completed')
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('channelFilter', 'all')
            ->assertSet('statusFilter', 'all');
    }

    public function test_ingredients_and_menu_filter_reset(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        Livewire::test(IngredientsIndex::class)
            ->set('search', 'Kopi')
            ->set('stockStatusFilter', 'low')
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('stockStatusFilter', 'all');

        Livewire::test(MenuIndex::class)
            ->set('search', 'Espresso')
            ->set('categoryFilter', 1)
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('categoryFilter', null);
    }
}
