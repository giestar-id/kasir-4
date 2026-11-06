<?php

namespace App\Services\Ai;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class ExtractionPipeline
{
    public function __construct(
        protected AiApiExtractor $aiApiExtractor,
        protected LocalOcrExtractor $localOcrExtractor,
    ) {}

    /**
     * Process image through Hybrid AI Pipeline:
     * Try Cloud AI API first, fallback to Local OCR on any failure or if unconfigured.
     */
    public function process(string $imagePath): ExtractionResult
    {
        $aiEnabled = filter_var(Setting::get('ai_enabled', '1'), FILTER_VALIDATE_BOOLEAN);
        $apiKey = Setting::get('ai_api_key');

        if (! $aiEnabled) {
            $result = $this->localOcrExtractor->extract($imagePath);
            $result->fallbackReason = 'AI dinonaktifkan di pengaturan, dibaca oleh mesin lokal.';

            return $result;
        }

        if (empty($apiKey)) {
            $result = $this->localOcrExtractor->extract($imagePath);
            $result->fallbackReason = 'API key AI belum diatur, dibaca oleh mesin lokal.';

            return $result;
        }

        try {
            return $this->aiApiExtractor->extract($imagePath);
        } catch (\Throwable $e) {
            Log::warning('AI API extraction failed, falling back to local OCR: '.$e->getMessage());

            $result = $this->localOcrExtractor->extract($imagePath);
            $result->fallbackReason = 'Koneksi AI API terkendala ('.substr($e->getMessage(), 0, 80).'...), dialihkan ke mesin lokal.';

            return $result;
        }
    }
}
