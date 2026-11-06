<?php

namespace App\Services\Ai;

class ExtractionResult
{
    public function __construct(
        public string $platform = 'gofood',
        public ?string $externalOrderId = null,
        public ?string $orderTime = null,
        public array $items = [],
        public int $total = 0,
        public int $subtotal = 0,
        public int $platformAdjustment = 0,
        public string $source = 'ai_local',
        public ?string $provider = null,
        public int $confidence = 80,
        public ?string $fallbackReason = null,
        public array $warnings = [],
    ) {}

    public function toArray(): array
    {
        return [
            'platform' => $this->platform,
            'external_order_id' => $this->externalOrderId,
            'order_time' => $this->orderTime,
            'items' => $this->items,
            'total' => $this->total,
            'subtotal' => $this->subtotal,
            'platform_adjustment' => $this->platformAdjustment,
            'source' => $this->source,
            'provider' => $this->provider,
            'confidence' => $this->confidence,
            'fallback_reason' => $this->fallbackReason,
            'warnings' => $this->warnings,
        ];
    }
}
