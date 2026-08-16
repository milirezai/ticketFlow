<?php

namespace App\Models\Ticket;

use App\Models\User\User;
use Database\Factories\TicketCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class TicketCategory extends Model
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
        return TicketCategoryFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (TicketCategory $category) {
            if (empty($category->slug)) {
                $category->slug = static::uniqueSlug($category->name);
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

    public function experts(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'expert_categories');
    }
}
