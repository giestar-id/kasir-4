<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sale extends Model
{
    public const STATUS_COMPLETED = 'completed';

    public const STATUS_VOID = 'void';

    public const CHANNEL_OFFLINE = 'offline';

    public const CHANNEL_GOFOOD = 'gofood';

    public const CHANNEL_SHOPEEFOOD = 'shopeefood';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_AI_API = 'ai_api';

    public const SOURCE_AI_LOCAL = 'ai_local';

    protected $fillable = [
        'number',
        'channel',
        'external_order_id',
        'status',
        'subtotal',
        'discount',
        'discount_type',
        'discount_reason',
        'platform_adjustment',
        'total',
        'payment_method',
        'paid_amount',
        'change_amount',
        'ordered_at',
        'source',
        'idempotency_key',
        'voided_at',
        'void_reason',
        'voided_by',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'integer',
        'discount' => 'integer',
        'platform_adjustment' => 'integer',
        'total' => 'integer',
        'paid_amount' => 'integer',
        'change_amount' => 'integer',
        'ordered_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function screenshotImport(): HasOne
    {
        return $this->hasOne(ScreenshotImport::class);
    }

    public function isVoid(): bool
    {
        return $this->status === self::STATUS_VOID;
    }

    public function scopeNonVoid(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_VOID);
    }
}
