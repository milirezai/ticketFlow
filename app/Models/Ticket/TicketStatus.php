<?php

namespace App\Models\Ticket;

use Database\Factories\TicketStatusFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketStatus extends Model
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
        return TicketStatusFactory::new();
    }

    public function ticket(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
