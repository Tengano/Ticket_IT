<?php

namespace App\Http\Controllers;

use App\Enums\LoaiSuKienPhieu;
use App\Http\Requests\LuuTepDinhKemRequest;
use App\Models\PhieuYeuCau;
use App\Models\TepDinhKem;
use App\Services\Phieu\DichVuPhieuYeuCau;
use App\Services\Phieu\DichVuTepDinhKem;
use App\Services\Phieu\DichVuThongBaoPhieu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TepDinhKemPhieuController extends Controller
{
    public function __construct(private readonly DichVuTepDinhKem $attachments) {}

    public function store(LuuTepDinhKemRequest $request, PhieuYeuCau $ticket, DichVuPhieuYeuCau $tickets, DichVuThongBaoPhieu $notifier): RedirectResponse
    {
        $created = $this->attachments->store($ticket, [$request->file('file')], $request->user());

        foreach ($created as $attachment) {
            $tickets->recordEvent($ticket, $request->user(), LoaiSuKienPhieu::DinhKemTep, null, null, [
                'ten' => $attachment->ten_goc,
                'attachment_id' => $attachment->id,
            ]);
            $notifier->attachmentAdded($ticket, $request->user(), $attachment->ten_goc);
        }

        return back()->with('success', 'Đã tải lên tệp đính kèm.');
    }

    public function download(PhieuYeuCau $ticket, TepDinhKem $attachment): StreamedResponse
    {
        $this->authorize('download', $attachment);
        abort_unless($attachment->phieu_yeu_cau_id === $ticket->id, 404);

        abort_unless(Storage::disk($attachment->disk)->exists($attachment->duong_dan), 404, 'Tệp không còn tồn tại.');

        return Storage::disk($attachment->disk)->download($attachment->duong_dan, $attachment->ten_goc);
    }
}
