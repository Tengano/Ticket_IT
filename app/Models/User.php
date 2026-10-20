<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name', 'email', 'phone', 'password', 'department_id', 'location_id',
    'is_active', 'is_vip', 'avatar', 'last_assigned_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_vip' => 'boolean',
            'last_assigned_at' => 'datetime',
        ];
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
     * @return BelongsToMany<Team, $this>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)
            ->withPivot(['is_lead', 'capacity'])
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Skill, $this>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class)->withPivot('level')->withTimestamps();
    }

    /**
     * @return BelongsToMany<Location, $this>
     */
    public function agentZones(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'agent_zones')->withTimestamps();
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function requestedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assignee_id');
    }

    /**
     * @return BelongsToMany<Ticket, $this>
     */
    public function watchedTickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'ticket_watchers')->withTimestamps();
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(['agent', 'lead', 'admin']);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isLeadOrAdmin(): bool
    {
        return $this->hasAnyRole(['lead', 'admin']);
    }

    public function initials(): string
    {
        $words = preg_split('/\s+/', trim($this->name)) ?: [];

        if ($words === []) {
            return '?';
        }

        $initials = count($words) > 1
            ? mb_substr($words[0], 0, 1).mb_substr(end($words), 0, 1)
            : mb_substr($words[0], 0, 2);

        return mb_strtoupper($initials);
    }

    public function roleLabel(): string
    {
        $labels = [
            'requester' => 'Người yêu cầu',
            'agent' => 'Kỹ thuật viên',
            'lead' => 'Trưởng nhóm IT',
            'admin' => 'Quản trị',
        ];

        $roles = $this->getRoleNames()
            ->map(fn (string $role) => $labels[$role] ?? $role)
            ->all();

        return $roles === [] ? 'Chưa gán vai trò' : implode(', ', $roles);
    }
}
