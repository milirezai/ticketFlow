<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\V1Controller;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\PasswordController;


// swagger

Route::get('/',[V1Controller::class,'index']);

// auth

Route::middleware('throttle')->prefix('auth')->group(function (){

    Route::post('/',[RegisterController::class,'index'])->name('register');
    Route::post('/login',[LoginController::class,'index'])->name('login');
    Route::post('/logout',[LogoutController::class,'index'])
        ->middleware('auth:sanctum')->name('logout');

    Route::prefix('password')->middleware('throttle')->controller(PasswordController::class)->group(function (){

        Route::post('change','changePassword')->middleware('auth:sanctum')
            ->name('password.change');
        Route::post('forgot','forgotPassword')->name('password.forgot');
        Route::post('/reset','resetPassword')->name('password.reset');

    });

});
