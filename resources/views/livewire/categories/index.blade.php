<div class="flex flex-col gap-5">

    <!-- Header Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#1E2029] tracking-tight">Kategori Menu</h1>
            <p class="text-xs text-[#4E5262] mt-0.5">Atur pengelompokan menu dan urutan tab tampilan pada kasir POS</p>
        </div>

        <x-ui.button
            variant="primary"
            size="md"
            icon="plus"
            wire:click="openCreateModal"
            @click="$dispatch('open-modal', 'category-form-modal')"
        >
            Tambah Kategori
        </x-ui.button>
    </div>

    <!-- Categories Table Card -->
    <div class="glass-card rounded-2xl overflow-hidden flex flex-col">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-[#ECEEF2] text-[11px] font-bold text-[#4E5262] uppercase tracking-wider bg-white/40">
                        <th class="py-3 px-4 w-20">Urutan</th>
                        <th class="py-3 px-4">Nama Kategori</th>
                        <th class="py-3 px-4 text-center">Jumlah Menu</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#ECEEF2]/70 text-xs">
                    @forelse($categories as $category)
                        <tr wire:key="category-{{ $category->id }}" class="hover:bg-white/50 transition-colors">
                            <td class="py-3.5 px-4 font-bold text-[#5E63E8] tabular-nums">
                                #{{ $category->sort_order }}
                            </td>
                            <td class="py-3.5 px-4 font-bold text-[#1E2029]">
                                {{ $category->name }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2.5 py-1 rounded-md bg-[#ECEEF2] text-[#5A5D6C] font-semibold text-xs tabular-nums">
                                    {{ $category->menus_count }} Menu
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <x-ui.button
                                        variant="secondary"
                                        size="sm"
                                        wire:click="openEditModal({{ $category->id }})"
                                    >
                                        Edit
                                    </x-ui.button>
                                    <button
                                        type="button"
                                        wire:click="openDeleteModal({{ $category->id }})"
                                        class="p-1.5 text-[#E5484D] hover:text-[#D03B40] bg-[#E5484D]/10 hover:bg-[#E5484D]/20 border border-[#E5484D]/20 rounded-lg transition-colors cursor-pointer"
                                        title="Hapus Kategori"
                                    >
                                        <x-ui.icon name="trash-2" class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <x-ui.empty-state
                                    icon="folder"
                                    title="Belum ada kategori menu"
                                    description="Tambahkan kategori seperti Makanan, Minuman, atau Paket Hemat."
                                >
                                    <x-ui.button
                                        variant="primary"
                                        size="sm"
                                        icon="plus"
                                        wire:click="openCreateModal"
                                        @click="$dispatch('open-modal', 'category-form-modal')"
                                    >
                                        Tambah Kategori
                                    </x-ui.button>
                                </x-ui.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal M12: Tambah / Edit Kategori -->
    <x-ui.modal name="category-form-modal" :title="$editingCategoryId ? 'Edit Kategori' : 'Tambah Kategori Baru'" size="md">
        <form wire:submit.prevent="saveCategory" class="flex flex-col gap-4">
            
            <x-ui.input
                label="Nama Kategori"
                wire:model="categoryName"
                placeholder="Contoh: Makanan Utama, Minuman Segar, Camilan"
                :error="$errors->first('categoryName')"
                required
            />

            <x-ui.input
                label="Urutan Tampil (Angka)"
                type="number"
                wire:model="sortOrder"
                :error="$errors->first('sortOrder')"
                required
            />

            <div class="flex items-center justify-end gap-2.5 mt-2">
                <x-ui.button variant="secondary" size="md" @click="$dispatch('close-modal', 'category-form-modal')">
                    Batal
                </x-ui.button>
                <x-ui.button variant="primary" size="md" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove>Simpan Kategori</span>
                    <span wire:loading>Menyimpan...</span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <!-- Modal M13: Hapus Kategori -->
    <x-ui.modal name="delete-category-modal" title="Hapus Kategori?" size="sm">
        <div class="flex flex-col gap-3">
            <p class="text-xs text-[#5A5D6C] leading-relaxed">
                Apakah Anda yakin ingin menghapus kategori ini? Kategori yang masih berisi menu tidak dapat dihapus.
            </p>

            <div class="flex items-center justify-end gap-2.5 mt-3">
                <x-ui.button variant="secondary" size="md" @click="$dispatch('close-modal', 'delete-category-modal')">
                    Batal
                </x-ui.button>
                <x-ui.button variant="danger" size="md" wire:click="executeDelete">
                    Hapus Kategori
                </x-ui.button>
            </div>
        </div>
    </x-ui.modal>

</div>

