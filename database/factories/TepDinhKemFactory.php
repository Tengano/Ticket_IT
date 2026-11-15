<?php

namespace Database\Factories;

use App\Models\NguoiDung;
use App\Models\PhieuYeuCau;
use App\Models\TepDinhKem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TepDinhKem>
 */
class TepDinhKemFactory extends Factory
{
    protected $model = TepDinhKem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phieu_yeu_cau_id' => PhieuYeuCau::factory(),
            'binh_luan_id' => null,
            'nguoi_tai_len_id' => NguoiDung::factory(),
            'disk' => 'local',
            'duong_dan' => 'phieu/'.fake()->uuid().'.pdf',
            'ten_goc' => fake()->word().'.pdf',
            'dinh_dang' => 'application/pdf',
            'kich_thuoc' => fake()->numberBetween(1024, 204800),
        ];
    }
}
