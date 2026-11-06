<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'price',
        'is_active',
        'image_path',
        'note',
    ];

    protected $casts = [
        'price' => 'integer',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(MenuAlias::class);
    }

    public function extraCosts(): HasMany
    {
        return $this->hasMany(MenuExtraCost::class);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'recipes')
            ->withPivot('qty_per_portion')
            ->withTimestamps();
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function isRecipeComplete(): bool
    {
        return $this->relationLoaded('recipes')
            ? $this->recipes->isNotEmpty()
            : $this->recipes()->exists();
    }

    public function calculateHpp(): int
    {
        $ingredientsCost = 0;
        $recipes = $this->relationLoaded('recipes')
            ? $this->recipes
            : $this->recipes()->with('ingredient')->get();

        foreach ($recipes as $recipe) {
            $ingredient = $recipe->ingredient;
            if ($ingredient) {
                // qty_per_portion * avg_cost_per_base
                $cost = (float) $recipe->qty_per_portion * (float) $ingredient->avg_cost_per_base;
                $ingredientsCost += (int) round($cost);
            }
        }

        $extraCost = $this->relationLoaded('extraCosts')
            ? (int) $this->extraCosts->sum('amount')
            : (int) $this->extraCosts()->sum('amount');

        return $ingredientsCost + $extraCost;
    }

    public function getMarginAttribute(): array
    {
        $hpp = $this->calculateHpp();
        $marginRp = $this->price - $hpp;
        $marginPct = $this->price > 0 ? round(($marginRp / $this->price) * 100, 1) : 0;

        return [
            'hpp' => $hpp,
            'margin_rp' => $marginRp,
            'margin_pct' => $marginPct,
            'is_complete' => $this->isRecipeComplete(),
        ];
    }
}
