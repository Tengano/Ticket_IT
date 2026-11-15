<?php

namespace App\Services\Phieu;

use App\Enums\TrangThaiPhieu;
use App\Models\BinhLuanPhieu;
use App\Models\NguoiDung;
use App\Models\PhieuYeuCau;
use App\Notifications\ThongBaoPhieu;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Throwable;

class DichVuThongBaoPhieu
{
    public function ticketCreated(PhieuYeuCau $ticket): void
    {
        $recipients = $this->teamStaff($ticket);

        if ($recipients->isEmpty()) {
            $recipients = NguoiDung::query()->active()->role(['lead', 'admin'])->get();
        }

        $this->send(
            $recipients,
            $ticket,
            'Phiếu yêu cầu mới cần xử lý',
            "{$ticket->requester->ten} vừa tạo phiếu \"{$ticket->tieu_de}\".",
            $ticket->nguoi_yeu_cau_id,
        );
    }

    public function statusChanged(PhieuYeuCau $ticket, NguoiDung $actor, TrangThaiPhieu $from, TrangThaiPhieu $to): void
    {
        $recipients = collect([$ticket->requester, $ticket->assignee])
            ->filter()
            ->merge($this->teamStaff($ticket));

        $this->send(
            $recipients,
            $ticket,
            'Cập nhật trạng thái phiếu',
            "Trạng thái phiếu đã đổi từ \"{$from->label()}\" sang \"{$to->label()}\".",
            $actor->id,
        );
    }

    public function ticketAssigned(PhieuYeuCau $ticket, NguoiDung $assignee, NguoiDung $actor): void
    {
        $this->send(
            collect([$assignee]),
            $ticket,
            'Bạn được phân công phiếu',
            "{$actor->ten} đã phân công phiếu \"{$ticket->tieu_de}\" cho bạn.",
            $actor->id,
        );
    }

    public function commentAdded(BinhLuanPhieu $comment): void
    {
        $ticket = $comment->ticket;

        $recipients = $comment->isInternal()
            ? collect([$ticket->assignee])->merge($this->teamStaff($ticket))
            : collect([$ticket->requester, $ticket->assignee])->merge($ticket->watchers);

        $this->send(
            $recipients->filter(fn (?NguoiDung $user) => $user !== null),
            $ticket,
            $comment->isInternal() ? 'Ghi chú nội bộ mới' : 'Phản hồi mới trên phiếu',
            $comment->isInternal()
                ? "{$comment->author->ten} đã thêm ghi chú nội bộ."
                : "{$comment->author->ten} vừa phản hồi phiếu của bạn.",
            $comment->nguoi_dung_id,
        );
    }

    public function attachmentAdded(PhieuYeuCau $ticket, NguoiDung $actor, string $fileName): void
    {
        $recipients = collect([$ticket->requester, $ticket->assignee])->merge($this->teamStaff($ticket));

        $this->send(
            $recipients->filter(fn (?NguoiDung $user) => $user !== null),
            $ticket,
            'Tệp đính kèm mới',
            "{$actor->ten} đã đính kèm tệp \"{$fileName}\".",
            $actor->id,
        );
    }

    /**
     * @return Collection<int, NguoiDung>
     */
    private function teamStaff(PhieuYeuCau $ticket): Collection
    {
        if ($ticket->nhom_xu_ly_id === null) {
            return collect();
        }

        return NguoiDung::query()
            ->active()
            ->role(['agent', 'lead'])
            ->where(function ($query) use ($ticket): void {
                $query->whereHas('teams', fn ($teams) => $teams->whereKey($ticket->nhom_xu_ly_id))
                    ->orWhereHas('teams', fn ($teams) => $teams->whereKey($ticket->nhom_xu_ly_id)->where('nhom_xu_ly_nguoi_dung.la_truong_nhom', true));
            })
            ->get();
    }

    /**
     * @param  Collection<int, NguoiDung>  $recipients
     */
    private function send(Collection $recipients, PhieuYeuCau $ticket, string $title, string $message, ?int $exceptUserId = null): void
    {
        $recipients = $recipients
            ->filter()
            ->unique('id')
            ->reject(fn (NguoiDung $user) => $user->id === $exceptUserId || ! $user->dang_hoat_dong);

        if ($recipients->isEmpty()) {
            return;
        }

        try {
            Notification::send(
                $recipients,
                new ThongBaoPhieu($ticket, $title, $message, route('phieu.show', $ticket)),
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
