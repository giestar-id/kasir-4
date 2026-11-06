<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuAlias;
use App\Models\Setting;
use App\Services\Ai\ExtractionPipeline;
use App\Services\Ai\LocalOcrExtractor;
use App\Services\Ai\MenuMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HybridAiPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_matcher_with_name_and_alias(): void
    {
        $matcher = app(MenuMatcher::class);
        $cat = Category::create(['name' => 'Makanan', 'sort_order' => 1]);

        $menu = Menu::create([
            'category_id' => $cat->id,
            'name' => 'Ayam Geprek Sambal Bawang',
            'price' => 25000,
            'is_active' => true,
        ]);

        MenuAlias::create([
            'menu_id' => $menu->id,
            'alias' => 'Geprek Bawang',
            'normalized_alias' => MenuAlias::normalize('Geprek Bawang'),
        ]);

        // Exact match
        $res1 = $matcher->match('Ayam Geprek Sambal Bawang');
        $this->assertTrue($res1['is_matched']);
        $this->assertEquals($menu->id, $res1['menu']->id);

        // Alias match
        $res2 = $matcher->match('Geprek Bawang');
        $this->assertTrue($res2['is_matched']);
        $this->assertEquals($menu->id, $res2['menu']->id);

        // Fuzzy match
        $res3 = $matcher->match('Ayam Geprek Bawang Lvl 1');
        $this->assertEquals($menu->id, $res3['menu']?->id);
    }

    public function test_pipeline_falls_back_to_local_when_api_key_empty(): void
    {
        Setting::set('ai_enabled', '1');
        Setting::set('ai_api_key', ''); // Empty key

        $pipeline = app(ExtractionPipeline::class);

        // Create a fake test image file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_img');
        file_put_contents($tempFile, 'dummy-image-content');

        $result = $pipeline->process($tempFile);

        $this->assertEquals('ai_local', $result->source);
        $this->assertStringContainsString('mesin lokal', strtolower($result->fallbackReason));

        @unlink($tempFile);
    }

    public function test_native_windows_ocr_extracts_real_shopee_receipt(): void
    {
        $realImagePath = storage_path('app/private/screenshots/HBDZlF7kdIzMcO9tG4CchgPvYeYTgeTVp5QkG1uD.webp');
        if (! file_exists($realImagePath)) {
            $this->markTestSkipped('Sample image not present');
        }

        $extractor = app(LocalOcrExtractor::class);
        $result = $extractor->extract($realImagePath);

        $this->assertEquals('shopeefood', $result->platform);
        $this->assertEquals('240422DEXY3V7G', $result->externalOrderId);
        $this->assertNotEmpty($result->items);
        $this->assertStringContainsString('CAPACITOR', $result->items[0]['raw_name']);
        $this->assertEquals(45000, $result->total);
        $this->assertFalse($result->items[0]['is_matched']);
    }
}
