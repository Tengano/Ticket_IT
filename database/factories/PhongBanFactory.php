<?php

namespace Database\Factories;

use App\Models\PhongBan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PhongBan>
 */
class PhongBanFactory extends Factory
{
    protected $model = PhongBan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ma' => 'PB-'.fake()->unique()->numerify('####'),
            'ten' => 'Phòng '.fake()->unique()->word(),
            'mo_ta' => fake()->sentence(),
            'dang_hoat_dong' => true,
        ];
    }
}
