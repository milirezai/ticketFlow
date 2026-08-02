<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\V1Controller;
use App\Http\Controllers\Api\V1\Auth\RegisterController;




// swagger

Route::get('/',[V1Controller::class,'index']);

// auth

Route::prefix('auth')->group(function (){
    Route::post('/',[RegisterController::class]);
});
