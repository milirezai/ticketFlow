<?php

namespace App\Models\Access;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
    
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
