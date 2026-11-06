<?php

use App\Livewire\Auth\Login;
use App\Livewire\Categories\Index as CategoriesIndex;
use App\Livewire\Dashboard;
use App\Livewire\Employees\Index as EmployeesIndex;
use App\Livewire\Ingredients\Index as IngredientsIndex;
use App\Livewire\Menu\Index as MenuIndex;
use App\Livewire\OnlineOrders\Index as OnlineOrdersIndex;
use App\Livewire\Pos\Cashier;
use App\Livewire\Purchases\Index as PurchasesIndex;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Livewire\Settings\Index as SettingsIndex;
use App\Livewire\StockOpname\Index as StockOpnameIndex;
use App\Livewire\Transactions\History as TransactionsHistory;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {

    Route::post('/logout', function (Request $request) {
        $user = Auth::user();
        if ($user) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'logout',
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'ip' => $request->ip(),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');

    // Shared Operational Access (Owner & Cashier)
    Route::get('/pos', Cashier::class)->name('pos');
    Route::get('/pesanan-online', OnlineOrdersIndex::class)->name('online-orders');
    Route::get('/riwayat-transaksi', TransactionsHistory::class)->name('transactions');
    Route::get('/bahan-baku', IngredientsIndex::class)->name('ingredients');

    // Owner Only Access
    Route::middleware('role.owner')->group(function () {
        Route::get('/', Dashboard::class);
        Route::get('/dashboard', Dashboard::class)->name('dashboard');
        Route::get('/menu', MenuIndex::class)->name('menu');
        Route::get('/kategori', CategoriesIndex::class)->name('categories');
        Route::get('/pembelian', PurchasesIndex::class)->name('purchases');
        Route::get('/stok-opname', StockOpnameIndex::class)->name('stock-opname');
        Route::get('/laporan', ReportsIndex::class)->name('reports');
        Route::get('/pekerja', EmployeesIndex::class)->name('employees');
        Route::get('/pengaturan', SettingsIndex::class)->name('settings');
    });
});
