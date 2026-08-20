<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Milirulepilot\Facade\Registry;
use App\Decisions\TicketEscalatedDecision;
use App\Decisions\LowPriorityTicketEscalatedTimeDecision;
use App\Decisions\MediumPriorityTicketEscalatedTimeDecision;
use App\Decisions\HighPriorityTicketEscalatedTimeDecision;
use App\Decisions\CriticalPriorityTicketEscalatedTimeDecision;


class ApiRouteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        Registry::decisions([
            'ticketEscalated' => TicketEscalatedDecision::class,
            'lowPriorityTicketEscalatedTime' => LowPriorityTicketEscalatedTimeDecision::class,
            'mediumPriorityTicketEscalatedTime' => MediumPriorityTicketEscalatedTimeDecision::class,
            'highPriorityTicketEscalatedTime' => HighPriorityTicketEscalatedTimeDecision::class,
            'criticalPriorityTicketEscalatedTime' => CriticalPriorityTicketEscalatedTimeDecision::class
        ]);
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
