<?php

namespace App\Providers;

use App\Models\Access\Permission;
use App\Models\User\User;
use App\Policies\Ticket\ExpertPolicy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

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
        Gate::before(fn(User $user, string $ability) => $user->hasRole('super-admin') ? true : null);
        if (Schema::hasTable('permissions')) {
            $permissionNames = Cache::rememberForever('permissions', function () {
                return Permission::query()->pluck('name')->all();
            });
            foreach ($permissionNames as $permission) {
                Gate::define($permission, fn(User $user) => $user->hasPermissionTo($permission));
            }
        }
        Gate::policy(User::class, ExpertPolicy::class);
    }
}
