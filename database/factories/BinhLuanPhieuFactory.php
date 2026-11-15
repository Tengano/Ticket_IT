<?php

namespace Database\Factories;

use App\Enums\LoaiBinhLuan;
use App\Models\BinhLuanPhieu;
use App\Models\NguoiDung;
use App\Models\PhieuYeuCau;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BinhLuanPhieu>
 */
class BinhLuanPhieuFactory extends Factory
{
    protected $model = BinhLuanPhieu::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phieu_yeu_cau_id' => PhieuYeuCau::factory(),
            'nguoi_dung_id' => NguoiDung::factory(),
            'noi_dung' => fake()->paragraph(),
            'loai' => LoaiBinhLuan::CongKhai,
            'la_phan_hoi_dau' => false,
        ];
    }

    public function internal(): static
    {
        return $this->state(fn (array $attributes) => [
            'loai' => LoaiBinhLuan::NoiBo,
        ]);
    }
}
