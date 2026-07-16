<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\V1Controller;

// swagger

Route::get('/',[V1Controller::class,'index']);

