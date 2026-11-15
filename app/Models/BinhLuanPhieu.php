<?php

namespace App\Models;

use App\Enums\LoaiBinhLuan;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['phieu_yeu_cau_id', 'nguoi_dung_id', 'noi_dung', 'loai', 'la_phan_hoi_dau'])]
class BinhLuanPhieu extends Model
{
    protected $table = 'binh_luan_phieu';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'loai' => LoaiBinhLuan::class,
            'la_phan_hoi_dau' => 'boolean',
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
    public function author(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    /**
     * @return HasMany<TepDinhKem, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TepDinhKem::class, 'binh_luan_id');
    }

    public function isInternal(): bool
    {
        return $this->loai->isInternal();
    }
}
