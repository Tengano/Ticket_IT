<?php

namespace Database\Factories;

use App\Enums\TrangThaiPhieu;
use App\Models\LichSuTrangThai;
use App\Models\NguoiDung;
use App\Models\PhieuYeuCau;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LichSuTrangThai>
 */
class LichSuTrangThaiFactory extends Factory
{
    protected $model = LichSuTrangThai::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phieu_yeu_cau_id' => PhieuYeuCau::factory(),
            'trang_thai_truoc' => null,
            'trang_thai_sau' => TrangThaiPhieu::Moi,
            'nguoi_thuc_hien_id' => NguoiDung::factory(),
            'ly_do' => null,
            'bat_dau_luc' => now(),
            'ket_thuc_luc' => null,
            'so_phut' => null,
        ];
    }
}
