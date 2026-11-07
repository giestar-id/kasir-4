<?php

namespace App\Livewire\Purchases;

use App\Models\Ingredient;
use App\Models\Purchase;
use App\Services\StockService;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $ingredientFilter = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingIngredientFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->ingredientFilter = null;
        $this->resetPage();
    }

    // Modal M16: Catat Pembelian
    public ?int $purchaseIngredientId = null;

    public string $purchaseDate = '';

    public float $qtyPurchaseUnit = 1;

    public int $totalCost = 0;

    public string $purchaseNote = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $this->purchaseDate = Carbon::today()->format('Y-m-d');
        $this->purchaseIngredientId = Ingredient::where('is_active', true)->first()?->id;
    }

    public function openCreateModal(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $this->purchaseDate = Carbon::today()->format('Y-m-d');
        $this->qtyPurchaseUnit = 1;
        $this->totalCost = 0;
        $this->purchaseNote = '';
        if (! $this->purchaseIngredientId) {
            $this->purchaseIngredientId = Ingredient::where('is_active', true)->first()?->id;
        }
        $this->dispatch('open-modal', 'purchase-modal');
    }

    public function getSelectedIngredientProperty(): ?Ingredient
    {
        return $this->purchaseIngredientId ? Ingredient::find($this->purchaseIngredientId) : null;
    }

    public function getConvertedBaseQtyProperty(): float
    {
        $ing = $this->selectedIngredient;
        if (! $ing) {
            return 0;
        }

        return $this->qtyPurchaseUnit * (float) $ing->unit_factor;
    }

    public function getCalculatedUnitCostBaseProperty(): int
    {
        if ($this->convertedBaseQty <= 0) {
            return 0;
        }

        return (int) round($this->totalCost / $this->convertedBaseQty);
    }

    public function getEstimatedNewAvgCostProperty(): int
    {
        $ing = $this->selectedIngredient;
        if (! $ing) {
            return 0;
        }

        $currentStock = (float) $ing->current_stock;
        $oldAvg = (int) $ing->avg_cost_per_base;
        $newBaseQty = $this->convertedBaseQty;

        if ($currentStock > 0 && ($currentStock + $newBaseQty) > 0) {
            return (int) round((($currentStock * $oldAvg) + $this->totalCost) / ($currentStock + $newBaseQty));
        }

        return $this->calculatedUnitCostBase;
    }

    public function savePurchase(StockService $stockService): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $this->validate([
            'purchaseIngredientId' => 'required|exists:ingredients,id',
            'purchaseDate' => 'required|date',
            'qtyPurchaseUnit' => 'required|numeric|min:0.001',
            'totalCost' => 'required|numeric|min:1',
        ], [
            'purchaseIngredientId.required' => 'Pilih bahan baku yang dibeli.',
            'totalCost.min' => 'Total biaya pembelian minimal Rp1.',
        ]);

        try {
            $stockService->recordPurchase(
                $this->purchaseIngredientId,
                $this->qtyPurchaseUnit,
                $this->totalCost,
                Carbon::parse($this->purchaseDate)->format('Y-m-d H:i:s'),
                $this->purchaseNote,
                auth()->id()
            );

            $this->dispatch('close-modal', 'purchase-modal');
            $this->dispatch('toast', [
                'type' => 'success',
                'title' => 'Pembelian Dicatat',
                'message' => 'Stok bahan baku berhasil bertambah dan harga rata-rata terhitung.',
            ]);

            $this->reset(['totalCost', 'purchaseNote']);

        } catch (\Throwable $e) {
            $this->dispatch('toast', [
                'type' => 'error',
                'title' => 'Gagal Mencatat',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        $ingredients = Ingredient::where('is_active', true)->orderBy('name')->get();

        $query = Purchase::with(['ingredient', 'user'])->latest('purchased_at');

        if ($this->ingredientFilter) {
            $query->where('ingredient_id', $this->ingredientFilter);
        }

        if (! empty(trim($this->search))) {
            $query->whereHas('ingredient', function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%');
            });
        }

        $purchases = $query->paginate(12);

        return view('livewire.purchases.index', [
            'ingredients' => $ingredients,
            'purchases' => $purchases,
        ])->layout('layouts.app', ['title' => 'Pembelian Bahan']);
    }
}
