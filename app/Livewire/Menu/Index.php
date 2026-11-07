<?php

namespace App\Livewire\Menu;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\MenuAlias;
use App\Models\MenuExtraCost;
use App\Models\Recipe;
use App\Services\CostingService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $categoryFilter = null;

    public string $recipeStatusFilter = 'all'; // all, complete, incomplete

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingRecipeStatusFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->categoryFilter = null;
        $this->recipeStatusFilter = 'all';
        $this->resetPage();
    }

    // Modal M9: Tambah/Edit Menu
    public ?int $editingMenuId = null;

    public string $menuName = '';

    public ?int $menuCategoryId = null;

    public int $menuPrice = 0;

    public string $menuAliases = '';

    public string $menuNote = '';

    public bool $menuIsActive = true;

    // Modal M10: Resep & HPP
    public ?int $recipeMenuId = null;

    public array $recipeIngredients = []; // [ [ 'ingredient_id' => ..., 'qty' => ... ] ]

    public array $recipeExtraCosts = []; // [ [ 'label' => ..., 'amount' => ... ] ]

    // Modal M11: Hapus Menu
    public ?int $deletingMenuId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
    }

    public function openCreateModal(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $this->reset(['editingMenuId', 'menuName', 'menuPrice', 'menuAliases', 'menuNote']);
        $this->menuCategoryId = Category::first()?->id;
        $this->menuIsActive = true;
        $this->dispatch('open-modal', 'menu-form-modal');
    }

    public function openEditModal(int $menuId): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $menu = Menu::with('aliases')->findOrFail($menuId);
        $this->editingMenuId = $menu->id;
        $this->menuName = $menu->name;
        $this->menuCategoryId = $menu->category_id;
        $this->menuPrice = (int) $menu->price;
        $this->menuAliases = $menu->aliases->pluck('alias')->implode(', ');
        $this->menuNote = $menu->note ?? '';
        $this->menuIsActive = $menu->is_active;

        $this->dispatch('open-modal', 'menu-form-modal');
    }

    public function saveMenu(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $this->validate([
            'menuName' => 'required|string|max:100',
            'menuCategoryId' => 'required|exists:categories,id',
            'menuPrice' => 'required|numeric|min:0',
        ], [
            'menuName.required' => 'Nama menu wajib diisi.',
            'menuCategoryId.required' => 'Kategori wajib dipilih.',
            'menuPrice.required' => 'Harga jual wajib diisi.',
        ]);

        $menu = DB::transaction(function () {
            $data = [
                'name' => $this->menuName,
                'category_id' => $this->menuCategoryId,
                'price' => $this->menuPrice,
                'note' => $this->menuNote,
                'is_active' => $this->menuIsActive,
            ];

            if ($this->editingMenuId) {
                $menu = Menu::findOrFail($this->editingMenuId);
                $menu->update($data);
            } else {
                $menu = Menu::create($data);
            }

            // Update aliases
            $menu->aliases()->delete();
            if (! empty(trim($this->menuAliases))) {
                $aliasList = array_map('trim', explode(',', $this->menuAliases));
                foreach ($aliasList as $alias) {
                    if (! empty($alias)) {
                        MenuAlias::create([
                            'menu_id' => $menu->id,
                            'alias' => $alias,
                            'normalized_alias' => MenuAlias::normalize($alias),
                        ]);
                    }
                }
            }

            return $menu;
        });

        $this->dispatch('close-modal', 'menu-form-modal');
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Menu Tersimpan',
            'message' => 'Menu '.$menu->name.' berhasil disimpan.',
        ]);
    }

    public function openRecipeModal(int $menuId, CostingService $costingService): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $menu = Menu::with(['recipes.ingredient', 'extraCosts'])->findOrFail($menuId);
        $this->recipeMenuId = $menu->id;

        $this->recipeIngredients = [];
        foreach ($menu->recipes as $r) {
            $this->recipeIngredients[] = [
                'ingredient_id' => $r->ingredient_id,
                'qty_per_portion' => (float) $r->qty_per_portion,
            ];
        }

        $this->recipeExtraCosts = [];
        foreach ($menu->extraCosts as $ec) {
            $this->recipeExtraCosts[] = [
                'label' => $ec->label,
                'amount' => (int) $ec->amount,
            ];
        }

        if (empty($this->recipeIngredients)) {
            $this->addRecipeIngredientRow();
        }

        $this->dispatch('open-modal', 'recipe-modal');
    }

    public function addRecipeIngredientRow(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $firstIng = Ingredient::where('is_active', true)->first();
        $this->recipeIngredients[] = [
            'ingredient_id' => $firstIng?->id,
            'qty_per_portion' => 1,
        ];
    }

    public function removeRecipeIngredientRow(int $index): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        unset($this->recipeIngredients[$index]);
        $this->recipeIngredients = array_values($this->recipeIngredients);
    }

    public function addExtraCostRow(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $this->recipeExtraCosts[] = [
            'label' => 'Kemasan / Packaging',
            'amount' => 1000,
        ];
    }

    public function removeExtraCostRow(int $index): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        unset($this->recipeExtraCosts[$index]);
        $this->recipeExtraCosts = array_values($this->recipeExtraCosts);
    }

    public function saveRecipe(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        if (! $this->recipeMenuId) {
            return;
        }

        $menu = Menu::findOrFail($this->recipeMenuId);

        DB::transaction(function () use ($menu) {
            // Update recipes
            $menu->recipes()->delete();
            foreach ($this->recipeIngredients as $r) {
                if (! empty($r['ingredient_id']) && (float) ($r['qty_per_portion'] ?? 0) > 0) {
                    Recipe::create([
                        'menu_id' => $menu->id,
                        'ingredient_id' => $r['ingredient_id'],
                        'qty_per_portion' => $r['qty_per_portion'],
                    ]);
                }
            }

            // Update extra costs
            $menu->extraCosts()->delete();
            foreach ($this->recipeExtraCosts as $ec) {
                if (! empty(trim($ec['label'] ?? '')) && (int) ($ec['amount'] ?? 0) > 0) {
                    MenuExtraCost::create([
                        'menu_id' => $menu->id,
                        'label' => $ec['label'],
                        'amount' => $ec['amount'],
                    ]);
                }
            }
        });

        $this->dispatch('close-modal', 'recipe-modal');
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Resep Diperbarui',
            'message' => 'Resep dan HPP untuk '.$menu->name.' berhasil diperbarui.',
        ]);
    }

    public function openDeleteModal(int $menuId): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $this->deletingMenuId = $menuId;
        $this->dispatch('open-modal', 'delete-menu-modal');
    }

    public function executeDelete(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        if (! $this->deletingMenuId) {
            return;
        }

        $menu = Menu::findOrFail($this->deletingMenuId);

        // Check if menu has sales transactions (PRD 5.3)
        if ($menu->saleItems()->exists()) {
            $menu->is_active = false;
            $menu->save();

            $this->dispatch('close-modal', 'delete-menu-modal');
            $this->dispatch('toast', [
                'type' => 'warning',
                'title' => 'Menu Dinonaktifkan',
                'message' => 'Menu memiliki riwayat transaksi sehingga dinonaktifkan (bukan dihapus).',
            ]);
        } else {
            $menu->delete();
            $this->dispatch('close-modal', 'delete-menu-modal');
            $this->dispatch('toast', [
                'type' => 'success',
                'title' => 'Menu Dihapus',
                'message' => 'Menu berhasil dihapus permanen.',
            ]);
        }

        $this->deletingMenuId = null;
    }

    public function deleteMenu(?int $id = null): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        if ($id) {
            $this->deletingMenuId = $id;
        }
        $this->executeDelete();
    }

    public function render()
    {
        $categories = Category::orderBy('sort_order')->get();
        $ingredients = $this->recipeMenuId ? Ingredient::where('is_active', true)->orderBy('name')->get() : collect();

        $query = Menu::with(['category', 'aliases', 'recipes.ingredient', 'extraCosts']);

        if ($this->categoryFilter) {
            $query->where('category_id', $this->categoryFilter);
        }

        if ($this->recipeStatusFilter === 'complete') {
            $query->whereHas('recipes');
        } elseif ($this->recipeStatusFilter === 'incomplete') {
            $query->whereDoesntHave('recipes');
        }

        if (! empty(trim($this->search))) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhereHas('aliases', function ($aq) {
                        $aq->where('alias', 'like', '%'.$this->search.'%');
                    });
            });
        }

        $menus = $query->orderBy('name')->paginate(12);

        $selectedRecipeMenu = $this->recipeMenuId ? Menu::with(['recipes.ingredient', 'extraCosts'])->find($this->recipeMenuId) : null;

        return view('livewire.menu.index', [
            'categories' => $categories,
            'ingredients' => $ingredients,
            'menus' => $menus,
            'selectedRecipeMenu' => $selectedRecipeMenu,
        ])->layout('layouts.app', ['title' => 'Menu & Resep']);
    }
}
