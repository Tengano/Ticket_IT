<?php

namespace App\Services\Tickets;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Notifications\TicketNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Throwable;

class TicketNotifier
{
    public function ticketCreated(Ticket $ticket): void
    {
        $recipients = $this->teamStaff($ticket);

        if ($recipients->isEmpty()) {
            $recipients = User::query()->active()->role(['lead', 'admin'])->get();
        }

        $this->send(
            $recipients,
            $ticket,
            'Ticket mới cần xử lý',
            "{$ticket->requester->name} vừa tạo ticket \"{$ticket->subject}\".",
            $ticket->requester_id,
        );
    }

    public function statusChanged(Ticket $ticket, User $actor, TicketStatus $from, TicketStatus $to): void
    {
        $recipients = collect([$ticket->requester, $ticket->assignee])
            ->filter()
            ->merge($this->teamStaff($ticket));

        $this->send(
            $recipients,
            $ticket,
            'Cập nhật trạng thái ticket',
            "Trạng thái ticket đã đổi từ \"{$from->label()}\" sang \"{$to->label()}\".",
            $actor->id,
        );
    }

    public function ticketAssigned(Ticket $ticket, User $assignee, User $actor): void
    {
        $this->send(
            collect([$assignee]),
            $ticket,
            'Bạn được phân công ticket',
            "{$actor->name} đã phân công ticket \"{$ticket->subject}\" cho bạn.",
            $actor->id,
        );
    }

    public function commentAdded(TicketComment $comment): void
    {
        $ticket = $comment->ticket;

        $recipients = $comment->isInternal()
            ? collect([$ticket->assignee])->merge($this->teamStaff($ticket))
            : collect([$ticket->requester, $ticket->assignee])->merge($ticket->watchers);

        $this->send(
            $recipients->filter(fn (?User $user) => $user !== null),
            $ticket,
            $comment->isInternal() ? 'Ghi chú nội bộ mới' : 'Phản hồi mới trên ticket',
            $comment->isInternal()
                ? "{$comment->author->name} đã thêm ghi chú nội bộ."
                : "{$comment->author->name} vừa phản hồi ticket của bạn.",
            $comment->user_id,
        );
    }

    public function attachmentAdded(Ticket $ticket, User $actor, string $fileName): void
    {
        $recipients = collect([$ticket->requester, $ticket->assignee])->merge($this->teamStaff($ticket));

        $this->send(
            $recipients->filter(fn (?User $user) => $user !== null),
            $ticket,
            'Tệp đính kèm mới',
            "{$actor->name} đã đính kèm tệp \"{$fileName}\".",
            $actor->id,
        );
    }

    /**
     * @return Collection<int, User>
     */
    private function teamStaff(Ticket $ticket): Collection
    {
        if ($ticket->assigned_team_id === null) {
            return collect();
        }

        return User::query()
            ->active()
            ->role(['agent', 'lead'])
            ->where(function ($query) use ($ticket): void {
                $query->whereHas('teams', fn ($teams) => $teams->whereKey($ticket->assigned_team_id))
                    ->orWhereHas('teams', fn ($teams) => $teams->whereKey($ticket->assigned_team_id)->where('team_user.is_lead', true));
            })
            ->get();
    }

    /**
     * @param  Collection<int, User>  $recipients
     */
    private function send(Collection $recipients, Ticket $ticket, string $title, string $message, ?int $exceptUserId = null): void
    {
        $recipients = $recipients
            ->filter()
            ->unique('id')
            ->reject(fn (User $user) => $user->id === $exceptUserId || ! $user->is_active);

        if ($recipients->isEmpty()) {
            return;
        }

        try {
            Notification::send(
                $recipients,
                new TicketNotification($ticket, $title, $message, route('tickets.show', $ticket)),
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
