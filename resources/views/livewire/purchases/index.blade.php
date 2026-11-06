<div class="flex flex-col gap-5">

    <!-- Header Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#1E2029] tracking-tight">Pembelian Bahan Baku</h1>
            <p class="text-xs text-[#4E5262] mt-0.5">Catat belanja bahan untuk menambah stok dan menghitung harga rata-rata tertimbang</p>
        </div>

        <x-ui.button
            variant="primary"
            size="md"
            icon="plus"
            wire:click="openCreateModal"
            @click="$dispatch('open-modal', 'purchase-modal')"
        >
            Catat Pembelian
        </x-ui.button>
    </div>

    <!-- Filters Bar Card -->
    <div class="glass-card rounded-2xl p-4 flex flex-col sm:flex-row items-center gap-3">
        <div class="relative w-full sm:w-72">
            <x-ui.input
                wire:model.live.debounce.250ms="search"
                icon="search"
                placeholder="Cari nama bahan belanjaan..."
            />
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto">
            <select
                wire:model.live="ingredientFilter"
                class="bg-white/80 border border-[#ECEEF2] text-xs rounded-xl px-3 py-2 text-[#5A5D6C] focus:outline-none focus:border-[#5E63E8]"
            >
                <option value="">Semua Bahan Baku</option>
                @foreach($ingredients as $ing)
                    <option value="{{ $ing->id }}">{{ $ing->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Table Card -->
    <div class="glass-card rounded-2xl overflow-hidden flex flex-col">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-[#ECEEF2] text-[11px] font-bold text-[#4E5262] uppercase tracking-wider bg-white/40">
                        <th class="py-3 px-4">Tanggal Beli</th>
                        <th class="py-3 px-4">Nama Bahan</th>
                        <th class="py-3 px-4 text-right">Jumlah Belanja</th>
                        <th class="py-3 px-4 text-right">Konversi Dasar</th>
                        <th class="py-3 px-4 text-right">Total Dibayar</th>
                        <th class="py-3 px-4 text-right">Harga / Satuan Dasar</th>
                        <th class="py-3 px-4">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#ECEEF2]/70 text-xs">
                    @forelse($purchases as $p)
                        <tr wire:key="purchase-{{ $p->id }}" class="hover:bg-white/50 transition-colors">
                            <td class="py-3.5 px-4 text-[#4E5262]">
                                {{ $p->purchased_at->format('d M Y') }}
                            </td>
                            <td class="py-3.5 px-4 font-bold text-[#1E2029]">
                                {{ $p->ingredient?->name ?? 'Bahan Dihapus' }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-semibold text-[#5A5D6C] tabular-nums">
                                {{ number_format($p->qty_purchase_unit, 2, ',', '.') }} {{ $p->ingredient?->purchase_unit }}
                            </td>
                            <td class="py-3.5 px-4 text-right text-[#4E5262] tabular-nums">
                                {{ number_format($p->qty_base, 1, ',', '.') }} {{ $p->ingredient?->base_unit }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-bold text-[#1E2029] tabular-nums">
                                Rp{{ number_format($p->total_cost, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-semibold text-[#5E63E8] tabular-nums">
                                Rp{{ number_format($p->unit_cost_per_base, 0, ',', '.') }} / {{ $p->ingredient?->base_unit }}
                            </td>
                            <td class="py-3.5 px-4 text-[#4E5262]">
                                {{ $p->note ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <x-ui.empty-state
                                    icon="shopping-bag"
                                    title="Belum ada riwayat pembelian bahan"
                                    description="Catat pengeluaran belanja bahan dapur untuk memperbarui stok dan rata-rata harga pokok bahan."
                                >
                                    <x-ui.button
                                        variant="primary"
                                        size="sm"
                                        icon="plus"
                                        wire:click="openCreateModal"
                                        @click="$dispatch('open-modal', 'purchase-modal')"
                                    >
                                        Catat Pembelian Sekarang
                                    </x-ui.button>
                                </x-ui.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($purchases->hasPages())
            <div class="p-4 border-t border-[#ECEEF2]">
                {{ $purchases->links() }}
            </div>
        @endif
    </div>

    <!-- Modal M16: Catat Pembelian Bahan Baku -->
    <x-ui.modal name="purchase-modal" title="Catat Pembelian Bahan Baku" size="md">
        <form wire:submit.prevent="savePurchase" class="flex flex-col gap-4">
            
            <x-ui.select label="Bahan Baku" wire:model.live="purchaseIngredientId" required>
                @foreach($ingredients as $ing)
                    <option value="{{ $ing->id }}">{{ $ing->name }} (Beli: {{ $ing->purchase_unit }})</option>
                @endforeach
            </x-ui.select>

            <div class="grid grid-cols-2 gap-3">
                <x-ui.input
                    label="Tanggal Pembelian"
                    type="date"
                    wire:model="purchaseDate"
                    required
                />

                <x-ui.input
                    label="Jumlah (Satuan Beli)"
                    type="number"
                    step="0.01"
                    wire:model.live="qtyPurchaseUnit"
                    :suffix="$this->selectedIngredient?->purchase_unit"
                    required
                />
            </div>

            <!-- Conversion Preview -->
            @if($this->selectedIngredient)
                <div class="p-3 rounded-xl bg-white/70 border border-[#ECEEF2] flex items-center justify-between text-xs text-[#5A5D6C]">
                    <span>Hasil Konversi Satuan Dasar:</span>
                    <span class="font-bold text-[#1E2029] tabular-nums">
                        {{ number_format($this->convertedBaseQty, 1, ',', '.') }} {{ $this->selectedIngredient->base_unit }}
                    </span>
                </div>
            @endif

            <x-ui.input
                label="Total Harga Dibayar (Rp)"
                type="number"
                wire:model.live="totalCost"
                prefix="Rp"
                :error="$errors->first('totalCost')"
                required
            />

            <!-- Calculated Preview -->
            @if($this->selectedIngredient && $totalCost > 0)
                <div class="p-3.5 rounded-xl bg-[#5E63E8]/5 border border-[#5E63E8]/20 flex flex-col gap-1.5 text-xs">
                    <div class="flex justify-between text-[#5A5D6C]">
                        <span>Harga Beli per {{ $this->selectedIngredient->base_unit }}:</span>
                        <span class="font-bold tabular-nums text-[#1E2029]">
                            Rp{{ number_format($this->calculatedUnitCostBase, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="flex justify-between font-bold text-[#5E63E8] pt-1 border-t border-[#5E63E8]/10">
                        <span>Estimasi Rata-rata Tertimbang Baru:</span>
                        <span class="tabular-nums">
                            Rp{{ number_format($this->estimatedNewAvgCost, 0, ',', '.') }} / {{ $this->selectedIngredient->base_unit }}
                        </span>
                    </div>
                </div>
            @endif

            <x-ui.input
                label="Catatan Pembelian (Supplier / Pasar)"
                wire:model="purchaseNote"
                placeholder="Contoh: Belanja di Pasar Induk, Supplier Beras Makmur"
            />

            <div class="flex items-center justify-end gap-2.5 mt-2">
                <x-ui.button variant="secondary" size="md" @click="$dispatch('close-modal', 'purchase-modal')">
                    Batal
                </x-ui.button>
                <x-ui.button variant="primary" size="md" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove>Simpan Pembelian</span>
                    <span wire:loading>Menyimpan...</span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

</div>

