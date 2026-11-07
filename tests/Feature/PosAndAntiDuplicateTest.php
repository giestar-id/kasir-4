<?php

namespace Tests\Feature;

use App\Livewire\Pos\Cashier;
use App\Models\Category;
use App\Models\Menu;
use App\Models\User;
use App\Services\SaleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PosAndAntiDuplicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_sequential_transaction_numbering(): void
    {
        $saleService = app(SaleService::class);
        $date = Carbon::parse('2026-11-05');

        $num1 = $saleService->generateTransactionNumber($date);
        $this->assertEquals('TRX-20261105-0001', $num1);

        $owner = User::factory()->create(['role' => 'owner']);
        $cat = Category::create(['name' => 'Minuman', 'sort_order' => 1]);
        $teh = Menu::create(['category_id' => $cat->id, 'name' => 'Es Teh Manis', 'price' => 4000]);

        $sale1 = $saleService->createSale([
            'channel' => 'offline',
            'ordered_at' => $date,
        ], [['menu_id' => $teh->id, 'qty' => 1]], $owner);

        $this->assertEquals('TRX-20261105-0001', $sale1->number);

        $sale2 = $saleService->createSale([
            'channel' => 'offline',
            'ordered_at' => $date,
        ], [['menu_id' => $teh->id, 'qty' => 1]], $owner);

        $this->assertEquals('TRX-20261105-0002', $sale2->number);
    }

    public function test_idempotency_key_prevents_duplicate(): void
    {
        $saleService = app(SaleService::class);
        $owner = User::factory()->create(['role' => 'owner']);
        $cat = Category::create(['name' => 'Minuman', 'sort_order' => 1]);
        $teh = Menu::create(['category_id' => $cat->id, 'name' => 'Es Jeruk', 'price' => 5000]);

        $idempotencyKey = 'cart-uuid-12345';

        $sale1 = $saleService->createSale([
            'channel' => 'offline',
            'idempotency_key' => $idempotencyKey,
        ], [['menu_id' => $teh->id, 'qty' => 1]], $owner);

        $sale2 = $saleService->createSale([
            'channel' => 'offline',
            'idempotency_key' => $idempotencyKey,
        ], [['menu_id' => $teh->id, 'qty' => 1]], $owner);

        $this->assertEquals($sale1->id, $sale2->id);
    }

    public function test_external_order_id_duplicate_rejection(): void
    {
        $saleService = app(SaleService::class);
        $owner = User::factory()->create(['role' => 'owner']);
        $cat = Category::create(['name' => 'Makanan', 'sort_order' => 1]);
        $menu = Menu::create(['category_id' => $cat->id, 'name' => 'Bebek Goreng', 'price' => 35000]);

        $saleService->createSale([
            'channel' => 'gofood',
            'external_order_id' => 'GF-999888',
        ], [['menu_id' => $menu->id, 'qty' => 1]], $owner);

        $this->expectException(ValidationException::class);

        $saleService->createSale([
            'channel' => 'gofood',
            'external_order_id' => 'GF-999888',
        ], [['menu_id' => $menu->id, 'qty' => 1]], $owner);
    }

    public function test_different_channels_allow_same_external_order_id(): void
    {
        $saleService = app(SaleService::class);
        $owner = User::factory()->create(['role' => 'owner']);
        $cat = Category::create(['name' => 'Makanan', 'sort_order' => 1]);
        $menu = Menu::create(['category_id' => $cat->id, 'name' => 'Bebek Goreng', 'price' => 35000]);

        $saleGofood = $saleService->createSale([
            'channel' => 'gofood',
            'external_order_id' => 'ORD-12345',
        ], [['menu_id' => $menu->id, 'qty' => 1]], $owner);

        $saleShopee = $saleService->createSale([
            'channel' => 'shopeefood',
            'external_order_id' => 'ORD-12345',
        ], [['menu_id' => $menu->id, 'qty' => 1]], $owner);

        $this->assertNotEquals($saleGofood->id, $saleShopee->id);
        $this->assertEquals('gofood', $saleGofood->channel);
        $this->assertEquals('shopeefood', $saleShopee->channel);
        $this->assertEquals('ORD-12345', $saleGofood->external_order_id);
        $this->assertEquals('ORD-12345', $saleShopee->external_order_id);
    }

    public function test_offline_channel_allows_empty_external_order_id_multiple_times(): void
    {
        $saleService = app(SaleService::class);
        $owner = User::factory()->create(['role' => 'owner']);
        $cat = Category::create(['name' => 'Minuman', 'sort_order' => 1]);
        $menu = Menu::create(['category_id' => $cat->id, 'name' => 'Kopi Hitam', 'price' => 8000]);

        $sale1 = $saleService->createSale([
            'channel' => 'offline',
            'external_order_id' => null,
        ], [['menu_id' => $menu->id, 'qty' => 1]], $owner);

        $sale2 = $saleService->createSale([
            'channel' => 'offline',
            'external_order_id' => null,
        ], [['menu_id' => $menu->id, 'qty' => 1]], $owner);

        $this->assertNotEquals($sale1->id, $sale2->id);
        $this->assertNull($sale1->external_order_id);
        $this->assertNull($sale2->external_order_id);
    }

    public function test_livewire_pos_cashier_anti_duplicate_protection(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier', 'is_active' => true]);
        $this->actingAs($cashier);

        $cat = Category::create(['name' => 'Minuman', 'sort_order' => 1]);
        $menu = Menu::create(['category_id' => $cat->id, 'name' => 'Es Cendol', 'price' => 12000, 'is_active' => true]);

        $component = Livewire::test(Cashier::class)
            ->call('addToCart', $menu->id)
            ->set('paymentMethod', 'cash')
            ->set('paidAmount', 12000);

        $capturedKey = $component->get('idempotencyKey');
        $this->assertNotEmpty($capturedKey);

        // First click
        $component->call('processSale');
        $this->assertDatabaseCount('sales', 1);

        // Simulate duplicate submission with same idempotency key directly on service
        $saleService = app(SaleService::class);
        $sale2 = $saleService->createSale([
            'channel' => 'offline',
            'idempotency_key' => $capturedKey,
        ], [['menu_id' => $menu->id, 'qty' => 1]], $cashier);

        // Still only 1 sale in DB
        $this->assertDatabaseCount('sales', 1);
        $this->assertEquals($capturedKey, $sale2->idempotency_key);
    }
}
