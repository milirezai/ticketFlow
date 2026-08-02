<?php

namespace App\Models\Service\Otp;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    protected $fillable = [
        "authenticatable_type",
        "authenticatable_id",
        "code",
        "is_used",
        "expired_at"
    ];

    protected $cast = [
        "code" => "hashed",
        "is_used" => "boolean",
        "expires_at" => "datetime"
    ];
}
