<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use Livewire\Component;

class Index extends Component
{
    public string $categoryName = '';

    public int $sortOrder = 0;

    public ?int $editingCategoryId = null;

    public ?int $deletingCategoryId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
    }

    public function openCreateModal(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $this->reset(['editingCategoryId', 'categoryName']);
        $this->sortOrder = (Category::max('sort_order') ?? 0) + 1;
        $this->dispatch('open-modal', 'category-form-modal');
    }

    public function openEditModal(int $id): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $category = Category::findOrFail($id);
        $this->editingCategoryId = $category->id;
        $this->categoryName = $category->name;
        $this->sortOrder = $category->sort_order;
        $this->dispatch('open-modal', 'category-form-modal');
    }

    public function saveCategory(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $this->validate([
            'categoryName' => 'required|string|max:50',
            'sortOrder' => 'required|integer',
        ], [
            'categoryName.required' => 'Nama kategori wajib diisi.',
            'sortOrder.required' => 'Urutan tampil wajib diisi.',
        ]);

        if ($this->editingCategoryId) {
            $cat = Category::findOrFail($this->editingCategoryId);
            $cat->update([
                'name' => $this->categoryName,
                'sort_order' => $this->sortOrder,
            ]);
        } else {
            $cat = Category::create([
                'name' => $this->categoryName,
                'sort_order' => $this->sortOrder,
            ]);
        }

        $this->dispatch('close-modal', 'category-form-modal');
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Kategori Tersimpan',
            'message' => 'Kategori '.$cat->name.' berhasil disimpan.',
        ]);
    }

    public function openDeleteModal(int $id): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $this->deletingCategoryId = $id;
        $this->dispatch('open-modal', 'delete-category-modal');
    }

    public function executeDelete(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        if (! $this->deletingCategoryId) {
            return;
        }

        $cat = Category::withCount('menus')->findOrFail($this->deletingCategoryId);

        if ($cat->menus_count > 0) {
            $this->dispatch('toast', [
                'type' => 'error',
                'title' => 'Tidak Dapat Dihapus',
                'message' => 'Kategori masih memiliki '.$cat->menus_count.' menu terdaftar.',
            ]);
            $this->dispatch('close-modal', 'delete-category-modal');

            return;
        }

        $cat->delete();
        $this->dispatch('close-modal', 'delete-category-modal');
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Kategori Dihapus',
            'message' => 'Kategori berhasil dihapus.',
        ]);
        $this->deletingCategoryId = null;
    }

    public function deleteCategory(?int $id = null): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        if ($id) {
            $this->deletingCategoryId = $id;
        }
        $this->executeDelete();
    }

    public function render()
    {
        $categories = Category::withCount('menus')->orderBy('sort_order')->get();

        return view('livewire.categories.index', [
            'categories' => $categories,
        ])->layout('layouts.app', ['title' => 'Kategori Menu']);
    }
}
