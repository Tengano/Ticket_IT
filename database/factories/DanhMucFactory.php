<?php

namespace Database\Factories;

use App\Enums\UuTienPhieu;
use App\Models\DanhMuc;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DanhMuc>
 */
class DanhMucFactory extends Factory
{
    protected $model = DanhMuc::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'danh_muc_cha_id' => null,
            'ma' => 'dm-'.fake()->unique()->slug(2),
            'ten' => fake()->unique()->words(2, true),
            'mo_ta' => fake()->sentence(),
            'uu_tien_mac_dinh' => UuTienPhieu::P3,
            'dang_hoat_dong' => true,
        ];
    }

    public function childOf(DanhMuc $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'danh_muc_cha_id' => $parent->id,
        ]);
    }
}
