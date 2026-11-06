<div class="flex flex-col gap-5">

    <!-- Header & Period Filter Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#1E2029] tracking-tight">Laporan Penjualan & Laba</h1>
            <p class="text-xs text-[#4E5262] mt-0.5">Analisis akurat omzet bersih, realisasi HPP, margin kotor, dan valuasi aset stok</p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <div class="flex items-center bg-white/70 border border-[#ECEEF2] p-1 rounded-xl shadow-2xs">
                <button
                    type="button"
                    wire:click="setPeriod('today')"
                    class="px-3 py-1 rounded-lg text-xs font-semibold transition-all cursor-pointer {{ $period === 'today' ? 'bg-[#5E63E8] text-white shadow-xs' : 'text-[#5A5D6C] hover:text-[#1E2029]' }}"
                >
                    Hari Ini
                </button>
                <button
                    type="button"
                    wire:click="setPeriod('week')"
                    class="px-3 py-1 rounded-lg text-xs font-semibold transition-all cursor-pointer {{ $period === 'week' ? 'bg-[#5E63E8] text-white shadow-xs' : 'text-[#5A5D6C] hover:text-[#1E2029]' }}"
                >
                    Minggu Ini
                </button>
                <button
                    type="button"
                    wire:click="setPeriod('month')"
                    class="px-3 py-1 rounded-lg text-xs font-semibold transition-all cursor-pointer {{ $period === 'month' ? 'bg-[#5E63E8] text-white shadow-xs' : 'text-[#5A5D6C] hover:text-[#1E2029]' }}"
                >
                    Bulan Ini
                </button>
            </div>

            <x-ui.button
                variant="secondary"
                size="md"
                icon="calendar"
                @click="$dispatch('open-modal', 'custom-date-modal')"
            >
                <span>{{ $period === 'custom' ? date('d M', strtotime($startDate)) . ' - ' . date('d M', strtotime($endDate)) : 'Kustom' }}</span>
            </x-ui.button>

            <x-ui.button
                variant="primary"
                size="md"
                icon="download"
                wire:click="exportCsv"
            >
                Ekspor CSV
            </x-ui.button>
        </div>
    </div>

    <!-- 3 Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        
        <!-- KPI 1: Omzet Bersih -->
        <div class="glass-card rounded-2xl p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-[#5A5D6C]">Total Omzet Bersih</span>
                <div class="w-8 h-8 rounded-xl bg-[#5E63E8]/10 text-[#5E63E8] flex items-center justify-center">
                    <x-ui.icon name="wallet" class="w-4 h-4" />
                </div>
            </div>
            <div>
                <div class="text-2xl font-bold text-[#1E2029] tracking-tight tabular-nums">
                    Rp{{ number_format($totalOmzet, 0, ',', '.') }}
                </div>
                <span class="text-xs text-[#4E5262] mt-1 block">Semua kanal non-void</span>
            </div>
        </div>

        <!-- KPI 2: HPP Terpakai -->
        <div class="glass-card rounded-2xl p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-[#5A5D6C]">Total HPP Bahan Terpakai</span>
                <div class="w-8 h-8 rounded-xl bg-[#F4A261]/15 text-[#EA580C] flex items-center justify-center">
                    <x-ui.icon name="boxes" class="w-4 h-4" />
                </div>
            </div>
            <div>
                <div class="text-2xl font-bold text-[#1E2029] tracking-tight tabular-nums">
                    Rp{{ number_format($totalHpp, 0, ',', '.') }}
                </div>
                <span class="text-xs text-[#4E5262] mt-1 block">Snapshot modal bahan terjual</span>
            </div>
        </div>

        <!-- KPI 3: Estimasi Laba Bersih -->
        <div class="glass-card rounded-2xl p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-[#5A5D6C]">Estimasi Laba Bersih</span>
                @if($incompleteHppCount > 0)
                    <x-ui.badge variant="warning" size="sm">HPP Tidak Lengkap ({{ $incompleteHppCount }} item)</x-ui.badge>
                @else
                    <x-ui.badge variant="success" size="sm">HPP Lengkap</x-ui.badge>
                @endif
            </div>
            <div>
                <div class="text-2xl font-bold text-[#2EB872] tracking-tight tabular-nums">
                    Rp{{ number_format($grossProfit, 0, ',', '.') }}
                </div>
                <div class="flex items-center gap-1.5 mt-1 text-xs text-[#4E5262]">
                    <span>Margin keuntungan bersih: <strong class="text-[#1E2029] font-bold">{{ $profitMarginPct }}%</strong></span>
                </div>
            </div>
        </div>

    </div>

    <!-- Report Tabs -->
    <div class="glass-card rounded-2xl p-2 flex items-center gap-1.5 overflow-x-auto scrollbar-none">
        <button
            type="button"
            wire:click="$set('activeTab', 'per_menu')"
            class="px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all cursor-pointer {{ $activeTab === 'per_menu' ? 'bg-[#5E63E8] text-white shadow-xs' : 'text-[#5A5D6C] hover:bg-white/60 hover:text-[#1E2029]' }}"
        >
            Per Menu
        </button>
        <button
            type="button"
            wire:click="$set('activeTab', 'per_period')"
            class="px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all cursor-pointer {{ $activeTab === 'per_period' ? 'bg-[#5E63E8] text-white shadow-xs' : 'text-[#5A5D6C] hover:bg-white/60 hover:text-[#1E2029]' }}"
        >
            Per Periode (Harian)
        </button>
        <button
            type="button"
            wire:click="$set('activeTab', 'per_channel')"
            class="px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all cursor-pointer {{ $activeTab === 'per_channel' ? 'bg-[#5E63E8] text-white shadow-xs' : 'text-[#5A5D6C] hover:bg-white/60 hover:text-[#1E2029]' }}"
        >
            Per Kanal Penjualan
        </button>
        <button
            type="button"
            wire:click="$set('activeTab', 'per_ingredient')"
            class="px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all cursor-pointer {{ $activeTab === 'per_ingredient' ? 'bg-[#5E63E8] text-white shadow-xs' : 'text-[#5A5D6C] hover:bg-white/60 hover:text-[#1E2029]' }}"
        >
            Valuasi Stok Bahan Baku
        </button>
    </div>

    <!-- Active Report Table -->
    <div class="glass-card rounded-2xl overflow-hidden flex flex-col">
        <div class="overflow-x-auto">
            
            @if($activeTab === 'per_menu')
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-[#ECEEF2] text-[11px] font-bold text-[#4E5262] uppercase tracking-wider bg-white/40">
                            <th class="py-3 px-4">Nama Menu</th>
                            <th class="py-3 px-4 text-center">Terjual (Qty)</th>
                            <th class="py-3 px-4 text-right">Omzet (Rp)</th>
                            <th class="py-3 px-4 text-right">HPP Total (Rp)</th>
                            <th class="py-3 px-4 text-right">Estimasi Laba Bersih (Rp)</th>
                            <th class="py-3 px-4 text-right">Margin (%)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#ECEEF2]/70 text-xs">
                        @forelse($menuReport as $m)
                            @php
                                $profit = max(0, $m->total_omzet - $m->total_hpp);
                                $margin = $m->total_omzet > 0 ? round(($profit / $m->total_omzet) * 100, 1) : 0;
                            @endphp
                            <tr class="hover:bg-white/50 transition-colors">
                                <td class="py-3.5 px-4 font-bold text-[#1E2029]">{{ $m->menu_name_snapshot }}</td>
                                <td class="py-3.5 px-4 text-center font-bold tabular-nums">{{ $m->total_qty }} porsi</td>
                                <td class="py-3.5 px-4 text-right font-bold text-[#1E2029] tabular-nums">Rp{{ number_format($m->total_omzet, 0, ',', '.') }}</td>
                                <td class="py-3.5 px-4 text-right text-[#4E5262] tabular-nums">Rp{{ number_format($m->total_hpp, 0, ',', '.') }}</td>
                                <td class="py-3.5 px-4 text-right font-bold text-[#2EB872] tabular-nums">Rp{{ number_format($profit, 0, ',', '.') }}</td>
                                <td class="py-3.5 px-4 text-right font-bold text-[#5E63E8] tabular-nums">{{ $margin }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-[#4E5262]">
                                    Belum ada transaksi menu pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            @elseif($activeTab === 'per_period')
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-[#ECEEF2] text-[11px] font-bold text-[#4E5262] uppercase tracking-wider bg-white/40">
                            <th class="py-3 px-4">Tanggal</th>
                            <th class="py-3 px-4 text-center">Jumlah Transaksi</th>
                            <th class="py-3 px-4 text-right">Total Diskon (Rp)</th>
                            <th class="py-3 px-4 text-right">Total Omzet (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#ECEEF2]/70 text-xs">
                        @forelse($dailyReport as $d)
                            <tr class="hover:bg-white/50 transition-colors">
                                <td class="py-3.5 px-4 font-bold text-[#1E2029]">{{ \Carbon\Carbon::parse($d->date)->format('d M Y') }}</td>
                                <td class="py-3.5 px-4 text-center font-bold tabular-nums">{{ $d->count }} Transaksi</td>
                                <td class="py-3.5 px-4 text-right text-[#E5484D] tabular-nums">Rp{{ number_format($d->discount, 0, ',', '.') }}</td>
                                <td class="py-3.5 px-4 text-right font-bold text-[#5E63E8] tabular-nums">Rp{{ number_format($d->omzet, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-12 text-center text-[#4E5262]">
                                    Belum ada transaksi pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            @elseif($activeTab === 'per_channel')
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-[#ECEEF2] text-[11px] font-bold text-[#4E5262] uppercase tracking-wider bg-white/40">
                            <th class="py-3 px-4">Kanal Penjualan</th>
                            <th class="py-3 px-4 text-center">Jumlah Transaksi</th>
                            <th class="py-3 px-4 text-right">Penyesuaian Platform (Rp)</th>
                            <th class="py-3 px-4 text-right">Total Omzet Bersih (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#ECEEF2]/70 text-xs">
                        @forelse($channelReport as $c)
                            <tr class="hover:bg-white/50 transition-colors">
                                <td class="py-3.5 px-4 font-bold uppercase text-[#1E2029]">{{ $c->channel }}</td>
                                <td class="py-3.5 px-4 text-center font-bold tabular-nums">{{ $c->count }} Transaksi</td>
                                <td class="py-3.5 px-4 text-right tabular-nums text-[#4E5262]">Rp{{ number_format($c->adjustment, 0, ',', '.') }}</td>
                                <td class="py-3.5 px-4 text-right font-bold text-[#5E63E8] tabular-nums">Rp{{ number_format($c->omzet, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-12 text-center text-[#4E5262]">
                                    Belum ada transaksi kanal pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            @else
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-[#ECEEF2] text-[11px] font-bold text-[#4E5262] uppercase tracking-wider bg-white/40">
                            <th class="py-3 px-4">Nama Bahan</th>
                            <th class="py-3 px-4 text-right">Stok Fisik Saat Ini</th>
                            <th class="py-3 px-4 text-right">Harga Pokok Rata-rata</th>
                            <th class="py-3 px-4 text-right">Total Nilai Aset Stok (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#ECEEF2]/70 text-xs">
                        @foreach($inventoryReport as $ing)
                            @php
                                $assetVal = (int) round((float) $ing->current_stock * (float) $ing->avg_cost_per_base);
                            @endphp
                            <tr class="hover:bg-white/50 transition-colors">
                                <td class="py-3.5 px-4 font-bold text-[#1E2029]">{{ $ing->name }}</td>
                                <td class="py-3.5 px-4 text-right font-semibold text-[#5A5D6C] tabular-nums">
                                    {{ number_format($ing->current_stock, 1, ',', '.') }} {{ $ing->base_unit }}
                                </td>
                                <td class="py-3.5 px-4 text-right text-[#4E5262] tabular-nums">
                                    Rp{{ number_format($ing->avg_cost_per_base, 0, ',', '.') }} / {{ $ing->base_unit }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-bold text-[#5E63E8] tabular-nums">
                                    Rp{{ number_format($assetVal, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                        <tr class="bg-white/60 font-bold border-t-2 border-[#ECEEF2]">
                            <td colspan="3" class="py-3.5 px-4 text-[#1E2029]">TOTAL NILAI ASET INVENTARIS:</td>
                            <td class="py-3.5 px-4 text-right text-base text-[#5E63E8] tabular-nums">
                                Rp{{ number_format($totalInventoryValue, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            @endif

        </div>
    </div>

    <!-- Modal M1: Rentang Tanggal Kustom -->
    <x-ui.modal name="custom-date-modal" title="Pilih Rentang Tanggal Laporan" size="md">
        <form x-data="{ start: '{{ $startDate }}', end: '{{ $endDate }}' }" @submit.prevent="$wire.applyCustomPeriod(start, end)" class="flex flex-col gap-4">
            <x-ui.input
                label="Tanggal Mulai"
                type="date"
                x-model="start"
                required
            />
            <x-ui.input
                label="Tanggal Selesai"
                type="date"
                x-model="end"
                required
            />

            <div class="flex items-center justify-end gap-2.5 mt-4">
                <x-ui.button variant="secondary" size="md" @click="$dispatch('close-modal', 'custom-date-modal')">
                    Batal
                </x-ui.button>
                <x-ui.button variant="primary" size="md" type="submit">
                    Terapkan Rentang
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

</div>

