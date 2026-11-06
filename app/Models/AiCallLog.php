<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiCallLog extends Model
{
    protected $fillable = [
        'provider',
        'model',
        'status',
        'latency_ms',
        'error_code',
        'error_message',
        'est_cost',
    ];

    protected $casts = [
        'latency_ms' => 'integer',
        'est_cost' => 'integer',
    ];
}
