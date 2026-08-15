<?php

namespace App\Models\User;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Access\Permission;
use App\Models\Access\Role;
use App\Models\Service\Otp\Otp;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketCategory;
use App\Models\Ticket\TicketFile;
use App\Models\Ticket\TicketMessage;
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

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function newFactory()
    {
        return UserFactory::new();
    }

    public function otps(): HasMany
    {
        return $this->hasMany(Otp::class);
    }

    public function unusedOtp()
    {
        return $this->otps()->where("is_used", false)->first();
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(TicketFile::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
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

    public function expertCategories(): BelongsToMany
    {
        return $this->belongsToMany(TicketCategory::class, 'expert_categories');
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function isExpert(): bool
    {
        return $this->hasRole('expert');
    }
}
