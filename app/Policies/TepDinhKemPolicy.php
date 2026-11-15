<?php

namespace App\Policies;

use App\Models\NguoiDung;
use App\Models\TepDinhKem;

class TepDinhKemPolicy
{
    public function download(NguoiDung $user, TepDinhKem $attachment): bool
    {
        if (! $attachment->ticket->isVisibleTo($user)) {
            return false;
        }

        return $user->isStaff() || ! $attachment->isInternal();
    }
}
