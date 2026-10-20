<?php

namespace Tests\Feature\Ticket;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $requester;

    private User $agent;

    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->requester = User::factory()->requester()->create();
        $this->agent = User::factory()->agent()->create();

        $this->ticket = Ticket::factory()->create([
            'requester_id' => $this->requester->id,
            'status' => TicketStatus::New,
        ]);
    }

    public function test_agent_can_move_ticket_through_full_lifecycle(): void
    {
        $this->actingAs($this->agent)
            ->patch(route('tickets.update', $this->ticket), ['status' => 'in_progress'])
            ->assertSessionHasNoErrors();

        $this->assertSame(TicketStatus::InProgress, $this->ticket->refresh()->status);

        $this->actingAs($this->agent)
            ->patch(route('tickets.update', $this->ticket), ['status' => 'resolved'])
            ->assertSessionHasNoErrors();

        $this->assertSame(TicketStatus::Resolved, $this->ticket->refresh()->status);
        $this->assertNotNull($this->ticket->resolved_at);

        $this->actingAs($this->agent)
            ->patch(route('tickets.update', $this->ticket), ['status' => 'closed'])
            ->assertSessionHasNoErrors();

        $this->ticket->refresh();

        $this->assertSame(TicketStatus::Closed, $this->ticket->status);
        $this->assertNotNull($this->ticket->closed_at);
        $this->assertSame(3, $this->ticket->statusHistories()->count());

        $this->assertDatabaseHas('ticket_events', [
            'ticket_id' => $this->ticket->id,
            'type' => 'status_changed',
            'from' => 'in_progress',
            'to' => 'resolved',
        ]);
        $this->assertDatabaseHas('ticket_events', [
            'ticket_id' => $this->ticket->id,
            'type' => 'closed',
        ]);
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        $response = $this->actingAs($this->agent)->patch(route('tickets.update', $this->ticket), [
            'status' => 'resolved',
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertSame(TicketStatus::New, $this->ticket->refresh()->status);
    }

    public function test_requester_can_close_own_ticket_but_not_others(): void
    {
        $this->actingAs($this->requester)
            ->post(route('tickets.close', $this->ticket))
            ->assertSessionHasNoErrors();

        $this->assertSame(TicketStatus::Closed, $this->ticket->refresh()->status);

        $otherRequester = User::factory()->requester()->create();
        $otherTicket = Ticket::factory()->create(['requester_id' => $otherRequester->id]);

        $this->actingAs($this->requester)
            ->post(route('tickets.close', $otherTicket))
            ->assertForbidden();
    }

    public function test_requester_can_reopen_resolved_ticket(): void
    {
        $this->actingAs($this->agent)->patch(route('tickets.update', $this->ticket), ['status' => 'in_progress']);
        $this->actingAs($this->agent)->patch(route('tickets.update', $this->ticket), ['status' => 'resolved']);

        $this->actingAs($this->requester)
            ->post(route('tickets.reopen', $this->ticket))
            ->assertSessionHasNoErrors();

        $ticket = $this->ticket->refresh();

        $this->assertSame(TicketStatus::Reopened, $ticket->status);
        $this->assertSame(1, $ticket->reopened_count);
        $this->assertNull($ticket->resolved_at);
        $this->assertNull($ticket->closed_at);
    }

    public function test_requester_cannot_update_ticket_status(): void
    {
        $this->actingAs($this->requester)
            ->patch(route('tickets.update', $this->ticket), ['status' => 'in_progress'])
            ->assertForbidden();
    }

    public function test_assigning_ticket_updates_assignee_and_status(): void
    {
        $this->actingAs($this->agent)
            ->post(route('tickets.assign', $this->ticket), ['assignee_id' => $this->agent->id])
            ->assertSessionHasNoErrors();

        $ticket = $this->ticket->refresh();

        $this->assertSame($this->agent->id, $ticket->assignee_id);
        $this->assertSame(TicketStatus::Assigned, $ticket->status);
        $this->assertNotNull($ticket->assigned_at);
        $this->assertDatabaseHas('ticket_events', [
            'ticket_id' => $ticket->id,
            'type' => 'assigned',
        ]);
    }

    public function test_requester_cannot_assign_ticket(): void
    {
        $this->actingAs($this->requester)
            ->post(route('tickets.assign', $this->ticket), ['assignee_id' => $this->requester->id])
            ->assertForbidden();
    }
}
