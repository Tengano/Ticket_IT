<?php

namespace Database\Factories;

use App\Enums\LoaiSuKienPhieu;
use App\Models\NguoiDung;
use App\Models\PhieuYeuCau;
use App\Models\SuKienPhieu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SuKienPhieu>
 */
class SuKienPhieuFactory extends Factory
{
    protected $model = SuKienPhieu::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phieu_yeu_cau_id' => PhieuYeuCau::factory(),
            'nguoi_thuc_hien_id' => NguoiDung::factory(),
            'loai' => LoaiSuKienPhieu::TaoMoi,
            'tu_gia_tri' => null,
            'den_gia_tri' => null,
            'du_lieu_them' => [],
        ];
    }
}
