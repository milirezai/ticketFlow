<?php

namespace App\Models\Ticket;

use App\Models\User\User;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use SoftDeletes,HasFactory;
    protected $fillable = ['subject', 'user_id','assigned_to','ticket_category_id',
        'ticket_priority_id','ticket_status_id', 'created_at','updated_at'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime'];
    protected static function newFactory()
    {
        return TicketFactory::new();
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class,'assigned_to');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class,'ticket_category_id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(TicketPriority::class,'ticket_priority_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class,'ticket_status_id');
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
