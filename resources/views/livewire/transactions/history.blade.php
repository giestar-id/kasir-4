<div class="flex flex-col gap-5">

    <!-- Header Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#1E2029] tracking-tight">Riwayat Transaksi</h1>
            <p class="text-xs text-[#4E5262] mt-0.5">
                @if(auth()->user()?->isCashier())
                    Menampilkan transaksi shift aktif hari ini
                @else
                    Daftar seluruh transaksi masuk dari kasir offline dan pesanan online
                @endif
            </p>
        </div>
    </div>

    <!-- Filters Bar Card -->
    <div class="glass-card rounded-2xl p-3.5 sm:p-4 flex flex-col xl:flex-row items-stretch xl:items-center justify-between gap-3 min-w-0">
        
        <!-- Search Input -->
        <div class="relative flex-1 min-w-[240px]">
            <x-ui.input
                wire:model.live.debounce.300ms="search"
                icon="search"
                placeholder="Cari no. transaksi / order ID / nama barang..."
            />
        </div>

        <!-- Filters Group -->
        <div class="flex flex-wrap sm:flex-nowrap items-center gap-2.5 shrink-0">
            @if(auth()->user()?->isOwner())
                <div class="flex items-center gap-1.5 w-full sm:w-auto">
                    <div class="relative w-full sm:w-36 shrink-0">
                        <input
                            type="date"
                            wire:model.live="startDate"
                            class="w-full h-10 bg-white/90 text-[#1E2029] text-xs rounded-xl px-3 py-2 border border-[#ECEEF2] shadow-2xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#5E63E8]/20 focus:border-[#5E63E8] transition-all cursor-pointer"
                            title="Tanggal Mulai"
                        />
                    </div>
                    <span class="text-xs font-semibold text-[#4E5262] shrink-0">s/d</span>
                    <div class="relative w-full sm:w-36 shrink-0">
                        <input
                            type="date"
                            wire:model.live="endDate"
                            class="w-full h-10 bg-white/90 text-[#1E2029] text-xs rounded-xl px-3 py-2 border border-[#ECEEF2] shadow-2xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#5E63E8]/20 focus:border-[#5E63E8] transition-all cursor-pointer"
                            title="Tanggal Akhir"
                        />
                    </div>
                </div>
            @endif

            <!-- Channel Filter -->
            <div class="relative w-full sm:w-40 md:w-44 shrink-0">
                <select
                    wire:model.live="channelFilter"
                    class="w-full h-10 bg-white/90 text-[#1E2029] text-xs sm:text-sm rounded-xl py-2 pl-3.5 pr-8 border border-[#ECEEF2] shadow-2xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#5E63E8]/20 focus:border-[#5E63E8] transition-all appearance-none cursor-pointer"
                >
                    <option value="all">Semua Kanal</option>
                    <option value="offline">Offline</option>
                    <option value="gofood">GoFood</option>
                    <option value="shopeefood">ShopeeFood</option>
                </select>
                <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-[#4E5262]">
                    <x-ui.icon name="chevron-down" class="w-3.5 h-3.5 shrink-0" />
                </div>
            </div>

            <!-- Status Filter -->
            <div class="relative w-full sm:w-40 md:w-44 shrink-0">
                <select
                    wire:model.live="statusFilter"
                    class="w-full h-10 bg-white/90 text-[#1E2029] text-xs sm:text-sm rounded-xl py-2 pl-3.5 pr-8 border border-[#ECEEF2] shadow-2xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#5E63E8]/20 focus:border-[#5E63E8] transition-all appearance-none cursor-pointer"
                >
                    <option value="all">Semua Status</option>
                    <option value="completed">Selesai</option>
                    <option value="void">Dibatalkan (Void)</option>
                </select>
                <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-[#4E5262]">
                    <x-ui.icon name="chevron-down" class="w-3.5 h-3.5 shrink-0" />
                </div>
            </div>

            @if($search || $channelFilter !== 'all' || $statusFilter !== 'all' || $startDate || $endDate)
                <button
                    type="button"
                    wire:click="resetFilters"
                    class="h-10 px-3.5 text-xs font-bold text-[#4E5262] hover:text-[#E5484D] hover:bg-[#E5484D]/10 bg-white/80 border border-[#ECEEF2] rounded-xl transition-all cursor-pointer flex items-center gap-1.5 shrink-0 shadow-2xs"
                    title="Reset Filter"
                >
                    <x-ui.icon name="rotate-ccw" class="w-3.5 h-3.5 shrink-0" />
                    <span class="hidden sm:inline">Reset</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Transactions Table Card -->
    <div class="glass-card rounded-2xl overflow-hidden flex flex-col">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-[#ECEEF2] text-[11px] font-bold text-[#4E5262] uppercase tracking-wider bg-white/40">
                        <th class="py-3 px-4">No. Transaksi</th>
                        <th class="py-3 px-4">Waktu</th>
                        <th class="py-3 px-4">Kanal</th>
                        <th class="py-3 px-4">Nama Barang</th>
                        <th class="py-3 px-4">No. Eksternal</th>
                        <th class="py-3 px-4">Metode</th>
                        <th class="py-3 px-4 text-right">Total (Rp)</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Kasir</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#ECEEF2]/70 text-xs">
                    @forelse($sales as $sale)
                        <tr wire:key="sale-{{ $sale->id }}" class="hover:bg-white/50 transition-colors {{ $sale->isVoid() ? 'opacity-60 bg-red-50/20' : '' }}">
                            <td class="py-3.5 px-4 font-bold text-[#1E2029]">
                                {{ $sale->number }}
                            </td>
                            <td class="py-3.5 px-4 text-[#4E5262]">
                                {{ $sale->ordered_at->format('d/m/Y, H:i') }}
                            </td>
                            <td class="py-3.5 px-4">
                                @if($sale->channel === 'offline')
                                    <x-ui.badge variant="info" size="sm">Offline</x-ui.badge>
                                @elseif($sale->channel === 'gofood')
                                    <x-ui.badge variant="gofood" size="sm">GoFood</x-ui.badge>
                                @else
                                    <x-ui.badge variant="shopeefood" size="sm">ShopeeFood</x-ui.badge>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-[#1E2029]">
                                @php
                                    $itemsCount = $sale->items->count();
                                @endphp
                                @if($itemsCount > 0)
                                    <div class="flex flex-col gap-0.5 max-w-[200px] xl:max-w-[240px]">
                                        <div class="font-bold text-xs text-[#1E2029] truncate" title="{{ $sale->items->map(fn($it) => $it->qty . 'x ' . ($it->menu_name_snapshot ?: ($it->menu?->name ?? 'Barang')))->implode(', ') }}">
                                            @php
                                                $itemSummaries = $sale->items->take(2)->map(function($it) {
                                                    return $it->qty . 'x ' . ($it->menu_name_snapshot ?: ($it->menu?->name ?? 'Barang'));
                                                })->implode(', ');
                                            @endphp
                                            {{ $itemSummaries }}
                                            @if($itemsCount > 2)
                                                <span class="text-[11px] text-[#5E63E8] font-bold">+{{ $itemsCount - 2 }}</span>
                                            @endif
                                        </div>
                                        <span class="text-[11px] text-[#4E5262]">
                                            Total {{ $sale->items->sum('qty') }} porsi/item
                                        </span>
                                    </div>
                                @else
                                    <span class="text-[#8C90A0] italic text-[11px]">-</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-[#5A5D6C]">
                                {{ $sale->external_order_id ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4 uppercase text-[#5A5D6C] font-semibold">
                                {{ $sale->payment_method }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-bold text-[#1E2029] tabular-nums">
                                Rp{{ number_format($sale->total, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4">
                                @if($sale->isVoid())
                                    <x-ui.badge variant="error" size="sm">Void</x-ui.badge>
                                @else
                                    <x-ui.badge variant="success" size="sm">Selesai</x-ui.badge>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-[#5A5D6C]">
                                {{ $sale->user?->name ?? 'Kasir' }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <x-ui.button
                                        variant="secondary"
                                        size="sm"
                                        wire:click="openDetailModal({{ $sale->id }})"
                                    >
                                        Detail
                                    </x-ui.button>
                                    @if(auth()->user()?->isOwner() && ! $sale->isVoid())
                                        <x-ui.button
                                            variant="danger"
                                            size="sm"
                                            wire:click="openVoidModal({{ $sale->id }})"
                                        >
                                            Void
                                        </x-ui.button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <x-ui.empty-state
                                    icon="receipt"
                                    title="Belum ada transaksi"
                                    description="Semua transaksi yang diselesaikan di Kasir POS atau Pesanan Online akan tercatat di sini."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sales->hasPages())
            <div class="p-4 border-t border-[#ECEEF2]">
                {{ $sales->links() }}
            </div>
        @endif
    </div>

    <!-- Modal M7: Detail Transaksi -->
    <x-ui.modal name="detail-sale-modal" title="Detail Transaksi" size="lg">
        @if($selectedSale)
            <div class="flex flex-col gap-4">
                
                <!-- Status & Meta Grid -->
                <div class="p-4 rounded-xl bg-white/70 border border-[#ECEEF2] grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                    <div>
                        <span class="text-[#4E5262]">Nomor Transaksi</span>
                        <div class="font-bold text-[#1E2029] mt-0.5">{{ $selectedSale->number }}</div>
                    </div>
                    <div>
                        <span class="text-[#4E5262]">Waktu Pesanan</span>
                        <div class="font-bold text-[#1E2029] mt-0.5">{{ $selectedSale->ordered_at->format('d M Y, H:i') }}</div>
                    </div>
                    <div>
                        <span class="text-[#4E5262]">Kanal</span>
                        <div class="font-bold text-[#1E2029] uppercase mt-0.5">{{ $selectedSale->channel }}</div>
                    </div>
                    <div>
                        <span class="text-[#4E5262]">Nomor Eksternal</span>
                        <div class="font-bold text-[#1E2029] mt-0.5">{{ $selectedSale->external_order_id ?? '-' }}</div>
                    </div>
                    <div>
                        <span class="text-[#4E5262]">Kasir</span>
                        <div class="font-bold text-[#1E2029] mt-0.5">{{ $selectedSale->user?->name ?? 'Kasir' }}</div>
                    </div>
                    <div>
                        <span class="text-[#4E5262]">Status</span>
                        <div class="mt-0.5">
                            @if($selectedSale->isVoid())
                                <x-ui.badge variant="error" size="sm">Void</x-ui.badge>
                            @else
                                <x-ui.badge variant="success" size="sm">Selesai</x-ui.badge>
                            @endif
                        </div>
                    </div>
                </div>

                @if($selectedSale->isVoid())
                    <div class="p-3.5 rounded-xl bg-[#E5484D]/10 border border-[#E5484D]/20 text-xs flex flex-col gap-1 text-[#E5484D]">
                        <span class="font-bold">Informasi Pembatalan (Void):</span>
                        <span>Dibatalkan pada {{ $selectedSale->voided_at?->format('d M Y, H:i') }} oleh {{ $selectedSale->voidedBy?->name ?? 'Admin' }}</span>
                        <span><b>Alasan:</b> {{ $selectedSale->void_reason }}</span>
                    </div>
                @endif

                <!-- Item snapshot table -->
                <div class="flex flex-col gap-2.5">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold text-[#1E2029] flex items-center gap-1.5">
                            <x-ui.icon name="shopping-bag" class="w-4 h-4 text-[#5E63E8]" />
                            <span>Daftar Barang / Menu Yang Dibeli ({{ $selectedSale->items->count() }} item)</span>
                        </h4>
                        <span class="text-[11px] text-[#4E5262] font-semibold">
                            Total {{ $selectedSale->items->sum('qty') }} porsi
                        </span>
                    </div>

                    <div class="border border-[#ECEEF2] rounded-xl overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-white/60 text-[#4E5262] border-b border-[#ECEEF2]">
                                <tr>
                                    <th class="py-2.5 px-3">Nama Barang / Menu</th>
                                    <th class="py-2.5 px-3 text-center">Qty</th>
                                    <th class="py-2.5 px-3 text-right">Harga Satuan</th>
                                    @if(auth()->user()?->isOwner())
                                        <th class="py-2.5 px-3 text-right">HPP Snapshot</th>
                                    @endif
                                    <th class="py-2.5 px-3 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#ECEEF2]">
                                @forelse($selectedSale->items as $item)
                                    <tr>
                                        <td class="py-3 px-3 font-semibold text-[#1E2029]">
                                            <div class="flex items-start gap-2.5">
                                                <div class="w-7 h-7 rounded-lg bg-[#5E63E8]/10 text-[#5E63E8] flex items-center justify-center shrink-0 mt-0.5">
                                                    <x-ui.icon name="utensils" class="w-3.5 h-3.5" />
                                                </div>
                                                <div class="flex flex-col min-w-0">
                                                    <span class="font-bold text-[#1E2029] text-xs">
                                                        {{ $item->menu_name_snapshot ?: ($item->menu?->name ?? 'Barang #' . $item->menu_id) }}
                                                    </span>
                                                    @if($item->note)
                                                        <span class="text-[11px] text-[#5A5D6C] font-normal mt-0.5">Catatan: {{ $item->note }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-3 text-center tabular-nums font-semibold">{{ $item->qty }}</td>
                                        <td class="py-3 px-3 text-right tabular-nums">Rp{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                        @if(auth()->user()?->isOwner())
                                            <td class="py-3 px-3 text-right tabular-nums text-[#4E5262]">
                                                Rp{{ number_format($item->unit_hpp, 0, ',', '.') }}
                                            </td>
                                        @endif
                                        <td class="py-3 px-3 text-right font-bold text-[#1E2029] tabular-nums">
                                            Rp{{ number_format($item->qty * $item->unit_price, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ auth()->user()?->isOwner() ? 5 : 4 }}" class="py-4 text-center text-[#4E5262] italic">
                                            Tidak ada rincian barang untuk transaksi ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Totals Breakdown -->
                <div class="p-3.5 rounded-xl bg-white/60 border border-[#ECEEF2] flex flex-col gap-1.5 text-xs">
                    <div class="flex justify-between text-[#5A5D6C]">
                        <span>Subtotal:</span>
                        <span class="font-bold tabular-nums text-[#1E2029]">Rp{{ number_format($selectedSale->subtotal, 0, ',', '.') }}</span>
                    </div>
                    @if($selectedSale->discount > 0)
                        <div class="flex justify-between text-[#E5484D]">
                            <span>Diskon ({{ $selectedSale->discount_reason }}):</span>
                            <span class="font-bold tabular-nums">-Rp{{ number_format($selectedSale->discount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    @if($selectedSale->platform_adjustment != 0)
                        <div class="flex justify-between text-[#4E5262]">
                            <span>Penyesuaian Platform:</span>
                            <span class="font-bold tabular-nums">Rp{{ number_format($selectedSale->platform_adjustment, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    <div class="pt-2 border-t border-[#ECEEF2] flex justify-between font-bold text-sm text-[#5E63E8]">
                        <span>Total Transaksi:</span>
                        <span class="tabular-nums">Rp{{ number_format($selectedSale->total, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-[#ECEEF2]">
                    <x-ui.button variant="secondary" size="md" @click="$dispatch('close-modal', 'detail-sale-modal')">
                        Tutup
                    </x-ui.button>
                    @if(auth()->user()?->isOwner() && ! $selectedSale->isVoid())
                        <x-ui.button
                            variant="danger"
                            size="md"
                            wire:click="openVoidModal({{ $selectedSale->id }})"
                        >
                            Void Transaksi
                        </x-ui.button>
                    @endif
                </div>

            </div>
        @endif
    </x-ui.modal>

    <!-- Modal M8: Void Transaksi Confirmation -->
    <x-ui.modal name="void-sale-modal" title="Batalkan (Void) Transaksi?" size="sm">
        <form wire:submit.prevent="executeVoid" class="flex flex-col gap-4">
            <div class="p-3 rounded-xl bg-[#E5484D]/10 border border-[#E5484D]/20 text-xs text-[#E5484D] leading-relaxed">
                <span class="font-bold block mb-1">Perhatian Tindakan Berisiko:</span>
                Stok bahan baku akan otomatis dikembalikan ke inventaris (void_return) dan transaksi tidak akan lagi dihitung dalam omzet.
            </div>

            <x-ui.input
                label="Alasan Pembatalan (Wajib)"
                wire:model.defer="voidReason"
                placeholder="Contoh: Pelanggan salah pesan, transaksi salah ketik"
                :error="$errors->first('voidReason')"
                required
            />

            <div class="flex items-center justify-end gap-2.5 mt-2">
                <x-ui.button variant="secondary" size="md" @click="$dispatch('close-modal', 'void-sale-modal')">
                    Batal
                </x-ui.button>
                <x-ui.button variant="danger" size="md" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove>Ya, Void Transaksi</span>
                    <span wire:loading>Membatalkan...</span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

</div>

