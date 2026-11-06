<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScreenshotImport extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_NEEDS_REVIEW = 'needs_review';

    public const STATUS_READY_TO_SAVE = 'ready_to_save';

    public const STATUS_SAVED = 'saved';

    public const STATUS_DUPLICATE = 'duplicate';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'file_path',
        'file_hash',
        'status',
        'source',
        'platform',
        'raw_result',
        'confidence',
        'fallback_reason',
        'sale_id',
        'uploaded_by',
    ];

    protected $casts = [
        'raw_result' => 'array',
        'confidence' => 'integer',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
