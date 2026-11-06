<div class="flex flex-col gap-5">

    <!-- Header Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#1E2029] tracking-tight">Bahan Baku & Stok</h1>
            <p class="text-xs text-[#8E92A4] mt-0.5">Monitoring stok bahan dasar, satuan konversi, dan kartu riwayat mutasi stok</p>
        </div>

        @if(auth()->user()?->isOwner())
            <div class="flex items-center gap-2.5">
                <a href="{{ route('purchases') }}" wire:navigate>
                    <x-ui.button variant="secondary" size="md" icon="shopping-bag">
                        Catat Pembelian
                    </x-ui.button>
                </a>
                <a href="{{ route('stock-opname') }}" wire:navigate>
                    <x-ui.button variant="secondary" size="md" icon="clipboard-check">
                        Stok Opname
                    </x-ui.button>
                </a>
                <x-ui.button
                    variant="primary"
                    size="md"
                    icon="plus"
                    wire:click="openCreateModal"
                    @click="$dispatch('open-modal', 'ingredient-form-modal')"
                >
                    Tambah Bahan
                </x-ui.button>
            </div>
        @endif
    </div>

    <!-- Filter Bar Card -->
    <div class="glass-card rounded-2xl p-4 flex flex-col sm:flex-row items-center gap-3">
        <div class="relative w-full sm:w-72">
            <x-ui.input
                wire:model.live.debounce.250ms="search"
                icon="search"
                placeholder="Cari nama bahan baku..."
            />
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto">
            <select
                wire:model.live="stockStatusFilter"
                class="bg-white/80 border border-[#ECEEF2] text-xs rounded-xl px-3 py-2 text-[#5A5D6C] focus:outline-none focus:border-[#5E63E8]"
            >
                <option value="all">Semua Kondisi Stok</option>
                <option value="low">Stok Menipis (≤ Minimum)</option>
                <option value="negative">Stok Negatif (< 0)</option>
            </select>
        </div>
    </div>

    <!-- Ingredients Table Card -->
    <div class="glass-card rounded-2xl overflow-hidden flex flex-col">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-[#ECEEF2] text-[11px] font-bold text-[#8E92A4] uppercase tracking-wider bg-white/40">
                        <th class="py-3 px-4">Nama Bahan</th>
                        <th class="py-3 px-4">Satuan Dasar</th>
                        <th class="py-3 px-4">Satuan Beli & Konversi</th>
                        <th class="py-3 px-4 text-right">Stok Saat Ini</th>
                        <th class="py-3 px-4 text-right">Stok Min.</th>
                        @if(auth()->user()?->isOwner())
                            <th class="py-3 px-4 text-right">Harga Rata-rata Dasar</th>
                        @endif
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#ECEEF2]/70 text-xs">
                    @forelse($ingredients as $ing)
                        @php
                            $status = $ing->stock_status;
                        @endphp
                        <tr wire:key="ingredient-{{ $ing->id }}" class="hover:bg-white/50 transition-colors {{ $status === 'negative' ? 'bg-red-50/30' : ($status === 'low' ? 'bg-amber-50/30' : '') }}">
                            <td class="py-3.5 px-4 font-bold text-[#1E2029]">
                                {{ $ing->name }}
                            </td>
                            <td class="py-3.5 px-4 text-[#5A5D6C] font-semibold">
                                {{ $ing->base_unit }}
                            </td>
                            <td class="py-3.5 px-4 text-[#8E92A4]">
                                1 {{ $ing->purchase_unit }} = {{ number_format($ing->unit_factor, 0, ',', '.') }} {{ $ing->base_unit }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-bold tabular-nums {{ $status === 'negative' ? 'text-[#E5484D]' : ($status === 'low' ? 'text-[#D97706]' : 'text-[#1E2029]') }}">
                                {{ number_format($ing->current_stock, 1, ',', '.') }} {{ $ing->base_unit }}
                            </td>
                            <td class="py-3.5 px-4 text-right text-[#8E92A4] tabular-nums">
                                {{ number_format($ing->min_stock, 0, ',', '.') }} {{ $ing->base_unit }}
                            </td>
                            @if(auth()->user()?->isOwner())
                                <td class="py-3.5 px-4 text-right font-bold text-[#5A5D6C] tabular-nums">
                                    Rp{{ number_format($ing->avg_cost_per_base, 0, ',', '.') }}
                                </td>
                            @endif
                            <td class="py-3.5 px-4">
                                @if($status === 'negative')
                                    <x-ui.badge variant="error" size="sm">Negatif</x-ui.badge>
                                @elseif($status === 'low')
                                    <x-ui.badge variant="warning" size="sm">Menipis</x-ui.badge>
                                @else
                                    <x-ui.badge variant="success" size="sm">Aman</x-ui.badge>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <x-ui.button
                                        variant="secondary"
                                        size="sm"
                                        wire:click="openStockHistoryModal({{ $ing->id }})"
                                    >
                                        Riwayat
                                    </x-ui.button>
                                    @if(auth()->user()?->isOwner())
                                        <x-ui.button
                                            variant="secondary"
                                            size="sm"
                                            wire:click="openEditModal({{ $ing->id }})"
                                        >
                                            Edit
                                        </x-ui.button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()?->isOwner() ? 8 : 7 }}">
                                <x-ui.empty-state
                                    icon="boxes"
                                    title="Belum ada bahan baku"
                                    description="Daftarkan bahan baku seperti Ayam, Minyak, Beras, Cup Plastik untuk resep dan HPP."
                                >
                                    @if(auth()->user()?->isOwner())
                                        <x-ui.button
                                            variant="primary"
                                            size="sm"
                                            icon="plus"
                                            wire:click="openCreateModal"
                                            @click="$dispatch('open-modal', 'ingredient-form-modal')"
                                        >
                                            Tambah Bahan Sekarang
                                        </x-ui.button>
                                    @endif
                                </x-ui.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($ingredients->hasPages())
            <div class="p-4 border-t border-[#ECEEF2]">
                {{ $ingredients->links() }}
            </div>
        @endif
    </div>

    <!-- Modal M14: Tambah / Edit Bahan Baku -->
    <x-ui.modal name="ingredient-form-modal" :title="$editingIngredientId ? 'Edit Bahan Baku' : 'Tambah Bahan Baku Baru'" size="md">
        <form wire:submit.prevent="saveIngredient" class="flex flex-col gap-4">
            
            <x-ui.input
                label="Nama Bahan Baku"
                wire:model="ingredientName"
                placeholder="Contoh: Daging Ayam Fillet, Beras Ramos, Minyak Goreng"
                :error="$errors->first('ingredientName')"
                required
            />

            <div class="grid grid-cols-2 gap-3">
                <x-ui.input
                    label="Satuan Dasar Dapur"
                    wire:model="baseUnit"
                    placeholder="gram / ml / pcs"
                    hint="Dipakai saat meracik resep"
                    required
                />

                <x-ui.input
                    label="Satuan Beli Pasar/Supplier"
                    wire:model="purchaseUnit"
                    placeholder="kg / liter / dus / karung"
                    hint="Dipakai saat belanja stok"
                    required
                />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <x-ui.input
                    label="Faktor Konversi (ke satuan dasar)"
                    type="number"
                    step="0.001"
                    wire:model="unitFactor"
                    hint="Contoh: 1 kg = 1000 gram"
                    required
                />

                <x-ui.input
                    label="Batas Minimum Stok"
                    type="number"
                    step="0.01"
                    wire:model="minStock"
                    hint="Peringatan saat stok di bawah nilai ini"
                    required
                />
            </div>

            <div class="flex items-center gap-2 pt-1">
                <label class="flex items-center gap-2 text-xs font-semibold text-[#1E2029] cursor-pointer">
                    <input
                        type="checkbox"
                        wire:model="ingredientIsActive"
                        class="w-4 h-4 rounded text-[#5E63E8] accent-[#5E63E8]"
                    />
                    <span>Status Aktif</span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-2.5 mt-2">
                <x-ui.button variant="secondary" size="md" @click="$dispatch('close-modal', 'ingredient-form-modal')">
                    Batal
                </x-ui.button>
                <x-ui.button variant="primary" size="md" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove>Simpan Bahan Baku</span>
                    <span wire:loading>Menyimpan...</span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <!-- Modal M15: Riwayat Kartu Pergerakan Stok -->
    <x-ui.modal name="stock-history-modal" :title="'Kartu Stok: ' . ($selectedIngredient?->name ?? 'Bahan')" size="lg">
        <div class="flex flex-col gap-3">
            @if($selectedIngredient)
                <div class="p-3 rounded-xl bg-white/70 border border-[#ECEEF2] flex items-center justify-between text-xs">
                    <div>
                        <span class="text-[#8E92A4]">Saldo Stok Saat Ini:</span>
                        <span class="font-bold text-base text-[#1E2029] ml-1 tabular-nums">
                            {{ number_format($selectedIngredient->current_stock, 1, ',', '.') }} {{ $selectedIngredient->base_unit }}
                        </span>
                    </div>
                    @if(auth()->user()?->isOwner())
                        <div>
                            <span class="text-[#8E92A4]">Harga Rata-rata:</span>
                            <span class="font-bold text-[#5E63E8] ml-1 tabular-nums">
                                Rp{{ number_format($selectedIngredient->avg_cost_per_base, 0, ',', '.') }}/{{ $selectedIngredient->base_unit }}
                            </span>
                        </div>
                    @endif
                </div>

                <div class="border border-[#ECEEF2] rounded-xl overflow-hidden max-h-[360px] overflow-y-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-white/60 text-[#8E92A4] border-b border-[#ECEEF2] text-[11px] uppercase">
                            <tr>
                                <th class="py-2.5 px-3">Waktu</th>
                                <th class="py-2.5 px-3">Jenis</th>
                                <th class="py-2.5 px-3 text-right">Perubahan Qty</th>
                                <th class="py-2.5 px-3">Alasan / Referensi</th>
                                <th class="py-2.5 px-3">Petugas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#ECEEF2]">
                            @forelse($movements as $m)
                                <tr>
                                    <td class="py-2 px-3 text-[#8E92A4]">
                                        {{ $m->created_at->format('d/m/y H:i') }}
                                    </td>
                                    <td class="py-2 px-3">
                                        @php
                                            $mTypeBadge = match($m->type) {
                                                'purchase' => ['success', 'Beli'],
                                                'sale_usage' => ['neutral', 'Jual'],
                                                'void_return' => ['info', 'Void'],
                                                'waste' => ['error', 'Waste'],
                                                default => ['warning', 'Koreksi'],
                                            };
                                        @endphp
                                        <x-ui.badge :variant="$mTypeBadge[0]" size="sm">
                                            {{ $mTypeBadge[1] }}
                                        </x-ui.badge>
                                    </td>
                                    <td class="py-2 px-3 text-right font-bold tabular-nums {{ $m->qty_change > 0 ? 'text-[#2EB872]' : 'text-[#E5484D]' }}">
                                        {{ $m->qty_change > 0 ? '+' : '' }}{{ number_format($m->qty_change, 1, ',', '.') }} {{ $selectedIngredient->base_unit }}
                                    </td>
                                    <td class="py-2 px-3 text-[#5A5D6C]">
                                        {{ $m->reason ?: ($m->ref_type . ' #' . $m->ref_id) }}
                                    </td>
                                    <td class="py-2 px-3 text-[#8E92A4]">
                                        {{ $m->user?->name ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-[#8E92A4]">
                                        Belum ada mutasi stok tercatat untuk bahan ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end pt-3 border-t border-[#ECEEF2]">
                    <x-ui.button variant="secondary" size="md" @click="$dispatch('close-modal', 'stock-history-modal')">
                        Tutup
                    </x-ui.button>
                </div>
            @endif
        </div>
    </x-ui.modal>

</div>

