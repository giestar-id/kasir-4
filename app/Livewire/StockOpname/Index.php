<?php

namespace App\Livewire\StockOpname;

use App\Models\Ingredient;
use App\Models\StockMovement;
use App\Services\StockService;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    // Modal M17: Input Stok Opname
    public ?int $opnameIngredientId = null;

    public string $correctionType = 'adjustment'; // adjustment, waste

    public float $actualStock = 0;

    public string $reason = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $this->opnameIngredientId = Ingredient::where('is_active', true)->first()?->id;
        $this->updateActualDefault();
    }

    public function openCreateModal(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        if (! $this->opnameIngredientId) {
            $this->opnameIngredientId = Ingredient::where('is_active', true)->first()?->id;
        }
        $this->updateActualDefault();
        $this->reason = '';
        $this->dispatch('open-modal', 'opname-modal');
    }

    public function updateActualDefault(): void
    {
        $ing = $this->selectedIngredient;
        if ($ing) {
            $this->actualStock = (float) $ing->current_stock;
        }
    }

    public function updatedOpnameIngredientId(): void
    {
        $this->updateActualDefault();
    }

    public function getSelectedIngredientProperty(): ?Ingredient
    {
        return $this->opnameIngredientId ? Ingredient::find($this->opnameIngredientId) : null;
    }

    public function getDifferenceProperty(): float
    {
        $ing = $this->selectedIngredient;
        if (! $ing) {
            return 0;
        }

        return $this->actualStock - (float) $ing->current_stock;
    }

    public function saveOpname(StockService $stockService): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $this->validate([
            'opnameIngredientId' => 'required|exists:ingredients,id',
            'correctionType' => 'required|in:adjustment,waste',
            'actualStock' => 'required|numeric',
            'reason' => 'required|string|min:3',
        ], [
            'opnameIngredientId.required' => 'Pilih bahan baku yang dihitung.',
            'reason.required' => 'Alasan penyesuaian stok wajib diisi.',
            'reason.min' => 'Alasan minimal 3 karakter.',
        ]);

        try {
            $stockService->recordAdjustment(
                $this->opnameIngredientId,
                $this->correctionType,
                $this->actualStock,
                $this->reason,
                auth()->id()
            );

            $this->dispatch('close-modal', 'opname-modal');
            $this->dispatch('toast', [
                'type' => 'success',
                'title' => 'Stok Opname Tersimpan',
                'message' => 'Penyesuaian stok fisik berhasil dicatat dengan selisih '.number_format($this->difference, 1).'.',
            ]);

            $this->reason = '';

        } catch (\Throwable $e) {
            $this->dispatch('toast', [
                'type' => 'error',
                'title' => 'Gagal Menyimpan',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        $ingredients = Ingredient::where('is_active', true)->orderBy('name')->get();

        $query = StockMovement::with(['ingredient', 'user'])
            ->whereIn('type', [StockMovement::TYPE_ADJUSTMENT, StockMovement::TYPE_WASTE])
            ->latest();

        if (! empty(trim($this->search))) {
            $query->whereHas('ingredient', function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%');
            });
        }

        $adjustments = $query->paginate(12);

        return view('livewire.stock-opname.index', [
            'ingredients' => $ingredients,
            'adjustments' => $adjustments,
        ])->layout('layouts.app', ['title' => 'Stok Opname']);
    }
}
