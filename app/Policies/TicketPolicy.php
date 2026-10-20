<?php

namespace App\Policies;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Quản trị viên có toàn quyền.
     */
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('ticket.view.own') || $user->can('ticket.view.all');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $ticket->isVisibleTo($user);
    }

    public function create(User $user): bool
    {
        return $user->can('ticket.create');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->can('ticket.update') && $user->isStaff();
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->can('ticket.assign') && $user->isStaff();
    }

    public function close(User $user, Ticket $ticket): bool
    {
        if ($user->can('ticket.close') && $user->isStaff()) {
            return true;
        }

        return $ticket->requester_id === $user->id && ! $ticket->status->isClosed();
    }

    public function reopen(User $user, Ticket $ticket): bool
    {
        if ($user->can('ticket.reopen') && $user->isStaff()) {
            return true;
        }

        return $ticket->requester_id === $user->id
            && in_array($ticket->status, [TicketStatus::Resolved, TicketStatus::Closed], true);
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        if (! $ticket->isVisibleTo($user)) {
            return false;
        }

        return $ticket->status->isOpen() || $user->isStaff();
    }

    public function internalNote(User $user, Ticket $ticket): bool
    {
        return $user->can('ticket.internal-note') && $ticket->isVisibleTo($user);
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->can('admin.manage');
    }
}
