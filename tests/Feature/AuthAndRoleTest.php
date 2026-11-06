<?php

namespace Tests\Feature;

use App\Livewire\Categories\Index as CategoriesIndex;
use App\Livewire\Ingredients\Index as IngredientsIndex;
use App\Livewire\Menu\Index as MenuIndex;
use App\Livewire\Pos\Cashier as PosCashier;
use App\Livewire\Purchases\Index as PurchasesIndex;
use App\Livewire\Settings\Index as SettingsIndex;
use App\Livewire\StockOpname\Index as StockOpnameIndex;
use App\Livewire\Transactions\History as TransactionsHistory;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthAndRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect(route('login'));
    }

    public function test_cashier_cannot_access_owner_pages(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $this->actingAs($cashier);

        $this->get('/')->assertForbidden();
        $this->get(route('dashboard'))->assertForbidden();
        $this->get(route('menu'))->assertForbidden();
        $this->get(route('categories'))->assertForbidden();
        $this->get(route('purchases'))->assertForbidden();
        $this->get(route('stock-opname'))->assertForbidden();
        $this->get(route('reports'))->assertForbidden();
        $this->get(route('settings'))->assertForbidden();
    }

    public function test_cashier_can_access_operational_pages_only(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $this->actingAs($cashier);

        $this->get(route('pos'))->assertOk();
        $this->get(route('online-orders'))->assertOk();
        $this->get(route('transactions'))->assertOk();
        $this->get(route('ingredients'))->assertOk();
    }

    public function test_owner_can_access_all_pages(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->actingAs($owner);

        $this->get('/')->assertOk();
        $this->get(route('dashboard'))->assertOk();
        $this->get(route('pos'))->assertOk();
        $this->get(route('menu'))->assertOk();
        $this->get(route('categories'))->assertOk();
        $this->get(route('purchases'))->assertOk();
        $this->get(route('stock-opname'))->assertOk();
        $this->get(route('reports'))->assertOk();
        $this->get(route('settings'))->assertOk();
        $this->get(route('online-orders'))->assertOk();
        $this->get(route('transactions'))->assertOk();
        $this->get(route('ingredients'))->assertOk();
    }

    public function test_cashier_cannot_mutate_owner_actions_via_livewire(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $this->actingAs($cashier);

        // 1. Categories mutation
        Livewire::test(CategoriesIndex::class)
            ->assertForbidden();

        // 2. Menu mutation
        Livewire::test(MenuIndex::class)
            ->assertForbidden();

        // 3. Purchases mutation
        Livewire::test(PurchasesIndex::class)
            ->assertForbidden();

        // 4. Stock opname mutation
        Livewire::test(StockOpnameIndex::class)
            ->assertForbidden();

        // 5. Settings mutation
        Livewire::test(SettingsIndex::class)
            ->assertForbidden();
    }

    public function test_cashier_cannot_mutate_ingredients_or_void_sales(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $this->actingAs($cashier);

        // Ingredients page is viewable, but mutation must be 403 Forbidden
        Livewire::test(IngredientsIndex::class)
            ->set('ingredientName', 'Bahan Terlarang')
            ->set('baseUnit', 'gram')
            ->set('purchaseUnit', 'kg')
            ->set('unitFactor', 1000)
            ->set('minStock', 100)
            ->call('saveIngredient')
            ->assertForbidden();

        // Transactions page is viewable, but void must be 403 Forbidden
        $owner = User::factory()->create(['role' => 'owner']);
        $sale = Sale::create([
            'number' => 'TRX-TEST-001',
            'channel' => 'offline',
            'status' => Sale::STATUS_COMPLETED,
            'subtotal' => 20000,
            'discount' => 0,
            'total' => 20000,
            'payment_method' => 'cash',
            'paid_amount' => 20000,
            'change_amount' => 0,
            'ordered_at' => now(),
            'source' => Sale::SOURCE_MANUAL,
            'idempotency_key' => 'idemp-111',
            'user_id' => $owner->id,
        ]);

        Livewire::test(TransactionsHistory::class)
            ->set('voidSaleId', $sale->id)
            ->set('voidReason', 'Kasir coba void')
            ->call('executeVoid')
            ->assertForbidden();

        Livewire::test(TransactionsHistory::class)
            ->call('voidSale', $sale->id, 'Coba void langsung')
            ->assertForbidden();
    }

    public function test_cashier_discount_permission_at_pos(): void
    {
        $cat = Category::create(['name' => 'Makanan', 'sort_order' => 1]);
        $menu = Menu::create([
            'category_id' => $cat->id,
            'name' => 'Ayam Penyet',
            'price' => 20000,
            'is_active' => true,
        ]);

        // 1. Cashier without discount permission
        $cashierNoDiscount = User::factory()->create([
            'role' => 'cashier',
            'can_give_discount' => false,
            'is_active' => true,
        ]);

        $this->actingAs($cashierNoDiscount);

        Livewire::test(PosCashier::class)
            ->call('addToCart', $menu->id)
            ->set('discountAmount', 5000)
            ->set('discountReason', 'Diskon Kasir')
            ->set('paidAmount', 20000)
            ->call('processSale')
            ->assertForbidden();

        // 2. Cashier WITH discount permission
        $cashierWithDiscount = User::factory()->create([
            'role' => 'cashier',
            'can_give_discount' => true,
            'is_active' => true,
        ]);

        $this->actingAs($cashierWithDiscount);

        Livewire::test(PosCashier::class)
            ->call('addToCart', $menu->id)
            ->set('discountAmount', 5000)
            ->set('discountReason', 'Promo Kasir Berizin')
            ->set('paidAmount', 15000)
            ->call('processSale')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertDatabaseHas('sales', [
            'user_id' => $cashierWithDiscount->id,
            'discount' => 5000,
            'total' => 15000,
        ]);

        // 3. Owner can give discount freely
        $owner = User::factory()->create([
            'role' => 'owner',
            'can_give_discount' => false, // even if false, owner always has permission
            'is_active' => true,
        ]);

        $this->actingAs($owner);

        Livewire::test(PosCashier::class)
            ->call('addToCart', $menu->id)
            ->set('discountAmount', 10000)
            ->set('discountReason', 'Diskon Owner')
            ->set('paidAmount', 10000)
            ->call('processSale')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertDatabaseHas('sales', [
            'user_id' => $owner->id,
            'discount' => 10000,
            'total' => 10000,
        ]);
    }
}
