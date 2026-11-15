<?php

namespace Database\Factories;

use App\Models\NguoiDung;
use App\Models\NguoiTheoDoi;
use App\Models\PhieuYeuCau;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NguoiTheoDoi>
 */
class NguoiTheoDoiFactory extends Factory
{
    protected $model = NguoiTheoDoi::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phieu_yeu_cau_id' => PhieuYeuCau::factory(),
            'nguoi_dung_id' => NguoiDung::factory(),
        ];
    }
}
