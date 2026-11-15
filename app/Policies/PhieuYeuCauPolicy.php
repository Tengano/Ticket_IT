<?php

namespace App\Policies;

use App\Enums\TrangThaiPhieu;
use App\Models\NguoiDung;
use App\Models\PhieuYeuCau;

class PhieuYeuCauPolicy
{
    /**
     * Quản trị viên có toàn quyền.
     */
    public function before(NguoiDung $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(NguoiDung $user): bool
    {
        return $user->can('ticket.view.own') || $user->can('ticket.view.all');
    }

    public function view(NguoiDung $user, PhieuYeuCau $ticket): bool
    {
        return $ticket->isVisibleTo($user);
    }

    public function create(NguoiDung $user): bool
    {
        return $user->can('ticket.create');
    }

    public function update(NguoiDung $user, PhieuYeuCau $ticket): bool
    {
        return $user->can('ticket.update') && $user->isStaff();
    }

    public function assign(NguoiDung $user, PhieuYeuCau $ticket): bool
    {
        return $user->can('ticket.assign') && $user->isStaff();
    }

    public function close(NguoiDung $user, PhieuYeuCau $ticket): bool
    {
        if ($user->can('ticket.close') && $user->isStaff()) {
            return true;
        }

        return $ticket->nguoi_yeu_cau_id === $user->id && ! $ticket->trang_thai->isClosed();
    }

    public function reopen(NguoiDung $user, PhieuYeuCau $ticket): bool
    {
        if ($user->can('ticket.reopen') && $user->isStaff()) {
            return true;
        }

        return $ticket->nguoi_yeu_cau_id === $user->id
            && in_array($ticket->trang_thai, [TrangThaiPhieu::DaGiaiQuyet, TrangThaiPhieu::DaDong], true);
    }

    public function comment(NguoiDung $user, PhieuYeuCau $ticket): bool
    {
        if (! $ticket->isVisibleTo($user)) {
            return false;
        }

        return $ticket->trang_thai->isOpen() || $user->isStaff();
    }

    public function internalNote(NguoiDung $user, PhieuYeuCau $ticket): bool
    {
        return $user->can('ticket.internal-note') && $ticket->isVisibleTo($user);
    }

    public function delete(NguoiDung $user, PhieuYeuCau $ticket): bool
    {
        return $user->can('admin.manage');
    }
}
