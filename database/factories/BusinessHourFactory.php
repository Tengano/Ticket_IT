<?php

namespace Database\Factories;

use App\Models\BusinessCalendar;
use App\Models\BusinessHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessHour>
 */
class BusinessHourFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_calendar_id' => BusinessCalendar::factory(),
            'weekday' => 1,
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
        ];
    }
}
