<?php

namespace App\Models;

use App\Enums\MucAnhHuong;
use App\Enums\MucKhanCap;
use App\Enums\TrangThaiPhieu;
use App\Enums\UuTienPhieu;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'ma', 'tieu_de', 'mo_ta', 'nguoi_yeu_cau_id', 'phong_ban_id', 'vi_tri_id', 'danh_muc_id',
    'nhom_xu_ly_id', 'nguoi_xu_ly_id', 'trang_thai', 'uu_tien', 'anh_huong', 'khan_cap', 'kenh',
    'phan_cong_luc', 'phan_hoi_dau_luc', 'giai_quyet_luc', 'dong_luc',
])]
class PhieuYeuCau extends Model
{
    protected $table = 'phieu_yeu_cau';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trang_thai' => TrangThaiPhieu::class,
            'uu_tien' => UuTienPhieu::class,
            'anh_huong' => MucAnhHuong::class,
            'khan_cap' => MucKhanCap::class,
            'phan_cong_luc' => 'datetime',
            'phan_hoi_dau_luc' => 'datetime',
            'giai_quyet_luc' => 'datetime',
            'dong_luc' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<NguoiDung, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_yeu_cau_id');
    }

    /**
     * @return BelongsTo<NguoiDung, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_xu_ly_id');
    }

    /**
     * @return BelongsTo<NhomXuLy, $this>
     */
    public function assignedTeam(): BelongsTo
    {
        return $this->belongsTo(NhomXuLy::class, 'nhom_xu_ly_id');
    }

    /**
     * @return BelongsTo<DanhMuc, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(DanhMuc::class, 'danh_muc_id');
    }

    /**
     * @return BelongsTo<PhongBan, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(PhongBan::class, 'phong_ban_id');
    }

    /**
     * @return BelongsTo<ViTri, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(ViTri::class, 'vi_tri_id');
    }

    /**
     * @return HasMany<BinhLuanPhieu, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(BinhLuanPhieu::class, 'phieu_yeu_cau_id');
    }

    /**
     * @return HasMany<BinhLuanPhieu, $this>
     */
    public function publicComments(): HasMany
    {
        return $this->hasMany(BinhLuanPhieu::class, 'phieu_yeu_cau_id')->where('loai', 'cong_khai');
    }

    /**
     * @return HasMany<BinhLuanPhieu, $this>
     */
    public function internalNotes(): HasMany
    {
        return $this->hasMany(BinhLuanPhieu::class, 'phieu_yeu_cau_id')->where('loai', 'noi_bo');
    }

    /**
     * @return HasMany<TepDinhKem, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TepDinhKem::class, 'phieu_yeu_cau_id');
    }

    /**
     * @return HasMany<SuKienPhieu, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(SuKienPhieu::class, 'phieu_yeu_cau_id')->latest();
    }

    /**
     * @return HasMany<LichSuTrangThai, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(LichSuTrangThai::class, 'phieu_yeu_cau_id')->latest();
    }

    /**
     * @return BelongsToMany<NguoiDung, $this>
     */
    public function watchers(): BelongsToMany
    {
        return $this->belongsToMany(NguoiDung::class, 'nguoi_theo_doi', 'phieu_yeu_cau_id', 'nguoi_dung_id')
            ->withTimestamps();
    }

    /**
     * @param  Builder<PhieuYeuCau>  $query
     * @return Builder<PhieuYeuCau>
     */
    public function scopeVisibleTo(Builder $query, NguoiDung $user): Builder
    {
        if ($user->isStaff()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query->where('nguoi_yeu_cau_id', $user->id)
                ->orWhere('nguoi_xu_ly_id', $user->id)
                ->orWhereHas('watchers', fn (Builder $watchers) => $watchers->whereKey($user->id));
        });
    }

    public function isVisibleTo(NguoiDung $user): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return $this->nguoi_yeu_cau_id === $user->id
            || $this->nguoi_xu_ly_id === $user->id
            || $this->watchers()->whereKey($user->id)->exists();
    }

    public function isOpen(): bool
    {
        return $this->trang_thai->isOpen();
    }

    /**
     * @return array<int, TrangThaiPhieu>
     */
    public function availableTransitions(): array
    {
        return $this->trang_thai->allowedTransitions();
    }

    public function isFirstResponsePending(): bool
    {
        return $this->phan_hoi_dau_luc === null;
    }
}
