<?php

namespace Tests\Feature;

use App\Livewire\Employees\Index as EmployeesIndex;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create([
            'name' => 'Owner Store',
            'email' => 'owner@pos.test',
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->cashier = User::factory()->create([
            'name' => 'Kasir Satu',
            'email' => 'kasir1@pos.test',
            'role' => 'cashier',
            'is_active' => true,
            'can_give_discount' => false,
        ]);
    }

    /**
     * Test 1: Owner dapat melihat halaman kelola pekerja (/pekerja).
     */
    public function test_1_owner_can_view_employee_management_page(): void
    {
        $this->actingAs($this->owner)
            ->get('/pekerja')
            ->assertOk()
            ->assertSee('Kelola Pekerja')
            ->assertSee($this->cashier->name);

        $this->actingAs($this->owner)
            ->get(route('employees'))
            ->assertOk();

        Livewire::actingAs($this->owner)
            ->test(EmployeesIndex::class)
            ->assertOk()
            ->assertSee('Kelola Pekerja')
            ->assertSee($this->cashier->name);
    }

    /**
     * Test 2: Kasir dilarang mengakses /pekerja (HTTP 403 Forbidden).
     */
    public function test_2_cashier_forbidden_from_employee_management_page(): void
    {
        $this->actingAs($this->cashier)
            ->get('/pekerja')
            ->assertForbidden();

        $this->actingAs($this->cashier)
            ->get(route('employees'))
            ->assertForbidden();

        Livewire::actingAs($this->cashier)
            ->test(EmployeesIndex::class)
            ->assertForbidden();
    }

    /**
     * Test 3: Kasir dilarang mengakses /pengaturan (HTTP 403 Forbidden).
     */
    public function test_3_cashier_forbidden_from_settings_page(): void
    {
        $this->actingAs($this->cashier)
            ->get('/pengaturan')
            ->assertForbidden();

        $this->actingAs($this->cashier)
            ->get(route('settings'))
            ->assertForbidden();
    }

    /**
     * Test 4: Kasir dilarang mengakses /dashboard, /menu, /kategori, /pembelian, /stok-opname, /laporan (HTTP 403 Forbidden).
     */
    public function test_4_cashier_forbidden_from_other_owner_restricted_pages(): void
    {
        $this->actingAs($this->cashier);

        $this->get('/')->assertForbidden();
        $this->get('/dashboard')->assertForbidden();
        $this->get('/menu')->assertForbidden();
        $this->get('/kategori')->assertForbidden();
        $this->get('/pembelian')->assertForbidden();
        $this->get('/stok-opname')->assertForbidden();
        $this->get('/laporan')->assertForbidden();

        $this->get(route('dashboard'))->assertForbidden();
        $this->get(route('menu'))->assertForbidden();
        $this->get(route('categories'))->assertForbidden();
        $this->get(route('purchases'))->assertForbidden();
        $this->get(route('stock-opname'))->assertForbidden();
        $this->get(route('reports'))->assertForbidden();
    }

    /**
     * Test 5: Owner dapat membuat akun pekerja baru dengan validasi email unik dan password.
     */
    public function test_5_owner_can_create_new_employee_with_validation(): void
    {
        $this->actingAs($this->owner);

        // 1. Sukses membuat pekerja baru
        Livewire::test(EmployeesIndex::class)
            ->call('openCreateModal')
            ->set('name', 'Budi Kasir')
            ->set('email', 'budi.kasir@pos.test')
            ->set('password', 'secret123')
            ->set('role', 'cashier')
            ->set('can_give_discount', true)
            ->set('is_active', true)
            ->call('saveEmployee')
            ->assertDispatched('close-modal', 'employee-form-modal')
            ->assertDispatched('toast');

        $newUser = User::where('email', 'budi.kasir@pos.test')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('Budi Kasir', $newUser->name);
        $this->assertEquals('cashier', $newUser->role);
        $this->assertTrue($newUser->can_give_discount);
        $this->assertTrue($newUser->is_active);
        $this->assertTrue(Hash::check('secret123', $newUser->password));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->owner->id,
            'action' => 'create_employee',
            'subject_type' => User::class,
            'subject_id' => $newUser->id,
        ]);

        // 2. Validasi gagal jika email duplikat atau password terlalu pendek
        Livewire::test(EmployeesIndex::class)
            ->set('name', 'Duplikat')
            ->set('email', 'budi.kasir@pos.test')
            ->set('password', '123')
            ->set('role', 'cashier')
            ->call('saveEmployee')
            ->assertHasErrors(['email', 'password']);
    }

    /**
     * Test 6: Owner dapat mengubah data pekerja dan toggle hak diskon.
     */
    public function test_6_owner_can_update_employee_and_toggle_discount_permission(): void
    {
        $this->actingAs($this->owner);

        $initialPassword = $this->cashier->password;

        Livewire::test(EmployeesIndex::class)
            ->call('openEditModal', $this->cashier->id)
            ->assertSet('editingEmployeeId', $this->cashier->id)
            ->set('name', 'Kasir Satu Updated')
            ->set('can_give_discount', true)
            ->set('password', '') // keep password unchanged
            ->call('saveEmployee')
            ->assertDispatched('close-modal', 'employee-form-modal')
            ->assertDispatched('toast');

        $this->cashier->refresh();
        $this->assertEquals('Kasir Satu Updated', $this->cashier->name);
        $this->assertTrue($this->cashier->can_give_discount);
        $this->assertEquals($initialPassword, $this->cashier->password);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->owner->id,
            'action' => 'update_employee',
            'subject_type' => User::class,
            'subject_id' => $this->cashier->id,
        ]);
    }

    /**
     * Test 7: Owner dapat mengubah/mereset password pekerja.
     */
    public function test_7_owner_can_change_or_reset_employee_password(): void
    {
        $this->actingAs($this->owner);

        // Validasi gagal jika konfirmasi tidak cocok
        Livewire::test(EmployeesIndex::class)
            ->call('openPasswordModal', $this->cashier->id)
            ->set('newEmployeePassword', 'newsecret123')
            ->set('confirmEmployeePassword', 'wrongconfirmation')
            ->call('changeEmployeePassword')
            ->assertHasErrors(['confirmEmployeePassword']);

        // Sukses ganti password
        Livewire::test(EmployeesIndex::class)
            ->call('openPasswordModal', $this->cashier->id)
            ->set('newEmployeePassword', 'newsecret123')
            ->set('confirmEmployeePassword', 'newsecret123')
            ->call('changeEmployeePassword')
            ->assertDispatched('close-modal', 'employee-password-modal')
            ->assertDispatched('toast');

        $this->cashier->refresh();
        $this->assertTrue(Hash::check('newsecret123', $this->cashier->password));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->owner->id,
            'action' => 'change_employee_password',
            'subject_type' => User::class,
            'subject_id' => $this->cashier->id,
        ]);
    }

    /**
     * Test 8: Owner dapat menonaktifkan status akun pekerja.
     */
    public function test_8_owner_can_toggle_employee_active_status(): void
    {
        $this->actingAs($this->owner);

        $this->assertTrue($this->cashier->is_active);

        // Nonaktifkan
        Livewire::test(EmployeesIndex::class)
            ->call('toggleStatus', $this->cashier->id)
            ->assertDispatched('toast');

        $this->cashier->refresh();
        $this->assertFalse($this->cashier->is_active);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->owner->id,
            'action' => 'toggle_employee_status',
            'subject_type' => User::class,
            'subject_id' => $this->cashier->id,
        ]);

        // Aktifkan kembali
        Livewire::test(EmployeesIndex::class)
            ->call('toggleStatus', $this->cashier->id)
            ->assertDispatched('toast');

        $this->cashier->refresh();
        $this->assertTrue($this->cashier->is_active);
    }

    /**
     * Test 9: Owner dicegah menonaktifkan atau menghapus akunnya sendiri (self-deletion guard).
     */
    public function test_9_owner_prevented_from_deactivating_or_deleting_own_account(): void
    {
        $this->actingAs($this->owner);

        // Mencegah deaktifasi diri sendiri via toggleStatus
        Livewire::test(EmployeesIndex::class)
            ->call('toggleStatus', $this->owner->id)
            ->assertDispatched('toast');

        $this->owner->refresh();
        $this->assertTrue($this->owner->is_active);

        // Mencegah hapus akun diri sendiri via deleteEmployee
        Livewire::test(EmployeesIndex::class)
            ->call('deleteEmployee', $this->owner->id)
            ->assertStatus(400);

        $this->assertDatabaseHas('users', ['id' => $this->owner->id]);
    }

    /**
     * Test 10: Pekerja yang memiliki riwayat transaksi tidak dihapus permanen melainkan dinonaktifkan.
     */
    public function test_10_employee_with_sales_history_is_deactivated_instead_of_permanently_deleted(): void
    {
        $this->actingAs($this->owner);

        // 1. Kasir dengan riwayat transaksi
        $activeCashier = User::factory()->create([
            'role' => 'cashier',
            'is_active' => true,
        ]);

        Sale::create([
            'number' => 'POS-20261106-0001',
            'channel' => Sale::CHANNEL_OFFLINE,
            'status' => Sale::STATUS_COMPLETED,
            'subtotal' => 50000,
            'discount' => 0,
            'total' => 50000,
            'payment_method' => 'cash',
            'paid_amount' => 50000,
            'change_amount' => 0,
            'ordered_at' => now(),
            'source' => Sale::SOURCE_MANUAL,
            'idempotency_key' => 'TEST-POS-IDEM-001',
            'user_id' => $activeCashier->id,
        ]);

        Livewire::test(EmployeesIndex::class)
            ->call('deleteEmployee', $activeCashier->id)
            ->assertDispatched('toast');

        // User tetap ada di database, tetapi status is_active = false
        $this->assertDatabaseHas('users', [
            'id' => $activeCashier->id,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->owner->id,
            'action' => 'deactivate_employee_with_transactions',
            'subject_type' => User::class,
            'subject_id' => $activeCashier->id,
        ]);

        // 2. Kasir tanpa riwayat transaksi dihapus permanen
        $freshCashier = User::factory()->create([
            'role' => 'cashier',
            'is_active' => true,
        ]);

        Livewire::test(EmployeesIndex::class)
            ->call('deleteEmployee', $freshCashier->id)
            ->assertDispatched('toast');

        $this->assertDatabaseMissing('users', ['id' => $freshCashier->id]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->owner->id,
            'action' => 'delete_employee',
            'subject_type' => User::class,
            'subject_id' => $freshCashier->id,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('employees'))
            ->assertRedirect(route('login'));
    }

    public function test_search_and_filters_work(): void
    {
        $inactiveCashier = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi@pos.test',
            'role' => 'cashier',
            'is_active' => false,
            'can_give_discount' => true,
        ]);

        $this->actingAs($this->owner);

        // Search test
        Livewire::test(EmployeesIndex::class)
            ->set('search', 'Budi')
            ->assertSee('Budi Santoso')
            ->assertDontSee($this->cashier->name);

        // Status filter: active
        Livewire::test(EmployeesIndex::class)
            ->set('statusFilter', 'active')
            ->assertSee($this->cashier->name)
            ->assertDontSee('Budi Santoso');

        // Status filter: inactive
        Livewire::test(EmployeesIndex::class)
            ->set('statusFilter', 'inactive')
            ->assertSee('Budi Santoso')
            ->assertDontSee($this->cashier->name);

        // Discount filter: allowed
        Livewire::test(EmployeesIndex::class)
            ->set('discountFilter', 'allowed')
            ->assertSee('Budi Santoso')
            ->assertDontSee($this->cashier->name);

        // Discount filter: not_allowed
        Livewire::test(EmployeesIndex::class)
            ->set('discountFilter', 'not_allowed')
            ->assertSee($this->cashier->name)
            ->assertDontSee('Budi Santoso');
    }

    public function test_cashier_cannot_call_mutation_methods(): void
    {
        $this->actingAs($this->cashier);

        // 1. Livewire boot aborts with 403
        Livewire::test(EmployeesIndex::class)
            ->assertForbidden();

        // 2. Direct component method calls enforce 403 abort
        $component = new EmployeesIndex;

        try {
            $component->saveEmployee();
            $this->fail('Expected 403 HttpException was not thrown for saveEmployee.');
        } catch (HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }

        try {
            $component->changeEmployeePassword();
            $this->fail('Expected 403 HttpException was not thrown for changeEmployeePassword.');
        } catch (HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }

        try {
            $component->toggleStatus($this->cashier->id);
            $this->fail('Expected 403 HttpException was not thrown for toggleStatus.');
        } catch (HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }

        try {
            $component->deleteEmployee($this->cashier->id);
            $this->fail('Expected 403 HttpException was not thrown for deleteEmployee.');
        } catch (HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }
    }

    public function test_alias_methods_work_correctly(): void
    {
        $this->actingAs($this->owner);

        $testCashier = User::factory()->create([
            'role' => 'cashier',
            'is_active' => true,
        ]);

        // Test save() alias
        Livewire::test(EmployeesIndex::class)
            ->set('name', 'Alias Test')
            ->set('email', 'alias@pos.test')
            ->set('password', 'secret123')
            ->set('role', 'cashier')
            ->call('save')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('users', ['email' => 'alias@pos.test']);

        // Test changePassword() alias
        Livewire::test(EmployeesIndex::class)
            ->set('passwordEmployeeId', $testCashier->id)
            ->set('newEmployeePassword', 'newpassword999')
            ->set('confirmEmployeePassword', 'newpassword999')
            ->call('changePassword')
            ->assertDispatched('toast');

        $testCashier->refresh();
        $this->assertTrue(Hash::check('newpassword999', $testCashier->password));

        // Test delete() alias
        Livewire::test(EmployeesIndex::class)
            ->call('delete', $testCashier->id)
            ->assertDispatched('toast');

        $this->assertDatabaseMissing('users', ['id' => $testCashier->id]);
    }
}
