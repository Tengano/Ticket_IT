<?php

namespace Tests\Feature\Ticket;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $otherRequester;

    private User $agent;

    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->owner = User::factory()->requester()->create();
        $this->otherRequester = User::factory()->requester()->create();
        $this->agent = User::factory()->agent()->create();

        $this->ticket = Ticket::factory()->create([
            'requester_id' => $this->owner->id,
            'subject' => 'Không in được từ máy 305',
        ]);
    }

    public function test_requester_cannot_view_another_users_ticket(): void
    {
        $this->actingAs($this->otherRequester)
            ->get(route('tickets.show', $this->ticket))
            ->assertForbidden();
    }

    public function test_agent_can_view_any_ticket(): void
    {
        $this->actingAs($this->agent)
            ->get(route('tickets.show', $this->ticket))
            ->assertOk()
            ->assertSee('Không in được từ máy 305');
    }

    public function test_ticket_list_is_scoped_to_owner_for_requesters(): void
    {
        Ticket::factory()->create([
            'requester_id' => $this->otherRequester->id,
            'subject' => 'Ticket riêng của người khác',
        ]);

        $response = $this->actingAs($this->owner)->get(route('tickets.index'));

        $response->assertOk()
            ->assertSee('Không in được từ máy 305')
            ->assertDontSee('Ticket riêng của người khác');
    }

    public function test_internal_notes_are_hidden_from_requester(): void
    {
        TicketComment::factory()->create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->agent->id,
            'body' => 'Ghi chú nội bộ: kiểm tra log máy chủ in.',
            'type' => 'internal',
        ]);

        TicketComment::factory()->create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->agent->id,
            'body' => 'Phản hồi công khai: IT đang kiểm tra.',
            'type' => 'public',
        ]);

        $response = $this->actingAs($this->owner)->get(route('tickets.show', $this->ticket));

        $response->assertOk()
            ->assertSee('Phản hồi công khai: IT đang kiểm tra.')
            ->assertDontSee('Ghi chú nội bộ: kiểm tra log máy chủ in.')
            ->assertDontSee('đã thêm ghi chú nội bộ');
    }

    public function test_watcher_can_view_ticket(): void
    {
        $watcher = User::factory()->requester()->create();
        $this->ticket->watchers()->attach($watcher->id);

        $this->actingAs($watcher)
            ->get(route('tickets.show', $this->ticket))
            ->assertOk();
    }
}
