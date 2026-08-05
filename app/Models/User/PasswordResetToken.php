<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class PasswordResetToken extends Model
{
    protected $table = 'password_reset_tokens';
    protected $fillable = ['email' , 'token', 'created_at','expires_at'];
    protected $casts = ['created_at' => 'datetime','expires_at' => 'datetime'];
}
