<div class="flex flex-col gap-5">

    <!-- Header Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#1E2029] tracking-tight">Riwayat Transaksi</h1>
            <p class="text-xs text-[#8E92A4] mt-0.5">
                @if(auth()->user()?->isCashier())
                    Menampilkan transaksi shift aktif hari ini
                @else
                    Daftar seluruh transaksi masuk dari kasir offline dan pesanan online
                @endif
            </p>
        </div>
    </div>

    <!-- Filters Bar Card -->
    <div class="glass-card rounded-2xl p-4 flex flex-col md:flex-row items-center gap-3">
        <div class="relative w-full md:w-72">
            <x-ui.input
                wire:model.live.debounce.300ms="search"
                icon="search"
                placeholder="Cari no. transaksi / order ID..."
            />
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto flex-wrap">
            @if(auth()->user()?->isOwner())
                <input
                    type="date"
                    wire:model.live="startDate"
                    class="bg-white/80 border border-[#ECEEF2] text-xs rounded-xl px-3 py-2 text-[#5A5D6C] focus:outline-none focus:border-[#5E63E8]"
                />
                <span class="text-xs text-[#8E92A4]">s/d</span>
                <input
                    type="date"
                    wire:model.live="endDate"
                    class="bg-white/80 border border-[#ECEEF2] text-xs rounded-xl px-3 py-2 text-[#5A5D6C] focus:outline-none focus:border-[#5E63E8]"
                />
            @endif

            <select
                wire:model.live="channelFilter"
                class="bg-white/80 border border-[#ECEEF2] text-xs rounded-xl px-3 py-2 text-[#5A5D6C] focus:outline-none focus:border-[#5E63E8]"
            >
                <option value="all">Semua Kanal</option>
                <option value="offline">Offline</option>
                <option value="gofood">GoFood</option>
                <option value="shopeefood">ShopeeFood</option>
            </select>

            <select
                wire:model.live="statusFilter"
                class="bg-white/80 border border-[#ECEEF2] text-xs rounded-xl px-3 py-2 text-[#5A5D6C] focus:outline-none focus:border-[#5E63E8]"
            >
                <option value="all">Semua Status</option>
                <option value="completed">Selesai</option>
                <option value="void">Dibatalkan (Void)</option>
            </select>
        </div>
    </div>

    <!-- Transactions Table Card -->
    <div class="glass-card rounded-2xl overflow-hidden flex flex-col">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-[#ECEEF2] text-[11px] font-bold text-[#8E92A4] uppercase tracking-wider bg-white/40">
                        <th class="py-3 px-4">No. Transaksi</th>
                        <th class="py-3 px-4">Waktu</th>
                        <th class="py-3 px-4">Kanal</th>
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
                            <td class="py-3.5 px-4 text-[#8E92A4]">
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
                            <td colspan="9">
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
                        <span class="text-[#8E92A4]">Nomor Transaksi</span>
                        <div class="font-bold text-[#1E2029] mt-0.5">{{ $selectedSale->number }}</div>
                    </div>
                    <div>
                        <span class="text-[#8E92A4]">Waktu Pesanan</span>
                        <div class="font-bold text-[#1E2029] mt-0.5">{{ $selectedSale->ordered_at->format('d M Y, H:i') }}</div>
                    </div>
                    <div>
                        <span class="text-[#8E92A4]">Kanal</span>
                        <div class="font-bold text-[#1E2029] uppercase mt-0.5">{{ $selectedSale->channel }}</div>
                    </div>
                    <div>
                        <span class="text-[#8E92A4]">Nomor Eksternal</span>
                        <div class="font-bold text-[#1E2029] mt-0.5">{{ $selectedSale->external_order_id ?? '-' }}</div>
                    </div>
                    <div>
                        <span class="text-[#8E92A4]">Kasir</span>
                        <div class="font-bold text-[#1E2029] mt-0.5">{{ $selectedSale->user?->name ?? 'Kasir' }}</div>
                    </div>
                    <div>
                        <span class="text-[#8E92A4]">Status</span>
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
                <div class="border border-[#ECEEF2] rounded-xl overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-white/60 text-[#8E92A4] border-b border-[#ECEEF2]">
                            <tr>
                                <th class="py-2.5 px-3">Menu</th>
                                <th class="py-2.5 px-3 text-center">Qty</th>
                                <th class="py-2.5 px-3 text-right">Harga</th>
                                @if(auth()->user()?->isOwner())
                                    <th class="py-2.5 px-3 text-right">HPP Snapshot</th>
                                @endif
                                <th class="py-2.5 px-3 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#ECEEF2]">
                            @foreach($selectedSale->items as $item)
                                <tr>
                                    <td class="py-2.5 px-3 font-semibold text-[#1E2029]">
                                        {{ $item->menu_name_snapshot }}
                                        @if($item->note)
                                            <span class="block text-[11px] text-[#8E92A4] font-normal">Catatan: {{ $item->note }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3 text-center tabular-nums">{{ $item->qty }}</td>
                                    <td class="py-2.5 px-3 text-right tabular-nums">Rp{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                    @if(auth()->user()?->isOwner())
                                        <td class="py-2.5 px-3 text-right tabular-nums text-[#8E92A4]">
                                            Rp{{ number_format($item->unit_hpp, 0, ',', '.') }}
                                        </td>
                                    @endif
                                    <td class="py-2.5 px-3 text-right font-bold text-[#1E2029] tabular-nums">
                                        Rp{{ number_format($item->qty * $item->unit_price, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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
                        <div class="flex justify-between text-[#8E92A4]">
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

