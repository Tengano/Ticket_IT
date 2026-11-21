<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['ma', 'ten', 'dang_hoat_dong'])]
class PhongBan extends Model
{
    protected $table = 'phong_ban';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dang_hoat_dong' => 'boolean',
        ];
    }

    /**
     * @return HasMany<NguoiDung, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(NguoiDung::class, 'phong_ban_id');
    }

    /**
     * @return HasMany<PhieuYeuCau, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(PhieuYeuCau::class, 'phong_ban_id');
    }
}
