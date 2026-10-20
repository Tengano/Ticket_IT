<?php

namespace Tests\Feature\Ticket;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\User;
use App\Notifications\TicketNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TicketCommentTest extends TestCase
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
        $this->ticket = Ticket::factory()->create(['requester_id' => $this->requester->id]);
    }

    public function test_agent_public_reply_marks_first_response_and_notifies_requester(): void
    {
        Notification::fake();

        $this->actingAs($this->agent)
            ->post(route('tickets.comments.store', $this->ticket), [
                'body' => 'Xin chào, IT đã tiếp nhận và đang kiểm tra máy in.',
                'type' => 'public',
            ])
            ->assertSessionHasNoErrors();

        $this->ticket->refresh();
        $comment = TicketComment::query()->firstOrFail();

        $this->assertNotNull($this->ticket->first_responded_at);
        $this->assertTrue($comment->is_first_response);
        $this->assertDatabaseHas('ticket_events', [
            'ticket_id' => $this->ticket->id,
            'type' => 'commented',
        ]);

        Notification::assertSentTo($this->requester, TicketNotification::class);
    }

    public function test_second_agent_reply_is_not_first_response(): void
    {
        $this->actingAs($this->agent)->post(route('tickets.comments.store', $this->ticket), [
            'body' => 'Phản hồi đầu tiên.',
            'type' => 'public',
        ]);

        $this->actingAs($this->agent)->post(route('tickets.comments.store', $this->ticket), [
            'body' => 'Phản hồi thứ hai.',
            'type' => 'public',
        ]);

        $this->assertSame(1, TicketComment::query()->where('is_first_response', true)->count());
    }

    public function test_requester_cannot_add_internal_note(): void
    {
        $this->actingAs($this->requester)
            ->post(route('tickets.comments.store', $this->ticket), [
                'body' => 'Cố gắng thêm ghi chú nội bộ.',
                'type' => 'internal',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('ticket_comments', 0);
    }

    public function test_agent_can_add_internal_note(): void
    {
        $this->actingAs($this->agent)
            ->post(route('tickets.comments.store', $this->ticket), [
                'body' => 'Đã kiểm tra driver máy in, cần thay cáp mạng.',
                'type' => 'internal',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $this->ticket->id,
            'type' => 'internal',
        ]);
        $this->assertDatabaseHas('ticket_events', [
            'ticket_id' => $this->ticket->id,
            'type' => 'internal_note_added',
        ]);
    }

    public function test_requester_cannot_comment_on_another_users_ticket(): void
    {
        $other = User::factory()->requester()->create();

        $this->actingAs($other)
            ->post(route('tickets.comments.store', $this->ticket), [
                'body' => 'Tôi muốn bình luận vào ticket người khác.',
                'type' => 'public',
            ])
            ->assertForbidden();
    }

    public function test_requester_cannot_download_internal_attachment(): void
    {
        $comment = TicketComment::factory()->internal()->create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->agent->id,
        ]);

        $attachment = TicketAttachment::factory()->create([
            'ticket_id' => $this->ticket->id,
            'ticket_comment_id' => $comment->id,
            'uploaded_by' => $this->agent->id,
        ]);

        $this->actingAs($this->requester)
            ->get(route('tickets.attachments.download', [$this->ticket, $attachment]))
            ->assertForbidden();
    }
}
