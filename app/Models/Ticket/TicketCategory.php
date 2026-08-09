<?php

namespace App\Models\Ticket;

use Database\Factories\TicketCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketCategory extends Model
{
    use HasFactory;
    protected $fillable = ['name','description','status','created_at','updated_at'];
    protected $casts = [
        'status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected static function newFactory()
    {
        return TicketCategoryFactory::new();
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
