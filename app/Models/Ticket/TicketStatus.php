<?php

namespace App\Models\Ticket;

use Database\Factories\TicketStatusFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class TicketStatus extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['name', 'slug', 'description', 'status', 'created_at', 'updated_at'];
    protected $casts = [
        'status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    protected static function newFactory()
    {
        return TicketStatusFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (TicketStatus $status) {
            if (empty($status->slug)) {
                $status->slug = static::uniqueSlug($status->name);
            }
        });
    }

    protected static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $count = static::where('name', $name)->count();
        while (static::where('slug', $slug)->withTrashed()->exists()) {
            $slug = $base . '-' . ++$count;
        }
        return $slug;
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
