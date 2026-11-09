<?php

namespace App\Providers;

use App\Mcp\Servers\PosServer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Facades\Mcp;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        date_default_timezone_set('Asia/Jakarta');
        Carbon::setLocale('id');

        if ($this->app->isProduction() || strtolower((string) request()->header('x-forwarded-proto')) === 'https') {
            URL::forceScheme('https');
        }

        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Gate::define('owner', function (User $user) {
            return $user->isOwner();
        });

        Gate::define('cashier', function (User $user) {
            return $user->isCashier();
        });

        Gate::define('give-discount', function (User $user) {
            return $user->isOwner() || $user->can_give_discount;
        });

        if (class_exists(Mcp::class)) {
            Mcp::local('pos', PosServer::class);
            Mcp::web('/mcp/pos', PosServer::class);
        }
    }
}
