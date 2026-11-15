<?php

namespace App\Models;

use App\Enums\TrangThaiPhieu;
use Database\Factories\LichSuTrangThaiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['phieu_yeu_cau_id', 'trang_thai_truoc', 'trang_thai_sau', 'nguoi_thuc_hien_id', 'ly_do', 'bat_dau_luc', 'ket_thuc_luc', 'so_phut'])]
class LichSuTrangThai extends Model
{
    /** @use HasFactory<LichSuTrangThaiFactory> */
    use HasFactory;

    protected $table = 'lich_su_trang_thai';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trang_thai_truoc' => TrangThaiPhieu::class,
            'trang_thai_sau' => TrangThaiPhieu::class,
            'bat_dau_luc' => 'datetime',
            'ket_thuc_luc' => 'datetime',
            'so_phut' => 'integer',
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
     * @return BelongsTo<NguoiDung, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_thuc_hien_id');
    }
}
