<?php

namespace App\Providers;

use App\Models\ApiKey;
use App\Models\Asset;
use App\Models\AssetTransaction;
use App\Models\TradingBot;
use App\Policies\ApiKeyPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        ApiKey::class => ApiKeyPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define gates for admin access
        Gate::define('access admin panel', function ($user) {
            return $user->hasRole('admin') || $user->hasPermissionTo('access admin panel');
        });

        Gate::define('view users', function ($user) {
            return $user->hasRole('admin') || $user->hasPermissionTo('view users');
        });

        Gate::define('view roles', function ($user) {
            return $user->hasRole('admin') || $user->hasPermissionTo('view roles');
        });

        Gate::define('view permissions', function ($user) {
            return $user->hasRole('admin') || $user->hasPermissionTo('view permissions');
        });
    }
}
