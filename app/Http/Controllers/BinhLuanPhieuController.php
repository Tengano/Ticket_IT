<?php

namespace App\Http\Controllers;

use App\Enums\LoaiBinhLuan;
use App\Enums\LoaiSuKienPhieu;
use App\Http\Requests\LuuBinhLuanRequest;
use App\Models\PhieuYeuCau;
use App\Services\Phieu\DichVuPhieuYeuCau;
use App\Services\Phieu\DichVuTepDinhKem;
use App\Services\Phieu\DichVuThongBaoPhieu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class BinhLuanPhieuController extends Controller
{
    public function __construct(
        private readonly DichVuPhieuYeuCau $tickets,
        private readonly DichVuTepDinhKem $attachments,
        private readonly DichVuThongBaoPhieu $notifier,
    ) {}

    public function store(LuuBinhLuanRequest $request, PhieuYeuCau $ticket): RedirectResponse
    {
        $this->authorize('comment', $ticket);

        $data = $request->validated();
        $type = LoaiBinhLuan::from($data['loai']);
        $user = $request->user();

        if ($type->isInternal()) {
            $this->authorize('internalNote', $ticket);
        }

        $comment = DB::transaction(function () use ($request, $ticket, $data, $type, $user) {
            $isFirstResponse = false;

            if (! $type->isInternal() && $user->isStaff() && $ticket->phan_hoi_dau_luc === null) {
                $isFirstResponse = true;
                $ticket->update(['phan_hoi_dau_luc' => now()]);
            }

            $comment = $ticket->comments()->create([
                'nguoi_dung_id' => $user->id,
                'noi_dung' => $data['noi_dung'],
                'loai' => $type,
                'la_phan_hoi_dau' => $isFirstResponse,
            ]);

            if ($request->hasFile('attachments')) {
                $this->attachments->store($ticket, $request->file('attachments'), $user, $comment);
            }

            $this->tickets->recordEvent(
                $ticket,
                $user,
                $type->isInternal() ? LoaiSuKienPhieu::GhiChuNoiBo : LoaiSuKienPhieu::BinhLuan,
                null,
                null,
                ['comment_id' => $comment->id],
            );

            return $comment;
        });

        $this->notifier->commentAdded($comment->load(['ticket', 'author']));

        $message = $type->isInternal() ? 'Đã thêm ghi chú nội bộ.' : 'Đã gửi phản hồi.';

        return redirect()
            ->to(route('phieu.show', $ticket).'#comment-'.$comment->id)
            ->with('success', $message);
    }
}
