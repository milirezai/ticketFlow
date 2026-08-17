<?php

use App\Http\Controllers\Api\V1\Access\PermissionController;
use App\Http\Controllers\Api\V1\Access\RoleController;
use App\Http\Controllers\Api\V1\Access\UserAccessController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\V1Controller;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\PasswordController;
use App\Http\Controllers\Api\V1\Ticket\ExpertController;
use App\Http\Controllers\Api\V1\Ticket\TicketCategoryController;
use App\Http\Controllers\Api\V1\Ticket\TicketController;
use App\Http\Controllers\Api\V1\Ticket\TicketFileController;
use App\Http\Controllers\Api\V1\Ticket\TicketMessageController;
use App\Http\Controllers\Api\V1\Ticket\TicketPriorityController;
use App\Http\Controllers\Api\V1\Ticket\TicketStatusController;

// swagger

Route::get('/', [V1Controller::class, 'index']);

// auth

Route::middleware('throttle')->prefix('auth')->group(function () {

    Route::post('/', [RegisterController::class, 'index'])->name('register');
    Route::post('/login', [LoginController::class, 'index'])->name('login');
    Route::post('/logout', [LogoutController::class, 'index'])
        ->middleware('auth:sanctum')->name('logout');

    Route::prefix('password')->middleware('throttle')->controller(PasswordController::class)->group(function () {

        Route::post('change', 'changePassword')->middleware('auth:sanctum')
            ->name('password.change');
        Route::post('forgot', 'forgotPassword')->name('password.forgot');
        Route::post('/reset', 'resetPassword')->name('password.reset');
    });
});

// access
Route::middleware('auth:sanctum')->prefix('access')->name('access.')->group(function () {
    Route::apiResource('roles', RoleController::class);
    Route::post('roles/{role}/permissions/sync', [RoleController::class, 'syncPermissions'])->name('roles.syncPermission');
    Route::apiResource('permissions', PermissionController::class);
    Route::post('users/{user}/roles/sync', [UserAccessController::class, 'assignRoles'])->name('assignRoles');
    Route::post('users/{user}/permissions/sync', [UserAccessController::class, 'assignPermissions'])->name('assignPermissions');
});


// ticket
Route::middleware(['auth:sanctum', 'throttle'])->group(function () {
    Route::apiResource('tickets', TicketController::class);
    Route::apiResource('tickets.messages', TicketMessageController::class)->scoped();
    Route::apiResource('ticket-categories', TicketCategoryController::class);
    Route::apiResource('ticket-priorities', TicketPriorityController::class);
    Route::apiResource('ticket-statuses', TicketStatusController::class);
    Route::prefix('experts')->controller(ExpertController::class)->name('experts.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{expert}/tickets', 'tickets')->name('tickets');
        Route::get('/{expert}/categories', 'categories')->name('categories');
        Route::post('/{expert}/categories', 'syncCategories')->name('syncCategories');
        Route::post('/{expert}/assign', 'assign')->name('assign');
    });
    Route::apiResource('tickets.files', TicketFileController::class)->scoped()->only(['index', 'show', 'store', 'destroy']);
    Route::get('tickets/{ticket}/files/{file}/download', [TicketFileController::class, 'download'])->name('tickets.files.download')->scopeBindings();
});
