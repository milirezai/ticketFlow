<?php

namespace App\Models\Setting;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;
    protected $fillable = ['key','value','created_at','updated_at'];
    protected static function newFactory()
    {
        return SettingFactory::new();
    }
}
