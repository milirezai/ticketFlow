<?php

namespace App\Services\Auth\Contracts;

use App\Models\User\User;
use Illuminate\Foundation\Http\FormRequest;

interface AuthContract
{
    public function register(FormRequest $request): User;
    public function login(FormRequest $request): User;
    public function logout(User $user): void;
    public function changePassword(User $user, FormRequest $request): User;
    public function forgotPassword(FormRequest $request): void;
    public function resetPassword(FormRequest $request): User|bool;
}
