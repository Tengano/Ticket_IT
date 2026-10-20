<?php

namespace Database\Factories;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketStatusHistory>
 */
class TicketStatusHistoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'from_status' => null,
            'to_status' => TicketStatus::New,
            'actor_id' => User::factory(),
            'reason' => null,
            'started_at' => now(),
            'ended_at' => null,
            'minutes' => null,
        ];
    }
}
