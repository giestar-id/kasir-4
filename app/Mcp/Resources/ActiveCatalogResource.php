<?php

namespace App\Mcp\Resources;

use App\Models\Category;
use App\Models\Menu;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('Katalog Menu Aktif')]
#[Description('Daftar seluruh menu aktif beserta kategori, harga jual, dan status resep untuk referensi cepat AI.')]
#[Uri('pos://catalog/menus')]
#[MimeType('application/json')]
class ActiveCatalogResource extends Resource
{
    public function handle(): Response
    {
        $categories = Category::with(['menus' => function ($q) {
            $q->where('is_active', true)->with(['recipes.ingredient', 'extraCosts']);
        }])->orderBy('sort_order')->get();

        $catalog = $categories->map(fn ($cat) => [
            'category_id' => $cat->id,
            'category_name' => $cat->name,
            'menus' => $cat->menus->map(function (Menu $m) {
                $marginData = $m->margin;

                return [
                    'id' => $m->id,
                    'name' => $m->name,
                    'price' => $m->price,
                    'formatted_price' => 'Rp'.number_format($m->price, 0, ',', '.'),
                    'hpp' => $marginData['hpp'],
                    'formatted_hpp' => 'Rp'.number_format($marginData['hpp'], 0, ',', '.'),
                    'margin_pct' => $marginData['margin_pct'].'%',
                    'recipe_complete' => $marginData['is_complete'],
                ];
            }),
        ]);

        return Response::text(json_encode([
            'total_categories' => $categories->count(),
            'total_active_menus' => Menu::where('is_active', true)->count(),
            'catalog' => $catalog,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
