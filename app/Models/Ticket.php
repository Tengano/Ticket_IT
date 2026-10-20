<?php

namespace App\Models;

use App\Enums\Impact;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\Urgency;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code', 'subject', 'description', 'requester_id', 'department_id', 'location_id', 'category_id',
    'assigned_team_id', 'assignee_id', 'status', 'priority', 'impact', 'urgency', 'channel',
    'ai_confidence', 'ai_category_id', 'needs_triage', 'assigned_at', 'first_responded_at',
    'resolved_at', 'closed_at', 'handling_minutes', 'waiting_minutes', 'paused_minutes',
    'reopened_count', 'satisfaction_rating',
])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'impact' => Impact::class,
            'urgency' => Urgency::class,
            'ai_confidence' => 'float',
            'needs_triage' => 'boolean',
            'assigned_at' => 'datetime',
            'first_responded_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'handling_minutes' => 'integer',
            'waiting_minutes' => 'integer',
            'paused_minutes' => 'integer',
            'reopened_count' => 'integer',
            'satisfaction_rating' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function assignedTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'assigned_team_id');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function aiCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'ai_category_id');
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return HasMany<TicketComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    /**
     * @return HasMany<TicketComment, $this>
     */
    public function publicComments(): HasMany
    {
        return $this->hasMany(TicketComment::class)->where('type', 'public');
    }

    /**
     * @return HasMany<TicketComment, $this>
     */
    public function internalNotes(): HasMany
    {
        return $this->hasMany(TicketComment::class)->where('type', 'internal');
    }

    /**
     * @return HasMany<TicketAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    /**
     * @return HasMany<TicketEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(TicketEvent::class)->latest();
    }

    /**
     * @return HasMany<TicketStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(TicketStatusHistory::class)->latest();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function watchers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ticket_watchers')->withTimestamps();
    }

    /**
     * @param  Builder<Ticket>  $query
     * @return Builder<Ticket>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isStaff()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query->where('requester_id', $user->id)
                ->orWhere('assignee_id', $user->id)
                ->orWhereHas('watchers', fn (Builder $watchers) => $watchers->whereKey($user->id));
        });
    }

    public function isVisibleTo(User $user): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return $this->requester_id === $user->id
            || $this->assignee_id === $user->id
            || $this->watchers()->whereKey($user->id)->exists();
    }

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }

    /**
     * @return array<int, TicketStatus>
     */
    public function availableTransitions(): array
    {
        return $this->status->allowedTransitions();
    }

    public function isFirstResponsePending(): bool
    {
        return $this->first_responded_at === null;
    }
}
