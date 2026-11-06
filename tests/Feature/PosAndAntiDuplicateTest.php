<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Menu;
use App\Models\User;
use App\Services\SaleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
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
}
