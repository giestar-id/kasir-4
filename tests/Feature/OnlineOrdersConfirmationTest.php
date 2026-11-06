<?php

namespace Tests\Feature;

use App\Livewire\OnlineOrders\Index as OnlineOrdersIndex;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\Recipe;
use App\Models\Sale;
use App\Models\ScreenshotImport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OnlineOrdersConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmation_records_sale_deducts_stock_and_updates_status(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        $cat = Category::create(['name' => 'Minuman', 'sort_order' => 1]);

        $ingredient = Ingredient::create([
            'name' => 'Bubuk Teh',
            'base_unit' => 'gram',
            'purchase_unit' => 'kg',
            'unit_factor' => 1000,
            'current_stock' => 1000,
            'min_stock' => 200,
            'avg_cost_per_base' => 50,
            'is_active' => true,
        ]);

        $menu = Menu::create([
            'category_id' => $cat->id,
            'name' => 'Es Teh Manis',
            'price' => 5000,
            'is_active' => true,
        ]);

        Recipe::create([
            'menu_id' => $menu->id,
            'ingredient_id' => $ingredient->id,
            'qty_per_portion' => 10,
        ]);

        // Create import with parsed OCR result
        $import = ScreenshotImport::create([
            'file_path' => 'screenshots/test.webp',
            'file_hash' => 'dummy_hash_test_123',
            'status' => ScreenshotImport::STATUS_NEEDS_REVIEW,
            'platform' => 'shopeefood',
            'source' => 'ai_local',
            'uploaded_by' => $owner->id,
            'confidence' => 75,
            'raw_result' => [
                'platform' => 'shopeefood',
                'external_order_id' => '240422DEXY3V7G',
                'order_time' => Carbon::now()->format('Y-m-d H:i:s'),
                'total' => 15000,
                'items' => [
                    [
                        'raw_name' => 'Es Teh Manis Jumbo',
                        'qty' => 3,
                        'unit_price' => 5000,
                        'is_matched' => false,
                    ],
                ],
            ],
        ]);

        // 1. Verify table displays price and raw item
        Livewire::test(OnlineOrdersIndex::class)
            ->assertSee('Rp15.000')
            ->assertSee('240422DEXY3V7G')
            ->assertSee('Es Teh Manis Jumbo');

        // 2. Open confirmation modal and select menu
        $component = Livewire::test(OnlineOrdersIndex::class)
            ->call('openConfirmModal', $import->id)
            ->assertSet('confirmExternalId', '240422DEXY3V7G')
            ->assertSet('confirmPlatformTotal', 15000)
            ->call('onConfirmMenuSelect', 0, $menu->id)
            ->call('saveConfirmedOrder')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', 'ai-confirmation-modal')
            ->assertDispatched('toast');

        // 3. Verify Sale was created and recorded into revenue (pemasukan)
        $this->assertDatabaseHas('sales', [
            'channel' => 'shopeefood',
            'external_order_id' => '240422DEXY3V7G',
            'total' => 15000,
            'status' => Sale::STATUS_COMPLETED,
        ]);

        $sale = Sale::where('external_order_id', '240422DEXY3V7G')->first();
        $this->assertNotNull($sale);

        // Verify stock was deducted (1000 - 3 * 10 = 970)
        $this->assertEquals(970, $ingredient->fresh()->current_stock);

        // Verify import record was updated to saved and linked to sale
        $this->assertEquals(ScreenshotImport::STATUS_SAVED, $import->fresh()->status);
        $this->assertEquals($sale->id, $import->fresh()->sale_id);

        // Verify table now links to saved transaction
        Livewire::test(OnlineOrdersIndex::class)
            ->assertSee($sale->number)
            ->assertSee('Tersimpan');
    }
}
