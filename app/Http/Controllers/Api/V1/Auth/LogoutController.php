<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\Auth;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function index(Request $request, Auth $auth)
    {
        $auth->logout($request->user());
        return response()->noContent();
    }
}
