<div class="flex flex-col gap-5">

    <!-- Header Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#1E2029] tracking-tight">Menu & HPP</h1>
            <p class="text-xs text-[#4E5262] mt-0.5">Kelola katalog menu satu harga, alias platform AI, dan kalkulasi HPP resep</p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('categories') }}" wire:navigate>
                <x-ui.button variant="secondary" size="md" icon="folder-tree">
                    Kelola Kategori
                </x-ui.button>
            </a>

            <x-ui.button
                variant="primary"
                size="md"
                icon="plus"
                wire:click="openCreateModal"
                @click="$dispatch('open-modal', 'menu-form-modal')"
            >
                Tambah Menu
            </x-ui.button>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="glass-card rounded-2xl p-4 flex flex-col sm:flex-row items-center gap-3">
        <div class="relative w-full sm:w-72">
            <x-ui.input
                wire:model.live.debounce.250ms="search"
                icon="search"
                placeholder="Cari nama menu atau alias..."
            />
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto">
            <select
                wire:model.live="categoryFilter"
                class="bg-white/80 border border-[#ECEEF2] text-xs rounded-xl px-3 py-2 text-[#5A5D6C] focus:outline-none focus:border-[#5E63E8]"
            >
                <option value="">Semua Kategori</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Menu Table Card -->
    <div class="glass-card rounded-2xl overflow-hidden flex flex-col">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-[#ECEEF2] text-[11px] font-bold text-[#4E5262] uppercase tracking-wider bg-white/40">
                        <th class="py-3 px-4">Nama Menu</th>
                        <th class="py-3 px-4">Kategori</th>
                        <th class="py-3 px-4">Alias Platform</th>
                        <th class="py-3 px-4 text-right">Harga Jual</th>
                        <th class="py-3 px-4 text-right">HPP / Porsi</th>
                        <th class="py-3 px-4 text-right">Margin</th>
                        <th class="py-3 px-4">Status Resep</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#ECEEF2]/70 text-xs">
                    @forelse($menus as $menu)
                        @php
                            $margin = $menu->margin;
                        @endphp
                        <tr wire:key="menu-row-{{ $menu->id }}" class="hover:bg-white/50 transition-colors">
                            <td class="py-3.5 px-4 font-bold text-[#1E2029]">
                                {{ $menu->name }}
                            </td>
                            <td class="py-3.5 px-4 text-[#5A5D6C]">
                                {{ $menu->category?->name ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-[#4E5262] max-w-[160px] truncate">
                                {{ $menu->aliases->pluck('alias')->implode(', ') ?: '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-bold text-[#1E2029] tabular-nums">
                                Rp{{ number_format($menu->price, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-bold text-[#5A5D6C] tabular-nums">
                                Rp{{ number_format($margin['hpp'], 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 text-right tabular-nums">
                                <span class="font-bold text-[#2EB872]">Rp{{ number_format($margin['margin_rp'], 0, ',', '.') }}</span>
                                <span class="text-[10px] text-[#4E5262] block">({{ $margin['margin_pct'] }}%)</span>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($margin['is_complete'])
                                    <x-ui.badge variant="success" size="sm">Lengkap</x-ui.badge>
                                @else
                                    <x-ui.badge variant="warning" size="sm">Resep belum lengkap</x-ui.badge>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                @if($menu->is_active)
                                    <x-ui.badge variant="success" size="sm">Aktif</x-ui.badge>
                                @else
                                    <x-ui.badge variant="neutral" size="sm">Nonaktif</x-ui.badge>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <x-ui.button
                                        variant="secondary"
                                        size="sm"
                                        wire:click="openRecipeModal({{ $menu->id }})"
                                    >
                                        Resep
                                    </x-ui.button>
                                    <x-ui.button
                                        variant="secondary"
                                        size="sm"
                                        wire:click="openEditModal({{ $menu->id }})"
                                    >
                                        Edit
                                    </x-ui.button>
                                    <button
                                        type="button"
                                        wire:click="openDeleteModal({{ $menu->id }})"
                                        class="p-1.5 text-[#4E5262] hover:text-[#E5484D] rounded-lg transition-colors cursor-pointer"
                                        title="Hapus / Nonaktifkan"
                                    >
                                        <x-ui.icon name="trash-2" class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <x-ui.empty-state
                                    icon="utensils"
                                    title="Belum ada menu kuliner"
                                    description="Tambahkan menu makanan atau minuman pertama Anda agar kasir dapat mencatat pesanan."
                                >
                                    <x-ui.button
                                        variant="primary"
                                        size="sm"
                                        icon="plus"
                                        wire:click="openCreateModal"
                                        @click="$dispatch('open-modal', 'menu-form-modal')"
                                    >
                                        Tambah Menu Sekarang
                                    </x-ui.button>
                                </x-ui.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($menus->hasPages())
            <div class="p-4 border-t border-[#ECEEF2]">
                {{ $menus->links() }}
            </div>
        @endif
    </div>

    <!-- Modal M9: Tambah / Edit Menu & Alias -->
    <x-ui.modal name="menu-form-modal" :title="$editingMenuId ? 'Edit Menu' : 'Tambah Menu Baru'" size="md">
        <form wire:submit.prevent="saveMenu" class="flex flex-col gap-4">
            
            <x-ui.input
                label="Nama Menu"
                wire:model="menuName"
                placeholder="Contoh: Ayam Geprek Sambal Bawang"
                :error="$errors->first('menuName')"
                required
            />

            <x-ui.select label="Kategori" wire:model="menuCategoryId" required>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.input
                label="Harga Jual (Rp) - Satu harga semua kanal"
                type="number"
                wire:model="menuPrice"
                prefix="Rp"
                :error="$errors->first('menuPrice')"
                required
            />

            <x-ui.input
                label="Daftar Alias Platform (Pisahkan dengan koma)"
                wire:model="menuAliases"
                placeholder="Contoh: Geprek Bawang, Ayam Geprek Lvl 1, Ayam Pedas"
                hint="Dipakai untuk mencocokkan pesanan AI GoFood/ShopeeFood"
            />

            <x-ui.input
                label="Catatan Menu (Opsional)"
                wire:model="menuNote"
                placeholder="Keterangan internal dapur..."
            />

            <div class="flex items-center gap-2 pt-1">
                <label class="flex items-center gap-2 text-xs font-semibold text-[#1E2029] cursor-pointer">
                    <input
                        type="checkbox"
                        wire:model="menuIsActive"
                        class="w-4 h-4 rounded text-[#5E63E8] accent-[#5E63E8]"
                    />
                    <span>Status Aktif (Tampil di kasir)</span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-2.5 mt-3 pt-3 border-t border-[#ECEEF2]">
                <x-ui.button variant="secondary" size="md" @click="$dispatch('close-modal', 'menu-form-modal')">
                    Batal
                </x-ui.button>
                <x-ui.button variant="primary" size="md" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove>Simpan Menu</span>
                    <span wire:loading>Menyimpan...</span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <!-- Modal M10: Resep & HPP Menu -->
    <x-ui.modal name="recipe-modal" title="Atur Resep & HPP Menu" size="lg">
        <form wire:submit.prevent="saveRecipe" class="flex flex-col gap-4">
            
            <p class="text-xs text-[#4E5262]">
                Tentukan takaran bahan baku per 1 porsi untuk menghitung Harga Pokok Penjualan (HPP) secara otomatis.
            </p>

            <!-- Ingredient rows -->
            <div class="flex flex-col gap-2.5">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-bold text-[#1E2029]">Bahan Baku per Porsi</h4>
                    <button
                        type="button"
                        wire:click="addRecipeIngredientRow"
                        class="text-xs font-bold text-[#5E63E8] hover:underline cursor-pointer"
                    >
                        + Tambah Bahan
                    </button>
                </div>

                @foreach($recipeIngredients as $rIdx => $rItem)
                    <div class="flex items-center gap-2 p-2 rounded-xl bg-white/70 border border-[#ECEEF2]">
                        <select
                            wire:model.live="recipeIngredients.{{ $rIdx }}.ingredient_id"
                            class="flex-1 text-xs bg-white border border-[#ECEEF2] rounded-lg p-2"
                        >
                            <option value="">-- Pilih Bahan Baku --</option>
                            @foreach($ingredients as $ing)
                                <option value="{{ $ing->id }}">
                                    {{ $ing->name }} (@ Rp{{ number_format($ing->avg_cost_per_base, 0, ',', '.') }}/{{ $ing->base_unit }})
                                </option>
                            @endforeach
                        </select>

                        <div class="flex items-center gap-1">
                            <input
                                type="number"
                                step="0.01"
                                min="0.001"
                                wire:model.live="recipeIngredients.{{ $rIdx }}.qty_per_portion"
                                placeholder="Qty"
                                class="w-20 text-center text-xs bg-white border border-[#ECEEF2] rounded-lg p-2 font-bold"
                            />
                        </div>

                        <button
                            type="button"
                            wire:click="removeRecipeIngredientRow({{ $rIdx }})"
                            class="p-2 text-[#4E5262] hover:text-[#E5484D] rounded-lg"
                        >
                            <x-ui.icon name="trash" class="w-4 h-4" />
                        </button>
                    </div>
                @endforeach
            </div>

            <!-- Extra Packaging Costs -->
            <div class="flex flex-col gap-2.5 pt-2 border-t border-[#ECEEF2]">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-bold text-[#1E2029]">Biaya Tambahan (Kemasan, dll)</h4>
                    <button
                        type="button"
                        wire:click="addExtraCostRow"
                        class="text-xs font-bold text-[#5E63E8] hover:underline cursor-pointer"
                    >
                        + Biaya Tambahan
                    </button>
                </div>

                @foreach($recipeExtraCosts as $eIdx => $eItem)
                    <div class="flex items-center gap-2">
                        <input
                            type="text"
                            wire:model.defer="recipeExtraCosts.{{ $eIdx }}.label"
                            placeholder="Nama biaya (mis. Box Kemasan)..."
                            class="flex-1 text-xs bg-white border border-[#ECEEF2] rounded-lg p-2"
                        />
                        <input
                            type="number"
                            wire:model.live="recipeExtraCosts.{{ $eIdx }}.amount"
                            placeholder="Rp"
                            class="w-28 text-xs bg-white border border-[#ECEEF2] rounded-lg p-2 font-bold"
                        />
                        <button
                            type="button"
                            wire:click="removeExtraCostRow({{ $eIdx }})"
                            class="p-2 text-[#4E5262] hover:text-[#E5484D]"
                        >
                            <x-ui.icon name="trash" class="w-4 h-4" />
                        </button>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-end gap-2.5 mt-3 pt-3 border-t border-[#ECEEF2]">
                <x-ui.button variant="secondary" size="md" @click="$dispatch('close-modal', 'recipe-modal')">
                    Batal
                </x-ui.button>
                <x-ui.button variant="primary" size="md" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove>Simpan Resep & HPP</span>
                    <span wire:loading>Menyimpan...</span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <!-- Modal M11: Hapus Menu -->
    <x-ui.modal name="delete-menu-modal" title="Hapus / Nonaktifkan Menu?" size="sm">
        <div class="flex flex-col gap-3">
            <p class="text-xs text-[#5A5D6C] leading-relaxed">
                Menu yang belum pernah memiliki riwayat transaksi akan dihapus secara permanen. Jika sudah pernah ada transaksi, menu otomatis dinonaktifkan agar laporan lama tetap valid.
            </p>

            <div class="flex items-center justify-end gap-2.5 mt-3">
                <x-ui.button variant="secondary" size="md" @click="$dispatch('close-modal', 'delete-menu-modal')">
                    Batal
                </x-ui.button>
                <x-ui.button variant="danger" size="md" wire:click="executeDelete">
                    Ya, Lanjutkan
                </x-ui.button>
            </div>
        </div>
    </x-ui.modal>

</div>

