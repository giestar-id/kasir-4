<?php

use App\Http\Controllers\McpInfoController;
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

// MCP Server Endpoints
Route::get('/mcp/info', [McpInfoController::class, 'index'])->name('mcp.info');

if (class_exists(\Laravel\Mcp\Facades\Mcp::class)) {
    \Laravel\Mcp\Facades\Mcp::web('/mcp/pos', \App\Mcp\Servers\PosServer::class)
        ->middleware([\App\Http\Middleware\AuthenticateMcpKey::class]);
}

// Override GET & OPTIONS for Claude Web (claude.ai) CORS preflight & connectivity validation
Route::match(['get', 'options'], '/mcp/pos', function (Request $request) {
    if ($request->isMethod('OPTIONS')) {
        return response('', 204)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, DELETE')
            ->header('Access-Control-Allow-Headers', 'Authorization, X-MCP-KEY, x-api-key, Content-Type, Accept, MCP-Protocol-Version, Mcp-Method, Mcp-Name')
            ->header('Access-Control-Max-Age', '86400');
    }

    return response()->json([
        'status' => 'online',
        'server' => config('mcp.server_name', 'POS Kuliner MCP Server'),
        'version' => config('mcp.version', '1.1.0'),
        'protocol' => 'Model Context Protocol (JSON-RPC 2.0)',
        'message' => 'MCP Server is authenticated and operational. Send POST requests with JSON-RPC payloads to call tools.',
        'authenticated' => true,
        'transport' => 'Streamable HTTP (POST)',
    ]);
})->middleware([\App\Http\Middleware\AuthenticateMcpKey::class]);
