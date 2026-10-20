<?php

namespace Database\Factories;

use App\Models\BusinessCalendar;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'TEAM-'.fake()->unique()->numerify('####'),
            'name' => 'Nhóm '.fake()->unique()->word(),
            'description' => fake()->sentence(),
            'lead_id' => User::factory(),
            'business_calendar_id' => BusinessCalendar::factory(),
            'assignment_strategy' => 'skill_weighted',
            'capacity' => 20,
            'is_active' => true,
        ];
    }
}
