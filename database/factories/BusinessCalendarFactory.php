<?php

namespace Database\Factories;

use App\Models\BusinessCalendar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessCalendar>
 */
class BusinessCalendarFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Lịch làm việc '.fake()->unique()->numerify('####'),
            'timezone' => 'Asia/Ho_Chi_Minh',
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
