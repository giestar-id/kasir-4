<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuExtraCost extends Model
{
    protected $fillable = [
        'menu_id',
        'label',
        'amount',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }
}
