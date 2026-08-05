<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ChangePasswordRequest;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Services\Auth\Auth;

class PasswordController extends Controller
{
    public function changePassword(ChangePasswordRequest $request, Auth $auth)
    {
        $user = $auth->changePassword($request->user(),$request);
        $token = $user->createToken('auth-login');

        return $user->toResource(UserResource::class)
            ->additional(['token' => $token->plainTextToken]);
    }
    public function forgotPassword(ForgotPasswordRequest $request, Auth $auth)
    {
        $auth->forgotPassword($request);
        return response()->noContent(200);
    }
    public function resetPassword(ResetPasswordRequest $request, Auth $auth)
    {
        $user = $auth->resetPassword($request);
    }

}

