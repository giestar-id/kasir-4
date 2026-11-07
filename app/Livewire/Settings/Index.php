<?php

namespace App\Livewire\Settings;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\Ai\ExtractionPipeline;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Index extends Component
{
    use WithFileUploads;

    public string $activeTab = 'profile'; // profile, security, ai, backup

    // Profile Settings
    public string $storeName = '';

    public string $storeAddress = '';

    public string $storePhone = '';

    public string $receiptFooter = '';

    public string $gofoodCommission = '20';

    public string $shopeefoodCommission = '20';

    // Security Settings
    public string $oldPassword = '';

    public string $newPassword = '';

    public string $confirmPassword = '';

    // Cashier Management (M18)
    public ?int $editingCashierId = null;

    public string $cashierName = '';

    public string $cashierEmail = '';

    public string $cashierPassword = '';

    public bool $cashierCanDiscount = false;

    public bool $cashierIsActive = true;

    // AI Settings
    public bool $aiEnabled = true;

    public string $aiProvider = 'gemini';

    public string $aiCustomUrl = '';

    public string $aiApiKey = '';

    public string $aiModel = '';

    public string $aiBudgetLimit = '500000';

    public string $confidenceThreshold = '85';

    public string $screenshotRetention = '30';

    public bool $autoSaveAi = false;

    // AI Test States
    public ?string $testConnectionStatus = null; // null, testing, success, failed

    public ?string $testConnectionMessage = null;

    // AI Sample Test Modal (M19)
    public $sampleTestImage;

    public ?array $sampleTestResult = null;

    public function boot(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403, 'Akses khusus Pemilik outlet.');
    }

    public function mount(): void
    {
        // Load Profile
        $this->storeName = Setting::get('store_name', 'POS Kuliner Satu Outlet');
        $this->storeAddress = Setting::get('store_address', '');
        $this->storePhone = Setting::get('store_phone', '');
        $this->receiptFooter = Setting::get('receipt_footer', '');
        $this->gofoodCommission = Setting::get('gofood_commission_pct', '20');
        $this->shopeefoodCommission = Setting::get('shopeefood_commission_pct', '20');

        // Load AI
        $this->aiEnabled = filter_var(Setting::get('ai_enabled', '1'), FILTER_VALIDATE_BOOLEAN);
        $this->aiProvider = Setting::get('ai_provider', 'gemini');
        $this->aiCustomUrl = Setting::get('ai_custom_url', '');
        $this->aiModel = Setting::get('ai_model', 'gemini-1.5-flash');
        $this->aiBudgetLimit = Setting::get('ai_budget_limit', '500000');
        $this->confidenceThreshold = Setting::get('confidence_threshold', '85');
        $this->screenshotRetention = Setting::get('screenshot_retention_days', '30');
        $this->autoSaveAi = filter_var(Setting::get('auto_save_ai', '0'), FILTER_VALIDATE_BOOLEAN);

        // Check if API key exists
        $hasKey = ! empty(Setting::get('ai_api_key'));
        if ($hasKey) {
            $this->testConnectionStatus = 'success';
            $this->testConnectionMessage = 'Tersimpan (Terenkripsi)';
        }
    }

    public function saveProfile(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $this->validate([
            'storeName' => 'required|string|max:100',
        ], [
            'storeName.required' => 'Nama toko wajib diisi.',
        ]);

        Setting::set('store_name', $this->storeName);
        Setting::set('store_address', $this->storeAddress);
        Setting::set('store_phone', $this->storePhone);
        Setting::set('receipt_footer', $this->receiptFooter);
        Setting::set('gofood_commission_pct', $this->gofoodCommission);
        Setting::set('shopeefood_commission_pct', $this->shopeefoodCommission);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'update_store_profile',
            'meta' => [
                'store_name' => $this->storeName,
                'store_phone' => $this->storePhone,
            ],
            'ip' => request()->ip(),
        ]);

        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Profil Disimpan',
            'message' => 'Pengaturan profil toko berhasil diperbarui.',
        ]);
    }

    public function changePassword(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $this->validate([
            'oldPassword' => 'required',
            'newPassword' => 'required|string|min:10',
            'confirmPassword' => 'required|same:newPassword',
        ], [
            'oldPassword.required' => 'Password lama wajib diisi.',
            'newPassword.min' => 'Password baru minimal 10 karakter sesuai standar keamanan.',
            'confirmPassword.same' => 'Konfirmasi password tidak cocok.',
        ]);

        $user = auth()->user();

        if (! Hash::check($this->oldPassword, $user->password)) {
            $this->dispatch('toast', [
                'type' => 'error',
                'title' => 'Password Salah',
                'message' => 'Password lama yang Anda masukkan tidak sesuai.',
            ]);

            return;
        }

        $user->password = Hash::make($this->newPassword);
        $user->save();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'change_password',
            'ip' => request()->ip(),
        ]);

        $this->reset(['oldPassword', 'newPassword', 'confirmPassword']);
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Password Diperbarui',
            'message' => 'Password Anda berhasil diubah.',
        ]);
    }

    public function openCreateCashierModal(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $this->reset(['editingCashierId', 'cashierName', 'cashierEmail', 'cashierPassword']);
        $this->cashierCanDiscount = false;
        $this->cashierIsActive = true;
        $this->dispatch('open-modal', 'cashier-form-modal');
        $this->dispatch('open-modal-cashier-form-modal');
    }

    public function openEditCashierModal(int $id): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $cashier = User::where('role', 'cashier')->findOrFail($id);
        $this->editingCashierId = $cashier->id;
        $this->cashierName = $cashier->name;
        $this->cashierEmail = $cashier->email;
        $this->cashierPassword = '';
        $this->cashierCanDiscount = $cashier->can_give_discount;
        $this->cashierIsActive = $cashier->is_active;

        $this->dispatch('open-modal', 'cashier-form-modal');
        $this->dispatch('open-modal-cashier-form-modal');
    }

    public function saveCashier(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        if ($this->editingCashierId) {
            User::where('id', $this->editingCashierId)->where('role', 'cashier')->firstOrFail();
        }

        $rules = [
            'cashierName' => 'required|string|max:100',
            'cashierEmail' => 'required|email|unique:users,email,'.$this->editingCashierId,
        ];

        if (! $this->editingCashierId || ! empty($this->cashierPassword)) {
            $rules['cashierPassword'] = 'required|min:6';
        }

        $this->validate($rules, [
            'cashierName.required' => 'Nama kasir wajib diisi.',
            'cashierEmail.required' => 'Email kasir wajib diisi.',
            'cashierPassword.min' => 'Password kasir minimal 6 karakter.',
        ]);

        $data = [
            'name' => $this->cashierName,
            'email' => $this->cashierEmail,
            'role' => 'cashier',
            'can_give_discount' => $this->cashierCanDiscount,
            'is_active' => $this->cashierIsActive,
        ];

        if (! empty($this->cashierPassword)) {
            $data['password'] = Hash::make($this->cashierPassword);
        }

        $isNew = empty($this->editingCashierId);
        if ($this->editingCashierId) {
            $cashier = User::where('id', $this->editingCashierId)->where('role', 'cashier')->firstOrFail();
            $cashier->update($data);
        } else {
            $cashier = User::create($data);
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $isNew ? 'create_cashier' : 'update_cashier',
            'subject_type' => User::class,
            'subject_id' => $cashier->id,
            'meta' => [
                'name' => $cashier->name,
                'email' => $cashier->email,
                'role' => $cashier->role,
                'can_give_discount' => $cashier->can_give_discount,
                'is_active' => $cashier->is_active,
            ],
            'ip' => request()->ip(),
        ]);

        $this->dispatch('close-modal', 'cashier-form-modal');
        $this->dispatch('close-modal-cashier-form-modal');
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Akun Kasir Tersimpan',
            'message' => 'Akun kasir '.$this->cashierName.' berhasil disimpan.',
        ]);
    }

    public function saveAiSettings(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        Setting::set('ai_enabled', $this->aiEnabled ? '1' : '0');
        Setting::set('ai_provider', $this->aiProvider);
        Setting::set('ai_custom_url', $this->aiCustomUrl);
        Setting::set('ai_model', $this->aiModel);
        Setting::set('ai_budget_limit', $this->aiBudgetLimit);
        Setting::set('confidence_threshold', $this->confidenceThreshold);
        Setting::set('screenshot_retention_days', $this->screenshotRetention);
        Setting::set('auto_save_ai', $this->autoSaveAi ? '1' : '0');

        if (! empty(trim($this->aiApiKey))) {
            Setting::set('ai_api_key', trim($this->aiApiKey), encrypt: true);
            $this->aiApiKey = '';
            $this->testConnectionStatus = 'success';
            $this->testConnectionMessage = 'Key Terenkripsi Tersimpan';
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'update_ai_settings',
            'meta' => [
                'ai_enabled' => $this->aiEnabled,
                'ai_provider' => $this->aiProvider,
                'ai_model' => $this->aiModel,
            ],
            'ip' => request()->ip(),
        ]);

        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Pengaturan AI Disimpan',
            'message' => 'Konfigurasi integrasi AI hybrid berhasil disimpan.',
        ]);
    }

    public function testAiConnection(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $apiKey = Setting::get('ai_api_key');

        if (empty($apiKey)) {
            $this->testConnectionStatus = 'failed';
            $this->testConnectionMessage = 'API key belum diatur.';
            $this->dispatch('toast', [
                'type' => 'warning',
                'title' => 'Key Kosong',
                'message' => 'Masukkan dan simpan API key terlebih dahulu.',
            ]);

            return;
        }

        $startTime = microtime(true);

        try {
            // Lightweight test request
            if ($this->aiProvider === 'gemini') {
                $url = 'https://generativelanguage.googleapis.com/v1beta/models';
                $res = Http::withHeaders(['x-goog-api-key' => $apiKey])->timeout(8)->get($url);
                if (! $res->successful()) {
                    throw new \RuntimeException($res->json('error.message') ?? 'HTTP '.$res->status());
                }
            } elseif ($this->aiProvider === 'anthropic') {
                $url = 'https://api.anthropic.com/v1/messages';
                $res = Http::withHeaders(['x-api-key' => $apiKey, 'anthropic-version' => '2023-06-01'])
                    ->timeout(8)
                    ->post($url, [
                        'model' => $this->aiModel ?: 'claude-3-5-haiku-20241022',
                        'max_tokens' => 10,
                        'messages' => [['role' => 'user', 'content' => 'Ping']],
                    ]);
                if (! $res->successful()) {
                    throw new \RuntimeException($res->json('error.message') ?? 'HTTP '.$res->status());
                }
            } else {
                $endpoint = $this->aiCustomUrl ?: 'https://api.openai.com/v1';
                $res = Http::withToken($apiKey)->timeout(8)->get(rtrim($endpoint, '/').'/models');
                if (! $res->successful()) {
                    throw new \RuntimeException($res->json('error.message') ?? 'HTTP '.$res->status());
                }
            }

            $latency = (int) round((microtime(true) - $startTime) * 1000);
            $this->testConnectionStatus = 'success';
            $this->testConnectionMessage = "Terhubung ({$latency}ms) - Model: {$this->aiModel}";

            $this->dispatch('toast', [
                'type' => 'success',
                'title' => 'Tes Berhasil',
                'message' => 'Koneksi ke penyedia AI berhasil diverifikasi.',
            ]);

        } catch (\Throwable $e) {
            $this->testConnectionStatus = 'failed';
            $this->testConnectionMessage = 'Gagal: '.substr($e->getMessage(), 0, 80);

            $this->dispatch('toast', [
                'type' => 'error',
                'title' => 'Tes Gagal',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function deleteApiKey(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        Setting::set('ai_api_key', null);
        $this->testConnectionStatus = null;
        $this->testConnectionMessage = null;

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'delete_ai_api_key',
            'ip' => request()?->ip(),
        ]);

        $this->dispatch('close-modal', 'delete-key-modal');
        $this->dispatch('close-modal-delete-key-modal');
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'API Key Dihapus',
            'message' => 'API Key berhasil dihapus dari penyimpanan aman.',
        ]);
    }

    public function runSampleTest(ExtractionPipeline $pipeline): void
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $this->validate([
            'sampleTestImage' => 'required|image|mimes:jpeg,png,jpg,webp|mimetypes:image/jpeg,image/png,image/webp|max:8192',
        ], [
            'sampleTestImage.required' => 'Berkas gambar contoh wajib diunggah.',
            'sampleTestImage.image' => 'Berkas harus berupa gambar.',
            'sampleTestImage.mimes' => 'Format gambar harus JPG, JPEG, PNG, atau WEBP.',
            'sampleTestImage.mimetypes' => 'Mime-type berkas harus berupa image/jpeg, image/png, atau image/webp.',
            'sampleTestImage.max' => 'Ukuran berkas maksimal 8 MB.',
        ]);

        $path = $this->sampleTestImage->getRealPath();

        try {
            $startTime = microtime(true);
            $result = $pipeline->process($path);
            $latency = (int) round((microtime(true) - $startTime) * 1000);

            $this->sampleTestResult = [
                'platform' => $result->platform,
                'external_order_id' => $result->externalOrderId,
                'order_time' => $result->orderTime,
                'items' => $result->items,
                'total' => $result->total,
                'source' => $result->source,
                'provider' => $result->provider,
                'confidence' => $result->confidence,
                'latency' => $latency,
            ];

            $this->dispatch('toast', [
                'type' => 'success',
                'title' => 'Uji Coba Berhasil',
                'message' => 'Hasil ekstraksi contoh struk berhasil ditampilkan.',
            ]);

        } catch (\Throwable $e) {
            $this->dispatch('toast', [
                'type' => 'error',
                'title' => 'Gagal Uji Coba',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function exportDatabase(): StreamedResponse
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $filename = 'backup_pos_'.date('Ymd_His').'.sql';

        return response()->streamDownload(function () {
            $tables = ['users', 'settings', 'categories', 'menus', 'menu_aliases', 'menu_extra_costs', 'ingredients', 'recipes', 'purchases', 'stock_movements', 'sales', 'sale_items', 'screenshot_imports', 'ai_call_logs', 'audit_logs'];

            echo "-- DUMP DATABASE POS KULINER SATU OUTLET\n";
            echo '-- GENERATED AT: '.Carbon::now()->toIso8601String()."\n\n";

            foreach ($tables as $table) {
                $rows = DB::table($table)->get();
                echo "-- Table: {$table} (".count($rows)." rows)\n";
                foreach ($rows as $row) {
                    $cols = array_keys((array) $row);
                    $vals = array_map(function ($val) {
                        return is_null($val) ? 'NULL' : "'".addslashes((string) $val)."'";
                    }, (array) $row);

                    echo "INSERT INTO `{$table}` (`".implode('`, `', $cols).'`) VALUES ('.implode(', ', $vals).");\n";
                }
                echo "\n";
            }
        }, $filename, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function render()
    {
        $cashiers = User::where('role', 'cashier')->latest()->get();
        $hasSavedKey = ! empty(Setting::get('ai_api_key'));

        return view('livewire.settings.index', [
            'cashiers' => $cashiers,
            'hasSavedKey' => $hasSavedKey,
        ])->layout('layouts.app', ['title' => 'Pengaturan Outlet']);
    }
}
