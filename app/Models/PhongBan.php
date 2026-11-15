<?php

namespace App\Models;

use Database\Factories\PhongBanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['ma', 'ten', 'mo_ta', 'dang_hoat_dong'])]
class PhongBan extends Model
{
    /** @use HasFactory<PhongBanFactory> */
    use HasFactory;

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
