<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Menu;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Generate unique, race-condition safe transaction number: TRX-YYYYMMDD-0001
     */
    public function generateTransactionNumber(?Carbon $date = null): string
    {
        $targetDate = $date ?? Carbon::now();
        $dateStr = $targetDate->format('Ymd');
        $prefix = "TRX-{$dateStr}-";

        // Find last transaction for today with atomic lock
        $lastSale = Sale::where('number', 'like', "{$prefix}%")
            ->orderBy('number', 'desc')
            ->lockForUpdate()
            ->first();

        if ($lastSale) {
            $lastSeq = (int) substr($lastSale->number, strlen($prefix));
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix.str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create and record a sale with atomic stock deduction and snapshot prices/HPP.
     */
    public function createSale(array $data, array $items, User $user): Sale
    {
        return DB::transaction(function () use ($data, $items, $user) {
            $idempotencyKey = $data['idempotency_key'] ?? (string) Str::uuid();

            // Check if already processed (Idempotency)
            $existing = Sale::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }

            // Check channel & external_order_id uniqueness
            $channel = $data['channel'] ?? Sale::CHANNEL_OFFLINE;
            $externalOrderId = $data['external_order_id'] ?? null;

            if ($externalOrderId && $channel !== Sale::CHANNEL_OFFLINE) {
                $duplicate = Sale::where('channel', $channel)
                    ->where('external_order_id', $externalOrderId)
                    ->first();
                if ($duplicate) {
                    throw ValidationException::withMessages([
                        'external_order_id' => "Pesanan dengan nomor {$externalOrderId} pada platform {$channel} sudah tercatat sebelumnya (No: {$duplicate->number}).",
                    ]);
                }
            }

            if (empty($items)) {
                throw ValidationException::withMessages([
                    'items' => 'Transaksi harus memiliki setidaknya satu item menu.',
                ]);
            }

            $orderedAt = isset($data['ordered_at']) ? Carbon::parse($data['ordered_at']) : Carbon::now();
            $number = $this->generateTransactionNumber($orderedAt);

            // Pre-load all menus with recipes and ingredients in a single query
            $menuIds = array_filter(array_column($items, 'menu_id'));
            $menus = ! empty($menuIds)
                ? Menu::with(['recipes.ingredient', 'extraCosts'])->whereIn('id', $menuIds)->get()->keyBy('id')
                : collect();

            // Compute items
            $subtotal = 0;
            $computedItems = [];

            foreach ($items as $itemData) {
                $menu = $menus->get($itemData['menu_id'] ?? null);
                $qty = max(1, (int) ($itemData['qty'] ?? 1));
                if (array_key_exists('unit_price', $itemData) && $itemData['unit_price'] !== null) {
                    $unitPrice = (int) $itemData['unit_price'];
                } elseif (array_key_exists('price', $itemData) && $itemData['price'] !== null) {
                    $unitPrice = (int) $itemData['price'];
                } elseif ($menu) {
                    $unitPrice = (int) $menu->price;
                } else {
                    $unitPrice = 0;
                }
                $unitHpp = $menu ? $menu->calculateHpp() : 0;
                $menuName = $menu ? $menu->name : ($itemData['menu_name'] ?? 'Menu Tidak Dikenal');
                $note = $itemData['note'] ?? null;

                $itemSubtotal = $qty * $unitPrice;
                $subtotal += $itemSubtotal;

                $computedItems[] = [
                    'menu_id' => $menu?->id,
                    'menu_name_snapshot' => $menuName,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'unit_hpp' => $unitHpp,
                    'note' => $note,
                ];
            }

            $discount = max(0, (int) ($data['discount'] ?? 0));
            $platformAdjustment = (int) ($data['platform_adjustment'] ?? 0);
            $total = max(0, $subtotal - $discount + $platformAdjustment);

            $paymentMethod = $data['payment_method'] ?? 'cash';
            if ($paymentMethod === 'cash') {
                $paidAmount = (int) ($data['paid_amount'] ?? $total);
                if ($paidAmount < $total) {
                    throw ValidationException::withMessages([
                        'paid_amount' => 'Jumlah pembayaran tunai (Rp '.number_format($paidAmount, 0, ',', '.').') kurang dari total tagihan (Rp '.number_format($total, 0, ',', '.').').',
                    ]);
                }
                $changeAmount = $paidAmount - $total;
            } else {
                $paidAmount = $total;
                $changeAmount = 0;
            }

            $sale = Sale::create([
                'number' => $number,
                'channel' => $channel,
                'external_order_id' => $externalOrderId,
                'status' => Sale::STATUS_COMPLETED,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'discount_type' => $data['discount_type'] ?? null,
                'discount_reason' => $data['discount_reason'] ?? null,
                'platform_adjustment' => $platformAdjustment,
                'total' => $total,
                'payment_method' => $paymentMethod,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'ordered_at' => $orderedAt,
                'source' => $data['source'] ?? Sale::SOURCE_MANUAL,
                'idempotency_key' => $idempotencyKey,
                'user_id' => $user->id,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($computedItems as $cItem) {
                $sale->items()->create($cItem);
            }

            // Deduct stock
            $this->stockService->deductForSale($sale);

            return $sale;
        });
    }

    /**
     * Void a transaction and return stock.
     */
    public function voidSale(Sale $sale, string $reason, User $user): Sale
    {
        return DB::transaction(function () use ($sale, $reason, $user) {
            $lockedSale = Sale::where('id', $sale->id)->lockForUpdate()->firstOrFail();

            if ($lockedSale->isVoid()) {
                throw new \RuntimeException('Transaksi ini sudah dibatalkan sebelumnya.');
            }

            $lockedSale->status = Sale::STATUS_VOID;
            $lockedSale->voided_at = Carbon::now();
            $lockedSale->void_reason = $reason;
            $lockedSale->voided_by = $user->id;
            $lockedSale->save();

            // Return stock
            $this->stockService->returnForVoid($lockedSale, $reason, $user->id);

            // Audit log
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'void_sale',
                'subject_type' => Sale::class,
                'subject_id' => $lockedSale->id,
                'meta' => [
                    'number' => $lockedSale->number,
                    'total' => $lockedSale->total,
                    'reason' => $reason,
                ],
                'ip' => request()?->ip(),
            ]);

            return $lockedSale;
        });
    }
}
