<?php

namespace App\Livewire\Employees;

use App\Models\AuditLog;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all'; // all, active, inactive

    public string $discountFilter = 'all'; // all, allowed, not_allowed

    public function resetFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->discountFilter = 'all';
        $this->resetPage();
    }

    // Form Tambah / Edit Pekerja
    public ?int $editingEmployeeId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'cashier';

    public bool $can_give_discount = false;

    public bool $is_active = true;

    // Form Ubah Password
    public ?int $passwordEmployeeId = null;

    public string $passwordEmployeeName = '';

    public string $newEmployeePassword = '';

    public string $confirmEmployeePassword = '';

    // Modal Konfirmasi Hapus
    public ?int $deletingEmployeeId = null;

    public string $deletingEmployeeName = '';

    public bool $deletingEmployeeHasSales = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403, 'Akses khusus Pemilik outlet.');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDiscountFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403, 'Akses khusus Pemilik outlet.');

        $this->reset([
            'editingEmployeeId',
            'name',
            'email',
            'password',
        ]);
        $this->role = 'cashier';
        $this->can_give_discount = false;
        $this->is_active = true;
        $this->resetValidation();
        $this->dispatch('open-modal', 'employee-form-modal');
        $this->dispatch('open-modal-employee-form-modal');
    }

    public function openEditModal(int $id): void
    {
        abort_unless(auth()->user()?->isOwner(), 403, 'Akses khusus Pemilik outlet.');

        $employee = User::where('role', '!=', 'owner')->findOrFail($id);
        $this->editingEmployeeId = $employee->id;
        $this->name = $employee->name;
        $this->email = $employee->email;
        $this->password = '';
        $this->role = 'cashier';
        $this->can_give_discount = (bool) $employee->can_give_discount;
        $this->is_active = (bool) $employee->is_active;

        $this->resetValidation();
        $this->dispatch('open-modal', 'employee-form-modal');
        $this->dispatch('open-modal-employee-form-modal');
    }

    public function saveEmployee(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403, 'Akses khusus Pemilik outlet.');

        $rules = [
            'name' => 'required|string|max:100',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->editingEmployeeId),
            ],
            'role' => 'required|in:cashier,owner',
            'can_give_discount' => 'boolean',
            'is_active' => 'boolean',
        ];

        if (! $this->editingEmployeeId) {
            $rules['password'] = 'required|string|min:6';
        } else {
            $rules['password'] = 'nullable|string|min:6';
        }

        $this->validate($rules, [
            'name.required' => 'Nama pekerja wajib diisi.',
            'name.max' => 'Nama pekerja maksimal 100 karakter.',
            'email.required' => 'Email pekerja wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan oleh akun lain.',
            'role.required' => 'Role wajib dipilih.',
            'role.in' => 'Role harus cashier atau owner.',
            'password.required' => 'Password wajib diisi untuk akun baru.',
            'password.min' => 'Password minimal 6 karakter.',
        ]);

        $data = [
            'name' => trim($this->name),
            'email' => strtolower(trim($this->email)),
            'role' => 'cashier',
            'can_give_discount' => (bool) $this->can_give_discount,
            'is_active' => (bool) $this->is_active,
        ];

        if (! empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        $isNew = empty($this->editingEmployeeId);
        if ($isNew) {
            $employee = User::create($data);
        } else {
            $employee = User::where('role', '!=', 'owner')->findOrFail($this->editingEmployeeId);
            $employee->update($data);
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $isNew ? 'create_employee' : 'update_employee',
            'subject_type' => User::class,
            'subject_id' => $employee->id,
            'meta' => [
                'name' => $employee->name,
                'email' => $employee->email,
                'role' => $employee->role,
                'can_give_discount' => $employee->can_give_discount,
                'is_active' => $employee->is_active,
            ],
            'ip' => request()?->ip(),
        ]);

        $this->dispatch('close-modal', 'employee-form-modal');
        $this->dispatch('close-modal-employee-form-modal');
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => $isNew ? 'Pekerja Ditambahkan' : 'Pekerja Diperbarui',
            'message' => 'Akun pekerja '.$employee->name.' berhasil disimpan.',
        ]);

        $this->reset([
            'editingEmployeeId',
            'name',
            'email',
            'password',
        ]);
    }

    public function save(): void
    {
        $this->saveEmployee();
    }

    public function openPasswordModal(int $id): void
    {
        abort_unless(auth()->user()?->isOwner(), 403, 'Akses khusus Pemilik outlet.');

        $employee = User::where('role', '!=', 'owner')->findOrFail($id);
        $this->passwordEmployeeId = $employee->id;
        $this->passwordEmployeeName = $employee->name;
        $this->newEmployeePassword = '';
        $this->confirmEmployeePassword = '';

        $this->resetValidation();
        $this->dispatch('open-modal', 'employee-password-modal');
        $this->dispatch('open-modal-employee-password-modal');
    }

    public function changeEmployeePassword(): void
    {
        abort_unless(auth()->user()?->isOwner(), 403, 'Akses khusus Pemilik outlet.');

        $this->validate([
            'newEmployeePassword' => 'required|string|min:6',
            'confirmEmployeePassword' => 'required|same:newEmployeePassword',
        ], [
            'newEmployeePassword.required' => 'Password baru wajib diisi.',
            'newEmployeePassword.min' => 'Password baru minimal 6 karakter.',
            'confirmEmployeePassword.required' => 'Konfirmasi password wajib diisi.',
            'confirmEmployeePassword.same' => 'Konfirmasi password tidak cocok dengan password baru.',
        ]);

        $employee = User::where('role', '!=', 'owner')->findOrFail($this->passwordEmployeeId);
        $employee->update([
            'password' => Hash::make($this->newEmployeePassword),
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'change_employee_password',
            'subject_type' => User::class,
            'subject_id' => $employee->id,
            'meta' => [
                'name' => $employee->name,
                'email' => $employee->email,
            ],
            'ip' => request()?->ip(),
        ]);

        $this->reset([
            'passwordEmployeeId',
            'passwordEmployeeName',
            'newEmployeePassword',
            'confirmEmployeePassword',
        ]);

        $this->dispatch('close-modal', 'employee-password-modal');
        $this->dispatch('close-modal-employee-password-modal');
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Password Diperbarui',
            'message' => 'Password untuk '.$employee->name.' berhasil diubah.',
        ]);
    }

    public function changePassword(): void
    {
        $this->changeEmployeePassword();
    }

    public function toggleStatus(int $id): void
    {
        abort_unless(auth()->user()?->isOwner(), 403, 'Akses khusus Pemilik outlet.');

        $employee = User::findOrFail($id);

        if ($employee->isOwner() || $id === (int) auth()->id()) {
            $this->dispatch('toast', [
                'type' => 'error',
                'title' => 'Tindakan Ditolak',
                'message' => 'Anda tidak dapat menonaktifkan akun Pemilik.',
            ]);

            return;
        }

        $employee->update([
            'is_active' => ! $employee->is_active,
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'toggle_employee_status',
            'subject_type' => User::class,
            'subject_id' => $employee->id,
            'meta' => [
                'name' => $employee->name,
                'email' => $employee->email,
                'is_active' => $employee->is_active,
            ],
            'ip' => request()?->ip(),
        ]);

        $statusText = $employee->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Status Berubah',
            'message' => 'Akun pekerja '.$employee->name.' berhasil '.$statusText.'.',
        ]);
    }

    public function openDeleteModal(int $id): void
    {
        abort_unless(auth()->user()?->isOwner(), 403, 'Akses khusus Pemilik outlet.');

        $employee = User::findOrFail($id);

        if ($employee->isOwner() || $id === (int) auth()->id()) {
            abort(400, 'Anda tidak dapat menghapus akun Pemilik.');
        }

        $this->deletingEmployeeId = $employee->id;
        $this->deletingEmployeeName = $employee->name;
        $this->deletingEmployeeHasSales = Sale::where('user_id', $id)->exists();

        $this->dispatch('open-modal', 'employee-delete-modal');
        $this->dispatch('open-modal-employee-delete-modal');
    }

    public function deleteEmployee(int $id): void
    {
        abort_unless(auth()->user()?->isOwner(), 403, 'Akses khusus Pemilik outlet.');

        $employee = User::findOrFail($id);

        if ($employee->isOwner() || $id === (int) auth()->id()) {
            abort(400, 'Anda tidak dapat menghapus akun Pemilik.');
        }

        $hasSales = Sale::where('user_id', $id)->exists();

        if ($hasSales) {
            $employee->update(['is_active' => false]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'deactivate_employee_with_transactions',
                'subject_type' => User::class,
                'subject_id' => $employee->id,
                'meta' => [
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'reason' => 'has_sales_transactions',
                ],
                'ip' => request()?->ip(),
            ]);

            $this->dispatch('close-modal', 'employee-delete-modal');
            $this->dispatch('close-modal-employee-delete-modal');
            $this->dispatch('toast', [
                'type' => 'warning',
                'title' => 'Akun Dinonaktifkan',
                'message' => 'Pekerja '.$employee->name.' telah memiliki riwayat transaksi penjualan. Demi menjaga integritas data keuangan & audit, akun dinonaktifkan dan tidak dihapus.',
            ]);
        } else {
            $employeeName = $employee->name;
            $employeeEmail = $employee->email;
            $employeeId = $employee->id;

            $employee->delete();

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'delete_employee',
                'subject_type' => User::class,
                'subject_id' => $employeeId,
                'meta' => [
                    'name' => $employeeName,
                    'email' => $employeeEmail,
                ],
                'ip' => request()?->ip(),
            ]);

            $this->dispatch('close-modal', 'employee-delete-modal');
            $this->dispatch('close-modal-employee-delete-modal');
            $this->dispatch('toast', [
                'type' => 'success',
                'title' => 'Pekerja Dihapus',
                'message' => 'Akun pekerja '.$employeeName.' berhasil dihapus secara permanen.',
            ]);
        }

        $this->reset([
            'deletingEmployeeId',
            'deletingEmployeeName',
            'deletingEmployeeHasSales',
        ]);
    }

    public function delete(?int $id = null): void
    {
        $targetId = $id ?? $this->deletingEmployeeId;
        if ($targetId) {
            $this->deleteEmployee($targetId);
        }
    }

    public function render(): View
    {
        $query = User::query()
            ->where('role', '!=', 'owner')
            ->withCount('sales');

        if (! empty(trim($this->search))) {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        if ($this->statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        if ($this->discountFilter === 'allowed') {
            $query->where('can_give_discount', true);
        } elseif ($this->discountFilter === 'not_allowed') {
            $query->where('can_give_discount', false);
        }

        $employees = $query->orderBy('name')->paginate(10);

        // Combined metrics in a single database query for speed
        $metrics = User::where('role', '!=', 'owner')
            ->selectRaw("
                COUNT(*) as total_count,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_count,
                SUM(CASE WHEN role = 'cashier' THEN 1 ELSE 0 END) as cashier_count,
                SUM(CASE WHEN can_give_discount = 1 THEN 1 ELSE 0 END) as discount_count
            ")
            ->first();

        return view('livewire.employees.index', [
            'employees' => $employees,
            'totalCount' => (int) ($metrics->total_count ?? 0),
            'activeCount' => (int) ($metrics->active_count ?? 0),
            'inactiveCount' => (int) ($metrics->inactive_count ?? 0),
            'cashierCount' => (int) ($metrics->cashier_count ?? 0),
            'discountCount' => (int) ($metrics->discount_count ?? 0),
        ])->layout('layouts.app', ['title' => 'Kelola Pekerja']);
    }
}
