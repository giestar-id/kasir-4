<div class="flex flex-col gap-5">

    <!-- Header & Action Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-[#1E2029] tracking-tight">Pesanan Online</h1>
            <p class="text-xs text-[#4E5262] mt-0.5">Integrasi pesanan GoFood & ShopeeFood dengan ekstraksi AI hybrid</p>
        </div>

        <div class="flex items-center gap-2.5">
            <x-ui.button
                variant="secondary"
                size="md"
                icon="plus-circle"
                @click="$dispatch('open-modal', 'manual-online-modal')"
            >
                Input Manual
            </x-ui.button>

            <x-ui.button
                variant="primary"
                size="md"
                icon="upload"
                @click="$dispatch('open-modal', 'upload-screenshot-modal')"
            >
                Unggah Screenshot
            </x-ui.button>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="glass-card rounded-2xl p-2 flex items-center gap-1.5 overflow-x-auto scrollbar-none">
        @php
            $tabs = [
                'all' => 'Semua',
                'needs_review' => 'Perlu Dicek',
                'ready_to_save' => 'Siap Disimpan',
                'saved' => 'Selesai',
                'failed' => 'Gagal',
            ];
        @endphp
        @foreach($tabs as $tabKey => $tabLabel)
            <button
                type="button"
                wire:click="setStatusFilter('{{ $tabKey }}')"
                class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all cursor-pointer {{ $statusFilter === $tabKey ? 'bg-[#5E63E8] text-white shadow-xs' : 'text-[#5A5D6C] hover:text-[#1E2029] hover:bg-white/60' }}"
            >
                {{ $tabLabel }}
            </button>
        @endforeach
    </div>

    <!-- Queue Table Container -->
    <div class="glass-card rounded-2xl overflow-hidden flex flex-col">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-[#ECEEF2] text-[11px] font-bold text-[#4E5262] uppercase tracking-wider bg-white/40">
                        <th class="py-3 px-4">Waktu Unggah</th>
                        <th class="py-3 px-4">Kanal</th>
                        <th class="py-3 px-4">Nomor Pesanan</th>
                        <th class="py-3 px-4">Total & Item Terbaca</th>
                        <th class="py-3 px-4">Sumber Baca</th>
                        <th class="py-3 px-4">Keyakinan</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#ECEEF2]/70 text-xs">
                    @forelse($imports as $imp)
                        <tr wire:key="import-{{ $imp->id }}" class="hover:bg-white/50 transition-colors">
                            <td class="py-3.5 px-4 text-[#4E5262]">
                                {{ $imp->created_at->format('d M Y, H:i') }}
                            </td>
                            <td class="py-3.5 px-4">
                                <x-ui.badge :variant="$imp->platform === 'shopeefood' ? 'shopeefood' : 'gofood'" size="sm">
                                    {{ $imp->platform === 'shopeefood' ? 'ShopeeFood' : 'GoFood' }}
                                </x-ui.badge>
                            </td>
                            <td class="py-3.5 px-4 font-bold text-[#1E2029]">
                                <div class="flex flex-col">
                                    <span>{{ $imp->raw_result['external_order_id'] ?? 'Pesanan #' . $imp->id }}</span>
                                    @if(!empty($imp->raw_result['order_time']))
                                        <span class="text-[10px] text-[#4E5262] font-normal">
                                            Waktu: {{ \Carbon\Carbon::parse($imp->raw_result['order_time'])->format('d M Y, H:i') }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                @php
                                    $rowTotal = (int) ($imp->raw_result['total'] ?? ($imp->sale?->total ?? 0));
                                    $itemCount = count($imp->raw_result['items'] ?? []);
                                    $firstItem = $imp->raw_result['items'][0]['raw_name'] ?? ($imp->raw_result['items'][0]['name'] ?? null);
                                @endphp
                                <div class="flex flex-col">
                                    <span class="font-bold text-xs sm:text-sm tabular-nums {{ $rowTotal > 0 ? 'text-[#2EB872]' : 'text-[#4E5262]' }}">
                                        Rp{{ number_format($rowTotal, 0, ',', '.') }}
                                    </span>
                                    @if($itemCount > 0)
                                        <span class="text-[11px] text-[#4E5262] truncate max-w-[200px]" title="{{ $firstItem }}">
                                            {{ $itemCount }} item · {{ $firstItem }}
                                        </span>
                                    @else
                                        <span class="text-[10px] text-[#4E5262]">Item belum ditentukan</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($imp->source === 'ai_api')
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-[#5E63E8]">
                                        <x-ui.icon name="sparkles" class="w-3.5 h-3.5" />
                                        <span>AI Cloud</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-[#5A5D6C]">
                                        <x-ui.icon name="cpu" class="w-3.5 h-3.5" />
                                        <span>OCR Lokal</span>
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 tabular-nums">
                                <span class="font-bold text-[#1E2029]">{{ $imp->confidence ?? 85 }}%</span>
                            </td>
                            <td class="py-3.5 px-4">
                                @php
                                    $statusBadge = match($imp->status) {
                                        'saved' => ['success', 'Tersimpan'],
                                        'ready_to_save' => ['info', 'Siap Simpan'],
                                        'needs_review' => ['warning', 'Perlu Cek'],
                                        'duplicate' => ['error', 'Duplikat'],
                                        'failed' => ['error', 'Gagal'],
                                        default => ['neutral', 'Menunggu'],
                                    };
                                @endphp
                                <x-ui.badge :variant="$statusBadge[0]" size="sm">
                                    {{ $statusBadge[1] }}
                                </x-ui.badge>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                @if($imp->status === 'saved')
                                    <div class="flex flex-col items-end gap-0.5">
                                        <span class="text-xs font-bold text-[#2EB872] flex items-center gap-1">
                                            <x-ui.icon name="check-circle" class="w-3.5 h-3.5" />
                                            <span>Tersimpan</span>
                                        </span>
                                        @if($imp->sale)
                                            <a
                                                href="{{ route('transactions') }}?search={{ $imp->sale->number }}"
                                                wire:navigate
                                                class="text-[11px] text-[#5E63E8] hover:underline font-semibold"
                                                title="Lihat transaksi di Riwayat Transaksi"
                                            >
                                                #{{ $imp->sale->number }} &rarr;
                                            </a>
                                        @endif
                                    </div>
                                @else
                                    <x-ui.button
                                        variant="primary"
                                        size="sm"
                                        wire:click="openConfirmModal({{ $imp->id }})"
                                    >
                                        Periksa & Simpan
                                    </x-ui.button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <x-ui.empty-state
                                    icon="receipt"
                                    title="Belum ada antrian pesanan online"
                                    description="Unggah screenshot struk pesanan GoFood atau ShopeeFood untuk membaca dan memasukkan transaksi otomatis."
                                >
                                    <x-ui.button
                                        variant="primary"
                                        size="sm"
                                        icon="upload"
                                        @click="$dispatch('open-modal', 'upload-screenshot-modal')"
                                    >
                                        Unggah Sekarang
                                    </x-ui.button>
                                </x-ui.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($imports->hasPages())
            <div class="p-4 border-t border-[#ECEEF2]">
                {{ $imports->links() }}
            </div>
        @endif
    </div>

    <!-- Modal M4: Unggah Screenshot Pesanan Online -->
    <x-ui.modal name="upload-screenshot-modal" title="Unggah Screenshot Pesanan Online" size="md">
        <form wire:submit.prevent="uploadScreenshots" class="flex flex-col gap-4">
            
            @php
                $files = is_array($uploadedFiles) ? $uploadedFiles : ($uploadedFiles ? [$uploadedFiles] : []);
                $files = array_values(array_filter($files));
            @endphp

            <!-- Dropzone Container with Alpine state & Livewire upload hooks -->
            <div
                x-data="{
                    isDropping: false,
                    isUploading: false,
                    uploadProgress: 0,
                    handleDrop(e) {
                        this.isDropping = false;
                        const files = e.dataTransfer.files;
                        if (files && files.length > 0) {
                            this.isUploading = true;
                            this.uploadProgress = 15;
                            @this.uploadMultiple(
                                'uploadedFiles',
                                files,
                                () => { this.isUploading = false; this.uploadProgress = 100; },
                                () => { this.isUploading = false; },
                                (event) => { this.uploadProgress = event.detail.progress; }
                            );
                        }
                    }
                }"
                x-on:livewire-upload-start="isUploading = true; uploadProgress = 10"
                x-on:livewire-upload-finish="isUploading = false; uploadProgress = 100"
                x-on:livewire-upload-error="isUploading = false"
                x-on:livewire-upload-progress="uploadProgress = $event.detail.progress"
                @dragover.prevent="isDropping = true"
                @dragleave.prevent="isDropping = false"
                @drop.prevent="handleDrop($event)"
                class="border-2 border-dashed rounded-2xl p-6 text-center flex flex-col items-center justify-center transition-all relative overflow-hidden"
                :class="isDropping ? 'border-[#5E63E8] bg-[#5E63E8]/10 scale-[1.01]' : 'border-[#ECEEF2] bg-white/50'"
            >
                <div class="w-12 h-12 rounded-xl bg-[#5E63E8]/10 text-[#5E63E8] flex items-center justify-center mb-2.5">
                    <x-ui.icon name="image" class="w-6 h-6" />
                </div>
                <h4 class="text-xs sm:text-sm font-bold text-[#1E2029]">Seret dan lepas file di sini</h4>
                <p class="text-xs text-[#4E5262] mt-0.5">atau klik tombol telusuri (Maksimal 8 MB per file)</p>

                <input
                    type="file"
                    x-ref="fileInput"
                    wire:model="uploadedFiles"
                    multiple
                    accept="image/png,image/jpeg,image/webp"
                    class="hidden"
                />

                <x-ui.button
                    type="button"
                    variant="secondary"
                    size="sm"
                    class="mt-3"
                    @click="$refs.fileInput.click()"
                >
                    <x-ui.icon name="folder-up" class="w-3.5 h-3.5 mr-1" />
                    Pilih Gambar
                </x-ui.button>

                <!-- Live Upload Progress Bar -->
                <div x-show="isUploading" x-cloak class="w-full mt-4 flex flex-col gap-1.5 items-center bg-white/90 p-3 rounded-xl border border-[#ECEEF2] shadow-2xs">
                    <div class="flex items-center justify-between w-full text-xs font-semibold text-[#5E63E8]">
                        <span class="flex items-center gap-1.5">
                            <x-ui.icon name="loader" class="w-3.5 h-3.5 animate-spin text-[#5E63E8]" />
                            <span>Mengunggah file ke server...</span>
                        </span>
                        <span class="tabular-nums" x-text="uploadProgress + '%'"></span>
                    </div>
                    <div class="w-full bg-[#ECEEF2] h-2 rounded-full overflow-hidden">
                        <div
                            class="bg-[#5E63E8] h-full transition-all duration-150 rounded-full"
                            :style="'width: ' + uploadProgress + '%'"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Error Feedback -->
            @error('uploadedFiles')
                <div class="p-3 rounded-xl bg-[#E5484D]/10 border border-[#E5484D]/30 flex items-center gap-2 text-xs text-[#E5484D]">
                    <x-ui.icon name="alert-circle" class="w-4 h-4 shrink-0" />
                    <span>{{ $message }}</span>
                </div>
            @enderror
            @error('uploadedFiles.*')
                <div class="p-3 rounded-xl bg-[#E5484D]/10 border border-[#E5484D]/30 flex items-center gap-2 text-xs text-[#E5484D]">
                    <x-ui.icon name="alert-circle" class="w-4 h-4 shrink-0" />
                    <span>{{ $message }}</span>
                </div>
            @enderror

            <!-- List of Selected / Uploaded Files -->
            @if(count($files) > 0)
                <div class="p-3.5 rounded-xl bg-white/80 border border-[#ECEEF2] flex flex-col gap-2">
                    <div class="flex items-center justify-between text-xs font-bold text-[#1E2029]">
                        <span class="flex items-center gap-1.5 text-[#2EB872]">
                            <x-ui.icon name="check-circle" class="w-4 h-4 text-[#2EB872]" />
                            <span>{{ count($files) }} file siap diproses:</span>
                        </span>
                        <button
                            type="button"
                            wire:click="$set('uploadedFiles', [])"
                            class="text-[11px] font-semibold text-[#E5484D] hover:underline cursor-pointer"
                        >
                            Hapus Semua
                        </button>
                    </div>
                    <ul class="flex flex-col gap-1.5 text-xs text-[#5A5D6C] max-h-40 overflow-y-auto pr-1">
                        @foreach($files as $idx => $f)
                            @if(is_object($f) && method_exists($f, 'getClientOriginalName'))
                                <li class="flex items-center justify-between p-2 rounded-lg bg-[#F8F8FA] border border-[#ECEEF2]/70">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <x-ui.icon name="file-text" class="w-4 h-4 text-[#4E5262] shrink-0" />
                                        <span class="truncate font-medium text-[#1E2029]">{{ $f->getClientOriginalName() }}</span>
                                        <span class="text-[10px] text-[#4E5262] shrink-0">({{ number_format($f->getSize() / 1024, 0) }} KB)</span>
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="removeUploadedFile({{ $idx }})"
                                        class="p-1 text-[#4E5262] hover:text-[#E5484D] rounded cursor-pointer shrink-0 ml-2"
                                        title="Hapus file ini"
                                    >
                                        <x-ui.icon name="x" class="w-3.5 h-3.5" />
                                    </button>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Modal Actions -->
            <div class="flex items-center justify-end gap-2.5 mt-2">
                <x-ui.button variant="secondary" size="md" @click="$dispatch('close-modal', 'upload-screenshot-modal')">
                    Batal
                </x-ui.button>
                <x-ui.button
                    variant="primary"
                    size="md"
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="uploadScreenshots"
                    :disabled="count($files) === 0"
                >
                    <span wire:loading.remove wire:target="uploadScreenshots" class="flex items-center gap-2">
                        <x-ui.icon name="sparkles" class="w-4 h-4" />
                        <span>Proses Screenshot</span>
                    </span>
                    <span wire:loading wire:target="uploadScreenshots" class="flex items-center gap-2">
                        <x-ui.icon name="loader" class="w-4 h-4 animate-spin" />
                        <span>Membaca & Mengekstrak Data...</span>
                    </span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <!-- Modal M5: Konfirmasi Ekstraksi AI (Split View) -->
    <x-ui.modal name="ai-confirmation-modal" title="Konfirmasi Pesanan Online" size="xl">
        <form wire:submit.prevent="saveConfirmedOrder" class="flex flex-col gap-4">
            
            @if($isDuplicateWarning)
                <div class="p-3 rounded-xl bg-[#E5484D]/10 border border-[#E5484D]/30 flex items-start gap-2.5 text-xs text-[#E5484D]">
                    <x-ui.icon name="alert-triangle" class="w-4 h-4 shrink-0 mt-0.5" />
                    <span>{{ $duplicateWarningMessage }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <!-- Left: Order Metadata -->
                <div class="flex flex-col gap-3 p-4 rounded-xl bg-white/60 border border-[#ECEEF2]">
                    <h4 class="text-xs font-bold text-[#1E2029]">Detail Pesanan Platform</h4>

                    <x-ui.select label="Platform" wire:model="confirmPlatform">
                        <option value="gofood">GoFood</option>
                        <option value="shopeefood">ShopeeFood</option>
                    </x-ui.select>

                    <x-ui.input
                        label="Nomor Pesanan Eksternal"
                        wire:model="confirmExternalId"
                        :error="$errors->first('confirmExternalId')"
                        required
                    />

                    <div>
                        <x-ui.input
                            label="Waktu Pesanan"
                            type="datetime-local"
                            wire:model.live="confirmOrderTime"
                            :error="$errors->first('confirmOrderTime')"
                            required
                        />
                        <div class="flex items-center justify-between mt-1">
                            <button
                                type="button"
                                wire:click="useCurrentTime"
                                class="text-[11px] text-[#5E63E8] hover:underline font-semibold cursor-pointer block"
                            >
                                &bull; Gunakan Waktu Sekarang (Hari Ini)
                            </button>
                        </div>
                        @if($confirmOrderTime && !\Carbon\Carbon::parse($confirmOrderTime)->isToday())
                            <p class="text-[11px] text-[#D97706] mt-1.5 flex items-center gap-1 font-medium bg-[#F59E0B]/10 p-2 rounded-lg border border-[#F59E0B]/20">
                                <x-ui.icon name="alert-triangle" class="w-3.5 h-3.5 shrink-0 text-[#D97706]" />
                                <span>Tanggal struk terbaca bukan hari ini (<b>{{ \Carbon\Carbon::parse($confirmOrderTime)->format('d M Y') }}</b>). Transaksi akan tercatat pada tanggal tersebut di Dashboard.</span>
                            </p>
                        @endif
                    </div>

                    <x-ui.input
                        label="Total Tagihan di Platform (Rp)"
                        type="number"
                        wire:model.live="confirmPlatformTotal"
                        prefix="Rp"
                        :error="$errors->first('confirmPlatformTotal')"
                        required
                    />
                </div>

                <!-- Right: Item Matching List -->
                <div class="flex flex-col gap-2.5 p-4 rounded-xl bg-white/60 border border-[#ECEEF2] max-h-[360px] overflow-y-auto">
                    <h4 class="text-xs font-bold text-[#1E2029]">Pencocokan Item Menu</h4>

                    @foreach($confirmItems as $idx => $cItem)
                        <div class="p-3 rounded-xl bg-white border border-[#ECEEF2] flex flex-col gap-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-[#1E2029]">{{ $cItem['raw_name'] }}</span>
                                @if($cItem['is_matched'])
                                    <x-ui.badge variant="success" size="sm">Cocok</x-ui.badge>
                                @else
                                    <x-ui.badge variant="error" size="sm">Perlu Dipilih</x-ui.badge>
                                @endif
                            </div>

                            <select
                                wire:change="onConfirmMenuSelect({{ $idx }}, $event.target.value)"
                                class="w-full text-xs bg-white border border-[#ECEEF2] rounded-lg p-2"
                            >
                                <option value="">-- Pilih Menu Terdaftar --</option>
                                @foreach($menus as $m)
                                    <option value="{{ $m->id }}" {{ $cItem['menu_id'] == $m->id ? 'selected' : '' }}>
                                        {{ $m->name }} (Rp{{ number_format($m->price, 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>

                            <div class="flex items-center justify-between text-xs pt-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[#4E5262]">Qty:</span>
                                    <input
                                        type="number"
                                        min="1"
                                        wire:model.live="confirmItems.{{ $idx }}.qty"
                                        class="w-12 text-center border border-[#ECEEF2] rounded-md py-0.5 text-xs font-bold"
                                    />
                                </div>
                                <span class="font-bold tabular-nums">
                                    Rp{{ number_format($cItem['qty'] * $cItem['unit_price'], 0, ',', '.') }}
                                </span>
                            </div>

                            <label class="flex items-center gap-1.5 text-[11px] text-[#5A5D6C] cursor-pointer mt-1">
                                <input
                                    type="checkbox"
                                    wire:model="confirmItems.{{ $idx }}.save_as_alias"
                                    class="w-3.5 h-3.5 rounded text-[#5E63E8] accent-[#5E63E8]"
                                />
                                <span>Simpan nama ini sebagai alias menu</span>
                            </label>
                        </div>
                    @endforeach
                </div>

            </div>

            <!-- Calculation Summary -->
            <div class="p-3.5 rounded-xl bg-[#5E63E8]/5 border border-[#5E63E8]/20 flex items-center justify-between text-xs">
                <div>
                    <span class="text-[#5A5D6C]">Subtotal Menu: <b>Rp{{ number_format($this->confirmSubtotal, 0, ',', '.') }}</b></span>
                    <span class="mx-2 text-[#4E5262]">|</span>
                    <span class="text-[#5A5D6C]">Penyesuaian Platform: <b>Rp{{ number_format($this->confirmAdjustment, 0, ',', '.') }}</b></span>
                </div>
                <div>
                    <span class="text-[#5A5D6C]">Total Pemasukan (Omzet):</span>
                    <span class="text-base font-bold text-[#2EB872] ml-1 tabular-nums">Rp{{ number_format($confirmPlatformTotal, 0, ',', '.') }}</span>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5 mt-2">
                <x-ui.button variant="secondary" size="md" @click="$dispatch('close-modal', 'ai-confirmation-modal')">
                    Batal
                </x-ui.button>
                <x-ui.button variant="primary" size="md" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove class="flex items-center gap-1.5">
                        <x-ui.icon name="check-circle" class="w-4 h-4" />
                        <span>Simpan ke Pemasukan</span>
                    </span>
                    <span wire:loading>Mencatat Transaksi...</span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <!-- Modal M6: Input Pesanan Online Manual -->
    <x-ui.modal name="manual-online-modal" title="Input Pesanan Online Manual" size="md">
        <form wire:submit.prevent="saveManualOrder" class="flex flex-col gap-4">
            
            <x-ui.select label="Kanal Penjualan" wire:model="manualChannel" :error="$errors->first('manualChannel')" required>
                <option value="gofood">GoFood</option>
                <option value="shopeefood">ShopeeFood</option>
            </x-ui.select>

            <x-ui.input
                label="Nomor Pesanan Eksternal"
                wire:model="manualExternalId"
                placeholder="Contoh: GF-839210 atau SPF-99214"
                :error="$errors->first('manualExternalId')"
                required
            />

            <x-ui.input
                label="Waktu Pesanan"
                type="datetime-local"
                wire:model="manualOrderTime"
                :error="$errors->first('manualOrderTime')"
                required
            />

            <!-- Item selection -->
            <div class="flex flex-col gap-2">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-semibold text-[#5A5D6C]">Daftar Menu</label>
                    <button
                        type="button"
                        wire:click="addManualItem"
                        class="text-xs font-bold text-[#5E63E8] hover:underline cursor-pointer"
                    >
                        + Tambah Menu
                    </button>
                </div>

                @foreach($manualItems as $mIdx => $mItem)
                    <div class="flex items-center gap-2">
                        <select
                            wire:change="onManualMenuChange({{ $mIdx }}, $event.target.value)"
                            class="flex-1 text-xs bg-white border border-[#ECEEF2] rounded-xl p-2.5"
                        >
                            @foreach($menus as $m)
                                <option value="{{ $m->id }}" {{ ($mItem['menu_id'] ?? null) == $m->id ? 'selected' : '' }}>
                                    {{ $m->name }} (Rp{{ number_format($m->price, 0, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                        <input
                            type="number"
                            min="1"
                            wire:model.live="manualItems.{{ $mIdx }}.qty"
                            class="w-16 text-center text-xs bg-white border border-[#ECEEF2] rounded-xl p-2.5 font-bold"
                        />
                        @if(count($manualItems) > 1)
                            <button
                                type="button"
                                wire:click="removeManualItem({{ $mIdx }})"
                                class="p-2 text-[#4E5262] hover:text-[#E5484D]"
                            >
                                <x-ui.icon name="trash" class="w-4 h-4" />
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>

            <x-ui.input
                label="Total Tagihan di Platform (Rp)"
                type="number"
                wire:model.live="manualPlatformTotal"
                prefix="Rp"
                :error="$errors->first('manualPlatformTotal')"
                required
            />

            <div class="p-3 rounded-xl bg-white/70 border border-[#ECEEF2] text-xs flex justify-between">
                <span class="text-[#4E5262]">Penyesuaian Platform:</span>
                <span class="font-bold tabular-nums {{ $this->manualAdjustment < 0 ? 'text-[#E5484D]' : 'text-[#2EB872]' }}">
                    Rp{{ number_format($this->manualAdjustment, 0, ',', '.') }}
                </span>
            </div>

            <x-ui.input
                label="Catatan (Opsional)"
                wire:model="manualNotes"
                placeholder="Catatan tambahan..."
            />

            <div class="flex items-center justify-end gap-2.5 mt-2">
                <x-ui.button variant="secondary" size="md" @click="$dispatch('close-modal', 'manual-online-modal')">
                    Batal
                </x-ui.button>
                <x-ui.button variant="primary" size="md" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove>Simpan Pesanan</span>
                    <span wire:loading>Menyimpan...</span>
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

</div>

