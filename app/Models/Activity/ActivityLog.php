<?php

namespace App\Models\Activity;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'subject_type', 'subject_id', 'action', 'description', 'properties', 'created_at','updated_at'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime', 'properties' => 'array'];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
