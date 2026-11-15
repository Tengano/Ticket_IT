<?php

namespace Database\Factories;

use App\Models\NguoiDung;
use App\Models\NhomXuLy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NhomXuLy>
 */
class NhomXuLyFactory extends Factory
{
    protected $model = NhomXuLy::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ma' => 'NHOM-'.fake()->unique()->numerify('####'),
            'ten' => 'Nhóm '.fake()->unique()->word(),
            'mo_ta' => fake()->sentence(),
            'truong_nhom_id' => NguoiDung::factory(),
            'chien_luoc_phan_cong' => 'khop_ky_nang',
            'dinh_muc' => 20,
            'dang_hoat_dong' => true,
        ];
    }
}
