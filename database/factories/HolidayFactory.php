<?php

namespace Database\Factories;

use App\Models\BusinessCalendar;
use App\Models\Holiday;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Holiday>
 */
class HolidayFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_calendar_id' => BusinessCalendar::factory(),
            'date' => fake()->unique()->dateTimeBetween('-1 year', '+1 year')->format('Y-m-d'),
            'name' => fake()->randomElement(['Quốc khánh', 'Giỗ Tổ', 'Nghỉ bù', 'Ngày lễ']),
        ];
    }
}
