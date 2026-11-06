<?php

namespace App\Services\Ai;

interface OrderExtractor
{
    /**
     * Extract structured order data from image path.
     */
    public function extract(string $imagePath): ExtractionResult;
}
