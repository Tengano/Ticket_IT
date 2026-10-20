<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Phòng '.fake()->unique()->numberBetween(100, 999),
            'building' => 'Tòa '.fake()->randomElement(['A', 'B', 'C']),
            'floor' => 'Tầng '.fake()->numberBetween(1, 5),
            'zone' => 'Khu '.fake()->randomElement(['A', 'B', 'C']),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
