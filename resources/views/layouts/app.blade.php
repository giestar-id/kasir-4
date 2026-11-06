<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'POS Kuliner Satu Outlet' }}</title>

    <!-- Google Fonts Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        [x-cloak] { display: none !important; }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="glass-canvas min-h-screen text-[#1E2029] antialiased selection:bg-[#5E63E8]/20 selection:text-[#5E63E8]">

    <!-- Soft Ambient Mesh Blobs (Pure CSS decorative layer under glass UI) -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
        <div class="absolute -top-32 -left-32 w-[520px] h-[520px] bg-[#5E63E8]/16 rounded-full blur-[110px]"></div>
        <div class="absolute top-1/4 -right-24 w-[560px] h-[560px] bg-[#F4A261]/15 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-10 left-1/4 w-[600px] h-[600px] bg-[#64CCF3]/16 rounded-full blur-[130px]"></div>
        <div class="absolute -bottom-24 right-10 w-[500px] h-[500px] bg-[#2EB872]/12 rounded-full blur-[110px]"></div>
    </div>

    <div
        x-data="{
            mobileNavOpen: false,
            desktopCollapsed: localStorage.getItem('pos_sidebar_collapsed') === 'true',
            isDesktop: window.innerWidth >= 768,
            toggleDesktopSidebar() {
                this.desktopCollapsed = !this.desktopCollapsed;
                localStorage.setItem('pos_sidebar_collapsed', this.desktopCollapsed);
                setTimeout(() => window.refreshIcons && window.refreshIcons(), 50);
            }
        }"
        x-init="
            $watch('mobileNavOpen', value => {
                if (value) {
                    document.body.classList.add('overflow-hidden');
                    setTimeout(() => window.refreshIcons && window.refreshIcons(), 50);
                } else {
                    document.body.classList.remove('overflow-hidden');
                }
            })
        "
        @resize.window="isDesktop = (window.innerWidth >= 768); if (isDesktop) mobileNavOpen = false;"
        @keydown.escape.window="mobileNavOpen = false"
        class="relative z-10 min-h-screen flex flex-col md:flex-row"
    >

        <!-- Mobile Drawer Overlay -->
        <div
            x-show="mobileNavOpen"
            x-cloak
            x-transition:enter="transition-opacity ease-linear duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="mobileNavOpen = false"
            class="fixed inset-0 glass-scrim z-40 md:hidden"
        ></div>

        <!-- Sidebar Navigation (Desktop Fixed & Mobile Slide-over) -->
        <aside
            :class="[
                mobileNavOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full md:translate-x-0',
                (isDesktop && desktopCollapsed) ? 'md:w-[76px] md:px-2.5' : 'md:w-[240px] md:px-4'
            ]"
            class="fixed top-0 bottom-0 left-0 w-[280px] max-w-[85vw] md:w-[240px] px-3.5 sm:px-4 h-screen overflow-y-auto overflow-x-hidden glass-sidebar z-[45] flex flex-col py-4 transition-all duration-300 ease-in-out"
        >
            <!-- Top Header in Sidebar -->
            <div class="flex items-center px-1 sm:px-1.5 mb-4 shrink-0" :class="(isDesktop && desktopCollapsed) ? 'justify-center' : 'justify-between'">
                <a href="{{ auth()->user()?->isOwner() ? route('dashboard') : route('pos') }}" wire:navigate.hover class="flex items-center gap-2.5 group min-w-0" :title="(isDesktop && desktopCollapsed) ? 'POS Kuliner' : ''">
                    <div class="w-9 h-9 rounded-xl bg-[#5E63E8] text-white flex items-center justify-center shadow-md shadow-[#5E63E8]/25 group-hover:scale-105 transition-transform shrink-0">
                        <x-ui.icon name="store" class="w-5 h-5 shrink-0" />
                    </div>
                    <div x-show="!(isDesktop && desktopCollapsed)" x-cloak class="flex flex-col min-w-0 overflow-hidden">
                        <span class="text-sm font-bold text-[#1E2029] tracking-tight leading-tight truncate">POS Kuliner</span>
                        <span class="text-[10px] font-medium text-[#8E92A4] truncate">Satu Outlet · POS</span>
                    </div>
                </a>

                <!-- Mobile Drawer Close Button only -->
                <button
                    type="button"
                    @click="mobileNavOpen = false"
                    class="md:hidden p-2 text-[#8E92A4] hover:text-[#1E2029] hover:bg-black/5 rounded-xl cursor-pointer transition-colors shrink-0"
                    title="Tutup Menu"
                >
                    <x-ui.icon name="x" class="w-5 h-5 shrink-0" />
                </button>
            </div>

            <!-- Navigation Links (Full Scrollable area) -->
            <nav class="flex flex-col gap-1 flex-1 pb-4">
                @php
                    $user = auth()->user();
                    $isOwner = $user && $user->isOwner();
                @endphp

                @if($isOwner)
                    <x-nav-link route="dashboard" icon="layout-dashboard" label="Dashboard" />
                @endif

                <x-nav-link route="pos" icon="shopping-cart" label="Kasir POS" />
                <x-nav-link route="online-orders" icon="bike" label="Pesanan Online" />
                <x-nav-link route="transactions" icon="receipt" label="Riwayat Transaksi" />

                @if($isOwner)
                    <div x-show="!(isDesktop && desktopCollapsed)" x-cloak class="my-2 border-t border-[#ECEEF2] px-2 pt-2">
                        <span class="text-[10px] font-bold text-[#8E92A4] uppercase tracking-wider">Katalog & Stok</span>
                    </div>
                    <div x-show="isDesktop && desktopCollapsed" x-cloak class="my-2 border-t border-[#ECEEF2] mx-2"></div>

                    <x-nav-link route="menu" icon="utensils" label="Menu & Resep" />
                    <x-nav-link route="categories" icon="folder-tree" label="Kategori" />
                    <x-nav-link route="ingredients" icon="boxes" label="Bahan Baku" />
                    <x-nav-link route="purchases" icon="shopping-bag" label="Pembelian Bahan" />
                    <x-nav-link route="stock-opname" icon="clipboard-check" label="Stok Opname" />
                    <x-nav-link route="reports" icon="bar-chart-3" label="Laporan Penjualan" />

                    <div x-show="!(isDesktop && desktopCollapsed)" x-cloak class="my-2 border-t border-[#ECEEF2] px-2 pt-2">
                        <span class="text-[10px] font-bold text-[#8E92A4] uppercase tracking-wider">Pengaturan</span>
                    </div>
                    <div x-show="isDesktop && desktopCollapsed" x-cloak class="my-2 border-t border-[#ECEEF2] mx-2"></div>

                    <x-nav-link route="employees" icon="users" label="Kelola Pekerja" />
                    <x-nav-link route="settings" icon="settings" label="Pengaturan" />
                @else
                    <div x-show="!(isDesktop && desktopCollapsed)" x-cloak class="my-2 border-t border-[#ECEEF2] px-2 pt-2">
                        <span class="text-[10px] font-bold text-[#8E92A4] uppercase tracking-wider">Operasional Kasir</span>
                    </div>
                    <div x-show="isDesktop && desktopCollapsed" x-cloak class="my-2 border-t border-[#ECEEF2] mx-2"></div>

                    <x-nav-link route="ingredients" icon="boxes" label="Pantau Stok Bahan" />
                @endif
            </nav>
        </aside>

        <!-- Main Workspace Area -->
        <div
            :class="(isDesktop && desktopCollapsed) ? 'md:pl-[76px]' : 'md:pl-[240px]'"
            class="flex-1 flex flex-col min-h-screen transition-all duration-300 ease-in-out"
        >

            <!-- Top Header Glass Navbar -->
            <header
                x-data="{ isScrolled: false }"
                @scroll.window="isScrolled = (window.pageYOffset > 10)"
                :class="isScrolled ? 'bg-white/94 shadow-md shadow-[#1E2029]/4 border-[#ECEEF2]' : 'bg-white/82 shadow-xs border-white/90'"
                class="sticky top-0 z-30 h-16 backdrop-blur-2xl px-3 sm:px-5 lg:px-8 flex items-center justify-between border-b transition-all duration-200"
            >
                <!-- Left: Hamburger (Mobile) & Desktop Sidebar Toggle & Outlet Name -->
                <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                    <!-- Mobile Hamburger Button (h-10 w-10) -->
                    <button
                        type="button"
                        @click="mobileNavOpen = true"
                        class="md:hidden flex items-center justify-center h-10 w-10 text-[#5A5D6C] hover:text-[#1E2029] bg-white/80 rounded-xl border border-[#ECEEF2] shadow-2xs hover:bg-white transition-all cursor-pointer shrink-0"
                        title="Buka Menu"
                    >
                        <x-ui.icon name="menu" class="w-5 h-5 shrink-0" />
                    </button>

                    <!-- Desktop Sidebar Toggle Button with Animated Morph Icons (h-10 w-10) -->
                    <button
                        type="button"
                        @click="toggleDesktopSidebar()"
                        :title="(isDesktop && desktopCollapsed) ? 'Buka Sidebar' : 'Tutup Sidebar'"
                        class="hidden md:flex items-center justify-center h-10 w-10 rounded-xl bg-white/80 hover:bg-white text-[#5A5D6C] hover:text-[#5E63E8] border border-[#ECEEF2] shadow-2xs transition-all duration-300 active:scale-95 cursor-pointer group relative overflow-hidden shrink-0"
                    >
                        <!-- Icon when sidebar is OPEN: click to close -->
                        <span
                            x-show="!(isDesktop && desktopCollapsed)"
                            x-transition:enter="transition ease-out duration-300 transform"
                            x-transition:enter-start="opacity-0 -rotate-90 scale-50"
                            x-transition:enter-end="opacity-100 rotate-0 scale-100"
                            x-transition:leave="transition ease-in duration-200 transform absolute"
                            x-transition:leave-start="opacity-100 rotate-0 scale-100"
                            x-transition:leave-end="opacity-0 rotate-90 scale-50"
                            class="flex items-center justify-center shrink-0"
                        >
                            <x-ui.icon name="panel-left-close" class="w-4 h-4 text-[#5A5D6C] group-hover:text-[#5E63E8] transition-colors shrink-0" />
                        </span>

                        <!-- Icon when sidebar is CLOSED: click to open (different logo/icon with animation) -->
                        <span
                            x-show="isDesktop && desktopCollapsed"
                            x-cloak
                            x-transition:enter="transition ease-out duration-300 transform"
                            x-transition:enter-start="opacity-0 rotate-90 scale-50"
                            x-transition:enter-end="opacity-100 rotate-0 scale-100"
                            x-transition:leave="transition ease-in duration-200 transform absolute"
                            x-transition:leave-start="opacity-100 rotate-0 scale-100"
                            x-transition:leave-end="opacity-0 -rotate-90 scale-50"
                            class="flex items-center justify-center shrink-0"
                        >
                            <x-ui.icon name="panel-left-open" class="w-4 h-4 text-[#5E63E8] group-hover:text-[#4D52D6] transition-colors shrink-0" />
                        </span>
                    </button>

                    <!-- Outlet Name Pill (Uniform h-10 size, visible on lg+ screens to prevent tablet header overflow) -->
                    <div class="hidden lg:flex items-center gap-2.5 text-xs text-[#8E92A4] bg-white/80 border border-[#ECEEF2] h-10 px-3.5 rounded-xl shadow-2xs backdrop-blur-md min-w-0 shrink">
                        <x-ui.icon name="store" class="w-4 h-4 text-[#5E63E8] shrink-0" />
                        <span class="font-bold text-[#1E2029] truncate max-w-[140px] xl:max-w-[220px]">{{ \App\Models\Setting::get('store_name', 'POS Kuliner Satu Outlet') }}</span>
                    </div>
                </div>

                <!-- Right: User Profile & Red Logout Button (Uniform h-10 size) -->
                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    <!-- User Profile Pill (Uniform h-10 size) -->
                    <div class="flex items-center gap-2 sm:gap-2.5 h-10 px-2.5 sm:px-3 rounded-xl bg-white/80 border border-[#ECEEF2] shadow-2xs backdrop-blur-md min-w-0">
                        <div class="w-7 h-7 rounded-lg bg-[#5E63E8]/12 text-[#5E63E8] flex items-center justify-center font-bold text-xs shrink-0">
                            {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="flex flex-col text-left leading-none justify-center gap-0.5 min-w-0">
                            <span class="text-xs font-bold text-[#1E2029] truncate max-w-[70px] xs:max-w-[100px] sm:max-w-[130px] md:max-w-[150px] lg:max-w-[180px]">{{ auth()->user()?->name ?? 'User' }}</span>
                            <span class="text-[10px] font-medium text-[#8E92A4] truncate">
                                {{ auth()->user()?->isOwner() ? 'Pemilik Outlet' : 'Kasir' }}
                            </span>
                        </div>
                    </div>

                    <!-- Red Logout Button (Uniform h-10 size, Vibrant Red) -->
                    <form method="POST" action="{{ route('logout') }}" class="m-0 shrink-0">
                        @csrf
                        <button
                            type="submit"
                            title="Keluar dari Aplikasi"
                            class="h-10 px-3 sm:px-3.5 rounded-xl bg-[#E5484D] hover:bg-[#D03B40] active:bg-[#BA2E33] text-white shadow-sm shadow-[#E5484D]/25 border border-[#E5484D]/20 transition-all duration-150 flex items-center gap-1.5 sm:gap-2 text-xs font-bold cursor-pointer group select-none active:scale-95 shrink-0"
                        >
                            <x-ui.icon name="log-out" class="w-4 h-4 text-white group-hover:-translate-x-0.5 transition-transform shrink-0" />
                            <span class="hidden md:inline">Keluar</span>
                        </button>
                    </form>
                </div>
            </header>

            <!-- Page Dynamic Body -->
            <main class="flex-1 p-4 sm:p-7 max-w-7xl w-full mx-auto">
                {{ $slot }}
            </main>
        </div>
    </div>

    <!-- Toast Notifications Layer -->
    <x-ui.toast />

    @livewireScripts
</body>
</html>
