<div class="flex flex-col gap-5">

    <!-- Header Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#1E2029] tracking-tight">Dashboard</h1>
            <p class="text-xs text-[#4E5262] mt-0.5">Ringkasan performa penjualan, laba kotor, dan operasional outlet</p>
        </div>

        <a href="{{ route('pos') }}" wire:navigate class="shrink-0">
            <x-ui.button variant="primary" size="md" icon="plus">
                Transaksi Baru
            </x-ui.button>
        </a>
    </div>

    <!-- Period Filter Bar Card (Unified & Symmetrical) -->
    <div class="glass-card rounded-2xl p-3.5 sm:p-4 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 min-w-0">
        
        <!-- Active Period Info -->
        <div class="flex items-center gap-2.5 text-xs text-[#4E5262]">
            <div class="w-8 h-8 rounded-xl bg-[#5E63E8]/10 text-[#5E63E8] flex items-center justify-center shrink-0">
                <x-ui.icon name="calendar" class="w-4 h-4" />
            </div>
            <div class="flex flex-col">
                <span class="text-[11px] text-[#4E5262] font-medium">Periode Aktif:</span>
                <span class="font-bold text-[#1E2029]">
                    @if($period === 'today')
                        Hari Ini &bull; {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}
                    @elseif($period === '7_days')
                        7 Hari Terakhir &bull; {{ \Carbon\Carbon::parse($startDate)->format('d M') }} – {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                    @elseif($period === 'month')
                        Bulan Ini &bull; {{ \Carbon\Carbon::parse($startDate)->format('d M') }} – {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                    @else
                        Kustom &bull; {{ \Carbon\Carbon::parse($startDate)->format('d M') }} – {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                    @endif
                </span>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <!-- Period Selector Tabs -->
            <div class="flex items-center bg-[#F8F8FA] border border-[#ECEEF2] p-1 rounded-xl shadow-2xs">
                <button
                    type="button"
                    wire:click="setPeriod('today')"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer {{ $period === 'today' ? 'bg-white text-[#5E63E8] shadow-xs font-bold' : 'text-[#5A5D6C] hover:text-[#1E2029]' }}"
                >
                    Hari Ini
                </button>
                <button
                    type="button"
                    wire:click="setPeriod('7_days')"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer {{ $period === '7_days' ? 'bg-white text-[#5E63E8] shadow-xs font-bold' : 'text-[#5A5D6C] hover:text-[#1E2029]' }}"
                >
                    7 Hari
                </button>
                <button
                    type="button"
                    wire:click="setPeriod('month')"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer {{ $period === 'month' ? 'bg-white text-[#5E63E8] shadow-xs font-bold' : 'text-[#5A5D6C] hover:text-[#1E2029]' }}"
                >
                    Bulan Ini
                </button>
            </div>

            <!-- Custom Date Modal Button -->
            <x-ui.button
                variant="secondary"
                size="md"
                icon="calendar"
                wire:click="openCustomDateModal"
                @click="$dispatch('open-modal', 'custom-date-modal')"
            >
                <span>{{ $period === 'custom' ? date('d M', strtotime($startDate)) . ' - ' . date('d M', strtotime($endDate)) : 'Rentang Kustom' }}</span>
            </x-ui.button>
        </div>
    </div>

    <!-- 12-Column Main Workspace -->
    <div class="grid grid-cols-12 gap-5 items-start">
        
        <!-- Left 8 Columns -->
        <div class="col-span-12 lg:col-span-8 flex flex-col gap-5">
            
            <!-- 3 KPI Cards Row -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                
                <!-- KPI 1: Penjualan -->
                <div class="glass-card rounded-2xl p-5 relative overflow-hidden flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-[#5A5D6C]">Penjualan Periode Ini</span>
                        <div class="w-8 h-8 rounded-xl bg-[#5E63E8]/10 text-[#5E63E8] flex items-center justify-center">
                            <x-ui.icon name="trending-up" class="w-4 h-4" />
                        </div>
                    </div>
                    <div>
                        <div class="text-xl sm:text-2xl font-bold text-[#1E2029] tracking-tight tabular-nums">
                            Rp{{ number_format($totalOmzet, 0, ',', '.') }}
                        </div>
                        <div class="flex items-center gap-1.5 mt-1 text-xs text-[#4E5262]">
                            <x-ui.icon name="receipt" class="w-3.5 h-3.5" />
                            <span>{{ $totalTransactions }} Transaksi selesai</span>
                        </div>
                    </div>
                </div>

                <!-- KPI 2: Estimasi Laba Bersih -->
                <div class="glass-card rounded-2xl p-5 relative overflow-hidden flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-[#5A5D6C]">Estimasi Laba Bersih</span>
                        <div class="w-8 h-8 rounded-xl bg-[#2EB872]/10 text-[#2EB872] flex items-center justify-center" title="{{ $incompleteHppCount > 0 ? 'HPP Parsial' : 'HPP Lengkap' }}">
                            <x-ui.icon name="wallet" class="w-4 h-4" />
                        </div>
                    </div>
                    <div>
                        <div class="text-xl sm:text-2xl font-bold text-[#2EB872] tracking-tight tabular-nums">
                            Rp{{ number_format($grossProfit, 0, ',', '.') }}
                        </div>
                        <div class="flex items-center gap-1.5 mt-1 text-xs text-[#4E5262]">
                            <x-ui.icon name="pie-chart" class="w-3.5 h-3.5" />
                            <span>Margin keuntungan <strong class="text-[#1E2029] font-bold">{{ $profitMarginPct }}%</strong></span>
                        </div>
                    </div>
                </div>

                <!-- KPI 3: Pesanan Menunggu -->
                <div class="glass-card rounded-2xl p-5 relative overflow-hidden flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-[#5A5D6C]">Pesanan Menunggu</span>
                        <div class="w-8 h-8 rounded-xl bg-[#F59E0B]/10 text-[#F59E0B] flex items-center justify-center">
                            <x-ui.icon name="bike" class="w-4 h-4" />
                        </div>
                    </div>
                    <div>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-xl sm:text-2xl font-bold text-[#1E2029] tracking-tight tabular-nums">{{ $pendingOrdersCount }}</span>
                            <span class="text-xs text-[#5A5D6C] font-semibold">Pesanan Online</span>
                        </div>
                        <div class="flex items-center gap-1.5 mt-1 text-xs text-[#F59E0B]">
                            <x-ui.icon name="clock" class="w-3.5 h-3.5" />
                            <a href="{{ route('online-orders') }}" wire:navigate class="hover:underline font-medium">Perlu dikonfirmasi &rarr;</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Trend Chart Card -->
            <div class="glass-card rounded-2xl p-5 sm:p-6 flex flex-col">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
                    <div>
                        <h2 class="text-base font-bold text-[#1E2029] tracking-tight">Tren Performa Penjualan</h2>
                        <p class="text-xs text-[#4E5262] mt-0.5">Monitoring omzet dan estimasi laba bersih outlet</p>
                    </div>

                    <div class="flex items-center bg-[#F8F8FA] border border-[#ECEEF2] p-1 rounded-xl shadow-2xs self-start sm:self-auto">
                        <button
                            type="button"
                            wire:click="$set('chartMetric', 'omzet')"
                            class="px-3 py-1 rounded-lg text-xs font-semibold transition-all cursor-pointer {{ $chartMetric === 'omzet' ? 'bg-white text-[#5E63E8] shadow-xs' : 'text-[#4E5262] hover:text-[#1E2029]' }}"
                        >
                            Omzet
                        </button>
                        <button
                            type="button"
                            wire:click="$set('chartMetric', 'profit')"
                            class="px-3 py-1 rounded-lg text-xs font-semibold transition-all cursor-pointer {{ $chartMetric === 'profit' ? 'bg-white text-[#5E63E8] shadow-xs' : 'text-[#4E5262] hover:text-[#1E2029]' }}"
                        >
                            Laba Bersih
                        </button>
                    </div>
                </div>

                <!-- Visual Chart Display -->
                <div class="w-full h-56 rounded-xl bg-white/50 border border-[#ECEEF2] p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between text-xs text-[#4E5262]">
                        <span class="flex items-center gap-1.5">
                            <span class="w-3 h-1 rounded-full {{ $chartMetric === 'omzet' ? 'bg-[#5E63E8]' : 'bg-[#2EB872]' }}"></span>
                            <span>{{ $chartMetric === 'omzet' ? 'Total Omzet (Rp' . number_format($totalOmzet, 0, ',', '.') . ')' : 'Estimasi Laba (Rp' . number_format($grossProfit, 0, ',', '.') . ')' }}</span>
                        </span>
                        <span>{{ \Carbon\Carbon::parse($startDate)->format('d M') }} – {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</span>
                    </div>

                    <!-- Clean SVG Area Representation -->
                    <div class="relative w-full h-36 flex items-end justify-center">
                        @if($totalTransactions > 0)
                            <svg class="w-full h-full overflow-visible" viewBox="0 0 400 100" preserveAspectRatio="none">
                                <defs>
                                    <linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="{{ $chartMetric === 'omzet' ? '#5E63E8' : '#2EB872' }}" stop-opacity="0.3" />
                                        <stop offset="100%" stop-color="{{ $chartMetric === 'omzet' ? '#5E63E8' : '#2EB872' }}" stop-opacity="0.0" />
                                    </linearGradient>
                                </defs>
                                <path d="M 0 80 Q 80 40, 160 60 T 320 20 L 400 35 L 400 100 L 0 100 Z" fill="url(#chartGrad)" />
                                <path d="M 0 80 Q 80 40, 160 60 T 320 20 L 400 35" fill="none" stroke="{{ $chartMetric === 'omzet' ? '#5E63E8' : '#2EB872' }}" stroke-width="3" stroke-linecap="round" />
                                <circle cx="320" cy="20" r="4" fill="{{ $chartMetric === 'omzet' ? '#5E63E8' : '#2EB872' }}" />
                            </svg>
                        @else
                            <div class="flex flex-col items-center justify-center text-center text-[#4E5262]">
                                <x-ui.icon name="bar-chart-2" class="w-8 h-8 stroke-1 text-[#4E5262]/50 mb-1" />
                                <span class="text-xs">Belum ada transaksi pada periode ini</span>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-[#4E5262] border-t border-[#ECEEF2] pt-2">
                        <span>Awal Periode</span>
                        <span>Pertengahan</span>
                        <span>Akhir Periode</span>
                    </div>
                </div>
            </div>

            <!-- 5 Menu Terlaris Card -->
            <div class="glass-card rounded-2xl p-5 flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm sm:text-base font-bold text-[#1E2029]">5 Menu Terlaris</h3>
                    <a href="{{ route('reports') }}" wire:navigate class="text-xs font-semibold text-[#5E63E8] hover:underline">
                        Lihat Laporan Lengkap &rarr;
                    </a>
                </div>

                @if($topMenus->isNotEmpty())
                    <div class="flex flex-col gap-3">
                        @foreach($topMenus as $index => $menu)
                            @php
                                $maxQty = $topMenus->first()->total_qty ?: 1;
                                $pct = round(($menu->total_qty / $maxQty) * 100);
                            @endphp
                            <div class="flex flex-col gap-1.5">
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="w-5 h-5 rounded-md bg-[#ECEEF2] text-[#5A5D6C] font-bold text-[10px] flex items-center justify-center">
                                            {{ $index + 1 }}
                                        </span>
                                        <span class="font-semibold text-[#1E2029]">{{ $menu->menu_name_snapshot }}</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-[#4E5262]">{{ $menu->total_qty }} porsi</span>
                                        <span class="font-bold text-[#1E2029] tabular-nums">Rp{{ number_format($menu->total_revenue, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                                <div class="w-full h-2 rounded-full bg-[#ECEEF2] overflow-hidden">
                                    <div class="h-full rounded-full bg-[#5E63E8]" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty-state
                        icon="utensils"
                        title="Belum ada riwayat penjualan menu"
                        description="Data menu terlaris akan otomatis terisi saat transaksi berhasil diselesaikan."
                    />
                @endif
            </div>

        </div>

        <!-- Right 4 Columns -->
        <div class="col-span-12 lg:col-span-4 flex flex-col gap-5">
            
            <!-- Pembagian Kanal Penjualan Card -->
            <div class="glass-card rounded-2xl p-5 flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm sm:text-base font-bold text-[#1E2029]">Pembagian Kanal</h3>
                    <span class="text-xs text-[#4E5262]">{{ $totalTransactions }} Pesanan</span>
                </div>

                <div class="flex flex-col gap-3">
                    @foreach($channels as $key => $c)
                        @php
                            $channelPct = $totalOmzet > 0 ? round(($c['omzet'] / $totalOmzet) * 100) : 0;
                        @endphp
                        <div class="p-3 rounded-xl bg-white/60 border border-[#ECEEF2] flex flex-col gap-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full" style="background-color: {{ $c['color'] }}"></span>
                                    <span class="text-xs font-bold text-[#1E2029]">{{ $c['label'] }}</span>
                                </div>
                                <span class="text-xs font-bold tabular-nums" style="color: {{ $c['color'] }}">{{ $channelPct }}%</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-[#4E5262]">
                                <span>{{ $c['count'] }} Transaksi</span>
                                <span class="font-bold text-[#1E2029] tabular-nums">Rp{{ number_format($c['omzet'], 0, ',', '.') }}</span>
                            </div>
                            <div class="w-full h-1.5 rounded-full bg-[#ECEEF2] overflow-hidden">
                                <div class="h-full rounded-full" style="background-color: {{ $c['color'] }}; width: {{ $channelPct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Right Tab Card: Stok Kritis vs Pesanan Menunggu -->
            <div class="glass-card rounded-2xl p-5 flex flex-col">
                <div class="flex items-center border-b border-[#ECEEF2] pb-3 mb-4 gap-4">
                    <button
                        type="button"
                        wire:click="$set('rightTab', 'critical_stock')"
                        class="text-xs font-bold pb-1 transition-all border-b-2 cursor-pointer {{ $rightTab === 'critical_stock' ? 'border-[#5E63E8] text-[#5E63E8]' : 'border-transparent text-[#4E5262] hover:text-[#1E2029]' }}"
                    >
                        Bahan Stok Kritis ({{ $criticalStock->count() }})
                    </button>
                    <button
                        type="button"
                        wire:click="$set('rightTab', 'pending_orders')"
                        class="text-xs font-bold pb-1 transition-all border-b-2 cursor-pointer {{ $rightTab === 'pending_orders' ? 'border-[#5E63E8] text-[#5E63E8]' : 'border-transparent text-[#4E5262] hover:text-[#1E2029]' }}"
                    >
                        Pesanan Menunggu ({{ $pendingImports->count() }})
                    </button>
                </div>

                @if($rightTab === 'critical_stock')
                    @if($criticalStock->isNotEmpty())
                        <div class="flex flex-col gap-2.5">
                            @foreach($criticalStock as $ing)
                                <div class="p-2.5 rounded-xl bg-white/60 border border-[#ECEEF2] flex items-center justify-between">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-[#1E2029]">{{ $ing->name }}</span>
                                        <span class="text-[11px] text-[#4E5262]">Min: {{ number_format($ing->min_stock, 0, ',', '.') }} {{ $ing->base_unit }}</span>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs font-bold text-[#E5484D] tabular-nums">
                                            {{ number_format($ing->current_stock, 0, ',', '.') }} {{ $ing->base_unit }}
                                        </div>
                                        <span class="text-[10px] font-semibold text-[#E5484D]">Stok Menipis</span>
                                    </div>
                                </div>
                            @endforeach
                            <a href="{{ route('purchases') }}" wire:navigate class="mt-2 text-center text-xs font-semibold text-[#5E63E8] hover:underline">
                                + Catat Pembelian Bahan &rarr;
                            </a>
                        </div>
                    @else
                        <div class="py-6 flex flex-col items-center justify-center text-center text-[#4E5262]">
                            <x-ui.icon name="check-circle" class="w-8 h-8 text-[#2EB872] mb-1" />
                            <span class="text-xs font-semibold text-[#1E2029]">Semua Stok Bahan Aman</span>
                            <span class="text-[11px] text-[#4E5262]">Tidak ada bahan di bawah batas minimum.</span>
                        </div>
                    @endif
                @else
                    @if($pendingImports->isNotEmpty())
                        <div class="flex flex-col gap-2.5">
                            @foreach($pendingImports as $imp)
                                <div class="p-2.5 rounded-xl bg-white/60 border border-[#ECEEF2] flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <x-ui.badge :variant="$imp->platform === 'shopeefood' ? 'shopeefood' : 'gofood'" size="sm">
                                            {{ $imp->platform === 'shopeefood' ? 'ShopeeFood' : 'GoFood' }}
                                        </x-ui.badge>
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-[#1E2029]">{{ $imp->raw_result['external_order_id'] ?? 'Pesanan #' . $imp->id }}</span>
                                            <span class="text-[10px] text-[#4E5262]">{{ $imp->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                    <a href="{{ route('online-orders') }}" wire:navigate>
                                        <x-ui.button variant="secondary" size="sm">Periksa</x-ui.button>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-6 flex flex-col items-center justify-center text-center text-[#4E5262]">
                            <x-ui.icon name="check" class="w-8 h-8 text-[#5E63E8] mb-1" />
                            <span class="text-xs font-semibold text-[#1E2029]">Tidak Ada Antrian</span>
                            <span class="text-[11px] text-[#4E5262]">Semua screenshot pesanan online sudah selesai diproses.</span>
                        </div>
                    @endif
                @endif
            </div>

        </div>

    </div>

    <!-- Modal M1: Rentang Tanggal Kustom -->
    <x-ui.modal name="custom-date-modal" title="Pilih Rentang Tanggal" size="md">
        <form wire:submit="applyCustomPeriod" class="flex flex-col gap-4">
            <x-ui.input
                label="Tanggal Mulai"
                type="date"
                wire:model="customStartDate"
                required
            />
            <x-ui.input
                label="Tanggal Selesai"
                type="date"
                wire:model="customEndDate"
                required
            />

            <div class="flex items-center justify-end gap-2.5 mt-2 pt-3 border-t border-[#ECEEF2]">
                <x-ui.button variant="secondary" size="md" type="button" @click="$dispatch('close-modal', 'custom-date-modal')">
                    Batal
                </x-ui.button>
                <x-ui.button variant="primary" size="md" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="applyCustomPeriod">Terapkan Filter</span>
                    <span wire:loading wire:target="applyCustomPeriod">Menerapkan...</span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

</div>
