<?php

namespace App\Livewire\OnlineOrders;

use App\Models\Menu;
use App\Models\MenuAlias;
use App\Models\Sale;
use App\Models\ScreenshotImport;
use App\Services\Ai\ExtractionPipeline;
use App\Services\SaleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use WithFileUploads, WithPagination;

    public string $statusFilter = 'all'; // all, pending, needs_review, ready_to_save, saved, duplicate, failed

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function setStatusFilter(string $status): void
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    // Upload Modal (M4)
    public $uploadedFiles = [];

    public function removeUploadedFile(int $index): void
    {
        if (is_array($this->uploadedFiles) && isset($this->uploadedFiles[$index])) {
            unset($this->uploadedFiles[$index]);
            $this->uploadedFiles = array_values($this->uploadedFiles);
        } elseif (! is_array($this->uploadedFiles)) {
            $this->uploadedFiles = [];
        }
    }

    // Manual Modal (M6)
    public string $manualChannel = 'gofood';

    public string $manualExternalId = '';

    public string $manualOrderTime = '';

    public array $manualItems = [];

    public int $manualPlatformTotal = 0;

    public string $manualNotes = '';

    // Confirmation Modal (M5)
    public ?int $selectedImportId = null;

    public string $confirmPlatform = 'gofood';

    public string $confirmExternalId = '';

    public string $confirmOrderTime = '';

    public array $confirmItems = [];

    public int $confirmPlatformTotal = 0;

    public bool $isDuplicateWarning = false;

    public function useCurrentTime(): void
    {
        $this->confirmOrderTime = Carbon::now()->format('Y-m-d\TH:i');
    }

    public ?string $duplicateWarningMessage = null;

    public function mount(): void
    {
        $this->manualOrderTime = Carbon::now()->format('Y-m-d\TH:i');
        $this->addManualItem();
    }

    public function addManualItem(): void
    {
        $firstMenu = Menu::where('is_active', true)->first();
        $this->manualItems[] = [
            'menu_id' => $firstMenu?->id ?? null,
            'qty' => 1,
            'price' => (int) ($firstMenu?->price ?? 0),
        ];
    }

    public function removeManualItem(int $index): void
    {
        unset($this->manualItems[$index]);
        $this->manualItems = array_values($this->manualItems);
    }

    public function onManualMenuChange(int $index, int $menuId): void
    {
        $menu = Menu::find($menuId);
        if ($menu && isset($this->manualItems[$index])) {
            $this->manualItems[$index]['menu_id'] = $menu->id;
            $this->manualItems[$index]['price'] = (int) $menu->price;
        }
    }

    public function getManualSubtotalProperty(): int
    {
        $sum = 0;
        foreach ($this->manualItems as $item) {
            $sum += ($item['qty'] ?? 1) * ($item['price'] ?? 0);
        }

        return $sum;
    }

    public function getManualAdjustmentProperty(): int
    {
        return $this->manualPlatformTotal - $this->manualSubtotal;
    }

    public function saveManualOrder(SaleService $saleService): void
    {
        $this->validate([
            'manualChannel' => 'required|in:gofood,shopeefood',
            'manualExternalId' => 'required|string',
            'manualOrderTime' => 'required',
            'manualItems' => 'required|array|min:1',
            'manualPlatformTotal' => 'required|numeric|min:0',
        ], [
            'manualExternalId.required' => 'Nomor pesanan eksternal wajib diisi.',
            'manualItems.min' => 'Pilih setidaknya 1 item menu.',
        ]);

        try {
            $saleData = [
                'channel' => $this->manualChannel,
                'external_order_id' => $this->manualExternalId,
                'subtotal' => $this->manualSubtotal,
                'discount' => 0,
                'platform_adjustment' => $this->manualAdjustment,
                'total' => $this->manualPlatformTotal,
                'payment_method' => 'transfer',
                'paid_amount' => $this->manualPlatformTotal,
                'change_amount' => 0,
                'ordered_at' => Carbon::parse($this->manualOrderTime),
                'source' => Sale::SOURCE_MANUAL,
                'idempotency_key' => (string) Str::uuid(),
                'notes' => $this->manualNotes,
            ];

            $sale = $saleService->createSale($saleData, $this->manualItems, auth()->user());

            $this->reset(['manualExternalId', 'manualPlatformTotal', 'manualNotes']);
            $this->manualItems = [];
            $this->addManualItem();

            $this->dispatch('close-modal', 'manual-online-modal');
            $this->dispatch('toast', [
                'type' => 'success',
                'title' => 'Pesanan Tersimpan',
                'message' => 'Pesanan manual #'.$sale->number.' berhasil disimpan.',
            ]);

        } catch (\Throwable $e) {
            $this->dispatch('toast', [
                'type' => 'error',
                'title' => 'Gagal Menyimpan',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function uploadScreenshots(ExtractionPipeline $pipeline): void
    {
        $files = is_array($this->uploadedFiles) ? $this->uploadedFiles : ($this->uploadedFiles ? [$this->uploadedFiles] : []);
        $files = array_values(array_filter($files));

        if (empty($files)) {
            $this->dispatch('toast', [
                'type' => 'warning',
                'title' => 'Berkas Belum Dipilih',
                'message' => 'Silakan pilih atau seret setidaknya satu berkas screenshot terlebih dahulu.',
            ]);

            return;
        }

        $this->validate([
            'uploadedFiles.*' => 'required|image|mimes:jpeg,png,jpg,webp|mimetypes:image/jpeg,image/png,image/webp|max:8192',
        ], [
            'uploadedFiles.*.required' => 'Berkas gambar wajib diunggah.',
            'uploadedFiles.*.image' => 'Berkas harus berupa gambar.',
            'uploadedFiles.*.mimes' => 'Format gambar harus JPG, JPEG, PNG, atau WEBP.',
            'uploadedFiles.*.mimetypes' => 'Mime-type berkas harus berupa image/jpeg, image/png, atau image/webp.',
            'uploadedFiles.*.max' => 'Ukuran berkas maksimal 8 MB per file.',
        ]);

        $count = 0;
        $duplicateCount = 0;

        foreach ($files as $file) {
            if (! $file || ! is_object($file) || ! method_exists($file, 'getRealPath')) {
                continue;
            }

            $tempPath = $file->getRealPath();
            $fileHash = hash_file('sha256', $tempPath);

            // Layer 2: Check SHA-256 duplicate
            $existing = ScreenshotImport::where('file_hash', $fileHash)->first();
            if ($existing) {
                $duplicateCount++;
                $this->dispatch('toast', [
                    'type' => 'warning',
                    'title' => 'Gambar Duplikat',
                    'message' => 'Screenshot ('.(method_exists($file, 'getClientOriginalName') ? $file->getClientOriginalName() : 'file').') sudah pernah diunggah sebelumnya (ID: #'.$existing->id.').',
                ]);

                continue;
            }

            // Store private
            $path = $file->store('screenshots', 'local');
            $fullStoragePath = Storage::disk('local')->path($path);

            $import = ScreenshotImport::create([
                'file_path' => $path,
                'file_hash' => $fileHash,
                'status' => ScreenshotImport::STATUS_PROCESSING,
                'uploaded_by' => auth()->id(),
            ]);

            // Run Hybrid Extraction
            try {
                $result = $pipeline->process($fullStoragePath);

                $hasUnmatched = empty($result->items) || collect($result->items)->contains(fn ($it) => empty($it['is_matched']));
                $status = ($result->confidence >= 85 && count($result->warnings) === 0 && ! $hasUnmatched)
                    ? ScreenshotImport::STATUS_READY_TO_SAVE
                    : ScreenshotImport::STATUS_NEEDS_REVIEW;

                $import->update([
                    'status' => $status,
                    'source' => $result->source,
                    'platform' => $result->platform,
                    'raw_result' => $result->toArray(),
                    'confidence' => $result->confidence,
                    'fallback_reason' => $result->fallbackReason,
                ]);

                $count++;
            } catch (\Throwable $e) {
                $import->update([
                    'status' => ScreenshotImport::STATUS_FAILED,
                    'fallback_reason' => $e->getMessage(),
                ]);
            }
        }

        $this->uploadedFiles = [];
        $this->dispatch('close-modal', 'upload-screenshot-modal');

        if ($count > 0) {
            $this->dispatch('toast', [
                'type' => 'success',
                'title' => 'Selesai Diproses',
                'message' => "{$count} screenshot berhasil diekstraksi dan siap diperiksa di antrian.",
            ]);
        } elseif ($duplicateCount > 0) {
            // Duplicate warning toast already dispatched
        } else {
            $this->dispatch('toast', [
                'type' => 'info',
                'title' => 'Pemberitahuan',
                'message' => 'Tidak ada file baru yang dapat diproses.',
            ]);
        }
    }

    public function openConfirmModal(int $importId): void
    {
        $import = ScreenshotImport::findOrFail($importId);
        $this->selectedImportId = $import->id;

        $raw = $import->raw_result ?? [];

        $this->confirmPlatform = $import->platform ?? ($raw['platform'] ?? 'gofood');
        $this->confirmExternalId = $raw['external_order_id'] ?? '';
        $this->confirmOrderTime = isset($raw['order_time']) ? Carbon::parse($raw['order_time'])->format('Y-m-d\TH:i') : Carbon::now()->format('Y-m-d\TH:i');
        $this->confirmPlatformTotal = (int) ($raw['total'] ?? 0);

        $items = $raw['items'] ?? [];
        $this->confirmItems = [];

        foreach ($items as $item) {
            $this->confirmItems[] = [
                'raw_name' => $item['raw_name'] ?? $item['name'] ?? '',
                'menu_id' => $item['menu_id'] ?? null,
                'menu_name' => $item['menu_name'] ?? '',
                'qty' => (int) ($item['qty'] ?? 1),
                'unit_price' => (int) ($item['unit_price'] ?? 0),
                'is_matched' => (bool) ($item['is_matched'] ?? false),
                'match_score' => (float) ($item['match_score'] ?? 0),
                'save_as_alias' => false,
            ];
        }

        if ($this->confirmPlatformTotal <= 0 && $this->confirmSubtotal > 0) {
            $this->confirmPlatformTotal = $this->confirmSubtotal;
        }

        // Check Layer 3 duplicate: channel + time tolerance 2 min + total
        $this->isDuplicateWarning = false;
        $this->duplicateWarningMessage = null;

        if ($this->confirmExternalId) {
            $existSale = Sale::where('channel', $this->confirmPlatform)
                ->where('external_order_id', $this->confirmExternalId)
                ->first();
            if ($existSale) {
                $this->isDuplicateWarning = true;
                $this->duplicateWarningMessage = "PERINGATAN DUPLIKAT: Nomor pesanan {$this->confirmExternalId} sudah tercatat di transaksi #{$existSale->number}!";
            }
        }

        $this->dispatch('open-modal', 'ai-confirmation-modal');
    }

    public function onConfirmMenuSelect(int $index, int $menuId): void
    {
        $menu = Menu::find($menuId);
        if ($menu && isset($this->confirmItems[$index])) {
            $this->confirmItems[$index]['menu_id'] = $menu->id;
            $this->confirmItems[$index]['menu_name'] = $menu->name;
            $this->confirmItems[$index]['unit_price'] = (int) $menu->price;
            $this->confirmItems[$index]['is_matched'] = true;
            $this->confirmItems[$index]['match_score'] = 1.0;

            if ($this->confirmPlatformTotal <= 0 && $this->confirmSubtotal > 0) {
                $this->confirmPlatformTotal = $this->confirmSubtotal;
            }
        }
    }

    public function getConfirmSubtotalProperty(): int
    {
        $sum = 0;
        foreach ($this->confirmItems as $item) {
            $sum += ($item['qty'] ?? 1) * ($item['unit_price'] ?? 0);
        }

        return $sum;
    }

    public function getConfirmAdjustmentProperty(): int
    {
        return $this->confirmPlatformTotal - $this->confirmSubtotal;
    }

    public function saveConfirmedOrder(SaleService $saleService): void
    {
        if (! $this->selectedImportId) {
            return;
        }

        $import = ScreenshotImport::findOrFail($this->selectedImportId);

        $this->validate([
            'confirmPlatform' => 'required|in:gofood,shopeefood',
            'confirmExternalId' => 'required|string',
            'confirmOrderTime' => 'required',
            'confirmItems' => 'required|array|min:1',
            'confirmPlatformTotal' => 'required|numeric|min:0',
        ], [
            'confirmExternalId.required' => 'Nomor pesanan eksternal wajib diisi.',
            'confirmItems.min' => 'Pilih setidaknya 1 item menu.',
            'confirmPlatformTotal.min' => 'Total pemasukan platform minimal Rp0.',
        ]);

        // Validation: Ensure items are present and have selected menus
        if (empty($this->confirmItems)) {
            $this->dispatch('toast', [
                'type' => 'warning',
                'title' => 'Item Kosong',
                'message' => 'Masukkan setidaknya satu item menu sebelum menyimpan.',
            ]);

            return;
        }

        foreach ($this->confirmItems as $idx => $item) {
            if (empty($item['menu_id'])) {
                $rawItemName = ! empty($item['raw_name']) ? $item['raw_name'] : 'Item '.($idx + 1);
                $this->dispatch('toast', [
                    'type' => 'warning',
                    'title' => 'Pilih Menu Outlet',
                    'message' => "Silakan pilih menu terdaftar untuk item '{$rawItemName}' sebelum menyimpan ke transaksi.",
                ]);

                return;
            }
        }

        // Save aliases if checked
        foreach ($this->confirmItems as $item) {
            if (! empty($item['save_as_alias']) && ! empty($item['menu_id']) && ! empty($item['raw_name'])) {
                $norm = MenuAlias::normalize($item['raw_name']);
                MenuAlias::firstOrCreate(
                    ['normalized_alias' => $norm],
                    ['menu_id' => $item['menu_id'], 'alias' => $item['raw_name']]
                );
            }
        }

        try {
            $saleData = [
                'channel' => $this->confirmPlatform,
                'external_order_id' => $this->confirmExternalId,
                'subtotal' => $this->confirmSubtotal,
                'discount' => 0,
                'platform_adjustment' => $this->confirmAdjustment,
                'total' => $this->confirmPlatformTotal,
                'payment_method' => 'transfer',
                'paid_amount' => $this->confirmPlatformTotal,
                'change_amount' => 0,
                'ordered_at' => Carbon::parse($this->confirmOrderTime),
                'source' => $import->source === 'ai_api' ? Sale::SOURCE_AI_API : Sale::SOURCE_AI_LOCAL,
                'idempotency_key' => (string) Str::uuid(),
                'notes' => 'Diimpor dari screenshot #'.$import->id,
            ];

            $sale = $saleService->createSale($saleData, $this->confirmItems, auth()->user());

            $import->update([
                'status' => ScreenshotImport::STATUS_SAVED,
                'sale_id' => $sale->id,
            ]);

            $this->dispatch('close-modal', 'ai-confirmation-modal');
            $this->dispatch('toast', [
                'type' => 'success',
                'title' => 'Tercatat di Pemasukan',
                'message' => 'Transaksi #'.$sale->number.' (Rp'.number_format($sale->total, 0, ',', '.').') berhasil dicatat ke penjualan & stok dipotong.',
            ]);

        } catch (\Throwable $e) {
            $this->dispatch('toast', [
                'type' => 'error',
                'title' => 'Gagal Menyimpan',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        $query = ScreenshotImport::with(['sale', 'uploader'])->latest();

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $imports = $query->paginate(10);
        $menus = Menu::where('is_active', true)->orderBy('name')->get();

        $selectedImport = $this->selectedImportId ? ScreenshotImport::find($this->selectedImportId) : null;

        return view('livewire.online-orders.index', [
            'imports' => $imports,
            'menus' => $menus,
            'selectedImport' => $selectedImport,
        ])->layout('layouts.app', ['title' => 'Pesanan Online']);
    }
}
