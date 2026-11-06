<div class="flex flex-col gap-5">

    <!-- Header Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#1E2029] tracking-tight">Stok Opname & Penyesuaian</h1>
            <p class="text-xs text-[#4E5262] mt-0.5">Sinkronkan perhitungan fisik dapur dengan sistem dan catat bahan terbuang (waste)</p>
        </div>

        <x-ui.button
            variant="primary"
            size="md"
            icon="plus"
            wire:click="openCreateModal"
            @click="$dispatch('open-modal', 'opname-modal')"
        >
            Input Stok Opname
        </x-ui.button>
    </div>

    <!-- Filters Bar Card -->
    <div class="glass-card rounded-2xl p-4 flex flex-col sm:flex-row items-center gap-3">
        <div class="relative w-full sm:w-72">
            <x-ui.input
                wire:model.live.debounce.250ms="search"
                icon="search"
                placeholder="Cari nama bahan opname..."
            />
        </div>
    </div>

    <!-- Table Card -->
    <div class="glass-card rounded-2xl overflow-hidden flex flex-col">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-[#ECEEF2] text-[11px] font-bold text-[#4E5262] uppercase tracking-wider bg-white/40">
                        <th class="py-3 px-4">Waktu</th>
                        <th class="py-3 px-4">Nama Bahan</th>
                        <th class="py-3 px-4">Jenis</th>
                        <th class="py-3 px-4 text-right">Selisih Qty</th>
                        <th class="py-3 px-4">Alasan Wajib</th>
                        <th class="py-3 px-4">Petugas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#ECEEF2]/70 text-xs">
                    @forelse($adjustments as $adj)
                        <tr wire:key="adj-{{ $adj->id }}" class="hover:bg-white/50 transition-colors">
                            <td class="py-3.5 px-4 text-[#4E5262]">
                                {{ $adj->created_at->format('d M Y, H:i') }}
                            </td>
                            <td class="py-3.5 px-4 font-bold text-[#1E2029]">
                                {{ $adj->ingredient?->name ?? 'Bahan Dihapus' }}
                            </td>
                            <td class="py-3.5 px-4">
                                @if($adj->type === 'waste')
                                    <x-ui.badge variant="error" size="sm">Bahan Terbuang (Waste)</x-ui.badge>
                                @else
                                    <x-ui.badge variant="warning" size="sm">Stok Opname</x-ui.badge>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right font-bold tabular-nums {{ $adj->qty_change >= 0 ? 'text-[#2EB872]' : 'text-[#E5484D]' }}">
                                {{ $adj->qty_change > 0 ? '+' : '' }}{{ number_format($adj->qty_change, 1, ',', '.') }} {{ $adj->ingredient?->base_unit }}
                            </td>
                            <td class="py-3.5 px-4 text-[#5A5D6C] max-w-xs">
                                {{ $adj->reason }}
                            </td>
                            <td class="py-3.5 px-4 text-[#4E5262]">
                                {{ $adj->user?->name ?? 'Admin' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-ui.empty-state
                                    icon="clipboard-check"
                                    title="Belum ada riwayat stok opname"
                                    description="Lakukan pencatatan stok opname berkala untuk menyesuaikan stok dapur dengan saldo aplikasi."
                                >
                                    <x-ui.button
                                        variant="primary"
                                        size="sm"
                                        icon="plus"
                                        wire:click="openCreateModal"
                                        @click="$dispatch('open-modal', 'opname-modal')"
                                    >
                                        Input Stok Opname
                                    </x-ui.button>
                                </x-ui.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($adjustments->hasPages())
            <div class="p-4 border-t border-[#ECEEF2]">
                {{ $adjustments->links() }}
            </div>
        @endif
    </div>

    <!-- Modal M17: Input Stok Opname Fisik -->
    <x-ui.modal name="opname-modal" title="Input Stok Opname Fisik" size="md">
        <form wire:submit.prevent="saveOpname" class="flex flex-col gap-4">
            
            <x-ui.select label="Bahan Baku" wire:model.live="opnameIngredientId" required>
                @foreach($ingredients as $ing)
                    <option value="{{ $ing->id }}">{{ $ing->name }} (Satuan: {{ $ing->base_unit }})</option>
                @endforeach
            </x-ui.select>

            <x-ui.select label="Jenis Koreksi" wire:model="correctionType" required>
                <option value="adjustment">Stok Opname / Penyesuaian Fisik</option>
                <option value="waste">Bahan Rusak / Basi / Terbuang (Waste)</option>
            </x-ui.select>

            @if($this->selectedIngredient)
                <div class="p-3 rounded-xl bg-white/70 border border-[#ECEEF2] flex items-center justify-between text-xs text-[#5A5D6C]">
                    <span>Stok Sistem Saat Ini:</span>
                    <span class="font-bold text-[#1E2029] tabular-nums">
                        {{ number_format($this->selectedIngredient->current_stock, 1, ',', '.') }} {{ $this->selectedIngredient->base_unit }}
                    </span>
                </div>
            @endif

            <x-ui.input
                label="Stok Fisik Aktual (Satuan Dasar)"
                type="number"
                step="0.01"
                wire:model.live="actualStock"
                :suffix="$this->selectedIngredient?->base_unit"
                hint="Hasil hitungan timbang riil di dapur"
                required
            />

            @if($this->selectedIngredient)
                <div class="p-3.5 rounded-xl bg-[#5E63E8]/5 border border-[#5E63E8]/20 flex items-center justify-between text-xs">
                    <span class="text-[#5A5D6C]">Selisih Penyesuaian:</span>
                    <span class="font-bold text-sm tabular-nums {{ $this->difference >= 0 ? 'text-[#2EB872]' : 'text-[#E5484D]' }}">
                        {{ $this->difference > 0 ? '+' : '' }}{{ number_format($this->difference, 1, ',', '.') }} {{ $this->selectedIngredient->base_unit }}
                    </span>
                </div>
            @endif

            <x-ui.input
                label="Alasan Koreksi (Wajib)"
                wire:model="reason"
                placeholder="Contoh: Stok opname akhir pekan, ayam susut saat thawing, minyak tumpah"
                :error="$errors->first('reason')"
                required
            />

            <div class="flex items-center justify-end gap-2.5 mt-2">
                <x-ui.button variant="secondary" size="md" @click="$dispatch('close-modal', 'opname-modal')">
                    Batal
                </x-ui.button>
                <x-ui.button variant="primary" size="md" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove>Simpan Koreksi Stok</span>
                    <span wire:loading>Menyimpan...</span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

</div>

