<?php

namespace App\Models\User;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Access\Permission;
use App\Models\Access\Role;
use App\Models\Service\Otp\Otp;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketFile;
use App\Models\Ticket\TicketMessage;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable,HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name', 'last_name',
        'email', 'email_verified_at',
        'mobile', 'mobile_verified_at',
        'password', 'user_type',
        'profile_photo_path', 'activation',
        'activation_date', 'status',
        'status', 'remember_token'
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
}
