<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Location;
use App\Models\Skill;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $departments = Department::query()->pluck('id', 'code');
        $locations = Location::query()->pluck('id', 'name');
        $teams = Team::query()->pluck('id', 'code');
        $skills = Skill::query()->pluck('id', 'code');

        $users = [
            [
                'name' => 'Quản trị hệ thống',
                'email' => 'admin@ticketit.test',
                'role' => 'admin',
                'department' => 'CNTT',
                'location' => 'Phòng 305',
                'teams' => [],
                'skills' => [],
            ],
            [
                'name' => 'Trần Minh Khôi',
                'email' => 'lead@ticketit.test',
                'role' => 'lead',
                'department' => 'CNTT',
                'location' => 'Phòng 305',
                'teams' => ['helpdesk'],
                'skills' => ['network' => 3, 'windows' => 3, 'account' => 2, 'security' => 2],
            ],
            [
                'name' => 'Nguyễn Văn An',
                'email' => 'agent1@ticketit.test',
                'role' => 'agent',
                'department' => 'CNTT',
                'location' => 'Phòng 305',
                'teams' => ['helpdesk', 'infrastructure'],
                'skills' => ['network' => 4, 'windows' => 3, 'hardware' => 2, 'account' => 3],
            ],
            [
                'name' => 'Lê Thị Bình',
                'email' => 'agent2@ticketit.test',
                'role' => 'agent',
                'department' => 'CNTT',
                'location' => 'Phòng 201',
                'teams' => ['devices', 'applications'],
                'skills' => ['printer' => 4, 'hardware' => 4, 'windows' => 3, 'erp' => 2],
            ],
            [
                'name' => 'Phạm Văn Sinh',
                'email' => 'requester@ticketit.test',
                'role' => 'requester',
                'department' => 'HCNS',
                'location' => 'Phòng 305',
                'teams' => [],
                'skills' => [],
            ],
            [
                'name' => 'Hoàng Thị Lan',
                'email' => 'requester2@ticketit.test',
                'role' => 'requester',
                'department' => 'KETOAN',
                'location' => 'Phòng 201',
                'teams' => [],
                'skills' => [],
            ],
            [
                'name' => 'Đặng Quốc Bảo',
                'email' => 'director@ticketit.test',
                'role' => 'requester',
                'department' => 'BGD',
                'location' => 'Phòng họp lớn',
                'teams' => [],
                'skills' => [],
                'is_vip' => true,
            ],
        ];

        DB::transaction(function () use ($users, $departments, $locations, $teams, $skills): void {
            foreach ($users as $data) {
                $user = User::query()->updateOrCreate(
                    ['email' => $data['email']],
                    [
                        'name' => $data['name'],
                        'password' => 'password',
                        'department_id' => $departments[$data['department']] ?? null,
                        'location_id' => $locations[$data['location']] ?? null,
                        'is_active' => true,
                        'is_vip' => $data['is_vip'] ?? false,
                        'email_verified_at' => now(),
                    ],
                );

                $user->syncRoles([$data['role']]);

                $teamPivot = collect($data['teams'])
                    ->filter(fn (string $code) => isset($teams[$code]))
                    ->mapWithKeys(fn (string $code) => [
                        $teams[$code] => ['is_lead' => $code === 'helpdesk' && $data['role'] === 'lead', 'capacity' => null],
                    ])
                    ->all();

                $user->teams()->sync($teamPivot);

                $skillPivot = collect($data['skills'])
                    ->filter(fn (int $level, string $code) => isset($skills[$code]))
                    ->mapWithKeys(fn (int $level, string $code) => [$skills[$code] => ['level' => $level]])
                    ->all();

                $user->skills()->sync($skillPivot);
            }

            $lead = User::query()->where('email', 'lead@ticketit.test')->first();

            if ($lead !== null && isset($teams['helpdesk'])) {
                Team::query()->whereKey($teams['helpdesk'])->update(['lead_id' => $lead->id]);
            }
        });
    }
}
