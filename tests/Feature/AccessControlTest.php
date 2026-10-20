<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_requester_cannot_access_user_management(): void
    {
        $requester = User::factory()->requester()->create();

        $this->actingAs($requester)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_requester_cannot_access_catalog_management(): void
    {
        $requester = User::factory()->requester()->create();

        $this->actingAs($requester)
            ->get(route('admin.departments.index'))
            ->assertForbidden();

        $this->actingAs($requester)
            ->get(route('admin.teams.index'))
            ->assertForbidden();
    }

    public function test_agent_can_view_ticket_list_but_not_user_management(): void
    {
        $agent = User::factory()->agent()->create();

        $this->actingAs($agent)->get(route('tickets.index'))->assertOk();
        $this->actingAs($agent)->get(route('dashboard'))->assertOk();
        $this->actingAs($agent)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_lead_has_report_permission(): void
    {
        $lead = User::factory()->lead()->create();

        $this->assertTrue($lead->can('report.view'));
        $this->assertFalse($lead->can('user.manage'));
        $this->actingAs($lead)->get(route('tickets.index'))->assertOk();
    }

    public function test_admin_can_access_admin_screens(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.departments.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.locations.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.teams.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.skills.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.calendars.index'))->assertOk();
    }

    public function test_admin_bypasses_ticket_policy(): void
    {
        $admin = User::factory()->admin()->create();
        $requester = User::factory()->requester()->create();

        $ticket = Ticket::factory()->create(['requester_id' => $requester->id]);

        $this->assertTrue($admin->can('view', $ticket));
        $this->assertTrue($admin->can('update', $ticket));
        $this->assertTrue($admin->can('assign', $ticket));
    }

    public function test_admin_forms_are_rendered_successfully(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.users.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.departments.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.locations.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.teams.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.skills.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.calendars.create'))->assertOk();

        $member = User::factory()->agent()->create();
        $this->actingAs($admin)->get(route('admin.users.edit', $member))->assertOk();
    }

    public function test_requester_can_open_ticket_form_and_notifications(): void
    {
        $requester = User::factory()->requester()->create();

        $this->actingAs($requester)->get(route('tickets.create'))->assertOk();
        $this->actingAs($requester)->get(route('notifications.index'))->assertOk();
    }
}
