<?php

namespace App\Policies;

use App\Models\TicketAttachment;
use App\Models\User;

class TicketAttachmentPolicy
{
    public function download(User $user, TicketAttachment $attachment): bool
    {
        if (! $attachment->ticket->isVisibleTo($user)) {
            return false;
        }

        return $user->isStaff() || ! $attachment->isInternal();
    }
}
