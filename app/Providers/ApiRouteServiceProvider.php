<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ApiRouteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(function (){
                Route::prefix('v1')->group(base_path('routes/Api/V1/V1.php'));
            });
    }
}
