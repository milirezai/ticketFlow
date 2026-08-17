<?php

namespace App\Models\Ticket;

use App\Models\User\User;
use Database\Factories\TicketFileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TicketFile extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'ticket_id', 'path', 'type', 'size', 'status', 'created_at', 'updated_at'];
    protected $casts = ['status' => 'boolean', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    protected static function booted(): void
    {
        static::deleting(function (TicketFile $file) {
            Storage::disk('local')->delete($file->path);
        });
    }


    protected static function newFactory()
    {
        return TicketFileFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
