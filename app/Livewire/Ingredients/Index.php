<?php

namespace App\Livewire\Ingredients;

use App\Models\Ingredient;
use App\Models\StockMovement;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $stockStatusFilter = 'all'; // all, low, negative, active

    // Modal M14: Tambah / Edit Bahan
    public ?int $editingIngredientId = null;

    public string $ingredientName = '';

    public string $baseUnit = 'gram'; // gram, ml, pcs

    public string $purchaseUnit = 'kg'; // kg, liter, dus, pack

    public float $unitFactor = 1000;

    public float $minStock = 100;

    public bool $ingredientIsActive = true;

    // Modal M15: Riwayat Kartu Stok
    public ?int $selectedIngredientId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStockStatusFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->stockStatusFilter = 'all';
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $this->reset(['editingIngredientId', 'ingredientName']);
        $this->baseUnit = 'gram';
        $this->purchaseUnit = 'kg';
        $this->unitFactor = 1000;
        $this->minStock = 500;
        $this->ingredientIsActive = true;
        $this->dispatch('open-modal', 'ingredient-form-modal');
    }

    public function openEditModal(int $id): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $ing = Ingredient::findOrFail($id);
        $this->editingIngredientId = $ing->id;
        $this->ingredientName = $ing->name;
        $this->baseUnit = $ing->base_unit;
        $this->purchaseUnit = $ing->purchase_unit;
        $this->unitFactor = (float) $ing->unit_factor;
        $this->minStock = (float) $ing->min_stock;
        $this->ingredientIsActive = $ing->is_active;

        $this->dispatch('open-modal', 'ingredient-form-modal');
    }

    public function saveIngredient(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $this->validate([
            'ingredientName' => 'required|string|max:100',
            'baseUnit' => 'required|string|max:20',
            'purchaseUnit' => 'required|string|max:20',
            'unitFactor' => 'required|numeric|min:0.001',
            'minStock' => 'required|numeric|min:0',
        ], [
            'ingredientName.required' => 'Nama bahan baku wajib diisi.',
            'baseUnit.required' => 'Satuan dasar wajib diisi.',
            'unitFactor.min' => 'Faktor konversi minimal lebih dari 0.',
        ]);

        $data = [
            'name' => $this->ingredientName,
            'base_unit' => $this->baseUnit,
            'purchase_unit' => $this->purchaseUnit,
            'unit_factor' => $this->unitFactor,
            'min_stock' => $this->minStock,
            'is_active' => $this->ingredientIsActive,
        ];

        if ($this->editingIngredientId) {
            $ing = Ingredient::findOrFail($this->editingIngredientId);
            $ing->update($data);
        } else {
            $ing = Ingredient::create($data);
        }

        $this->dispatch('close-modal', 'ingredient-form-modal');
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Bahan Baku Tersimpan',
            'message' => 'Bahan '.$ing->name.' berhasil disimpan.',
        ]);
    }

    public function deleteIngredient(?int $id = null): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        if ($id) {
            $ing = Ingredient::findOrFail($id);
            $ing->delete();
        }
    }

    public function openStockHistoryModal(int $id): void
    {
        $this->selectedIngredientId = $id;
        $this->dispatch('open-modal', 'stock-history-modal');
    }

    public function render()
    {
        $query = Ingredient::query();

        if (! empty(trim($this->search))) {
            $query->where('name', 'like', '%'.$this->search.'%');
        }

        if ($this->stockStatusFilter === 'low') {
            $query->whereColumn('current_stock', '<=', 'min_stock');
        } elseif ($this->stockStatusFilter === 'negative') {
            $query->where('current_stock', '<', 0);
        }

        $ingredients = $query->orderBy('name')->paginate(12);

        $selectedIngredient = $this->selectedIngredientId ? Ingredient::find($this->selectedIngredientId) : null;
        $movements = $this->selectedIngredientId
            ? StockMovement::with('user')
                ->where('ingredient_id', $this->selectedIngredientId)
                ->latest()
                ->paginate(15)
            : collect();

        return view('livewire.ingredients.index', [
            'ingredients' => $ingredients,
            'selectedIngredient' => $selectedIngredient,
            'movements' => $movements,
        ])->layout('layouts.app', ['title' => 'Bahan Baku & Stok']);
    }
}
