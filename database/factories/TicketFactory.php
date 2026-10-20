<?php

namespace Database\Factories;

use App\Enums\Impact;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\Urgency;
use App\Models\Category;
use App\Models\Department;
use App\Models\Location;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'TK-'.now()->format('Y').'-'.fake()->unique()->numerify('######'),
            'subject' => fake()->sentence(6),
            'description' => fake()->paragraphs(2, true),
            'requester_id' => User::factory(),
            'department_id' => Department::factory(),
            'location_id' => Location::factory(),
            'category_id' => Category::factory(),
            'assigned_team_id' => null,
            'assignee_id' => null,
            'status' => TicketStatus::New,
            'priority' => TicketPriority::P3,
            'impact' => Impact::Medium,
            'urgency' => Urgency::Medium,
            'channel' => 'web',
            'needs_triage' => false,
            'reopened_count' => 0,
        ];
    }

    public function status(TicketStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }

    public function priority(TicketPriority $priority): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => $priority,
        ]);
    }

    public function assignedTo(User $agent, ?Team $team = null): static
    {
        return $this->state(fn (array $attributes) => [
            'assignee_id' => $agent->id,
            'assigned_team_id' => $team?->id ?? $attributes['assigned_team_id'] ?? null,
            'status' => TicketStatus::InProgress,
            'assigned_at' => now(),
        ]);
    }
}
