<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['ten', 'dang_hoat_dong'])]
class ViTri extends Model
{
    protected $table = 'vi_tri';

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
        return $this->hasMany(NguoiDung::class, 'vi_tri_id');
    }

    /**
     * @return HasMany<PhieuYeuCau, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(PhieuYeuCau::class, 'vi_tri_id');
    }

    public function fullName(): string
    {
        return $this->ten;
    }
}
