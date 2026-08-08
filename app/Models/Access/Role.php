<?php

namespace App\Models\Access;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User\User;


class Role extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description', 'status','created_at','updated_at'];

    protected static function newFactory()
    {
        return RoleFactory::new();
    }
    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
