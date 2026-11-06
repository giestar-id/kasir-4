<?php

namespace App\Services;

use App\Models\Menu;

class CostingService
{
    /**
     * Calculate HPP (Cost of Goods Sold) for a menu.
     */
    public function calculateMenuHpp(Menu $menu): int
    {
        return $menu->calculateHpp();
    }

    /**
     * Detailed breakdown of ingredients cost for a menu.
     */
    public function getRecipeBreakdown(Menu $menu): array
    {
        $breakdown = [];
        $recipes = $menu->relationLoaded('recipes')
            ? $menu->recipes
            : $menu->recipes()->with('ingredient')->get();

        $ingredientsCost = 0;
        foreach ($recipes as $recipe) {
            $ingredient = $recipe->ingredient;
            if (! $ingredient) {
                continue;
            }

            $cost = (int) round((float) $recipe->qty_per_portion * (float) $ingredient->avg_cost_per_base);
            $ingredientsCost += $cost;

            $breakdown[] = [
                'ingredient_id' => $ingredient->id,
                'ingredient_name' => $ingredient->name,
                'qty' => (float) $recipe->qty_per_portion,
                'base_unit' => $ingredient->base_unit,
                'avg_cost_per_base' => (int) $ingredient->avg_cost_per_base,
                'subtotal_cost' => $cost,
            ];
        }

        $extraCostsCollection = $menu->relationLoaded('extraCosts')
            ? $menu->extraCosts
            : $menu->extraCosts()->get();

        $extraCosts = $extraCostsCollection->map(function ($ec) {
            return [
                'id' => $ec->id,
                'label' => $ec->label,
                'amount' => (int) $ec->amount,
            ];
        })->toArray();

        $totalExtraCost = array_sum(array_column($extraCosts, 'amount'));
        $totalHpp = $ingredientsCost + $totalExtraCost;

        $marginRp = $menu->price - $totalHpp;
        $marginPct = $menu->price > 0 ? round(($marginRp / $menu->price) * 100, 1) : 0;

        return [
            'ingredients' => $breakdown,
            'extra_costs' => $extraCosts,
            'ingredients_cost' => $ingredientsCost,
            'extra_cost_total' => $totalExtraCost,
            'total_hpp' => $totalHpp,
            'selling_price' => (int) $menu->price,
            'margin_rp' => $marginRp,
            'margin_pct' => $marginPct,
            'is_complete' => count($breakdown) > 0,
        ];
    }
}
