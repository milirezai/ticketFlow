<?php

namespace App\Models\Service\Otp;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Otp extends Model
{
    protected $fillable = [
        "user_id",
        "code",
        "is_used",
        "expired_at"
    ];

    protected $casts = [
        "code" => "hashed",
        "is_used" => "boolean",
        "expired_at" => "datetime"
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
