<?php

namespace App\Services\Phieu;

use App\Models\BinhLuanPhieu;
use App\Models\NguoiDung;
use App\Models\PhieuYeuCau;
use App\Models\TepDinhKem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DichVuTepDinhKem
{
    /**
     * Lưu danh sách tệp đính kèm vào storage/app/private/tickets/{id}.
     *
     * @param  array<int, UploadedFile>  $files
     * @return array<int, TepDinhKem>
     */
    public function store(PhieuYeuCau $ticket, array $files, NguoiDung $uploader, ?BinhLuanPhieu $comment = null): array
    {
        $attachments = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $path = $file->store("phieu/{$ticket->id}", 'local');

            if ($path === false) {
                continue;
            }

            $attachments[] = TepDinhKem::query()->create([
                'phieu_yeu_cau_id' => $ticket->id,
                'binh_luan_id' => $comment?->id,
                'nguoi_tai_len_id' => $uploader->id,
                'disk' => 'local',
                'duong_dan' => $path,
                'ten_goc' => $file->getClientOriginalName(),
                'dinh_dang' => $file->getClientMimeType(),
                'kich_thuoc' => $file->getSize(),
            ]);
        }

        return $attachments;
    }

    public function delete(TepDinhKem $attachment): void
    {
        try {
            Storage::disk($attachment->disk)->delete($attachment->duong_dan);
        } catch (Throwable $exception) {
            report($exception);
        }

        $attachment->delete();
    }
}
