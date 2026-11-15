<?php

namespace Database\Factories;

use App\Models\ViTri;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ViTri>
 */
class ViTriFactory extends Factory
{
    protected $model = ViTri::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ten' => 'Phòng '.fake()->unique()->numberBetween(100, 999),
            'toa_nha' => 'Tòa '.fake()->randomElement(['A', 'B', 'C']),
            'tang' => 'Tầng '.fake()->numberBetween(1, 5),
            'khu_vuc' => 'Khu '.fake()->randomElement(['A', 'B', 'C']),
            'mo_ta' => fake()->sentence(),
            'dang_hoat_dong' => true,
        ];
    }
}
