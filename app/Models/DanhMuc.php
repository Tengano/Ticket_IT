<?php

namespace App\Models;

use App\Enums\UuTienPhieu;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['danh_muc_cha_id', 'ma', 'ten', 'mo_ta', 'nhom_mac_dinh_id', 'uu_tien_mac_dinh', 'dang_hoat_dong'])]
class DanhMuc extends Model
{
    protected $table = 'danh_muc';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'uu_tien_mac_dinh' => UuTienPhieu::class,
            'dang_hoat_dong' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<DanhMuc, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'danh_muc_cha_id');
    }

    /**
     * @return HasMany<DanhMuc, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'danh_muc_cha_id')->orderBy('ten');
    }

    /**
     * @return BelongsTo<NhomXuLy, $this>
     */
    public function defaultTeam(): BelongsTo
    {
        return $this->belongsTo(NhomXuLy::class, 'nhom_mac_dinh_id');
    }

    /**
     * @return HasMany<PhieuYeuCau, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(PhieuYeuCau::class, 'danh_muc_id');
    }

    /**
     * @param  Builder<DanhMuc>  $query
     * @return Builder<DanhMuc>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('dang_hoat_dong', true);
    }

    /**
     * @param  Builder<DanhMuc>  $query
     * @return Builder<DanhMuc>
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('danh_muc_cha_id')->orderBy('ten');
    }

    public function fullName(): string
    {
        return $this->parent ? "{$this->parent->ten} / {$this->ten}" : $this->ten;
    }
}
