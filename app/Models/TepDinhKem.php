<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'phieu_yeu_cau_id', 'binh_luan_id', 'nguoi_tai_len_id', 'disk', 'duong_dan', 'ten_goc', 'dinh_dang', 'kich_thuoc',
])]
class TepDinhKem extends Model
{
    protected $table = 'tep_dinh_kem';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kich_thuoc' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<PhieuYeuCau, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(PhieuYeuCau::class, 'phieu_yeu_cau_id');
    }

    /**
     * @return BelongsTo<BinhLuanPhieu, $this>
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(BinhLuanPhieu::class, 'binh_luan_id');
    }

    /**
     * @return BelongsTo<NguoiDung, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_tai_len_id');
    }

    public function isInternal(): bool
    {
        return $this->comment?->isInternal() ?? false;
    }

    public function humanSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = max($this->kich_thuoc, 0);
        $power = $size > 0 ? (int) floor(log($size, 1024)) : 0;
        $power = min($power, count($units) - 1);

        return number_format($size / (1024 ** $power), $power === 0 ? 0 : 1).' '.$units[$power];
    }
}
