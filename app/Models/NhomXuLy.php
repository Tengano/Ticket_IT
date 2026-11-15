<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['ma', 'ten', 'mo_ta', 'truong_nhom_id', 'chien_luoc_phan_cong', 'dinh_muc', 'dang_hoat_dong'])]
class NhomXuLy extends Model
{
    protected $table = 'nhom_xu_ly';

    /**
     * Chiến lược phân công hỗ trợ (hiển thị tiếng Việt).
     *
     * @return array<string, string>
     */
    public static function strategies(): array
    {
        return [
            'khop_ky_nang' => 'Luân chuyển theo tải công việc (khuyến nghị)',
            'it_viec_nhat' => 'Ít phiếu đang mở nhất',
            'xoay_vong' => 'Xoay vòng',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dinh_muc' => 'integer',
            'dang_hoat_dong' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<NguoiDung, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'truong_nhom_id');
    }

    /**
     * @return BelongsToMany<NguoiDung, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(NguoiDung::class, 'nhom_xu_ly_nguoi_dung', 'nhom_xu_ly_id', 'nguoi_dung_id')
            ->withPivot(['la_truong_nhom', 'dinh_muc'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<PhieuYeuCau, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(PhieuYeuCau::class, 'nhom_xu_ly_id');
    }

    /**
     * @return HasMany<DanhMuc, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(DanhMuc::class, 'nhom_mac_dinh_id');
    }
}
