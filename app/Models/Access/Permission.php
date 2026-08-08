<?php

namespace App\Models\Access;

use Database\Factories\PermissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User\User;

class Permission extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description', 'status','created_at','updated_at'];

    protected static function newFactory()
    {
        return PermissionFactory::new();
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
