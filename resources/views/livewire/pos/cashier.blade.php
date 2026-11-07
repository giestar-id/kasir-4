<div
    x-data="{
        cartOpen: false,
        initShortcuts() {
            window.addEventListener('keydown', (e) => {
                if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
                    e.preventDefault();
                    $refs.searchInput.focus();
                }
                if (e.key === 'F2') {
                    e.preventDefault();
                    $wire.openPaymentModal();
                }
                if (e.key === 'Escape') {
                    this.cartOpen = false;
                }
            });
        }
    }"
    x-init="initShortcuts()"
    class="flex flex-col gap-5 {{ !empty($cart) ? 'pb-24 lg:pb-0' : '' }}"
>

    <!-- Top POS Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <h1 class="text-xl sm:text-2xl font-bold text-[#1E2029] tracking-tight">Kasir POS</h1>
            <x-ui.badge variant="info" size="sm">
                Offline-Ready
            </x-ui.badge>
        </div>

        <div class="flex items-center gap-2 text-xs text-[#4E5262]">
            <span class="hidden sm:inline">Pintasan keyboard:</span>
            <kbd class="px-2 py-1 rounded-md bg-white border border-[#ECEEF2] text-[#1E2029] font-mono text-[11px] shadow-2xs">/ Cari</kbd>
            <kbd class="px-2 py-1 rounded-md bg-white border border-[#ECEEF2] text-[#1E2029] font-mono text-[11px] shadow-2xs">F2 Bayar</kbd>
            <kbd class="px-2 py-1 rounded-md bg-white border border-[#ECEEF2] text-[#1E2029] font-mono text-[11px] shadow-2xs">Esc Tutup</kbd>
        </div>
    </div>

    <!-- 12-Column Grid -->
    <div class="grid grid-cols-12 gap-5 items-start">

        <!-- Left 8 Columns: Catalog & Search -->
        <div class="col-span-12 lg:col-span-8 flex flex-col gap-4">

            <!-- Search and Filter Bar -->
            <div class="glass-card rounded-2xl p-3 sm:p-3.5 flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                <div class="relative w-full flex-1">
                    <x-ui.input
                        x-ref="searchInput"
                        wire:model.live.debounce.250ms="search"
                        icon="search"
                        placeholder="Ketik / untuk cari menu atau alias..."
                    />
                </div>

                <!-- Category Pills -->
                <div class="flex items-center gap-1.5 overflow-x-auto w-full sm:w-auto pb-1 sm:pb-0 scrollbar-none shrink-0">
                    <button
                        type="button"
                        wire:click="$set('selectedCategory', null)"
                        class="h-10 px-3.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer inline-flex items-center justify-center {{ is_null($selectedCategory) ? 'bg-[#5E63E8] text-white shadow-xs' : 'bg-white/80 text-[#5A5D6C] hover:bg-white hover:text-[#1E2029] border border-[#ECEEF2]' }}"
                    >
                        Semua
                    </button>
                    @foreach($categories as $cat)
                        <button
                            type="button"
                            wire:click="$set('selectedCategory', {{ $cat->id }})"
                            class="h-10 px-3.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer inline-flex items-center justify-center {{ $selectedCategory === $cat->id ? 'bg-[#5E63E8] text-white shadow-xs' : 'bg-white/80 text-[#5A5D6C] hover:bg-white hover:text-[#1E2029] border border-[#ECEEF2]' }}"
                        >
                            {{ $cat->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Product Cards Grid -->
            @if($menus->isNotEmpty())
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3.5">
                    @foreach($menus as $menu)
                        @php
                            $inCart = isset($cart[$menu->id]);
                            $cartQty = $inCart ? ($cart[$menu->id]['qty'] ?? 0) : 0;
                        @endphp
                        <div
                            wire:key="menu-{{ $menu->id }}"
                            wire:click="addToCart({{ $menu->id }})"
                            class="glass-card glass-card-hover rounded-2xl p-4 flex flex-col justify-between cursor-pointer select-none group transition-all duration-200 relative overflow-hidden {{ $inCart ? 'border-[#5E63E8] bg-[#5E63E8]/[0.05] ring-2 ring-[#5E63E8]/30 shadow-md' : 'hover:border-[#5E63E8]/40' }}"
                        >
                            <div>
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <span class="text-[11px] font-semibold text-[#4E5262] truncate">{{ $menu->category?->name ?? 'Menu' }}</span>
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        @if(! $menu->isRecipeComplete())
                                            <x-ui.badge variant="warning" size="sm">No Resep</x-ui.badge>
                                        @endif
                                        @if($inCart)
                                            <span class="px-2 py-0.5 rounded-lg bg-[#5E63E8] text-white font-bold text-[10px] tracking-tight shadow-xs flex items-center gap-1">
                                                <span>{{ $cartQty }}x</span>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <h4 class="text-sm font-bold text-[#1E2029] group-hover:text-[#5E63E8] transition-colors leading-snug line-clamp-2">
                                    {{ $menu->name }}
                                </h4>
                            </div>

                            <div class="mt-4 pt-3 border-t border-[#ECEEF2]/70 flex items-center justify-between">
                                <span class="text-sm font-bold {{ $inCart ? 'text-[#5E63E8]' : 'text-[#1E2029]' }} tabular-nums">
                                    Rp{{ number_format($menu->price, 0, ',', '.') }}
                                </span>
                                <div class="w-7 h-7 rounded-lg {{ $inCart ? 'bg-[#5E63E8] text-white shadow-xs scale-105' : 'bg-[#5E63E8]/10 text-[#5E63E8] group-hover:bg-[#5E63E8] group-hover:text-white' }} transition-all flex items-center justify-center">
                                    @if($inCart)
                                        <x-ui.icon name="check" class="w-4 h-4" />
                                    @else
                                        <x-ui.icon name="plus" class="w-4 h-4" />
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="glass-card rounded-2xl">
                    <x-ui.empty-state
                        icon="search-x"
                        title="Tidak ada menu ditemukan"
                        description="Coba ubah kata kunci pencarian atau kategori, atau tambahkan menu baru melalui menu Katalog."
                    >
                        <a href="{{ route('menu') }}" wire:navigate>
                            <x-ui.button variant="primary" size="sm" icon="plus">
                                Tambah Menu
                            </x-ui.button>
                        </a>
                    </x-ui.empty-state>
                </div>
            @endif

        </div>

        <!-- Mobile Drawer Backdrop Scrim -->
        <div
            x-show="cartOpen"
            x-cloak
            x-transition:enter="transition-opacity ease-linear duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="cartOpen = false"
            class="fixed inset-0 z-40 bg-[#183247]/30 backdrop-blur-[2px] lg:hidden"
        ></div>

        <!-- Right 4 Columns: Keranjang Aktif (Drawer on mobile/tablet, Sticky Sidebar on Desktop) -->
        <div
            id="cart-container"
            :class="cartOpen ? 'translate-y-0 shadow-2xl' : 'translate-y-full lg:translate-y-0'"
            class="fixed inset-x-0 bottom-0 z-50 lg:static lg:z-auto lg:col-span-4 max-h-[88vh] lg:max-h-none flex flex-col transition-transform duration-300 ease-out"
        >
            <div class="glass-card rounded-t-3xl lg:rounded-2xl flex flex-col shadow-2xl lg:shadow-lg overflow-hidden max-h-[88vh] lg:max-h-none border-t border-[#ECEEF2] lg:border-t-0">
                
                <!-- Mobile Drag Handle -->
                <div class="w-12 h-1.5 bg-[#D9DCE3] rounded-full mx-auto mt-2.5 mb-1 lg:hidden cursor-pointer" @click="cartOpen = false"></div>

                <!-- Cart Header -->
                <div class="px-5 py-3.5 border-b border-[#ECEEF2]/80 flex items-center justify-between bg-white/50">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-[#5E63E8]/10 text-[#5E63E8] flex items-center justify-center shrink-0">
                            <x-ui.icon name="shopping-bag" class="w-4 h-4" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-[#1E2029]">Keranjang Aktif</h3>
                            <p class="text-[11px] text-[#4E5262]">
                                {{ count($cart) }} menu dipilih ({{ array_sum(array_column($cart, 'qty')) }} porsi)
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        @if(!empty($cart))
                            <button
                                type="button"
                                wire:click="clearCart"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-red-100 bg-red-50 hover:bg-red-100 text-[#E5484D] px-2.5 py-1.5 text-xs font-semibold transition-colors cursor-pointer"
                                title="Kosongkan seluruh isi keranjang"
                            >
                                <x-ui.icon name="trash-2" class="w-3.5 h-3.5 shrink-0" />
                                <span>Kosongkan</span>
                            </button>
                        @endif

                        <!-- Close button on Mobile Drawer -->
                        <button
                            type="button"
                            @click="cartOpen = false"
                            class="lg:hidden p-1.5 rounded-lg text-[#5A5D6C] hover:text-[#1E2029] hover:bg-black/5 cursor-pointer"
                            title="Tutup keranjang"
                        >
                            <x-ui.icon name="x" class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                <!-- Cart Items List -->
                <div class="p-4 flex flex-col gap-3 overflow-y-auto max-h-[calc(88vh-290px)] lg:max-h-[380px] overscroll-contain">
                    @forelse($cart as $itemKey => $item)
                        <div wire:key="cart-{{ $item['menu_id'] }}" class="p-3.5 rounded-xl bg-white/80 border border-[#ECEEF2] flex flex-col gap-2 hover:border-[#5E63E8]/30 transition-colors shadow-2xs">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-xs font-bold text-[#1E2029] leading-snug truncate">{{ $item['name'] }}</h4>
                                    <span class="text-[11px] text-[#4E5262] tabular-nums">@ Rp{{ number_format($item['price'], 0, ',', '.') }}</span>
                                </div>
                                <span class="text-xs font-bold text-[#1E2029] tabular-nums shrink-0">
                                    Rp{{ number_format($item['price'] * $item['qty'], 0, ',', '.') }}
                                </span>
                            </div>

                            <!-- Qty steppers & Actions -->
                            <div class="flex items-center justify-between pt-1.5 border-t border-[#ECEEF2]/70">
                                <div class="flex items-center gap-1.5">
                                    <button
                                        type="button"
                                        wire:click="decreaseQty({{ $item['menu_id'] }})"
                                        class="w-7 h-7 rounded-lg bg-[#ECEEF2] hover:bg-[#D9DCE3] active:scale-95 text-[#1E2029] font-bold text-xs flex items-center justify-center cursor-pointer transition-all"
                                        title="Kurangi jumlah"
                                    >
                                        -
                                    </button>
                                    <span class="w-8 text-center text-xs font-bold text-[#1E2029] tabular-nums">{{ $item['qty'] }}</span>
                                    <button
                                        type="button"
                                        wire:click="increaseQty({{ $item['menu_id'] }})"
                                        class="w-7 h-7 rounded-lg bg-[#ECEEF2] hover:bg-[#D9DCE3] active:scale-95 text-[#1E2029] font-bold text-xs flex items-center justify-center cursor-pointer transition-all"
                                        title="Tambah jumlah"
                                    >
                                        +
                                    </button>
                                </div>

                                <button
                                    type="button"
                                    wire:click="removeFromCart({{ $item['menu_id'] }})"
                                    class="text-[#4E5262] hover:text-[#E5484D] hover:bg-red-50 p-1.5 rounded-lg transition-colors cursor-pointer"
                                    title="Hapus item dari keranjang"
                                >
                                    <x-ui.icon name="trash-2" class="w-3.5 h-3.5" />
                                </button>
                            </div>

                            <!-- Per item note -->
                            <div class="pt-0.5">
                                <input
                                    type="text"
                                    wire:model.blur="cart.{{ $itemKey }}.note"
                                    placeholder="Catatan item (pedas, tanpa es, dll)..."
                                    class="w-full text-[11px] bg-[#F8F9FB] rounded-lg px-2.5 py-1 text-[#5A5D6C] placeholder:text-[#4E5262]/60 border border-[#ECEEF2] focus:border-[#5E63E8] focus:bg-white outline-none transition-all"
                                />
                            </div>
                        </div>
                    @empty
                        <div class="py-12 flex flex-col items-center justify-center text-center text-[#4E5262]">
                            <div class="w-12 h-12 rounded-2xl bg-[#ECEEF2]/60 text-[#4E5262]/60 flex items-center justify-center mb-2.5">
                                <x-ui.icon name="shopping-bag" class="w-6 h-6 stroke-1" />
                            </div>
                            <span class="text-xs font-bold text-[#1E2029]">Keranjang masih kosong</span>
                            <span class="text-[11px] text-[#4E5262] mt-0.5 max-w-[200px]">Pilih menu di sebelah kiri untuk memulai transaksi baru.</span>
                        </div>
                    @endforelse
                </div>

                <!-- Transaction Summary Calculation -->
                <div class="p-4 sm:p-5 bg-white/70 border-t border-[#ECEEF2] flex flex-col gap-2.5 shrink-0">
                    
                    <div class="flex items-center justify-between text-xs text-[#5A5D6C]">
                        <span>Subtotal</span>
                        <span class="font-bold text-[#1E2029] tabular-nums">Rp{{ number_format($this->subtotal, 0, ',', '.') }}</span>
                    </div>

                    @if($this->discountValue > 0)
                        <div class="flex items-center justify-between text-xs text-[#E5484D]">
                            <span>Diskon ({{ $discountType === 'percent' ? $discountAmount . '%' : 'Rp' }})</span>
                            <span class="font-bold tabular-nums">-Rp{{ number_format($this->discountValue, 0, ',', '.') }}</span>
                        </div>
                    @endif

                    <div class="pt-2 border-t border-[#ECEEF2] flex items-baseline justify-between">
                        <span class="text-xs font-bold text-[#1E2029]">Total Tagihan</span>
                        <span class="text-xl font-bold text-[#5E63E8] tabular-nums">Rp{{ number_format($this->total, 0, ',', '.') }}</span>
                    </div>

                    <!-- Note input -->
                    <div class="mt-0.5">
                        <input
                            type="text"
                            wire:model.defer="transactionNote"
                            placeholder="Catatan pesanan / nama meja (opsional)..."
                            class="w-full text-xs bg-white/90 border border-[#ECEEF2] rounded-xl px-3 py-2 text-[#1E2029] placeholder:text-[#4E5262] focus:outline-none focus:border-[#5E63E8] shadow-2xs"
                        />
                    </div>

                    <!-- Payment Button -->
                    <button
                        type="button"
                        wire:click="openPaymentModal"
                        {{ empty($cart) ? 'disabled' : '' }}
                        class="w-full mt-1.5 bg-[#5E63E8] hover:bg-[#4D52D6] active:bg-[#4448CE] disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold text-sm py-3 px-4 rounded-xl shadow-md shadow-[#5E63E8]/20 transition-all flex items-center justify-center gap-2 cursor-pointer active:scale-[0.99]"
                    >
                        <x-ui.icon name="credit-card" class="w-4 h-4" />
                        <span>Bayar Sekarang (F2)</span>
                    </button>
                </div>

            </div>
        </div>

    </div>

    <!-- Mobile Floating Cart Toggle Bar (Like project_lumbung_padi, visible on mobile/tablet when cart has items) -->
    @if(!empty($cart))
        <div
            x-show="!cartOpen"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-6"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="fixed bottom-4 inset-x-4 z-30 lg:hidden"
        >
            <button
                type="button"
                @click="cartOpen = true"
                class="w-full bg-[#5E63E8] hover:bg-[#4D52D6] active:bg-[#4448CE] text-white p-3.5 rounded-2xl shadow-xl shadow-[#5E63E8]/30 flex items-center justify-between cursor-pointer transition-transform active:scale-[0.99]"
            >
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center font-bold text-xs shrink-0">
                        {{ array_sum(array_column($cart, 'qty')) }}
                    </div>
                    <div class="flex flex-col text-left">
                        <span class="text-xs font-bold leading-tight">Keranjang ({{ count($cart) }} menu)</span>
                        <span class="text-[10px] text-white/80">Buka rincian & bayar</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-bold tabular-nums">Rp{{ number_format($this->total, 0, ',', '.') }}</span>
                    <x-ui.icon name="chevron-up" class="w-4 h-4" />
                </div>
            </button>
        </div>
    @endif

    <!-- Modal M2: Pembayaran & Konfirmasi Transaksi -->
    <x-ui.modal name="payment-modal" title="Selesaikan Pembayaran" size="md">
        <form wire:submit.prevent="processSale" class="flex flex-col gap-4">
            
            <!-- Payment Method Tabs -->
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#5A5D6C]">Metode Pembayaran</label>
                <div class="grid grid-cols-3 gap-2">
                    <button
                        type="button"
                        wire:click="$set('paymentMethod', 'cash')"
                        class="py-2.5 px-3 rounded-xl border text-xs font-bold flex flex-col items-center gap-1.5 transition-all cursor-pointer {{ $paymentMethod === 'cash' ? 'bg-[#5E63E8]/10 border-[#5E63E8] text-[#5E63E8]' : 'bg-white border-[#ECEEF2] text-[#5A5D6C] hover:bg-[#F8F8FA]' }}"
                    >
                        <x-ui.icon name="banknote" class="w-4 h-4" />
                        <span>Tunai</span>
                    </button>
                    <button
                        type="button"
                        wire:click="$set('paymentMethod', 'qris')"
                        class="py-2.5 px-3 rounded-xl border text-xs font-bold flex flex-col items-center gap-1.5 transition-all cursor-pointer {{ $paymentMethod === 'qris' ? 'bg-[#5E63E8]/10 border-[#5E63E8] text-[#5E63E8]' : 'bg-white border-[#ECEEF2] text-[#5A5D6C] hover:bg-[#F8F8FA]' }}"
                    >
                        <x-ui.icon name="qr-code" class="w-4 h-4" />
                        <span>QRIS</span>
                    </button>
                    <button
                        type="button"
                        wire:click="$set('paymentMethod', 'transfer')"
                        class="py-2.5 px-3 rounded-xl border text-xs font-bold flex flex-col items-center gap-1.5 transition-all cursor-pointer {{ $paymentMethod === 'transfer' ? 'bg-[#5E63E8]/10 border-[#5E63E8] text-[#5E63E8]' : 'bg-white border-[#ECEEF2] text-[#5A5D6C] hover:bg-[#F8F8FA]' }}"
                    >
                        <x-ui.icon name="arrow-right-left" class="w-4 h-4" />
                        <span>Transfer</span>
                    </button>
                </div>
            </div>

            <!-- Discount Section (Owner or permitted Cashier) -->
            @if(auth()->user()?->isOwner() || auth()->user()?->can_give_discount)
                <div x-data="{ openDiscount: {{ $discountAmount > 0 ? 'true' : 'false' }} }" class="p-3.5 rounded-xl bg-white/70 border border-[#ECEEF2]">
                    <div class="flex items-center justify-between cursor-pointer select-none" @click="openDiscount = !openDiscount">
                        <span class="text-xs font-bold text-[#1E2029]">Diskon / Potongan Harga</span>
                        <x-ui.icon name="chevron-down" class="w-4 h-4 text-[#4E5262] transition-transform duration-200" ::class="openDiscount ? 'rotate-180' : ''" />
                    </div>

                    <div
                        x-show="openDiscount"
                        x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-2"
                        class="mt-3 flex flex-col gap-3"
                    >
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                wire:click="$set('discountType', 'fixed')"
                                class="py-1.5 rounded-lg border text-xs font-semibold {{ $discountType === 'fixed' ? 'bg-[#5E63E8] text-white' : 'bg-white border-[#ECEEF2] text-[#5A5D6C]' }}"
                            >
                                Nominal (Rp)
                            </button>
                            <button
                                type="button"
                                wire:click="$set('discountType', 'percent')"
                                class="py-1.5 rounded-lg border text-xs font-semibold {{ $discountType === 'percent' ? 'bg-[#5E63E8] text-white' : 'bg-white border-[#ECEEF2] text-[#5A5D6C]' }}"
                            >
                                Persen (%)
                            </button>
                        </div>

                        <x-ui.input
                            label="Nilai Diskon"
                            type="number"
                            wire:model.live="discountAmount"
                            :prefix="$discountType === 'fixed' ? 'Rp' : '%'"
                        />

                        @if($discountAmount > 0)
                            <x-ui.input
                                label="Alasan Diskon (Wajib)"
                                wire:model.defer="discountReason"
                                placeholder="Contoh: Promo Grand Opening, Teman Owner, dll"
                                required
                            />
                        @endif
                    </div>
                </div>
            @endif

            <!-- Total Preview Row -->
            <div class="p-4 rounded-xl bg-[#5E63E8]/5 border border-[#5E63E8]/20 flex items-center justify-between">
                <div>
                    <span class="text-xs font-medium text-[#5A5D6C]">Total yang harus dibayar:</span>
                    <div class="text-2xl font-bold text-[#5E63E8] tabular-nums">
                        Rp{{ number_format($this->total, 0, ',', '.') }}
                    </div>
                </div>
                <div class="text-right text-xs text-[#4E5262]">
                    <span>{{ count($cart) }} Menu</span>
                </div>
            </div>

            <!-- Cash Input & Quick Chips (if Tunai) -->
            @if($paymentMethod === 'cash')
                <div class="flex flex-col gap-2">
                    <x-ui.input
                        label="Uang Diterima"
                        type="number"
                        wire:model.live="paidAmount"
                        prefix="Rp"
                        required
                    />

                    <!-- Quick Nominal Chips -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <button
                            type="button"
                            wire:click="setUangPas"
                            class="px-2.5 py-1 rounded-lg bg-white border border-[#ECEEF2] text-xs font-semibold text-[#1E2029] hover:bg-[#F8F8FA] cursor-pointer"
                        >
                            Uang Pas
                        </button>
                        <button
                            type="button"
                            wire:click="setNominal(50000)"
                            class="px-2.5 py-1 rounded-lg bg-white border border-[#ECEEF2] text-xs font-semibold text-[#1E2029] hover:bg-[#F8F8FA] cursor-pointer"
                        >
                            Rp50.000
                        </button>
                        <button
                            type="button"
                            wire:click="setNominal(100000)"
                            class="px-2.5 py-1 rounded-lg bg-white border border-[#ECEEF2] text-xs font-semibold text-[#1E2029] hover:bg-[#F8F8FA] cursor-pointer"
                        >
                            Rp100.000
                        </button>
                        <button
                            type="button"
                            wire:click="setNominal(200000)"
                            class="px-2.5 py-1 rounded-lg bg-white border border-[#ECEEF2] text-xs font-semibold text-[#1E2029] hover:bg-[#F8F8FA] cursor-pointer"
                        >
                            Rp200.000
                        </button>
                    </div>

                    <!-- Calculated Kembalian -->
                    <div class="mt-2 p-3 rounded-xl bg-white/80 border border-[#ECEEF2] flex items-center justify-between">
                        <span class="text-xs font-bold text-[#5A5D6C]">Kembalian:</span>
                        <span class="text-lg font-bold text-[#2EB872] tabular-nums">
                            Rp{{ number_format($this->change, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            @endif

            <div class="flex items-center justify-end gap-2.5 mt-4 pt-3 border-t border-[#ECEEF2]">
                <x-ui.button variant="secondary" size="md" @click="$dispatch('close-modal', 'payment-modal')">
                    Batal
                </x-ui.button>
                <x-ui.button
                    variant="primary"
                    size="md"
                    type="submit"
                    wire:loading.attr="disabled"
                    icon="check"
                >
                    <span wire:loading.remove>Simpan & Cetak Struk</span>
                    <span wire:loading>Memproses...</span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <!-- Modal M3: Struk Transaksi (Thermal 58/80mm format) -->
    <x-ui.modal name="receipt-modal" title="Struk Transaksi" size="sm">
        @if($lastSale)
            <div id="receipt-print-area" class="bg-white p-4 font-mono text-xs text-black border border-gray-200 rounded-lg max-w-[320px] mx-auto leading-tight">
                
                <!-- Store Info -->
                <div class="text-center pb-3 border-b border-dashed border-gray-400">
                    <h2 class="text-sm font-bold uppercase">{{ \App\Models\Setting::get('store_name', 'POS KULINER') }}</h2>
                    <p class="text-[10px] text-gray-600 mt-0.5">{{ \App\Models\Setting::get('store_address', 'Jl. Boulevard Raya No. 8') }}</p>
                    <p class="text-[10px] text-gray-600">Telp: {{ \App\Models\Setting::get('store_phone', '0812-3456-7890') }}</p>
                </div>

                <!-- Transaction Meta -->
                <div class="py-2.5 border-b border-dashed border-gray-400 text-[11px] flex flex-col gap-0.5">
                    <div class="flex justify-between">
                        <span>No:</span>
                        <span class="font-bold">{{ $lastSale->number }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Waktu:</span>
                        <span>{{ $lastSale->ordered_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Kasir:</span>
                        <span>{{ $lastSale->user?->name ?? 'Kasir' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Kanal:</span>
                        <span class="uppercase">{{ $lastSale->channel }}</span>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="py-2.5 border-b border-dashed border-gray-400 flex flex-col gap-1.5">
                    @foreach($lastSale->items as $item)
                        <div class="flex flex-col">
                            <div class="flex justify-between">
                                <span class="font-bold">{{ $item->menu_name_snapshot }}</span>
                                <span>Rp{{ number_format($item->qty * $item->unit_price, 0, ',', '.') }}</span>
                            </div>
                            <div class="text-[10px] text-gray-600">
                                {{ $item->qty }} x Rp{{ number_format($item->unit_price, 0, ',', '.') }}
                                @if($item->note)
                                    ({{ $item->note }})
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Totals -->
                <div class="py-2.5 border-b border-dashed border-gray-400 flex flex-col gap-1 text-[11px]">
                    <div class="flex justify-between">
                        <span>Subtotal:</span>
                        <span>Rp{{ number_format($lastSale->subtotal, 0, ',', '.') }}</span>
                    </div>
                    @if($lastSale->discount > 0)
                        <div class="flex justify-between text-gray-600">
                            <span>Diskon:</span>
                            <span>-Rp{{ number_format($lastSale->discount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between font-bold text-xs pt-1 border-t border-gray-300">
                        <span>TOTAL:</span>
                        <span>Rp{{ number_format($lastSale->total, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-[11px] pt-1">
                        <span>Bayar ({{ strtoupper($lastSale->payment_method) }}):</span>
                        <span>Rp{{ number_format($lastSale->paid_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-[11px]">
                        <span>Kembali:</span>
                        <span>Rp{{ number_format($lastSale->change_amount, 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- Footer Note -->
                <div class="pt-3 text-center text-[10px] text-gray-600 leading-tight">
                    <p>{{ \App\Models\Setting::get('receipt_footer', 'Terima kasih atas kunjungan Anda!') }}</p>
                    <p class="mt-1 text-[9px] text-gray-600 font-semibold">--- SIMPAN STRUK INI ---</p>
                </div>
            </div>

            <!-- Modal Action Buttons -->
            <div class="mt-5 flex items-center justify-between no-print">
                <x-ui.button variant="secondary" size="md" @click="$dispatch('close-modal', 'receipt-modal')">
                    Tutup (Esc)
                </x-ui.button>
                <x-ui.button
                    variant="primary"
                    size="md"
                    icon="printer"
                    onclick="window.print()"
                >
                    Cetak Struk
                </x-ui.button>
            </div>
        @endif
    </x-ui.modal>

</div>

