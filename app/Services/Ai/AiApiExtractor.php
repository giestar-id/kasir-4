<?php

namespace App\Services\Ai;

use App\Models\AiCallLog;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class AiApiExtractor implements OrderExtractor
{
    public function __construct(
        protected MenuMatcher $menuMatcher
    ) {}

    public function extract(string $imagePath): ExtractionResult
    {
        $provider = Setting::get('ai_provider', 'gemini');
        $apiKey = Setting::get('ai_api_key');
        $model = Setting::get('ai_model') ?: $this->getDefaultModel($provider);
        $baseUrl = Setting::get('ai_custom_url');

        if (empty($apiKey)) {
            throw new \RuntimeException('API key AI belum diatur.');
        }

        $imageBytes = @file_get_contents($imagePath);
        if (! $imageBytes) {
            throw new \RuntimeException('Berkas gambar tidak dapat dibaca.');
        }
        $mime = mime_content_type($imagePath) ?: 'image/jpeg';
        $base64Image = base64_encode($imageBytes);

        $startTime = microtime(true);
        $status = 'success';
        $errorCode = null;
        $errorMessage = null;

        try {
            $jsonResponse = match ($provider) {
                'gemini' => $this->callGemini($apiKey, $model, $base64Image, $mime),
                'anthropic' => $this->callAnthropic($apiKey, $model, $base64Image, $mime),
                'openai', 'openrouter', 'custom' => $this->callOpenAiCompatible($provider, $apiKey, $model, $baseUrl, $base64Image, $mime),
                default => throw new \InvalidArgumentException("Penyedia AI {$provider} tidak didukung."),
            };

            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);

            AiCallLog::create([
                'provider' => $provider,
                'model' => $model,
                'status' => 'success',
                'latency_ms' => $latencyMs,
                'est_cost' => 50,
            ]);

            return $this->formatResult($jsonResponse, $provider);

        } catch (\Throwable $e) {
            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);
            AiCallLog::create([
                'provider' => $provider,
                'model' => $model,
                'status' => 'failed',
                'latency_ms' => $latencyMs,
                'error_code' => 'API_ERROR',
                'error_message' => substr($e->getMessage(), 0, 500),
                'est_cost' => 0,
            ]);

            throw $e;
        }
    }

    protected function getSystemPrompt(): string
    {
        return <<<'PROMPT'
Anda adalah asisten AI ekstraksi struk pesanan online (GoFood dan ShopeeFood) untuk outlet kuliner.
Tugas Anda mengekstrak informasi pesanan dari screenshot struk.
ATURAN KETAT PRIVASI: Dilarang keras mengekstrak nama pelanggan, nomor telepon, atau alamat pengantaran!

Keluarkan HANYA format JSON valid tanpa markdown tambahan atau blok ```json, dengan struktur:
{
  "platform": "gofood" | "shopeefood",
  "external_order_id": "string nomor pesanan eksternal",
  "order_time": "YYYY-MM-DD HH:mm:ss" atau waktu saat ini jika tidak tampak,
  "items": [
    {
      "name": "nama item yang tertera di struk",
      "qty": 1,
      "price": 25000
    }
  ],
  "total": 50000
}
PROMPT;
    }

    protected function callGemini(string $apiKey, string $model, string $base64Image, string $mime): array
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $response = Http::withHeaders([
            'x-goog-api-key' => $apiKey,
        ])->timeout(25)->post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $this->getSystemPrompt()],
                        [
                            'inline_data' => [
                                'mime_type' => $mime,
                                'data' => $base64Image,
                            ],
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'response_mime_type' => 'application/json',
            ],
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Gemini API Error: '.$response->body());
        }

        $body = $response->json();
        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '{}';

        return json_decode($text, true) ?: [];
    }

    protected function callAnthropic(string $apiKey, string $model, string $base64Image, string $mime): array
    {
        $url = 'https://api.anthropic.com/v1/messages';

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(25)->post($url, [
            'model' => $model,
            'max_tokens' => 1024,
            'system' => $this->getSystemPrompt(),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'image',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => $mime,
                                'data' => $base64Image,
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => 'Ekstrak detail pesanan sesuai instruksi JSON.',
                        ],
                    ],
                ],
            ],
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Anthropic API Error: '.$response->body());
        }

        $body = $response->json();
        $text = $body['content'][0]['text'] ?? '{}';
        // Clean possible markdown wrapper
        $cleaned = trim(preg_replace('/^```(?:json)?|```$/m', '', $text));

        return json_decode($cleaned, true) ?: [];
    }

    protected function callOpenAiCompatible(
        string $provider,
        string $apiKey,
        string $model,
        ?string $baseUrl,
        string $base64Image,
        string $mime
    ): array {
        $endpoint = match ($provider) {
            'openrouter' => 'https://openrouter.ai/api/v1/chat/completions',
            'custom' => rtrim($baseUrl ?: 'https://api.openai.com/v1', '/').'/chat/completions',
            default => 'https://api.openai.com/v1/chat/completions',
        };

        $response = Http::withToken($apiKey)->timeout(25)->post($endpoint, [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $this->getSystemPrompt()],
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => 'Ekstrak pesanan dari struk ini.'],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => "data:{$mime};base64,{$base64Image}",
                            ],
                        ],
                    ],
                ],
            ],
            'response_format' => ['type' => 'json_object'],
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException("{$provider} API Error: ".$response->body());
        }

        $body = $response->json();
        $text = $body['choices'][0]['message']['content'] ?? '{}';

        return json_decode($text, true) ?: [];
    }

    protected function formatResult(array $data, string $provider): ExtractionResult
    {
        $platform = strtolower($data['platform'] ?? 'gofood');
        $externalOrderId = $data['external_order_id'] ?? ('EXT-'.rand(100000, 999999));
        $orderTime = $data['order_time'] ?? Carbon::now()->format('Y-m-d H:i:s');
        $rawItems = $data['items'] ?? [];
        $total = (int) ($data['total'] ?? 0);

        $matchedItems = [];
        $calculatedSubtotal = 0;

        foreach ($rawItems as $item) {
            $rawName = $item['name'] ?? 'Item';
            $qty = max(1, (int) ($item['qty'] ?? 1));
            $match = $this->menuMatcher->match($rawName);

            $menu = $match['menu'];
            $unitPrice = $menu ? (int) $menu->price : (int) ($item['price'] ?? 0);
            $itemSubtotal = $qty * $unitPrice;
            $calculatedSubtotal += $itemSubtotal;

            $matchedItems[] = [
                'raw_name' => $rawName,
                'menu_id' => $menu?->id,
                'menu_name' => $menu?->name,
                'match_score' => $match['score'],
                'is_matched' => $match['is_matched'],
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'total_price' => $itemSubtotal,
            ];
        }

        if ($total <= 0) {
            $total = $calculatedSubtotal;
        }

        $platformAdjustment = $total - $calculatedSubtotal;

        return new ExtractionResult(
            platform: $platform,
            externalOrderId: $externalOrderId,
            orderTime: $orderTime,
            items: $matchedItems,
            total: $total,
            subtotal: $calculatedSubtotal,
            platformAdjustment: $platformAdjustment,
            source: 'ai_api',
            provider: "AI ({$provider})",
            confidence: 96,
            fallbackReason: null,
            warnings: [],
        );
    }

    protected function getDefaultModel(string $provider): string
    {
        return match ($provider) {
            'gemini' => 'gemini-1.5-flash',
            'anthropic' => 'claude-3-5-haiku-20241022',
            'openai' => 'gpt-4o-mini',
            'openrouter' => 'google/gemini-flash-1.5',
            default => 'gpt-4o-mini',
        };
    }
}
