<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuAlias extends Model
{
    protected $fillable = [
        'menu_id',
        'alias',
        'normalized_alias',
    ];

    public static function normalize(string $alias): string
    {
        $cleaned = strtolower(trim($alias));

        return preg_replace('/[^a-z0-9]/', '', $cleaned);
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }
}
