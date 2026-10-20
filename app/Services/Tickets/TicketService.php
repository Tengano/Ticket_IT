<?php

namespace App\Services\Tickets;

use App\Enums\Impact;
use App\Enums\TicketEventType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\Urgency;
use App\Models\Category;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketService
{
    public function __construct(private readonly TicketNotifier $notifier) {}

    /**
     * Tạo ticket mới: sinh mã TK-YYYY-NNNNNN, suy ra priority từ Impact × Urgency,
     * gán nhóm mặc định theo danh mục, ghi event + status history.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $requester, array $attributes): Ticket
    {
        return DB::transaction(function () use ($requester, $attributes): Ticket {
            $impact = Impact::from((int) ($attributes['impact'] ?? Impact::Medium->value));
            $urgency = Urgency::from((int) ($attributes['urgency'] ?? Urgency::Medium->value));
            $category = isset($attributes['category_id'])
                ? Category::query()->find($attributes['category_id'])
                : null;

            $ticket = Ticket::query()->create([
                'code' => $this->nextCode(),
                'subject' => $attributes['subject'],
                'description' => $attributes['description'],
                'requester_id' => $requester->id,
                'department_id' => $attributes['department_id'] ?? $requester->department_id,
                'location_id' => $attributes['location_id'] ?? $requester->location_id,
                'category_id' => $category?->id,
                'assigned_team_id' => $category?->default_team_id,
                'status' => TicketStatus::New,
                'priority' => TicketPriority::fromImpactUrgency($impact, $urgency),
                'impact' => $impact,
                'urgency' => $urgency,
                'channel' => $attributes['channel'] ?? 'web',
            ]);

            $ticket->watchers()->syncWithoutDetaching([$requester->id]);

            $this->recordEvent($ticket, $requester, TicketEventType::Created, null, TicketStatus::New->value);
            $this->recordStatusHistory($ticket, null, TicketStatus::New, $requester, 'Khởi tạo ticket');
            $this->notifier->ticketCreated($ticket);

            return $ticket->refresh();
        });
    }

    /**
     * Cập nhật thông tin cơ bản của ticket (không gồm trạng thái/phân công).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Ticket $ticket, array $attributes, User $actor): Ticket
    {
        return DB::transaction(function () use ($ticket, $attributes, $actor): Ticket {
            $changes = [];

            if (isset($attributes['subject']) && $attributes['subject'] !== $ticket->subject) {
                $changes['subject'] = $attributes['subject'];
            }

            if (isset($attributes['description']) && $attributes['description'] !== $ticket->description) {
                $changes['description'] = $attributes['description'];
            }

            foreach (['category_id', 'department_id', 'location_id'] as $field) {
                if (array_key_exists($field, $attributes) && (int) $attributes[$field] !== (int) $ticket->{$field}) {
                    $changes[$field] = $attributes[$field] ?: null;
                }
            }

            if ($changes === []) {
                return $ticket;
            }

            $ticket->update($changes);
            $this->recordEvent($ticket, $actor, TicketEventType::Updated, null, null, ['fields' => array_keys($changes)]);

            return $ticket->refresh();
        });
    }

    public function changePriority(Ticket $ticket, TicketPriority $priority, User $actor): Ticket
    {
        if ($ticket->priority === $priority) {
            return $ticket;
        }

        return DB::transaction(function () use ($ticket, $priority, $actor): Ticket {
            $from = $ticket->priority;
            $ticket->update(['priority' => $priority]);
            $this->recordEvent($ticket, $actor, TicketEventType::PriorityChanged, $from->value, $priority->value);

            return $ticket->refresh();
        });
    }

    public function assign(Ticket $ticket, ?User $assignee, ?Team $team, User $actor): Ticket
    {
        return DB::transaction(function () use ($ticket, $assignee, $team, $actor): Ticket {
            $updates = [
                'assignee_id' => $assignee?->id,
            ];

            if ($team !== null) {
                $updates['assigned_team_id'] = $team->id;
            }

            if ($assignee !== null && $ticket->assigned_at === null) {
                $updates['assigned_at'] = now();
                $updates['status'] = TicketStatus::Assigned;
            }

            $ticket->update($updates);

            if ($assignee !== null) {
                $assignee->update(['last_assigned_at' => now()]);
            }

            $this->recordEvent($ticket, $actor, TicketEventType::Assigned, null, $assignee?->name, [
                'assignee' => $assignee?->name,
                'assignee_id' => $assignee?->id,
                'team' => $team?->name ?? $ticket->assignedTeam?->name,
                'team_id' => $team?->id ?? $ticket->assigned_team_id,
            ]);

            if ($assignee !== null) {
                $this->notifier->ticketAssigned($ticket->refresh(), $assignee, $actor);
            }

            return $ticket->refresh();
        });
    }

    public function changeStatus(Ticket $ticket, TicketStatus $status, User $actor, ?string $reason = null): Ticket
    {
        if ($ticket->status === $status) {
            return $ticket;
        }

        if (! $ticket->status->canTransitionTo($status)) {
            throw ValidationException::withMessages([
                'status' => "Không thể chuyển ticket từ \"{$ticket->status->label()}\" sang \"{$status->label()}\".",
            ]);
        }

        return DB::transaction(function () use ($ticket, $status, $actor, $reason): Ticket {
            $from = $ticket->status;
            $now = now();
            $updates = ['status' => $status];

            $openHistory = $ticket->statusHistories()->whereNull('ended_at')->latest()->first();

            if ($openHistory !== null) {
                $openHistory->update([
                    'ended_at' => $now,
                    'minutes' => max(0, (int) $openHistory->started_at?->diffInMinutes($now)),
                ]);
            }

            if ($status === TicketStatus::Resolved) {
                $updates['resolved_at'] = $now;
                $updates['handling_minutes'] = max(
                    0,
                    (int) $ticket->created_at->diffInMinutes($now) - $ticket->paused_minutes,
                );
            }

            if ($status === TicketStatus::Closed) {
                $updates['closed_at'] = $now;
                $updates['resolved_at'] = $ticket->resolved_at ?? $now;
            }

            if ($status === TicketStatus::Reopened) {
                $updates['reopened_count'] = $ticket->reopened_count + 1;
                $updates['resolved_at'] = null;
                $updates['closed_at'] = null;
            }

            $ticket->update($updates);

            $this->recordStatusHistory($ticket, $from, $status, $actor, $reason);
            $this->recordEvent($ticket, $actor, TicketEventType::StatusChanged, $from->value, $status->value, [
                'reason' => $reason,
            ]);

            if ($status === TicketStatus::Closed) {
                $this->recordEvent($ticket, $actor, TicketEventType::Closed);
            }

            if ($status === TicketStatus::Reopened) {
                $this->recordEvent($ticket, $actor, TicketEventType::Reopened);
            }

            $this->notifier->statusChanged($ticket->refresh(), $actor, $from, $status);

            return $ticket->refresh();
        });
    }

    public function close(Ticket $ticket, User $actor, ?string $reason = null): Ticket
    {
        return $this->changeStatus($ticket, TicketStatus::Closed, $actor, $reason ?? 'Đóng ticket');
    }

    public function reopen(Ticket $ticket, User $actor, ?string $reason = null): Ticket
    {
        return $this->changeStatus($ticket, TicketStatus::Reopened, $actor, $reason ?? 'Người dùng mở lại ticket');
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function recordEvent(
        Ticket $ticket,
        ?User $actor,
        TicketEventType $type,
        ?string $from = null,
        ?string $to = null,
        array $meta = [],
    ): TicketEvent {
        return TicketEvent::query()->create([
            'ticket_id' => $ticket->id,
            'actor_id' => $actor?->id,
            'type' => $type,
            'from' => $from,
            'to' => $to,
            'meta' => $meta,
        ]);
    }

    private function recordStatusHistory(
        Ticket $ticket,
        ?TicketStatus $from,
        TicketStatus $to,
        ?User $actor,
        ?string $reason = null,
    ): void {
        $ticket->statusHistories()->create([
            'from_status' => $from,
            'to_status' => $to,
            'actor_id' => $actor?->id,
            'reason' => $reason,
            'started_at' => now(),
        ]);
    }

    private function nextCode(): string
    {
        $year = now()->format('Y');
        $prefix = "TK-{$year}-";

        $latestCode = Ticket::query()
            ->where('code', 'like', $prefix.'%')
            ->lockForUpdate()
            ->max('code');

        $sequence = $latestCode ? ((int) substr($latestCode, -6)) + 1 : 1;

        return sprintf('%s%06d', $prefix, $sequence);
    }
}
