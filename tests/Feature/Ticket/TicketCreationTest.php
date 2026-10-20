<?php

namespace Tests\Feature\Ticket;

use App\Models\Category;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketCreationTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private Team $team;

    private User $requester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->team = Team::factory()->create(['name' => 'Thiết bị']);
        $this->category = Category::factory()->create([
            'name' => 'Máy in',
            'default_team_id' => $this->team->id,
        ]);
        $this->requester = User::factory()->requester()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'subject' => 'Không in được từ máy 305',
            'description' => 'Máy in không nhận lệnh in, đèn báo lỗi nhấp nháy từ sáng nay.',
            'category_id' => $this->category->id,
            'impact' => 2,
            'urgency' => 2,
        ], $overrides);
    }

    public function test_requester_can_create_ticket(): void
    {
        Notification::fake();
        User::factory()->admin()->create();

        $response = $this->actingAs($this->requester)->post(route('tickets.store'), $this->payload());

        $ticket = Ticket::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('tickets.show', $ticket));

        $this->assertMatchesRegularExpression('/^TK-\d{4}-\d{6}$/', $ticket->code);
        $this->assertSame('P3', $ticket->priority->value);
        $this->assertSame('new', $ticket->status->value);
        $this->assertSame($this->requester->id, $ticket->requester_id);
        $this->assertSame($this->team->id, $ticket->assigned_team_id);

        $this->assertDatabaseHas('ticket_events', [
            'ticket_id' => $ticket->id,
            'type' => 'created',
        ]);
        $this->assertDatabaseHas('ticket_status_histories', [
            'ticket_id' => $ticket->id,
            'to_status' => 'new',
        ]);
        $this->assertDatabaseHas('ticket_watchers', [
            'ticket_id' => $ticket->id,
            'user_id' => $this->requester->id,
        ]);

        Notification::assertCount(1);
    }

    public function test_ticket_code_increments_for_each_ticket(): void
    {
        $this->actingAs($this->requester)->post(route('tickets.store'), $this->payload());
        $this->actingAs($this->requester)->post(route('tickets.store'), $this->payload(['subject' => 'Máy in bị kẹt giấy']));

        $codes = Ticket::query()->orderBy('id')->pluck('code')->all();
        $year = now()->format('Y');

        $this->assertSame(["TK-{$year}-000001", "TK-{$year}-000002"], $codes);
    }

    public function test_priority_is_derived_from_impact_and_urgency_matrix(): void
    {
        $cases = [
            [3, 3, 'P1'],
            [3, 2, 'P2'],
            [2, 3, 'P2'],
            [2, 2, 'P3'],
            [1, 1, 'P4'],
        ];

        foreach ($cases as [$impact, $urgency, $expected]) {
            $this->actingAs($this->requester)->post(route('tickets.store'), $this->payload([
                'impact' => $impact,
                'urgency' => $urgency,
            ]));

            $this->assertSame(
                $expected,
                Ticket::query()->latest('id')->firstOrFail()->priority->value,
                "Impact {$impact} × Urgency {$urgency} phải là {$expected}",
            );
        }
    }

    public function test_ticket_requires_valid_category_and_description(): void
    {
        $response = $this->actingAs($this->requester)->post(route('tickets.store'), [
            'subject' => 'Thiếu danh mục',
            'description' => 'ngắn',
            'impact' => 9,
            'urgency' => 2,
        ]);

        $response->assertSessionHasErrors(['category_id', 'description', 'impact']);
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_attachments_are_stored_with_ticket(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('loi-may-in.pdf', 120, 'application/pdf');

        $this->actingAs($this->requester)->post(route('tickets.store'), $this->payload([
            'attachments' => [$file],
        ]))->assertSessionHasNoErrors();

        $attachment = TicketAttachment::query()->firstOrFail();

        $this->assertSame('loi-may-in.pdf', $attachment->original_name);
        Storage::disk('local')->assertExists($attachment->path);
        $this->assertDatabaseHas('ticket_events', [
            'ticket_id' => $attachment->ticket_id,
            'type' => 'attachment_added',
        ]);
    }
}
