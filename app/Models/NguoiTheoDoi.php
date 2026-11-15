<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['phieu_yeu_cau_id', 'nguoi_dung_id'])]
class NguoiTheoDoi extends Model
{
    protected $table = 'nguoi_theo_doi';

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
    public function user(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }
}
