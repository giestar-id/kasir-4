<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'menu_id',
        'menu_name_snapshot',
        'qty',
        'unit_price',
        'unit_hpp',
        'note',
    ];

    protected $casts = [
        'qty' => 'integer',
        'unit_price' => 'integer',
        'unit_hpp' => 'integer',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function getSubtotalAttribute(): int
    {
        return $this->qty * $this->unit_price;
    }

    public function getTotalHppAttribute(): int
    {
        return $this->qty * $this->unit_hpp;
    }
}
