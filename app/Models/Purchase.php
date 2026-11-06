<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    protected $fillable = [
        'ingredient_id',
        'qty_purchase_unit',
        'qty_base',
        'total_cost',
        'unit_cost_per_base',
        'purchased_at',
        'note',
        'user_id',
    ];

    protected $casts = [
        'qty_purchase_unit' => 'float',
        'qty_base' => 'float',
        'total_cost' => 'integer',
        'unit_cost_per_base' => 'integer',
        'purchased_at' => 'datetime',
    ];

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
