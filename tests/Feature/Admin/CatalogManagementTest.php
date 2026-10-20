<?php

namespace Tests\Feature\Admin;

use App\Models\BusinessCalendar;
use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_create_and_update_department(): void
    {
        $this->actingAs($this->admin)->post(route('admin.departments.store'), [
            'code' => 'CNTT',
            'name' => 'Phòng Công nghệ thông tin',
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $department = Department::query()->where('code', 'CNTT')->firstOrFail();

        $this->actingAs($this->admin)->put(route('admin.departments.update', $department), [
            'code' => 'CNTT',
            'name' => 'Phòng CNTT & Chuyển đổi số',
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Phòng CNTT & Chuyển đổi số', $department->refresh()->name);
    }

    public function test_department_with_users_cannot_be_deleted(): void
    {
        $department = Department::factory()->create();
        User::factory()->create(['department_id' => $department->id]);

        $this->actingAs($this->admin)
            ->delete(route('admin.departments.destroy', $department))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    public function test_admin_can_create_location_and_skill(): void
    {
        $this->actingAs($this->admin)->post(route('admin.locations.store'), [
            'name' => 'Phòng 305',
            'building' => 'Tòa A',
            'floor' => 'Tầng 3',
            'zone' => 'Khu A',
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->admin)->post(route('admin.skills.store'), [
            'code' => 'network',
            'name' => 'Mạng – Wifi',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('locations', ['name' => 'Phòng 305', 'zone' => 'Khu A']);
        $this->assertDatabaseHas('skills', ['code' => 'network']);
    }

    public function test_admin_can_create_team_with_members(): void
    {
        $agent = User::factory()->agent()->create();
        $calendar = BusinessCalendar::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.teams.store'), [
            'code' => 'helpdesk',
            'name' => 'Hỗ trợ chung',
            'lead_id' => $agent->id,
            'business_calendar_id' => $calendar->id,
            'assignment_strategy' => 'skill_weighted',
            'capacity' => 20,
            'member_ids' => [$agent->id],
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $team = Team::query()->where('code', 'helpdesk')->firstOrFail();

        $this->assertSame($agent->id, $team->lead_id);
        $this->assertDatabaseHas('team_user', [
            'team_id' => $team->id,
            'user_id' => $agent->id,
            'is_lead' => true,
        ]);
        $this->assertSame($calendar->id, $team->business_calendar_id);
    }

    public function test_admin_can_create_calendar_with_hours_and_holidays(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.calendars.store'), [
            'name' => 'Lịch hành chính',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'is_default' => '1',
            'hours' => [
                ['weekday' => 1, 'start_time' => '08:00', 'end_time' => '12:00'],
                ['weekday' => 1, 'start_time' => '13:00', 'end_time' => '17:00'],
            ],
            'holidays' => [
                ['date' => '2026-09-02', 'name' => 'Quốc khánh'],
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $calendar = BusinessCalendar::query()->where('name', 'Lịch hành chính')->firstOrFail();

        $this->assertTrue($calendar->is_default);
        $this->assertSame(2, $calendar->hours()->count());
        $this->assertSame(1, $calendar->holidays()->count());
    }

    public function test_calendar_validation_rejects_invalid_time_range(): void
    {
        $this->actingAs($this->admin)->post(route('admin.calendars.store'), [
            'name' => 'Lịch sai',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'hours' => [
                ['weekday' => 1, 'start_time' => '17:00', 'end_time' => '08:00'],
            ],
        ])->assertSessionHasErrors('hours.0.end_time');
    }

    public function test_agent_cannot_manage_catalogs(): void
    {
        $agent = User::factory()->agent()->create();

        $this->actingAs($agent)->get(route('admin.locations.index'))->assertForbidden();
    }
}
