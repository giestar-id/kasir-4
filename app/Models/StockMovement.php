<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_SALE_USAGE = 'sale_usage';

    public const TYPE_VOID_RETURN = 'void_return';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_WASTE = 'waste';

    protected $fillable = [
        'ingredient_id',
        'type',
        'qty_change',
        'unit_cost',
        'ref_type',
        'ref_id',
        'reason',
        'user_id',
    ];

    protected $casts = [
        'qty_change' => 'float',
        'unit_cost' => 'integer',
        'ref_id' => 'integer',
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
