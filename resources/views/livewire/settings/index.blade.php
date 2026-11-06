<div class="flex flex-col gap-5">

    <!-- Header Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#1E2029] tracking-tight">Pengaturan Outlet</h1>
            <p class="text-xs text-[#4E5262] mt-0.5">Konfigurasi profil toko, akun kasir, enkripsi integrasi AI, dan cadangan database</p>
        </div>
    </div>

    <!-- Horizontal Tabs -->
    <div class="glass-card rounded-2xl p-2 flex items-center gap-1.5 overflow-x-auto scrollbar-none">
        <button
            type="button"
            wire:click="$set('activeTab', 'profile')"
            class="px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all cursor-pointer {{ $activeTab === 'profile' ? 'bg-[#5E63E8] text-white shadow-xs' : 'text-[#5A5D6C] hover:bg-white/60 hover:text-[#1E2029]' }}"
        >
            Profil Toko
        </button>
        <button
            type="button"
            wire:click="$set('activeTab', 'security')"
            class="px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all cursor-pointer {{ $activeTab === 'security' ? 'bg-[#5E63E8] text-white shadow-xs' : 'text-[#5A5D6C] hover:bg-white/60 hover:text-[#1E2029]' }}"
        >
            Keamanan & Kasir
        </button>
        <button
            type="button"
            wire:click="$set('activeTab', 'ai')"
            class="px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all cursor-pointer {{ $activeTab === 'ai' ? 'bg-[#5E63E8] text-white shadow-xs' : 'text-[#5A5D6C] hover:bg-white/60 hover:text-[#1E2029]' }}"
        >
            Integrasi AI Hybrid
        </button>
        <button
            type="button"
            wire:click="$set('activeTab', 'backup')"
            class="px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all cursor-pointer {{ $activeTab === 'backup' ? 'bg-[#5E63E8] text-white shadow-xs' : 'text-[#5A5D6C] hover:bg-white/60 hover:text-[#1E2029]' }}"
        >
            Cadangan Data
        </button>
    </div>

    <!-- Tab 1: Profil Toko -->
    @if($activeTab === 'profile')
        <div class="glass-card rounded-2xl p-6 sm:p-7 w-full flex flex-col gap-5">
            <div>
                <h2 class="text-base font-bold text-[#1E2029]">Identitas & Informasi Struk</h2>
                <p class="text-xs text-[#4E5262] mt-0.5">Informasi ini akan tercetak pada struk fisik kasir dan identitas outlet.</p>
            </div>

            <form wire:submit.prevent="saveProfile" class="flex flex-col gap-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.input
                        label="Nama Toko / Outlet"
                        wire:model="storeName"
                        placeholder="Contoh: POS Kuliner Nusantara"
                        required
                    />

                    <x-ui.input
                        label="Nomor Telepon / WhatsApp"
                        wire:model="storePhone"
                        placeholder="Contoh: 0812-3456-7890"
                    />
                </div>

                <x-ui.input
                    label="Alamat Lengkap Outlet"
                    wire:model="storeAddress"
                    placeholder="Contoh: Jl. Boulevard Raya No. 8, Kelapa Gading, Jakarta"
                />

                <x-ui.input
                    label="Catatan Kaki Struk"
                    wire:model="receiptFooter"
                    placeholder="Contoh: Terima kasih atas kunjungan Anda. Selamat menikmati!"
                />

                <div class="pt-4 border-t border-[#ECEEF2]">
                    <h3 class="text-xs font-bold text-[#1E2029] mb-3">Estimasi Komisi Pesanan Online</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-ui.input
                            label="Komisi Estimasi GoFood (%)"
                            type="number"
                            wire:model="gofoodCommission"
                            suffix="%"
                            hint="Dipakai untuk estimasi laba bersih di dashboard"
                        />
                        <x-ui.input
                            label="Komisi Estimasi ShopeeFood (%)"
                            type="number"
                            wire:model="shopeefoodCommission"
                            suffix="%"
                            hint="Dipakai untuk estimasi laba bersih di dashboard"
                        />
                    </div>
                </div>

                <div class="flex justify-end pt-3 border-t border-[#ECEEF2]">
                    <x-ui.button variant="primary" size="md" type="submit" wire:loading.attr="disabled">
                        <span wire:loading.remove>Simpan Perubahan</span>
                        <span wire:loading>Menyimpan...</span>
                    </x-ui.button>
                </div>
            </form>
        </div>
    @endif

    <!-- Tab 2: Keamanan & Akun Kasir -->
    @if($activeTab === 'security')
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start w-full">
            
            <!-- Left: Change Owner Password -->
            <div class="lg:col-span-5 glass-card rounded-2xl p-6 flex flex-col gap-4">
                <h3 class="text-base font-bold text-[#1E2029]">Ubah Password Pemilik</h3>
                <p class="text-xs text-[#4E5262]">Gunakan minimal 10 karakter dengan kombinasi huruf dan angka.</p>

                <form wire:submit.prevent="changePassword" class="flex flex-col gap-3.5">
                    <x-ui.input
                        label="Password Lama"
                        type="password"
                        wire:model="oldPassword"
                        required
                    />

                    <x-ui.input
                        label="Password Baru (min. 10 karakter)"
                        type="password"
                        wire:model="newPassword"
                        :error="$errors->first('newPassword')"
                        required
                    />

                    <x-ui.input
                        label="Konfirmasi Password Baru"
                        type="password"
                        wire:model="confirmPassword"
                        :error="$errors->first('confirmPassword')"
                        required
                    />

                    <div class="flex justify-end pt-2">
                        <x-ui.button variant="primary" size="md" type="submit" wire:loading.attr="disabled">
                            <span wire:loading.remove>Perbarui Password</span>
                            <span wire:loading>Memperbarui...</span>
                        </x-ui.button>
                    </div>
                </form>
            </div>

            <!-- Right: Cashier Accounts Table -->
            <div class="lg:col-span-7 glass-card rounded-2xl p-6 flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-[#1E2029]">Daftar Akun Kasir</h3>
                        <p class="text-xs text-[#4E5262]">Kasir hanya dapat mengakses POS dan tidak melihat data HPP / Laba</p>
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

                <div class="border border-[#ECEEF2] rounded-xl overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-white/60 text-[#4E5262] border-b border-[#ECEEF2] text-[11px] uppercase">
                            <tr>
                                <th class="py-2.5 px-3">Nama</th>
                                <th class="py-2.5 px-3">Email / User</th>
                                <th class="py-2.5 px-3">Diskon</th>
                                <th class="py-2.5 px-3">Status</th>
                                <th class="py-2.5 px-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#ECEEF2]">
                            @forelse($cashiers as $c)
                                <tr>
                                    <td class="py-2.5 px-3 font-bold text-[#1E2029]">{{ $c->name }}</td>
                                    <td class="py-2.5 px-3 text-[#5A5D6C]">{{ $c->email }}</td>
                                    <td class="py-2.5 px-3">
                                        @if($c->can_give_discount)
                                            <span class="text-[10px] text-[#2EB872] font-semibold">Diizinkan</span>
                                        @else
                                            <span class="text-[10px] text-[#4E5262]">Tidak</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3">
                                        @if($c->is_active)
                                            <x-ui.badge variant="success" size="sm">Aktif</x-ui.badge>
                                        @else
                                            <x-ui.badge variant="neutral" size="sm">Nonaktif</x-ui.badge>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3 text-right">
                                        <x-ui.button variant="secondary" size="sm" wire:click="openEditCashierModal({{ $c->id }})">
                                            Edit
                                        </x-ui.button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-[#4E5262]">
                                        Belum ada kasir tambahan. Gunakan tombol + Tambah Kasir di atas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    @endif

    <!-- Tab 3: Integrasi AI Hybrid -->
    @if($activeTab === 'ai')
        <div class="glass-card rounded-2xl p-6 sm:p-7 w-full flex flex-col gap-5">
            <div class="flex items-start justify-between gap-4">
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
                        <span wire:loading.remove>Simpan Pengaturan AI</span>
                        <span wire:loading>Menyimpan...</span>
                    </x-ui.button>
                </div>
            </form>
        </div>
    @endif

    <!-- Tab 4: Cadangan Data -->
    @if($activeTab === 'backup')
        <div class="glass-card rounded-2xl p-6 sm:p-7 w-full flex flex-col gap-5">
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
    @endif

    <!-- Modal M18: Tambah / Edit Akun Kasir -->
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
                    <span wire:loading.remove>Simpan Akun Kasir</span>
                    <span wire:loading>Menyimpan...</span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <!-- Modal M19: Tes Baca Gambar AI -->
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

    <!-- Modal M20: Hapus API Key? -->
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

