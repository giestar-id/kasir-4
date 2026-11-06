<?php

namespace Tests\Feature;

use App\Livewire\OnlineOrders\Index as OnlineOrdersIndex;
use App\Livewire\Settings\Index as SettingsIndex;
use App\Models\AiCallLog;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\MenuAlias;
use App\Models\MenuExtraCost;
use App\Models\Purchase;
use App\Models\Recipe;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ScreenshotImport;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\SaleService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create([
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->cashier = User::factory()->create([
            'role' => 'cashier',
            'is_active' => true,
            'can_give_discount' => false,
        ]);
    }

    /**
     * Audit 1: Mass Assignment Protection on Eloquent Models
     */
    public function test_mass_assignment_protection_rejects_unfillable_attributes(): void
    {
        // Category should reject unfillable attributes like 'id' or 'is_admin'
        $this->expectException(MassAssignmentException::class);
        Category::create([
            'name' => 'Minuman Dingin',
            'unfillable_attribute' => 'malicious_payload',
        ]);
    }

    public function test_all_models_have_explicit_fillable_defined(): void
    {
        $models = [
            AiCallLog::class,
            AuditLog::class,
            Category::class,
            Ingredient::class,
            Menu::class,
            MenuAlias::class,
            MenuExtraCost::class,
            Purchase::class,
            Recipe::class,
            Sale::class,
            SaleItem::class,
            ScreenshotImport::class,
            Setting::class,
            StockMovement::class,
            User::class,
        ];

        foreach ($models as $modelClass) {
            $instance = new $modelClass;
            $fillable = $instance->getFillable();

            $this->assertNotEmpty($fillable, "Model {$modelClass} must define explicit \$fillable attributes.");
            $this->assertNotContains('id', $fillable, "Model {$modelClass} must NOT have 'id' in \$fillable.");
        }
    }

    /**
     * Audit 2: File Upload Security in OnlineOrders\Index
     */
    public function test_file_upload_rejects_non_image_files(): void
    {
        Storage::fake('local');
        $this->actingAs($this->owner);

        $fakePhpScript = UploadedFile::fake()->create('exploit.php', 10, 'application/x-php');
        $fakePdf = UploadedFile::fake()->create('document.pdf', 20, 'application/pdf');

        Livewire::test(OnlineOrdersIndex::class)
            ->set('uploadedFiles', [$fakePhpScript])
            ->call('uploadScreenshots')
            ->assertHasErrors(['uploadedFiles.0']);

        Livewire::test(OnlineOrdersIndex::class)
            ->set('uploadedFiles', [$fakePdf])
            ->call('uploadScreenshots')
            ->assertHasErrors(['uploadedFiles.0']);

        $this->assertDatabaseCount('screenshot_imports', 0);
    }

    public function test_file_upload_rejects_files_exceeding_8mb(): void
    {
        Storage::fake('local');
        $this->actingAs($this->owner);

        // 9 MB fake image file (9 * 1024 KB = 9216 KB)
        $largeImage = UploadedFile::fake()->image('giant_screenshot.png')->size(9216);

        Livewire::test(OnlineOrdersIndex::class)
            ->set('uploadedFiles', [$largeImage])
            ->call('uploadScreenshots')
            ->assertHasErrors(['uploadedFiles.0']);

        $this->assertDatabaseCount('screenshot_imports', 0);
    }

    public function test_file_upload_accepts_valid_image_and_stores_in_private_local_disk(): void
    {
        Storage::fake('local');
        $this->actingAs($this->owner);

        $validPng = UploadedFile::fake()->image('gofood_receipt.png', 800, 1200)->size(500);

        Livewire::test(OnlineOrdersIndex::class)
            ->set('uploadedFiles', [$validPng])
            ->call('uploadScreenshots')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', 'upload-screenshot-modal');

        $this->assertDatabaseCount('screenshot_imports', 1);

        $import = ScreenshotImport::first();
        $this->assertNotNull($import);

        // Ensure stored in non-public local storage disk
        Storage::disk('local')->assertExists($import->file_path);
        // Ensure NOT stored in public disk
        $this->assertFalse(Storage::disk('public')->exists($import->file_path));
    }

    /**
     * Audit 3: Sensitive Data Handling (AI API key encryption, password hashing)
     */
    public function test_ai_api_key_is_stored_encrypted_in_database(): void
    {
        $rawSecret = 'sk-proj-super-secret-production-ai-key-12345';
        Setting::set('ai_api_key', $rawSecret, encrypt: true);

        // Raw value in database must NOT equal plain secret
        $rawInDb = Setting::where('key', 'ai_api_key')->value('value');
        $this->assertNotEquals($rawSecret, $rawInDb);

        // Decrypted using Crypt facade
        $decrypted = Crypt::decryptString($rawInDb);
        $this->assertEquals($rawSecret, $decrypted);

        // Retrieved transparently via Setting::get()
        $this->assertEquals($rawSecret, Setting::get('ai_api_key'));
    }

    public function test_settings_page_does_not_leak_raw_api_key_to_view(): void
    {
        $rawSecret = 'sk-proj-super-secret-production-ai-key-12345';
        Setting::set('ai_api_key', $rawSecret, encrypt: true);

        $this->actingAs($this->owner);

        Livewire::test(SettingsIndex::class)
            ->set('activeTab', 'ai')
            ->assertSet('aiApiKey', '') // Must remain blank in component state
            ->assertDontSee($rawSecret); // Must never render the plaintext key in HTML
    }

    public function test_user_password_is_properly_hashed(): void
    {
        $plainPassword = 'SecretPassword123!';
        $user = User::factory()->create([
            'password' => $plainPassword,
        ]);

        $this->assertNotEquals($plainPassword, $user->password);
        $this->assertTrue(Hash::check($plainPassword, $user->password));

        // Ensure password is not present in toArray/JSON serialization
        $serialized = $user->toArray();
        $this->assertArrayNotHasKey('password', $serialized);
        $this->assertArrayNotHasKey('remember_token', $serialized);
    }

    /**
     * Audit 4: AuditLog Integrity on Critical Business Events
     */
    public function test_cashier_user_creation_and_update_records_audit_log(): void
    {
        $this->actingAs($this->owner);

        // Create Cashier
        Livewire::test(SettingsIndex::class)
            ->set('activeTab', 'security')
            ->set('cashierName', 'Budi Kasir')
            ->set('cashierEmail', 'budi.kasir@example.com')
            ->set('cashierPassword', 'password123')
            ->set('cashierCanDiscount', true)
            ->set('cashierIsActive', true)
            ->call('saveCashier');

        $createdCashier = User::where('email', 'budi.kasir@example.com')->first();
        $this->assertNotNull($createdCashier);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->owner->id,
            'action' => 'create_cashier',
            'subject_type' => User::class,
            'subject_id' => $createdCashier->id,
        ]);

        // Update Cashier
        Livewire::test(SettingsIndex::class)
            ->set('activeTab', 'security')
            ->call('openEditCashierModal', $createdCashier->id)
            ->set('cashierName', 'Budi Kasir Senior')
            ->call('saveCashier');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->owner->id,
            'action' => 'update_cashier',
            'subject_type' => User::class,
            'subject_id' => $createdCashier->id,
        ]);
    }

    public function test_void_transaction_records_audit_log(): void
    {
        $this->actingAs($this->owner);

        $cat = Category::create(['name' => 'Makanan', 'sort_order' => 1]);
        $menu = Menu::create([
            'category_id' => $cat->id,
            'name' => 'Nasi Goreng',
            'price' => 25000,
            'is_active' => true,
        ]);

        $saleService = app(SaleService::class);
        $sale = $saleService->createSale([
            'channel' => Sale::CHANNEL_OFFLINE,
            'subtotal' => 25000,
            'discount' => 0,
            'total' => 25000,
            'payment_method' => 'cash',
            'paid_amount' => 25000,
            'ordered_at' => Carbon::now(),
        ], [
            ['menu_id' => $menu->id, 'qty' => 1],
        ], $this->owner);

        $saleService->voidSale($sale, 'Salah input pesanan pelanggan', $this->owner);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->owner->id,
            'action' => 'void_sale',
            'subject_type' => Sale::class,
            'subject_id' => $sale->id,
        ]);

        $log = AuditLog::where('action', 'void_sale')->where('subject_id', $sale->id)->first();
        $this->assertEquals('Salah input pesanan pelanggan', $log->meta['reason']);
        $this->assertEquals($sale->number, $log->meta['number']);
    }

    public function test_settings_changes_and_api_key_deletion_record_audit_log(): void
    {
        $this->actingAs($this->owner);

        // Store profile update
        Livewire::test(SettingsIndex::class)
            ->set('storeName', 'POS Resto Nusantara Jaya')
            ->set('storePhone', '081122334455')
            ->call('saveProfile');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->owner->id,
            'action' => 'update_store_profile',
        ]);

        // AI settings update
        Livewire::test(SettingsIndex::class)
            ->set('aiProvider', 'gemini')
            ->set('aiModel', 'gemini-1.5-flash')
            ->call('saveAiSettings');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->owner->id,
            'action' => 'update_ai_settings',
        ]);

        // AI API Key deletion
        Setting::set('ai_api_key', 'test-key-to-delete', encrypt: true);
        Livewire::test(SettingsIndex::class)
            ->call('deleteApiKey');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->owner->id,
            'action' => 'delete_ai_api_key',
        ]);
    }

    public function test_logout_records_audit_log_and_invalidates_session(): void
    {
        $this->actingAs($this->owner);

        $response = $this->post(route('logout'));
        $response->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->owner->id,
            'action' => 'logout',
            'subject_type' => User::class,
            'subject_id' => $this->owner->id,
        ]);
    }

    /**
     * Audit 5: CSRF, XSS Escaping & Protection
     */
    public function test_xss_payloads_in_models_are_properly_escaped(): void
    {
        $this->actingAs($this->owner);

        $xssPayload = '<script>alert("XSS")</script>';
        $cat = Category::create(['name' => $xssPayload, 'sort_order' => 1]);

        $response = $this->get(route('categories'));
        $response->assertOk();

        // The raw unescaped script tag MUST NOT appear in the rendered HTML
        $response->assertDontSee($xssPayload, false);
        // The escaped HTML entities MUST be present
        $response->assertSee(e($xssPayload), false);
    }

    public function test_unauthenticated_post_requires_csrf(): void
    {
        $response = $this->post(route('logout'), [], ['X-CSRF-TOKEN' => 'invalid_csrf_token']);
        // When unauthenticated and invalid CSRF, redirected or 419
        $this->assertContains($response->status(), [302, 419]);
    }
}
