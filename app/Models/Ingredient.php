<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    protected $fillable = [
        'name',
        'base_unit',
        'purchase_unit',
        'unit_factor',
        'avg_cost_per_base',
        'current_stock',
        'min_stock',
        'is_active',
    ];

    protected $casts = [
        'unit_factor' => 'float',
        'avg_cost_per_base' => 'integer',
        'current_stock' => 'float',
        'min_stock' => 'float',
        'is_active' => 'boolean',
    ];

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isNegativeStock(): bool
    {
        return (float) $this->current_stock < 0;
    }

    public function isLowStock(): bool
    {
        return (float) $this->current_stock <= (float) $this->min_stock;
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->isNegativeStock()) {
            return 'negative';
        }
        if ($this->isLowStock()) {
            return 'low';
        }

        return 'safe';
    }
}
