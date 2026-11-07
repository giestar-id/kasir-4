<div class="flex flex-col gap-5 sm:gap-6">

    @php
        $metricTotal = $totalCount ?? \App\Models\User::where('role', '!=', 'owner')->count();
        $metricActiveCashier = $activeCount ?? \App\Models\User::where('role', '!=', 'owner')->where('is_active', true)->count();
        $metricInactiveCashier = $inactiveCount ?? \App\Models\User::where('role', '!=', 'owner')->where('is_active', false)->count();
        $metricCanDiscount = $discountCount ?? \App\Models\User::where('role', '!=', 'owner')->where('can_give_discount', true)->count();
    @endphp

    <!-- Top Bar: Header & Action Button -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 min-w-0">
        <div class="flex flex-col min-w-0">
            <h1 class="text-xl sm:text-2xl font-bold text-[#1E2029] tracking-tight truncate">
                Kelola Pekerja & Kasir
            </h1>
            <p class="text-xs text-[#4E5262] mt-0.5 truncate sm:whitespace-normal">
                Manajemen akun staf kasir, hak otorisasi diskon transaksi POS, dan pengaturan status operasional outlet
            </p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
            <x-ui.button
                variant="primary"
                size="md"
                icon="user-plus"
                wire:click="openCreateModal"
                @click="$dispatch('open-modal', 'employee-form-modal')"
            >
                Tambah Pekerja
            </x-ui.button>
        </div>
    </div>

    <!-- 4 Metric / Statistic Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
        
        <!-- Card 1: Total Pekerja -->
        <div class="glass-card rounded-2xl p-4 sm:p-5 flex flex-col justify-between relative overflow-hidden transition-all duration-200 hover:shadow-md min-w-0">
            <div class="flex items-center justify-between mb-3 min-w-0">
                <span class="text-xs font-semibold text-[#5A5D6C] truncate">Total Pekerja</span>
                <div class="w-9 h-9 rounded-xl bg-[#5E63E8]/10 text-[#5E63E8] flex items-center justify-center shrink-0">
                    <x-ui.icon name="users" class="w-4 h-4 shrink-0" />
                </div>
            </div>
            <div class="min-w-0">
                <div class="text-2xl sm:text-3xl font-bold text-[#1E2029] tracking-tight tabular-nums truncate">
                    {{ number_format($metricTotal, 0, ',', '.') }}
                </div>
                <div class="flex items-center gap-1.5 mt-1 text-[11px] text-[#4E5262] min-w-0 truncate">
                    <x-ui.icon name="shield-check" class="w-3.5 h-3.5 text-[#5E63E8] shrink-0" />
                    <span class="truncate">Total kasir terdaftar</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Kasir Aktif -->
        <div class="glass-card rounded-2xl p-4 sm:p-5 flex flex-col justify-between relative overflow-hidden transition-all duration-200 hover:shadow-md min-w-0">
            <div class="flex items-center justify-between mb-3 min-w-0">
                <span class="text-xs font-semibold text-[#5A5D6C] truncate">Kasir Aktif</span>
                <div class="w-9 h-9 rounded-xl bg-[#2EB872]/10 text-[#2EB872] flex items-center justify-center shrink-0">
                    <x-ui.icon name="user-check" class="w-4 h-4 shrink-0" />
                </div>
            </div>
            <div class="min-w-0">
                <div class="text-2xl sm:text-3xl font-bold text-[#2EB872] tracking-tight tabular-nums truncate">
                    {{ number_format($metricActiveCashier, 0, ',', '.') }}
                </div>
                <div class="flex items-center gap-1.5 mt-1 text-[11px] text-[#4E5262] min-w-0 truncate">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#2EB872] shrink-0"></span>
                    <span class="truncate">Siap layani transaksi kasir</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Kasir Nonaktif -->
        <div class="glass-card rounded-2xl p-4 sm:p-5 flex flex-col justify-between relative overflow-hidden transition-all duration-200 hover:shadow-md min-w-0">
            <div class="flex items-center justify-between mb-3 min-w-0">
                <span class="text-xs font-semibold text-[#5A5D6C] truncate">Kasir Nonaktif</span>
                <div class="w-9 h-9 rounded-xl bg-[#E5484D]/10 text-[#E5484D] flex items-center justify-center shrink-0">
                    <x-ui.icon name="user-x" class="w-4 h-4 shrink-0" />
                </div>
            </div>
            <div class="min-w-0">
                <div class="text-2xl sm:text-3xl font-bold {{ $metricInactiveCashier > 0 ? 'text-[#E5484D]' : 'text-[#4E5262]' }} tracking-tight tabular-nums truncate">
                    {{ number_format($metricInactiveCashier, 0, ',', '.') }}
                </div>
                <div class="flex items-center gap-1.5 mt-1 text-[11px] text-[#4E5262] min-w-0 truncate">
                    <span class="w-1.5 h-1.5 rounded-full {{ $metricInactiveCashier > 0 ? 'bg-[#E5484D]' : 'bg-[#4E5262]' }} shrink-0"></span>
                    <span class="truncate">Akses sistem diblokir / cuti</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Berhak Beri Diskon -->
        <div class="glass-card rounded-2xl p-4 sm:p-5 flex flex-col justify-between relative overflow-hidden transition-all duration-200 hover:shadow-md min-w-0">
            <div class="flex items-center justify-between mb-3 min-w-0">
                <span class="text-xs font-semibold text-[#5A5D6C] truncate">Berhak Beri Diskon</span>
                <div class="w-9 h-9 rounded-xl bg-[#F59E0B]/10 text-[#D97706] flex items-center justify-center shrink-0">
                    <x-ui.icon name="badge-percent" class="w-4 h-4 shrink-0" />
                </div>
            </div>
            <div class="min-w-0">
                <div class="text-2xl sm:text-3xl font-bold text-[#D97706] tracking-tight tabular-nums truncate">
                    {{ number_format($metricCanDiscount, 0, ',', '.') }}
                </div>
                <div class="flex items-center gap-1.5 mt-1 text-[11px] text-[#4E5262] min-w-0 truncate">
                    <x-ui.icon name="percent" class="w-3.5 h-3.5 text-[#D97706] shrink-0" />
                    <span class="truncate">Otorisasi diskon di kasir POS</span>
                </div>
            </div>
        </div>

    </div>

    <!-- Filter Bar Card -->
    <div class="glass-card rounded-2xl p-3.5 sm:p-4 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3 min-w-0">
        
        <!-- Search Input -->
        <div class="relative flex-1 min-w-[240px]">
            <x-ui.input
                wire:model.live.debounce.300ms="search"
                icon="search"
                placeholder="Cari nama pekerja atau alamat email..."
            />
        </div>

        <!-- Filters Group -->
        <div class="flex flex-wrap sm:flex-nowrap items-center gap-2.5 shrink-0">
            
            <!-- Filter Status -->
            <div class="relative w-full sm:w-48 md:w-52 shrink-0">
                <select
                    wire:model.live="statusFilter"
                    class="w-full h-10 bg-white/90 text-[#1E2029] text-xs sm:text-sm rounded-xl py-2 pl-3.5 pr-8 border border-[#ECEEF2] shadow-2xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#5E63E8]/20 focus:border-[#5E63E8] transition-all appearance-none cursor-pointer"
                >
                    <option value="all">Semua Status</option>
                    <option value="active">Status: Aktif</option>
                    <option value="inactive">Status: Nonaktif</option>
                </select>
                <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-[#4E5262]">
                    <x-ui.icon name="chevron-down" class="w-3.5 h-3.5 shrink-0" />
                </div>
            </div>

            <!-- Filter Hak Diskon -->
            <div class="relative w-full sm:w-48 md:w-52 shrink-0">
                <select
                    wire:model.live="discountFilter"
                    class="w-full h-10 bg-white/90 text-[#1E2029] text-xs sm:text-sm rounded-xl py-2 pl-3.5 pr-8 border border-[#ECEEF2] shadow-2xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#5E63E8]/20 focus:border-[#5E63E8] transition-all appearance-none cursor-pointer"
                >
                    <option value="all">Semua Hak Diskon</option>
                    <option value="allowed">Boleh Beri Diskon</option>
                    <option value="not_allowed">Tanpa Hak Diskon</option>
                </select>
                <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-[#4E5262]">
                    <x-ui.icon name="chevron-down" class="w-3.5 h-3.5 shrink-0" />
                </div>
            </div>

            <!-- Reset Filter Button (Shown when filters are active) -->
            @if($search || $statusFilter !== 'all' || $discountFilter !== 'all')
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

    <!-- Responsive Employee Table Card -->
    <div class="glass-card rounded-2xl overflow-hidden flex flex-col min-w-0">
        
        <div class="overflow-x-auto min-w-0">
            <table class="w-full text-left border-collapse min-w-[760px]">
                <thead>
                    <tr class="border-b border-[#ECEEF2] text-[11px] font-bold text-[#4E5262] uppercase tracking-wider bg-white/40">
                        <th class="py-3.5 px-4 sm:px-5">Nama & Email</th>
                        <th class="py-3.5 px-4">Role / Jabatan</th>
                        <th class="py-3.5 px-4">Hak Diskon</th>
                        <th class="py-3.5 px-4">Status Aktif</th>
                        <th class="py-3.5 px-4">Terakhir Login / Bergabung</th>
                        <th class="py-3.5 px-4 sm:px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#ECEEF2]/70 text-xs">
                    @forelse($employees as $emp)
                        <tr wire:key="employee-{{ $emp->id }}" class="hover:bg-white/50 transition-colors">
                            
                            <!-- Kolom 1: Nama & Email -->
                            <td class="py-3.5 px-4 sm:px-5">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-xl bg-[#5E63E8]/10 text-[#5E63E8] flex items-center justify-center font-bold text-sm shrink-0 shadow-2xs">
                                        {{ strtoupper(substr($emp->name, 0, 1)) }}
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="font-bold text-[#1E2029] truncate text-sm" title="{{ $emp->name }}">
                                            {{ $emp->name }}
                                        </span>
                                        <span class="text-xs text-[#4E5262] truncate" title="{{ $emp->email }}">
                                            {{ $emp->email }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Kolom 2: Role / Jabatan (Badge) -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <x-ui.badge variant="neutral" size="sm" dot>
                                    Kasir Outlet
                                </x-ui.badge>
                            </td>

                            <!-- Kolom 3: Hak Diskon (Badge success / neutral) -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($emp->can_give_discount)
                                    <x-ui.badge variant="success" size="sm" dot>
                                        Boleh Diskon
                                    </x-ui.badge>
                                @else
                                    <x-ui.badge variant="neutral" size="sm" dot>
                                        Tanpa Diskon
                                    </x-ui.badge>
                                @endif
                            </td>

                            <!-- Kolom 4: Status Aktif (Toggle switch interaktif) -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="inline-flex items-center gap-2">
                                    <button
                                        type="button"
                                        wire:click="toggleStatus({{ $emp->id }})"
                                        wire:loading.attr="disabled"
                                        class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-[#5E63E8]/30 items-center px-0.5 {{ $emp->is_active ? 'bg-[#2EB872]' : 'bg-[#D1D5DB]' }}"
                                        role="switch"
                                        aria-checked="{{ $emp->is_active ? 'true' : 'false' }}"
                                        title="{{ $emp->is_active ? 'Klik untuk menonaktifkan akun pekerja' : 'Klik untuk mengaktifkan akun pekerja' }}"
                                    >
                                        <span class="sr-only">Toggle Status</span>
                                        <span
                                            class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-xs transition duration-200 ease-in-out {{ $emp->is_active ? 'translate-x-4' : 'translate-x-0' }}"
                                        ></span>
                                    </button>
                                    @if($emp->is_active)
                                        <x-ui.badge variant="success" size="sm">Aktif</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="neutral" size="sm">Nonaktif</x-ui.badge>
                                    @endif
                                </div>
                            </td>

                            <!-- Kolom 5: Terakhir Login / Tanggal Bergabung -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="flex flex-col text-xs leading-tight min-w-0">
                                    <span class="text-[#1E2029] font-medium flex items-center gap-1.5 truncate" title="{{ $emp->last_login_at ? $emp->last_login_at->translatedFormat('d M Y, H:i') : 'Belum pernah login' }}">
                                        <x-ui.icon name="clock" class="w-3.5 h-3.5 text-[#4E5262] shrink-0" />
                                        <span class="truncate">{{ $emp->last_login_at ? $emp->last_login_at->diffForHumans() : 'Belum pernah login' }}</span>
                                    </span>
                                    <span class="text-[11px] text-[#4E5262] mt-1 flex items-center gap-1.5 truncate">
                                        <x-ui.icon name="calendar" class="w-3.5 h-3.5 text-[#4E5262] shrink-0" />
                                        <span class="truncate">Daftar: {{ $emp->created_at ? $emp->created_at->format('d/m/Y') : '-' }}</span>
                                    </span>
                                </div>
                            </td>

                            <!-- Kolom 6: Aksi (Button Group) -->
                            <td class="py-3.5 px-4 sm:px-5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1">
                                    
                                    <!-- Tombol Edit (buka modal form) -->
                                    <button
                                        type="button"
                                        wire:click="openEditModal({{ $emp->id }})"
                                        @click="$dispatch('open-modal', 'employee-form-modal')"
                                        class="p-2 text-[#5A5D6C] hover:text-[#5E63E8] hover:bg-[#5E63E8]/10 rounded-xl transition-all cursor-pointer shrink-0"
                                        title="Edit Data Pekerja"
                                    >
                                        <x-ui.icon name="pencil" class="w-4 h-4 shrink-0" />
                                    </button>

                                    <!-- Tombol Ubah Password (buka modal reset password) -->
                                    <button
                                        type="button"
                                        wire:click="openPasswordModal({{ $emp->id }})"
                                        @click="$dispatch('open-modal', 'employee-password-modal')"
                                        class="p-2 text-[#5A5D6C] hover:text-[#D97706] hover:bg-[#F59E0B]/10 rounded-xl transition-all cursor-pointer shrink-0"
                                        title="Ubah Password"
                                    >
                                        <x-ui.icon name="key" class="w-4 h-4 shrink-0" />
                                    </button>

                                    <!-- Tombol Hapus (buka modal konfirmasi hapus) -->
                                    <button
                                        type="button"
                                        wire:click="openDeleteModal({{ $emp->id }})"
                                        @click="$dispatch('open-modal', 'employee-delete-modal')"
                                        class="p-2 text-[#E5484D] hover:text-[#D03B40] bg-[#E5484D]/10 hover:bg-[#E5484D]/20 border border-[#E5484D]/20 rounded-xl transition-all cursor-pointer shrink-0"
                                        title="Hapus Pekerja"
                                    >
                                        <x-ui.icon name="trash-2" class="w-4 h-4 shrink-0" />
                                    </button>

                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 px-4 text-center">
                                <x-ui.empty-state
                                    icon="users"
                                    title="Tidak ada data pekerja ditemukan"
                                    description="Coba sesuaikan kata kunci pencarian atau bersihkan filter status untuk menampilkan pekerja."
                                >
                                    @if($search || $statusFilter !== 'all' || $discountFilter !== 'all')
                                        <x-ui.button
                                            variant="secondary"
                                            size="sm"
                                            wire:click="$set('search', ''); $set('statusFilter', 'all'); $set('discountFilter', 'all');"
                                        >
                                            Reset Filter
                                        </x-ui.button>
                                    @else
                                        <x-ui.button
                                            variant="primary"
                                            size="sm"
                                            icon="user-plus"
                                            wire:click="openCreateModal"
                                            @click="$dispatch('open-modal', 'employee-form-modal')"
                                        >
                                            Tambah Pekerja Pertama
                                        </x-ui.button>
                                    @endif
                                </x-ui.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        @if(method_exists($employees, 'hasPages') && $employees->hasPages())
            <div class="px-4 sm:px-5 py-3 border-t border-[#ECEEF2] bg-white/40 flex items-center justify-between text-xs text-[#4E5262] min-w-0">
                {{ $employees->links() }}
            </div>
        @endif

    </div>

    <!-- MODAL 1: Tambah / Edit Pekerja -->
    <x-ui.modal name="employee-form-modal" :title="$editingEmployeeId ? 'Edit Data Pekerja' : 'Tambah Pekerja Baru'" size="md">
        <form wire:submit.prevent="saveEmployee" class="flex flex-col gap-4">
            
            <x-ui.input
                label="Nama Lengkap"
                icon="user"
                wire:model="name"
                placeholder="Contoh: Rian Pratama"
                :error="$errors->first('name')"
                required
            />

            <x-ui.input
                label="Alamat Email"
                icon="mail"
                type="email"
                wire:model="email"
                placeholder="Contoh: rian.kasir@outlet.com"
                hint="Digunakan untuk masuk ke sistem kasir"
                :error="$errors->first('email')"
                required
            />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-[#5A5D6C]">Role / Jabatan</label>
                    <div class="h-10 px-3.5 rounded-xl bg-[#ECEEF2]/40 border border-[#ECEEF2] flex items-center justify-between text-xs text-[#1E2029]">
                        <span class="font-bold flex items-center gap-1.5">
                            <x-ui.icon name="user" class="w-3.5 h-3.5 text-[#5E63E8]" />
                            Kasir Outlet
                        </span>
                        <span class="text-[10px] text-[#4E5262] font-semibold">Staf Kasir POS</span>
                    </div>
                </div>

                @if(!$editingEmployeeId)
                    <x-ui.input
                        label="Password Akun"
                        icon="lock"
                        type="password"
                        wire:model="password"
                        placeholder="Minimal 6 karakter"
                        :error="$errors->first('password')"
                        required
                    />
                @else
                    <x-ui.input
                        label="Password Baru (Opsional)"
                        icon="lock"
                        type="password"
                        wire:model="password"
                        placeholder="Kosongkan jika tak diubah"
                        :error="$errors->first('password')"
                    />
                @endif
            </div>

            <!-- Hak Otorisasi Diskon -->
            <div class="p-3.5 rounded-xl bg-white/60 border border-[#ECEEF2] flex items-start gap-3">
                <input
                    id="can_give_discount_input"
                    type="checkbox"
                    wire:model="can_give_discount"
                    class="w-4 h-4 mt-0.5 rounded text-[#5E63E8] accent-[#5E63E8] cursor-pointer"
                />
                <label for="can_give_discount_input" class="flex flex-col text-xs cursor-pointer select-none">
                    <span class="font-bold text-[#1E2029]">Otorisasi Pemberian Diskon</span>
                    <span class="text-[#4E5262] mt-0.5">Izinkan pekerja ini memberikan diskon manual pada transaksi penjualan di Kasir POS.</span>
                </label>
            </div>

            <!-- Status Akun Aktif -->
            <div class="p-3.5 rounded-xl bg-white/60 border border-[#ECEEF2] flex items-start gap-3">
                <input
                    id="is_active_input"
                    type="checkbox"
                    wire:model="is_active"
                    class="w-4 h-4 mt-0.5 rounded text-[#5E63E8] accent-[#5E63E8] cursor-pointer"
                />
                <label for="is_active_input" class="flex flex-col text-xs cursor-pointer select-none">
                    <span class="font-bold text-[#1E2029]">Status Akun Aktif</span>
                    <span class="text-[#4E5262] mt-0.5">
                        Pekerja hanya dapat masuk dan mengoperasikan kasir jika akun berstatus aktif.
                    </span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-2.5 mt-2 pt-3 border-t border-[#ECEEF2]">
                <x-ui.button variant="secondary" size="md" @click="$dispatch('close-modal', 'employee-form-modal')">
                    Batal
                </x-ui.button>
                <x-ui.button variant="primary" size="md" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveEmployee, save">Simpan Pekerja</span>
                    <span wire:loading wire:target="saveEmployee, save">Menyimpan...</span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <!-- MODAL 2: Ubah Password Pekerja -->
    <x-ui.modal name="employee-password-modal" title="Ubah Password Pekerja" size="md">
        <form wire:submit.prevent="changeEmployeePassword" class="flex flex-col gap-4">
            
            @if($passwordEmployeeName)
                <div class="p-3 rounded-xl bg-[#5E63E8]/8 border border-[#5E63E8]/20 flex items-center gap-2.5 text-xs text-[#1E2029]">
                    <div class="w-8 h-8 rounded-lg bg-[#5E63E8]/15 text-[#5E63E8] flex items-center justify-center font-bold shrink-0">
                        <x-ui.icon name="key" class="w-4 h-4 shrink-0" />
                    </div>
                    <div class="min-w-0">
                        <span class="text-[#4E5262] block text-[11px]">Ubah kata sandi akun pekerja:</span>
                        <span class="font-bold text-[#1E2029] truncate block">{{ $passwordEmployeeName }}</span>
                    </div>
                </div>
            @endif

            <x-ui.input
                label="Password Baru"
                icon="lock"
                type="password"
                wire:model="newEmployeePassword"
                placeholder="Minimal 6 karakter"
                :error="$errors->first('newEmployeePassword')"
                required
            />

            <x-ui.input
                label="Konfirmasi Password Baru"
                icon="lock"
                type="password"
                wire:model="confirmEmployeePassword"
                placeholder="Ketik ulang password baru"
                :error="$errors->first('confirmEmployeePassword')"
                required
            />

            <div class="flex items-center justify-end gap-2.5 mt-2 pt-3 border-t border-[#ECEEF2]">
                <x-ui.button variant="secondary" size="md" @click="$dispatch('close-modal', 'employee-password-modal')">
                    Batal
                </x-ui.button>
                <x-ui.button variant="primary" size="md" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="changeEmployeePassword, changePassword">Perbarui Password</span>
                    <span wire:loading wire:target="changeEmployeePassword, changePassword">Memperbarui...</span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <!-- MODAL 3: Konfirmasi Hapus Pekerja -->
    <x-ui.modal name="employee-delete-modal" title="Konfirmasi Tindakan Hapus" size="sm">
        <div class="flex flex-col gap-4">
            <div class="flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl {{ $deletingEmployeeHasSales ? 'bg-[#F59E0B]/15 text-[#D97706]' : 'bg-[#E5484D]/15 text-[#E5484D]' }} flex items-center justify-center shrink-0">
                    <x-ui.icon :name="$deletingEmployeeHasSales ? 'alert-triangle' : 'trash-2'" class="w-5 h-5 shrink-0" />
                </div>
                <div class="min-w-0">
                    <h4 class="text-sm font-bold text-[#1E2029] leading-snug">
                        @if($deletingEmployeeHasSales)
                            Nonaktifkan Akun Pekerja?
                        @else
                            Hapus Akun Pekerja?
                        @endif
                    </h4>
                    <p class="text-xs text-[#5A5D6C] mt-1 leading-relaxed">
                        @if($deletingEmployeeHasSales)
                            Pekerja <strong class="text-[#1E2029]">{{ $deletingEmployeeName }}</strong> memiliki riwayat transaksi penjualan. Demi menjaga integritas laporan keuangan & jejak audit, akun akan <strong>dinonaktifkan</strong> dan tidak dihapus secara permanen.
                        @else
                            Apakah Anda yakin ingin menghapus akun pekerja <strong class="text-[#1E2029]">{{ $deletingEmployeeName }}</strong>? Tindakan ini bersifat permanen dan tidak dapat dibatalkan.
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5 mt-2 pt-3 border-t border-[#ECEEF2]">
                <x-ui.button variant="secondary" size="md" @click="$dispatch('close-modal', 'employee-delete-modal')">
                    Batal
                </x-ui.button>
                <x-ui.button
                    :variant="$deletingEmployeeHasSales ? 'warning' : 'danger'"
                    size="md"
                    wire:click="delete"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="delete, deleteEmployee">
                        {{ $deletingEmployeeHasSales ? 'Ya, Nonaktifkan' : 'Ya, Hapus Permanen' }}
                    </span>
                    <span wire:loading wire:target="delete, deleteEmployee">Memproses...</span>
                </x-ui.button>
            </div>
        </div>
    </x-ui.modal>

</div>
