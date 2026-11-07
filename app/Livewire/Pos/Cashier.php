<?php

namespace App\Livewire\Pos;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Sale;
use App\Services\SaleService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Livewire\Component;

class Cashier extends Component
{
    public string $search = '';

    public ?int $selectedCategory = null;

    // Cart items: [ menu_id => [ 'menu_id' => ..., 'name' => ..., 'price' => ..., 'qty' => ..., 'note' => ... ] ]
    public array $cart = [];

    public string $transactionNote = '';

    public string $idempotencyKey = '';

    // Payment Modal State (M2)
    public string $paymentMethod = 'cash'; // cash, qris, transfer

    public string $discountType = 'fixed'; // fixed, percent

    public int $discountAmount = 0;

    public string $discountReason = '';

    public int $paidAmount = 0;

    // Last completed sale for Receipt Modal (M3)
    public ?int $lastSaleId = null;

    public function mount(): void
    {
        $this->idempotencyKey = (string) Str::uuid();
    }

    public function addToCart(int $menuId): void
    {
        $menu = Menu::where('is_active', true)->findOrFail($menuId);

        if (isset($this->cart[$menuId])) {
            $this->cart[$menuId]['qty']++;
        } else {
            $this->cart[$menuId] = [
                'menu_id' => $menu->id,
                'name' => $menu->name,
                'price' => (int) $menu->price,
                'qty' => 1,
                'note' => '',
            ];
        }

        $this->updatePaidAmount();
        $this->dispatch('toast', [
            'type' => 'info',
            'title' => 'Menu Ditambahkan',
            'message' => $menu->name.' ditambahkan ke keranjang.',
        ]);
    }

    public function increaseQty(int $menuId): void
    {
        if (isset($this->cart[$menuId])) {
            $this->cart[$menuId]['qty']++;
            $this->updatePaidAmount();
        }
    }

    public function decreaseQty(int $menuId): void
    {
        if (isset($this->cart[$menuId])) {
            if ($this->cart[$menuId]['qty'] > 1) {
                $this->cart[$menuId]['qty']--;
            } else {
                unset($this->cart[$menuId]);
            }
            $this->updatePaidAmount();
        }
    }

    public function removeFromCart(int $menuId): void
    {
        if (isset($this->cart[$menuId])) {
            unset($this->cart[$menuId]);
            $this->updatePaidAmount();
        }
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->transactionNote = '';
        $this->discountAmount = 0;
        $this->discountReason = '';
        $this->paidAmount = 0;
        $this->idempotencyKey = (string) Str::uuid();
    }

    public function getSubtotalProperty(): int
    {
        $total = 0;
        foreach ($this->cart as $item) {
            $total += $item['price'] * $item['qty'];
        }

        return $total;
    }

    public function getDiscountValueProperty(): int
    {
        if ($this->discountAmount <= 0) {
            return 0;
        }

        $user = auth()->user();
        if ($user && ! $user->isOwner() && ! $user->can_give_discount) {
            return 0;
        }

        if ($this->discountType === 'percent') {
            return (int) round(($this->subtotal * min(100, $this->discountAmount)) / 100);
        }

        return min($this->subtotal, $this->discountAmount);
    }

    public function getTotalProperty(): int
    {
        return max(0, $this->subtotal - $this->discountValue);
    }

    public function getChangeProperty(): int
    {
        if ($this->paymentMethod === 'cash') {
            return max(0, $this->paidAmount - $this->total);
        }

        return 0;
    }

    public function setNominal(int $amount): void
    {
        $this->paidAmount = $amount;
    }

    public function setUangPas(): void
    {
        $this->paidAmount = $this->total;
    }

    public function updatePaidAmount(): void
    {
        if ($this->paidAmount < $this->total) {
            $this->paidAmount = $this->total;
        }
    }

    public function openPaymentModal(): void
    {
        if (empty($this->cart)) {
            $this->dispatch('toast', [
                'type' => 'warning',
                'title' => 'Keranjang Kosong',
                'message' => 'Pilih setidaknya satu menu sebelum melakukan pembayaran.',
            ]);

            return;
        }

        $this->paidAmount = $this->total;
        $this->dispatch('open-modal', 'payment-modal');
    }

    public function processSale(SaleService $saleService): void
    {
        $this->resetValidation();

        if (empty($this->cart)) {
            return;
        }

        $user = auth()->user();

        // Check discount permission
        if ($this->discountAmount > 0) {
            abort_unless($user->isOwner() || $user->can_give_discount, 403, 'Akun kasir ini tidak memiliki izin untuk memberikan diskon.');

            if (empty(trim($this->discountReason))) {
                $this->addError('discountReason', 'Alasan pemberian diskon wajib diisi.');
                $this->dispatch('toast', [
                    'type' => 'error',
                    'title' => 'Alasan Wajib',
                    'message' => 'Alasan pemberian diskon wajib diisi.',
                ]);

                return;
            }
        }

        // Validate cash payment
        if ($this->paymentMethod === 'cash' && $this->paidAmount < $this->total) {
            $this->addError('paidAmount', 'Uang tunai yang diterima kurang dari total tagihan.');
            $this->dispatch('toast', [
                'type' => 'error',
                'title' => 'Uang Kurang',
                'message' => 'Uang tunai yang diterima kurang dari total tagihan.',
            ]);

            return;
        }

        try {
            $saleData = [
                'channel' => Sale::CHANNEL_OFFLINE,
                'external_order_id' => null,
                'subtotal' => $this->subtotal,
                'discount' => $this->discountValue,
                'discount_type' => $this->discountAmount > 0 ? $this->discountType : null,
                'discount_reason' => $this->discountAmount > 0 ? $this->discountReason : null,
                'platform_adjustment' => 0,
                'total' => $this->total,
                'payment_method' => $this->paymentMethod,
                'paid_amount' => $this->paymentMethod === 'cash' ? $this->paidAmount : $this->total,
                'change_amount' => $this->change,
                'ordered_at' => Carbon::now(),
                'source' => Sale::SOURCE_MANUAL,
                'idempotency_key' => $this->idempotencyKey,
                'notes' => $this->transactionNote,
            ];

            $sale = $saleService->createSale($saleData, array_values($this->cart), $user);

            $this->lastSaleId = $sale->id;

            // Reset cart
            $this->clearCart();
            $this->dispatch('close-modal', 'payment-modal');

            // Open Receipt Modal
            $this->dispatch('open-modal', 'receipt-modal');

            $this->dispatch('toast', [
                'type' => 'success',
                'title' => 'Transaksi Berhasil',
                'message' => 'Transaksi #'.$sale->number.' selesai dan stok bahan telah diperbarui.',
            ]);

        } catch (\Throwable $e) {
            $this->dispatch('toast', [
                'type' => 'error',
                'title' => 'Gagal Memproses Transaksi',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        $categories = Category::orderBy('sort_order')->get();

        $menusQuery = Menu::where('is_active', true)
            ->with(['category']);

        if ($this->selectedCategory) {
            $menusQuery->where('category_id', $this->selectedCategory);
        }

        if (! empty(trim($this->search))) {
            $menusQuery->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhereHas('aliases', function ($aq) {
                        $aq->where('alias', 'like', '%'.$this->search.'%');
                    });
            });
        }

        $menus = $menusQuery->orderBy('name')->get();

        $lastSale = $this->lastSaleId ? Sale::with(['items', 'user'])->find($this->lastSaleId) : null;

        return view('livewire.pos.cashier', [
            'categories' => $categories,
            'menus' => $menus,
            'lastSale' => $lastSale,
        ])->layout('layouts.app', ['title' => 'Kasir POS']);
    }
}
