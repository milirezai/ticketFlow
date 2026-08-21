<?php

namespace App\Models\Ticket;

use App\Models\Activity\ActivityLog;
use App\Models\User\User;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use SoftDeletes,HasFactory;
    protected $fillable = ['subject', 'user_id','assigned_to','ticket_category_id',
        'ticket_priority_id','ticket_status_id', 'created_at','updated_at','last_escalation'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime','last_escalation' => 'datetime'];
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

    // scope subject
    public function scopeSubject(Builder $query, string $subject): void
    {
        $query->when($subject, function (Builder $query) use ($subject){
            $query->where('subject','like',"%{$subject}%");
        });
    }
    // scope status
    public function scopeStatus(Builder $query, string $status): void
    {
        $query->whereHas('status',function (Builder $query) use ($status){
            $query->where('name','like',"%{$status}%");
        });
    }
    // scope priority
    public function scopePriority(Builder $query, string $priority): void
    {
        $query->whereHas('priority',function (Builder $query) use ($priority){
            $query->where('name','like',"%{$priority}%");
        });
    }
    // scope owner
    public function scopeOwner(Builder $query, int $owner): void
    {
        $query->where('user_id','=',$owner);
    }
    // scope category
    public function scopeCategory(Builder $query, string $category): void
    {
        $query->whereHas('category',function (Builder $query) use ($category){
            $query->where('name','like',"%{$category}%");
        });
    }
    // scope dateFrom
    protected function scopeDateFrom(Builder $query, string $dateFrom): Builder
    {
        return $query->whereDate('created_at','>=',$dateFrom);
    }
    // scope dateTo
    protected function scopeDateTo(Builder $query, string $dateTo): Builder
    {
        return $query->whereDate('created_at','<=',$dateTo);
    }
    // scope assignedTo
    protected function scopeAssignedTo(Builder $query, string $assignedTo): Builder
    {
        return $query->where('assigned_to','=',$assignedTo);
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(ActivityLog::class,'subject');
    }
}
