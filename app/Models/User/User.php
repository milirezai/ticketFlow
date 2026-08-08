<?php

namespace App\Models\User;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Access\Permission;
use App\Models\Access\Role;
use App\Models\Service\Otp\Otp;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'email_verified_at',
        'mobile',
        'mobile_verified_at',
        'password',
        'user_type',
        'profile_photo_path',
        'activation',
        'activation_date',
        'status',
        'status',
        'remember_token'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function otps(): HasMany
    {
        return $this->hasMany(Otp::class);
    }

    public function unusedOtp()
    {
        return $this->otps()->where("is_used", false)->first();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles->whereIn('name', $roles)->isNotEmpty();
    }

    public function hasRole(string|array $roles): bool
    {
        return is_array($roles) ? $this->hasAnyRole($roles) : $this->roles->contains('name', $roles);
    }

    public function hasPermissionThroughRole(string $permission): bool
    {
        return $this->roles
            ->flatMap->permissions
            ->pluck('name')
            ->contains($permission);
    }

    public function hasPermissionTo(string $permission): bool
    {
        return $this->hasPermissionThroughRole($permission) || $this->permissions->contains('name', $permission);
    }
}
