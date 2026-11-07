<div
    x-data="{
        activeTab: '{{ $activeTab ?? 'profile' }}',
        switchTab(tab) {
            this.activeTab = tab;
            try {
                this.$wire.set('activeTab', tab, false);
            } catch(e) {}
            this.$nextTick(() => {
                if (window.refreshIcons) window.refreshIcons();
            });
        }
    }"
    class="flex flex-col gap-5 sm:gap-6"
>

    <!-- Header Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 min-w-0">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#1E2029] tracking-tight">Pengaturan Outlet</h1>
            <p class="text-xs text-[#4E5262] mt-0.5">Konfigurasi profil toko, keamanan akun pemilik, integrasi AI, dan cadangan database</p>
        </div>
    </div>

    <!-- Tab Navigation Bar: Responsive Select (Mobile) & Pills (Desktop) -->
    <div class="glass-card rounded-2xl p-2 sm:p-2.5 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2">
        
        <!-- Mobile Dropdown Selector (Active on < sm screens) -->
        <div class="sm:hidden relative w-full">
            <select
                :value="activeTab"
                @change="switchTab($event.target.value)"
                class="w-full h-10 bg-white text-[#1E2029] text-xs font-bold rounded-xl py-2 pl-3.5 pr-8 border border-[#ECEEF2] shadow-2xs focus:outline-none focus:ring-2 focus:ring-[#5E63E8]/20 focus:border-[#5E63E8] appearance-none cursor-pointer"
            >
                <option value="profile">Profil Toko</option>
                <option value="security">Keamanan Pemilik & Kasir</option>
                <option value="ai">Integrasi AI Hybrid</option>
                <option value="backup">Cadangan Data</option>
            </select>
            <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-[#4E5262]">
                <x-ui.icon name="chevron-down" class="w-3.5 h-3.5" />
            </div>
        </div>

        <!-- Desktop & Tablet Tab Buttons (Hidden on < sm screens) -->
        <div class="hidden sm:flex items-center gap-1.5 overflow-x-auto scrollbar-none w-full">
            <button
                type="button"
                @click="switchTab('profile')"
                :class="activeTab === 'profile' ? 'bg-[#5E63E8] text-white shadow-xs' : 'text-[#5A5D6C] hover:bg-white/60 hover:text-[#1E2029]'"
                class="h-10 px-4 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer flex items-center gap-2"
            >
                <x-ui.icon name="store" class="w-4 h-4 shrink-0" />
                <span>Profil Toko</span>
            </button>
            <button
                type="button"
                @click="switchTab('security')"
                :class="activeTab === 'security' ? 'bg-[#5E63E8] text-white shadow-xs' : 'text-[#5A5D6C] hover:bg-white/60 hover:text-[#1E2029]'"
                class="h-10 px-4 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer flex items-center gap-2"
            >
                <x-ui.icon name="shield" class="w-4 h-4 shrink-0" />
                <span>Keamanan & Kasir</span>
            </button>
            <button
                type="button"
                @click="switchTab('ai')"
                :class="activeTab === 'ai' ? 'bg-[#5E63E8] text-white shadow-xs' : 'text-[#5A5D6C] hover:bg-white/60 hover:text-[#1E2029]'"
                class="h-10 px-4 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer flex items-center gap-2"
            >
                <x-ui.icon name="sparkles" class="w-4 h-4 shrink-0" />
                <span>Integrasi AI Hybrid</span>
            </button>
            <button
                type="button"
                @click="switchTab('backup')"
                :class="activeTab === 'backup' ? 'bg-[#5E63E8] text-white shadow-xs' : 'text-[#5A5D6C] hover:bg-white/60 hover:text-[#1E2029]'"
                class="h-10 px-4 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer flex items-center gap-2"
            >
                <x-ui.icon name="database" class="w-4 h-4 shrink-0" />
                <span>Cadangan Data</span>
            </button>
        </div>

    </div>

    <!-- TAB 1: Profil Toko -->
    <div x-show="activeTab === 'profile'" x-cloak class="glass-card rounded-2xl p-6 sm:p-7 w-full flex flex-col gap-5">
        <div>
            <h2 class="text-base font-bold text-[#1E2029]">Identitas & Informasi Struk</h2>
            <p class="text-xs text-[#4E5262] mt-0.5">Informasi ini akan tercetak pada struk fisik kasir dan identitas outlet Anda.</p>
        </div>

        <form wire:submit.prevent="saveProfile" class="flex flex-col gap-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.input
                    label="Nama Toko / Outlet"
                    icon="store"
                    wire:model="storeName"
                    placeholder="Contoh: POS Kuliner Nusantara"
                    :error="$errors->first('storeName')"
                    required
                />

                <x-ui.input
                    label="Nomor Telepon / WhatsApp"
                    icon="phone"
                    wire:model="storePhone"
                    placeholder="Contoh: 0812-3456-7890"
                />
            </div>

            <x-ui.input
                label="Alamat Lengkap Outlet"
                icon="map-pin"
                wire:model="storeAddress"
                placeholder="Contoh: Jl. Boulevard Raya No. 8, Kelapa Gading, Jakarta"
            />

            <x-ui.input
                label="Catatan Kaki Struk"
                icon="file-text"
                wire:model="receiptFooter"
                placeholder="Contoh: Terima kasih atas kunjungan Anda. Selamat menikmati!"
            />

            <div class="pt-4 border-t border-[#ECEEF2]">
                <h3 class="text-xs font-bold text-[#1E2029] mb-1">Estimasi Komisi Pesanan Online</h3>
                <p class="text-xs text-[#4E5262] mb-3">Persentase potongan komisi platform untuk menghitung estimasi laba bersih di dashboard.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-ui.input
                        label="Komisi Estimasi GoFood (%)"
                        type="number"
                        wire:model="gofoodCommission"
                        suffix="%"
                        placeholder="20"
                    />
                    <x-ui.input
                        label="Komisi Estimasi ShopeeFood (%)"
                        type="number"
                        wire:model="shopeefoodCommission"
                        suffix="%"
                        placeholder="20"
                    />
                </div>
            </div>

            <div class="flex justify-end pt-3 border-t border-[#ECEEF2]">
                <x-ui.button variant="primary" size="md" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveProfile">Simpan Perubahan</span>
                    <span wire:loading wire:target="saveProfile">Menyimpan...</span>
                </x-ui.button>
            </div>
        </form>
    </div>

    <!-- TAB 2: Keamanan Akun Pemilik & Kasir (Full-Width, Spacious Layout) -->
    <div x-show="activeTab === 'security'" x-cloak class="flex flex-col gap-5 w-full">
        
        <!-- Card 1: Ubah Password Pemilik -->
        <div class="glass-card rounded-2xl p-6 sm:p-7 w-full flex flex-col gap-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-[#ECEEF2] pb-3.5">
                <div>
                    <h3 class="text-base font-bold text-[#1E2029] flex items-center gap-2">
                        <x-ui.icon name="shield" class="w-4 h-4 text-[#5E63E8]" />
                        Ubah Password Pemilik
                    </h3>
                    <p class="text-xs text-[#4E5262] mt-0.5">Kata sandi akun utama Pemilik (Owner). Gunakan minimal 10 karakter dengan kombinasi huruf dan angka.</p>
                </div>
            </div>

            <form wire:submit.prevent="changePassword" class="flex flex-col gap-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-ui.input
                        label="Password Lama / Saat Ini"
                        type="password"
                        icon="lock"
                        wire:model="oldPassword"
                        placeholder="Ketik password lama"
                        :error="$errors->first('oldPassword')"
                        required
                    />

                    <x-ui.input
                        label="Password Baru"
                        type="password"
                        icon="key"
                        wire:model="newPassword"
                        placeholder="Minimal 10 karakter"
                        :error="$errors->first('newPassword')"
                        required
                    />

                    <x-ui.input
                        label="Konfirmasi Password Baru"
                        type="password"
                        icon="key"
                        wire:model="confirmPassword"
                        placeholder="Ketik ulang password baru"
                        :error="$errors->first('confirmPassword')"
                        required
                    />
                </div>

                <div class="flex justify-end pt-3 border-t border-[#ECEEF2]">
                    <x-ui.button variant="primary" size="md" type="submit" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="changePassword">Perbarui Password Pemilik</span>
                        <span wire:loading wire:target="changePassword">Memperbarui...</span>
                    </x-ui.button>
                </div>
            </form>
        </div>

        <!-- Card 2: Pengelolaan Akun Kasir (Full-Width, Spacious Table) -->
        <div class="glass-card rounded-2xl p-6 sm:p-7 w-full flex flex-col gap-5">
            
            <!-- Banner Rekomendasi Menu Kelola Pekerja -->
            <div class="p-4 sm:p-5 rounded-2xl bg-[#5E63E8]/8 border border-[#5E63E8]/20 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start sm:items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-[#5E63E8]/15 text-[#5E63E8] flex items-center justify-center shrink-0">
                        <x-ui.icon name="users" class="w-5 h-5" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-[#1E2029]">Pusat Pengelolaan Akun Pekerja & Kasir</span>
                        <span class="text-xs text-[#4E5262] mt-0.5">
                            Manajemen staf kasir lengkap dengan pencarian, filter status, otorisasi diskon transaksi POS, riwayat penjualan, dan audit log tersedia di menu <strong>Kelola Pekerja</strong>.
                        </span>
                    </div>
                </div>
                <a
                    href="{{ route('employees') }}"
                    wire:navigate
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-[#5E63E8] text-white text-xs font-bold hover:bg-[#4D52D6] transition-all shadow-sm shrink-0 cursor-pointer"
                >
                    <span>Buka Kelola Pekerja</span>
                    <x-ui.icon name="arrow-right" class="w-4 h-4" />
                </a>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2">
                <div>
                    <h3 class="text-base font-bold text-[#1E2029]">Daftar Akun Kasir Terdaftar</h3>
                    <p class="text-xs text-[#4E5262] mt-0.5">Kasir hanya dapat mengakses POS dan tidak dapat melihat data HPP, margin laba, atau pengaturan outlet.</p>
                </div>

                <x-ui.button
                    variant="primary"
                    size="sm"
                    icon="plus"
                    wire:click="openCreateCashierModal"
                    @click="$dispatch('open-modal', 'cashier-form-modal')"
                >
                    Tambah Kasir
                </x-ui.button>
            </div>

            <div class="border border-[#ECEEF2] rounded-xl overflow-x-auto w-full">
                <table class="w-full text-left text-xs min-w-[600px]">
                    <thead class="bg-white/60 text-[#4E5262] border-b border-[#ECEEF2] text-[11px] uppercase font-bold">
                        <tr>
                            <th class="py-3 px-4">Nama Lengkap</th>
                            <th class="py-3 px-4">Email / Login</th>
                            <th class="py-3 px-4">Hak Diskon POS</th>
                            <th class="py-3 px-4">Status Akun</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#ECEEF2]">
                        @forelse($cashiers as $c)
                            <tr class="hover:bg-white/40 transition-colors">
                                <td class="py-3 px-4 font-bold text-[#1E2029]">{{ $c->name }}</td>
                                <td class="py-3 px-4 text-[#4E5262]">{{ $c->email }}</td>
                                <td class="py-3 px-4">
                                    @if($c->can_give_discount)
                                        <x-ui.badge variant="success" size="sm" dot>Boleh Diskon</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="neutral" size="sm" dot>Tanpa Diskon</x-ui.badge>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    @if($c->is_active)
                                        <x-ui.badge variant="success" size="sm">Aktif</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="neutral" size="sm">Nonaktif</x-ui.badge>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <button
                                        type="button"
                                        wire:click="openEditCashierModal({{ $c->id }})"
                                        @click="$dispatch('open-modal', 'cashier-form-modal')"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold text-[#5E63E8] hover:bg-[#5E63E8]/10 transition-colors cursor-pointer"
                                    >
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-[#4E5262]">
                                    Belum ada akun kasir tambahan terdaftar. Gunakan tombol + Tambah Kasir di atas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

    </div>

    <!-- TAB 3: Integrasi AI Hybrid -->
    <div x-show="activeTab === 'ai'" x-cloak class="glass-card rounded-2xl p-6 sm:p-7 w-full flex flex-col gap-5">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
            <div>
                <h3 class="text-base font-bold text-[#1E2029]">Konfigurasi Integrasi AI</h3>
                <p class="text-xs text-[#4E5262] mt-0.5">Membaca otomatis struk pesanan GoFood & ShopeeFood dari screenshot</p>
            </div>

            <!-- Status Badge -->
            <div>
                @if($testConnectionStatus === 'success')
                    <x-ui.badge variant="success" size="md" dot>
                        {{ $testConnectionMessage ?? 'Terhubung' }}
                    </x-ui.badge>
                @elseif($testConnectionStatus === 'failed')
                    <x-ui.badge variant="error" size="md" dot>
                        {{ $testConnectionMessage ?? 'Koneksi Gagal' }}
                    </x-ui.badge>
                @else
                    <x-ui.badge variant="neutral" size="md" dot>
                        Belum Dikonfigurasi
                    </x-ui.badge>
                @endif
            </div>
        </div>

        <form wire:submit.prevent="saveAiSettings" class="flex flex-col gap-4">
            
            <div class="flex items-center gap-2 pb-2 border-b border-[#ECEEF2]">
                <label class="flex items-center gap-2 text-xs font-semibold text-[#1E2029] cursor-pointer">
                    <input
                        type="checkbox"
                        wire:model.live="aiEnabled"
                        class="w-4 h-4 rounded text-[#5E63E8] accent-[#5E63E8]"
                    />
                    <span>Aktifkan Pembacaan AI API (Jalur Utama)</span>
                </label>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-ui.select label="Penyedia AI" wire:model.live="aiProvider" required>
                    <option value="gemini">Google Gemini (Recommended)</option>
                    <option value="anthropic">Anthropic (Claude)</option>
                    <option value="openai">OpenAI (ChatGPT)</option>
                    <option value="openrouter">OpenRouter</option>
                    <option value="custom">Endpoint Kompatibel OpenAI (Kustom)</option>
                </x-ui.select>

                <x-ui.input
                    label="Model AI"
                    wire:model="aiModel"
                    placeholder="Contoh: gemini-1.5-flash, gpt-4o-mini"
                    required
                />
            </div>

            @if($aiProvider === 'custom')
                <x-ui.input
                    label="Base URL Endpoint Kustom"
                    wire:model="aiCustomUrl"
                    placeholder="https://api.your-endpoint.com/v1"
                    required
                />
            @endif

            <!-- API Key Section with Masked Indicator -->
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-[#5A5D6C]">API Key Penyedia AI</label>
                <div class="flex items-center gap-2">
                    <div class="flex-1">
                        <input
                            type="password"
                            wire:model="aiApiKey"
                            placeholder="{{ $hasSavedKey ? 'sk-•••••••••••••••• (Tersimpan Terenkripsi)' : 'Masukkan API Key Anda di sini...' }}"
                            class="w-full bg-white/90 text-[#1E2029] text-xs sm:text-sm rounded-xl py-2.5 px-3.5 border border-[#ECEEF2] focus:outline-none focus:border-[#5E63E8]"
                        />
                    </div>
                    @if($hasSavedKey)
                        <x-ui.button
                            type="button"
                            variant="danger"
                            size="md"
                            @click="$dispatch('open-modal', 'delete-key-modal')"
                        >
                            Hapus Key
                        </x-ui.button>
                    @endif
                </div>
                <span class="text-[11px] text-[#4E5262]">
                    Key disimpan menggunakan enkripsi AES-256 dan tidak pernah terekspos ke browser.
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 border-t border-[#ECEEF2]">
                <x-ui.input
                    label="Batas Anggaran Bulanan (Rp)"
                    type="number"
                    wire:model="aiBudgetLimit"
                    prefix="Rp"
                />
                <x-ui.input
                    label="Ambang Keyakinan (%)"
                    type="number"
                    wire:model="confidenceThreshold"
                    suffix="%"
                />
                <x-ui.input
                    label="Masa Simpan Screenshot (Hari)"
                    type="number"
                    wire:model="screenshotRetention"
                    suffix="Hari"
                />
            </div>

            <!-- Privacy Banner -->
            <div class="p-3.5 rounded-xl bg-blue-50/50 border border-blue-200/50 text-xs text-[#5A5D6C] flex items-start gap-2.5">
                <x-ui.icon name="info" class="w-4 h-4 text-[#5E63E8] shrink-0 mt-0.5" />
                <span>
                    <b>Kerahasiaan Data:</b> Gambar pesanan hanya dikirim ke penyedia AI untuk membaca menu dan total. Nama serta alamat pelanggan dilarang keras untuk diekstrak maupun disimpan ke sistem.
                </span>
            </div>

            <!-- Action Button Group -->
            <div class="flex items-center justify-between pt-3 border-t border-[#ECEEF2] flex-wrap gap-2">
                <div class="flex items-center gap-2">
                    <x-ui.button
                        type="button"
                        variant="secondary"
                        size="md"
                        icon="wifi"
                        wire:click="testAiConnection"
                        wire:loading.attr="disabled"
                    >
                        Tes Koneksi
                    </x-ui.button>
                    <x-ui.button
                        type="button"
                        variant="secondary"
                        size="md"
                        icon="file-search"
                        @click="$dispatch('open-modal', 'sample-test-modal')"
                    >
                        Tes Baca Gambar
                    </x-ui.button>
                </div>

                <x-ui.button variant="primary" size="md" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveAiSettings">Simpan Pengaturan AI</span>
                    <span wire:loading wire:target="saveAiSettings">Menyimpan...</span>
                </x-ui.button>
            </div>
        </form>
    </div>

    <!-- TAB 4: Cadangan Data -->
    <div x-show="activeTab === 'backup'" x-cloak class="glass-card rounded-2xl p-6 sm:p-7 w-full flex flex-col gap-5">
        <div>
            <h3 class="text-base font-bold text-[#1E2029]">Cadangan Basis Data Outlet</h3>
            <p class="text-xs text-[#4E5262] mt-0.5 leading-relaxed">
                Unduh salinan berkas SQL lengkap berisi katalog menu, resep, bahan baku, transaksi, pergerakan stok, dan pengaturan toko untuk arsip offline harian.
            </p>
        </div>

        <div class="p-5 rounded-xl bg-white/70 border border-[#ECEEF2] flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-xl bg-[#2EB872]/15 text-[#2EB872] flex items-center justify-center shrink-0">
                    <x-ui.icon name="database" class="w-6 h-6" />
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-bold text-[#1E2029]">Database SQLite / MySQL</span>
                    <span class="text-xs text-[#4E5262]">Semua tabel operasional, katalog resep, dan transaksi kasir siap diekspor</span>
                </div>
            </div>

            <x-ui.button
                variant="primary"
                size="md"
                icon="download"
                wire:click="exportDatabase"
            >
                Ekspor Database (.sql)
            </x-ui.button>
        </div>
    </div>

    <!-- MODAL M18: Tambah / Edit Akun Kasir -->
    <x-ui.modal name="cashier-form-modal" :title="$editingCashierId ? 'Edit Akun Kasir' : 'Tambah Akun Kasir Baru'" size="md">
        <form wire:submit.prevent="saveCashier" class="flex flex-col gap-4">
            <x-ui.input
                label="Nama Lengkap Kasir"
                wire:model="cashierName"
                placeholder="Contoh: Rian Pratama"
                :error="$errors->first('cashierName')"
                required
            />

            <x-ui.input
                label="Email / Username Login"
                type="email"
                wire:model="cashierEmail"
                placeholder="kasir2@outlet.id"
                :error="$errors->first('cashierEmail')"
                required
            />

            <x-ui.input
                label="Password Baru"
                type="password"
                wire:model="cashierPassword"
                placeholder="Minimal 6 karakter..."
                :error="$errors->first('cashierPassword')"
            />

            <div class="flex flex-col gap-2 pt-1">
                <label class="flex items-center gap-2 text-xs text-[#1E2029] cursor-pointer">
                    <input
                        type="checkbox"
                        wire:model="cashierCanDiscount"
                        class="w-4 h-4 rounded text-[#5E63E8] accent-[#5E63E8]"
                    />
                    <span>Izinkan kasir ini memberikan diskon pada kasir POS</span>
                </label>

                <label class="flex items-center gap-2 text-xs text-[#1E2029] cursor-pointer">
                    <input
                        type="checkbox"
                        wire:model="cashierIsActive"
                        class="w-4 h-4 rounded text-[#5E63E8] accent-[#5E63E8]"
                    />
                    <span>Status Akun Aktif</span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-2.5 mt-2">
                <x-ui.button variant="secondary" size="md" @click="$dispatch('close-modal', 'cashier-form-modal')">
                    Batal
                </x-ui.button>
                <x-ui.button variant="primary" size="md" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveCashier">Simpan Akun Kasir</span>
                    <span wire:loading wire:target="saveCashier">Menyimpan...</span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <!-- MODAL M19: Tes Baca Gambar AI -->
    <x-ui.modal name="sample-test-modal" title="Uji Ekstraksi Screenshot (Dry Run)" size="lg">
        <div class="flex flex-col gap-4">
            <p class="text-xs text-[#4E5262]">
                Unggah satu contoh screenshot untuk memverifikasi hasil ekstraksi AI/Lokal tanpa menyimpan transaksi ke database.
            </p>

            <input
                type="file"
                wire:model="sampleTestImage"
                accept="image/*"
                class="text-xs file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-[#5E63E8] file:text-white hover:file:bg-[#4D52D6]"
            />

            @if($sampleTestResult)
                <div class="p-4 rounded-xl bg-white/70 border border-[#ECEEF2] flex flex-col gap-2.5 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-[#1E2029]">Hasil Ekstraksi ({{ $sampleTestResult['latency'] }}ms)</span>
                        <x-ui.badge variant="success" size="sm">{{ $sampleTestResult['provider'] }}</x-ui.badge>
                    </div>
                    <div><b>Kanal:</b> {{ strtoupper($sampleTestResult['platform']) }}</div>
                    <div><b>Nomor Pesanan:</b> {{ $sampleTestResult['external_order_id'] }}</div>
                    <div><b>Total Platform:</b> Rp{{ number_format($sampleTestResult['total'], 0, ',', '.') }}</div>
                    
                    <div class="mt-2 border-t border-[#ECEEF2] pt-2">
                        <span class="font-bold block mb-1">Item Terbaca:</span>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($sampleTestResult['items'] as $item)
                                <li>{{ $item['raw_name'] }} (x{{ $item['qty'] }}) &rarr; <span class="text-[#2EB872]">{{ $item['menu_name'] ?: 'Perlu Dipilih' }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div class="flex items-center justify-end gap-2.5 mt-2">
                <x-ui.button variant="secondary" size="md" @click="$dispatch('close-modal', 'sample-test-modal')">
                    Tutup
                </x-ui.button>
                <x-ui.button
                    variant="primary"
                    size="md"
                    wire:click="runSampleTest"
                    wire:loading.attr="disabled"
                    wire:target="runSampleTest"
                >
                    <span wire:loading.remove wire:target="runSampleTest">Jalankan Tes Baca</span>
                    <span wire:loading wire:target="runSampleTest">Membaca...</span>
                </x-ui.button>
            </div>
        </div>
    </x-ui.modal>

    <!-- MODAL M20: Hapus API Key? -->
    <x-ui.modal name="delete-key-modal" title="Hapus API Key AI?" size="sm">
        <div class="flex flex-col gap-3">
            <p class="text-xs text-[#5A5D6C] leading-relaxed">
                API key yang tersimpan akan dihapus dari penyimpanan terenkripsi.
            </p>

            <div class="flex items-center justify-end gap-2.5 mt-3">
                <x-ui.button variant="secondary" size="md" @click="$dispatch('close-modal', 'delete-key-modal')">
                    Batal
                </x-ui.button>
                <x-ui.button variant="danger" size="md" wire:click="deleteApiKey">
                    Ya, Hapus Key
                </x-ui.button>
            </div>
        </div>
    </x-ui.modal>

</div>
