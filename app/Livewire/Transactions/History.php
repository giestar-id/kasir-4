<?php

namespace App\Livewire\Transactions;

use App\Models\Sale;
use App\Services\SaleService;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class History extends Component
{
    use WithPagination;

    public string $search = '';

    public string $channelFilter = 'all'; // all, offline, gofood, shopeefood

    public string $statusFilter = 'all'; // all, completed, void

    public ?string $startDate = null;

    public ?string $endDate = null;

    // Detail Modal (M7)
    public ?int $selectedSaleId = null;

    // Void Modal (M8)
    public ?int $voidSaleId = null;

    public string $voidReason = '';

    public function mount(): void
    {
        $this->startDate = Carbon::today()->subDays(30)->format('Y-m-d');
        $this->endDate = Carbon::today()->format('Y-m-d');
    }

    public function openDetailModal(int $saleId): void
    {
        $this->selectedSaleId = $saleId;
        $this->dispatch('open-modal', 'detail-sale-modal');
    }

    public function openVoidModal(int $saleId): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $sale = Sale::findOrFail($saleId);

        $this->voidSaleId = $sale->id;
        $this->voidReason = '';
        $this->dispatch('open-modal', 'void-sale-modal');
    }

    public function executeVoid(SaleService $saleService): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $this->validate([
            'voidReason' => 'required|string|min:3',
        ], [
            'voidReason.required' => 'Alasan pembatalan wajib diisi.',
            'voidReason.min' => 'Alasan pembatalan minimal 3 karakter.',
        ]);

        if (! $this->voidSaleId) {
            return;
        }

        $sale = Sale::findOrFail($this->voidSaleId);

        try {
            $saleService->voidSale($sale, $this->voidReason, auth()->user());

            $this->dispatch('close-modal', 'void-sale-modal');
            $this->dispatch('close-modal', 'detail-sale-modal');
            $this->dispatch('toast', [
                'type' => 'success',
                'title' => 'Transaksi Dibatalkan',
                'message' => 'Transaksi #'.$sale->number.' berhasil di-void dan stok dikembalikan.',
            ]);

            $this->voidSaleId = null;
            $this->voidReason = '';

        } catch (\Throwable $e) {
            $this->dispatch('toast', [
                'type' => 'error',
                'title' => 'Gagal Membatalkan',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function voidSale(?int $saleId = null, ?string $reason = null, ?SaleService $saleService = null): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        if ($saleId) {
            $this->voidSaleId = $saleId;
        }
        if ($reason) {
            $this->voidReason = $reason;
        }
        $this->executeVoid($saleService ?? app(SaleService::class));
    }

    public function render()
    {
        $query = Sale::with(['items', 'user', 'voidedBy'])->latest('ordered_at');

        // If Cashier role, restrict to today's transactions only (PRD Section 4)
        if (auth()->user()?->isCashier()) {
            $query->whereDate('ordered_at', Carbon::today());
        } elseif ($this->startDate && $this->endDate) {
            $query->whereBetween('ordered_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        if ($this->channelFilter !== 'all') {
            $query->where('channel', $this->channelFilter);
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if (! empty(trim($this->search))) {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('number', 'like', $term)
                    ->orWhere('external_order_id', 'like', $term);
            });
        }

        $sales = $query->paginate(12);

        $selectedSale = $this->selectedSaleId ? Sale::with(['items.menu', 'user', 'voidedBy'])->find($this->selectedSaleId) : null;

        return view('livewire.transactions.history', [
            'sales' => $sales,
            'selectedSale' => $selectedSale,
        ])->layout('layouts.app', ['title' => 'Riwayat Transaksi']);
    }
}
