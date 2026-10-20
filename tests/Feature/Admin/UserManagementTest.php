<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\Skill;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_create_user_with_role_team_and_skills(): void
    {
        $department = Department::factory()->create();
        $team = Team::factory()->create();
        $skill = Skill::factory()->create();

        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Nguyễn Văn An',
            'email' => 'an.nguyen@ticketit.test',
            'phone' => '0901234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'agent',
            'department_id' => $department->id,
            'team_ids' => [$team->id],
            'skill_ids' => [$skill->id],
            'is_active' => '1',
            'is_vip' => '0',
        ]);

        $response->assertRedirect(route('admin.users.index'))->assertSessionHasNoErrors();

        $user = User::query()->where('email', 'an.nguyen@ticketit.test')->firstOrFail();

        $this->assertTrue($user->hasRole('agent'));
        $this->assertTrue($user->teams->contains($team->id));
        $this->assertSame(3, $user->skills->first()->pivot->level);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertSame($department->id, $user->department_id);
    }

    public function test_admin_can_update_user_role_and_details(): void
    {
        $user = User::factory()->requester()->create();

        $this->actingAs($this->admin)->put(route('admin.users.update', $user), [
            'name' => 'Trần Minh Khôi',
            'email' => 'khoi.tran@ticketit.test',
            'role' => 'lead',
            'is_active' => '1',
            'is_vip' => '1',
        ])->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame('Trần Minh Khôi', $user->name);
        $this->assertTrue($user->hasRole('lead'));
        $this->assertTrue($user->is_vip);
    }

    public function test_admin_can_toggle_user_status(): void
    {
        $user = User::factory()->requester()->create(['is_active' => true]);

        $this->actingAs($this->admin)
            ->post(route('admin.users.toggle', $user))
            ->assertSessionHasNoErrors();

        $this->assertFalse($user->refresh()->is_active);

        $this->actingAs($this->admin)->post(route('admin.users.toggle', $user));

        $this->assertTrue($user->refresh()->is_active);
    }

    public function test_admin_cannot_toggle_own_account(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.users.toggle', $this->admin))
            ->assertForbidden();
    }

    public function test_agent_cannot_manage_users(): void
    {
        $agent = User::factory()->agent()->create();

        $this->actingAs($agent)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_admin_can_delete_user_without_tickets(): void
    {
        $user = User::factory()->requester()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.users.destroy', $user))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_user_with_tickets_cannot_be_deleted(): void
    {
        $user = User::factory()->requester()->create();
        Ticket::factory()->create(['requester_id' => $user->id]);

        $this->actingAs($this->admin)
            ->delete(route('admin.users.destroy', $user))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
