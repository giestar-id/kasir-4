<?php

namespace App\Services\Ai;

use App\Models\Menu;
use Carbon\Carbon;
use Illuminate\Support\Facades\Process;

class LocalOcrExtractor implements OrderExtractor
{
    public function __construct(
        protected MenuMatcher $menuMatcher
    ) {}

    public function extract(string $imagePath): ExtractionResult
    {
        $rawText = $this->runOcr($imagePath);

        return $this->parseText($rawText);
    }

    /**
     * Run OCR using native Windows Media OCR or Tesseract CLI.
     * ZERO dummy data: returns genuine extracted text or empty string on failure.
     */
    protected function runOcr(string $imagePath): string
    {
        // 1. Try Native Windows OCR if on Windows
        if (PHP_OS_FAMILY === 'Windows') {
            $scriptPath = base_path('app/Services/Ai/ocr_windows.ps1');
            if (file_exists($scriptPath)) {
                try {
                    $process = Process::run([
                        'powershell',
                        '-ExecutionPolicy',
                        'Bypass',
                        '-File',
                        $scriptPath,
                        '-ImagePath',
                        $imagePath,
                    ]);

                    if ($process->successful() && ! empty(trim($process->output()))) {
                        return trim($process->output());
                    }
                } catch (\Throwable $e) {
                    // Windows OCR failed, fallback to tesseract
                }
            }
        }

        // 2. Try calling Tesseract CLI if installed on system
        try {
            $process = Process::run(['tesseract', $imagePath, 'stdout', '-l', 'ind+eng', '--psm', '6']);
            if ($process->successful() && ! empty(trim($process->output()))) {
                return trim($process->output());
            }
        } catch (\Throwable $e) {
            // Tesseract not found or failed
        }

        // Return empty string on failure without any fake simulation string
        return '';
    }

    /**
     * Clean and normalize OCR string for currency amounts.
     * Resolves ambiguous characters commonly misread by OCR (O/0, S/5, l/1, B/8).
     */
    protected function cleanCurrency(string $raw): int
    {
        $normalized = str_replace(['O', 'o', 'D'], '0', $raw);
        $normalized = str_replace(['l', 'I', '|', '!'], '1', $normalized);
        $normalized = str_replace(['S', 's'], '5', $normalized);
        $normalized = str_replace(['B'], '8', $normalized);

        $cleaned = trim($normalized);

        // Pattern: e.g. '45.0' or '45.00' or '35.000'
        if (preg_match('/(\d+)[.,](\d{1,3})/', $cleaned, $m)) {
            $main = (int) $m[1];
            $sub = $m[2];
            if (strlen($sub) === 3) {
                return (int) ($main.$sub);
            }
            if ($main >= 1 && $main < 1000) {
                return $main * 1000;
            }
        }

        $digits = preg_replace('/[^0-9]/', '', $cleaned);
        $val = (int) $digits;

        if ($val >= 10 && $val < 1000) {
            $val *= 1000;
        }

        return $val;
    }

    /**
     * Parse raw OCR text into structured ExtractionResult.
     * Never generates fake menus or random dummy IDs.
     */
    public function parseText(string $text): ExtractionResult
    {
        $text = trim($text);

        // If no text was extracted by OCR
        if (empty($text)) {
            return new ExtractionResult(
                platform: 'gofood',
                externalOrderId: null,
                orderTime: Carbon::now()->format('Y-m-d H:i:s'),
                items: [],
                total: 0,
                subtotal: 0,
                platformAdjustment: 0,
                source: 'ai_local',
                provider: 'OCR Lokal (Tidak Terdeteksi)',
                confidence: 0,
                warnings: ['Teks gambar struk tidak dapat dibaca oleh OCR lokal. Silakan gunakan integrasi Cloud AI API di Pengaturan atau masukkan rincian pesanan secara manual.'],
            );
        }

        $lower = strtolower($text);

        // 1. Detect platform
        $platform = 'gofood';
        if (
            str_contains($lower, 'shopee') ||
            str_contains($lower, 'shopeefood') ||
            str_contains($lower, 'spf') ||
            str_contains($lower, 'nota pesanan') ||
            preg_match('/\b2[0-9]{5}[A-Z0-9]{6,10}\b/i', $text)
        ) {
            $platform = 'shopeefood';
        } elseif (
            str_contains($lower, 'gofood') ||
            str_contains($lower, 'gojek') ||
            str_contains($lower, 'gf-')
        ) {
            $platform = 'gofood';
        }

        // 2. Detect external order ID
        $orderNumber = null;
        if (preg_match('/\b(2[0-9]{5}[A-Za-z0-9]{6,10})\b/', $text, $matches)) {
            // Shopee format (e.g. 240422DEXY3V7G)
            $orderNumber = strtoupper($matches[1]);
        } elseif (preg_match('/(?:No\.?\s*Pesanan|Order\s*ID|Pesanan\s*#|Order\s*Number)[\s:]*([A-Za-z0-9-_]{6,24})/i', $text, $matches)) {
            $orderNumber = strtoupper(trim($matches[1]));
        } elseif (preg_match('/\b((?:GF|SPF|ORD|ID)[-_ ]?[A-Z0-9]{5,16})\b/i', $text, $matches)) {
            $orderNumber = strtoupper($matches[1]);
        }

        // 3. Detect order time
        $orderTime = Carbon::now()->format('Y-m-d H:i:s');
        if (preg_match('/(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/', $text, $dMatches)) {
            $dateStr = $dMatches[1];
            $parts = preg_split('/[\/\-]/', $dateStr);
            if (count($parts) === 3) {
                $year = strlen($parts[2]) === 2 ? '20'.$parts[2] : $parts[2];
                $timePart = '12:00:00';
                if (preg_match('/([012]?\d[:.][0-5]\d(?::[0-5]\d)?)/', $text, $tMatches)) {
                    $timePart = str_replace('.', ':', $tMatches[1]);
                    if (substr_count($timePart, ':') === 1) {
                        $timePart .= ':00';
                    }
                }
                $orderTime = sprintf('%04d-%02d-%02d %s', $year, $parts[1], $parts[0], $timePart);
            }
        }

        // 4. Detect total amount
        $total = 0;
        if (preg_match('/(?:Total|Grand\s*Total|Total\s*Bayar)[\s\w]*?(?:Rp\.?|IDR)?\s*([0-9A-Za-z.,]+)$/im', $text, $tMatch)) {
            $total = $this->cleanCurrency($tMatch[1]);
        }

        if ($total <= 0 && preg_match_all('/(?:Rp\.?|IDR)\s*([0-9A-Za-z.,]+)/i', $text, $allRps)) {
            $amounts = array_map([$this, 'cleanCurrency'], $allRps[1]);
            $validAmounts = array_filter($amounts, fn ($a) => $a >= 1000);
            if (! empty($validAmounts)) {
                $total = max($validAmounts);
            }
        }

        // 5. Match items with registered menu catalog
        $menus = Menu::with('aliases')->where('is_active', true)->get();
        $matchedItems = [];
        $calculatedSubtotal = 0;

        foreach ($menus as $menu) {
            $found = false;
            // Check menu name
            if (stripos($text, $menu->name) !== false) {
                $found = true;
            } else {
                // Check aliases
                $aliases = $menu->aliases->pluck('alias')->toArray();
                foreach ($aliases as $alias) {
                    if (stripos($text, $alias) !== false) {
                        $found = true;
                        break;
                    }
                }
            }

            if ($found) {
                $qty = 1;
                if (preg_match('/([0-9]+)\s*[xX]?\s*'.preg_quote($menu->name, '/').'/i', $text, $qMatch)) {
                    $qty = (int) $qMatch[1];
                } elseif (preg_match('/'.preg_quote($menu->name, '/').'\s*[xX]?\s*([0-9]+)/i', $text, $qMatch)) {
                    $qty = (int) $qMatch[1];
                }

                $itemSub = $qty * (int) $menu->price;
                $calculatedSubtotal += $itemSub;

                $matchedItems[] = [
                    'raw_name' => $menu->name,
                    'menu_id' => $menu->id,
                    'menu_name' => $menu->name,
                    'match_score' => 1.0,
                    'is_matched' => true,
                    'qty' => $qty,
                    'unit_price' => (int) $menu->price,
                    'total_price' => $itemSub,
                ];
            }
        }

        // 6. If no registered menu matched, extract genuine raw product text from receipt
        if (empty($matchedItems)) {
            $rawProduct = null;
            if (preg_match('/(?:Rincian\s*Pesanan|Produk)(.*?)(?:Bank|Hemat|Jasa\s*Kirim|Subtotal|Total)/is', $text, $pMatch)) {
                $candidate = trim(preg_replace('/\s+/', ' ', $pMatch[1]));
                // Filter out non-product header words
                $candidate = trim(str_ireplace(['Variasi', 'Harga Produk', 'Kuantitas', 'Subtotal', 'No.'], '', $candidate));
                if (strlen($candidate) > 2) {
                    $rawProduct = substr($candidate, 0, 80);
                }
            }

            if ($rawProduct) {
                $matchedItems[] = [
                    'raw_name' => $rawProduct,
                    'menu_id' => null,
                    'menu_name' => null,
                    'match_score' => 0.0,
                    'is_matched' => false,
                    'qty' => 1,
                    'unit_price' => $total > 0 ? $total : 0,
                    'total_price' => $total > 0 ? $total : 0,
                ];
                $calculatedSubtotal = $total;
            }
        }

        if ($total <= 0) {
            $total = $calculatedSubtotal;
        }

        $platformAdjustment = $total - $calculatedSubtotal;

        // 7. Calculate confidence and clear warnings
        $warnings = [];
        $confidence = 90;

        if (empty($matchedItems)) {
            $confidence = 30;
            $warnings[] = 'Tidak ada item menu yang terbaca secara otomatis. Silakan masukkan item menu pada form konfirmasi.';
        } else {
            $unmatched = array_filter($matchedItems, fn ($i) => empty($i['is_matched']));
            if (! empty($unmatched)) {
                $confidence = 65;
                $warnings[] = 'Item struk belum terdaftar di menu outlet ('.$unmatched[0]['raw_name'].'). Silakan pilih menu padanan.';
            }
        }

        if (! $orderNumber) {
            $confidence = min($confidence, 60);
            $warnings[] = 'Nomor pesanan eksternal tidak terbaca dengan pasti. Harap periksa nomor pesanan.';
        }

        if ($total <= 0) {
            $confidence = min($confidence, 50);
            $warnings[] = 'Total pembayaran belum terdeteksi. Silakan masukkan total tagihan.';
        }

        return new ExtractionResult(
            platform: $platform,
            externalOrderId: $orderNumber,
            orderTime: $orderTime,
            items: $matchedItems,
            total: $total,
            subtotal: $calculatedSubtotal,
            platformAdjustment: $platformAdjustment,
            source: 'ai_local',
            provider: 'OCR Lokal (Windows Native)',
            confidence: $confidence,
            warnings: $warnings,
        );
    }
}
