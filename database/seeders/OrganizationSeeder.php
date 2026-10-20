<?php

namespace Database\Seeders;

use App\Models\BusinessCalendar;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\Location;
use App\Models\Skill;
use App\Models\Team;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['code' => 'CNTT', 'name' => 'Phòng Công nghệ thông tin'],
            ['code' => 'HCNS', 'name' => 'Phòng Hành chính – Nhân sự'],
            ['code' => 'KETOAN', 'name' => 'Phòng Kế toán'],
            ['code' => 'DAOTAO', 'name' => 'Phòng Đào tạo'],
            ['code' => 'BGD', 'name' => 'Ban Giám hiệu'],
        ];

        foreach ($departments as $department) {
            Department::query()->updateOrCreate(
                ['code' => $department['code']],
                ['name' => $department['name'], 'is_active' => true],
            );
        }

        $locations = [
            ['name' => 'Phòng 305', 'building' => 'Tòa A', 'floor' => 'Tầng 3', 'zone' => 'Khu A'],
            ['name' => 'Phòng 201', 'building' => 'Tòa A', 'floor' => 'Tầng 2', 'zone' => 'Khu A'],
            ['name' => 'Phòng Lab 1', 'building' => 'Tòa B', 'floor' => 'Tầng 1', 'zone' => 'Khu B'],
            ['name' => 'Thư viện', 'building' => 'Tòa C', 'floor' => 'Tầng 1', 'zone' => 'Khu C'],
            ['name' => 'Phòng họp lớn', 'building' => 'Tòa A', 'floor' => 'Tầng 5', 'zone' => 'Khu A'],
        ];

        foreach ($locations as $location) {
            Location::query()->updateOrCreate(
                ['name' => $location['name'], 'building' => $location['building']],
                $location + ['is_active' => true],
            );
        }

        $calendar = BusinessCalendar::query()->firstOrCreate(
            ['name' => 'Lịch hành chính'],
            ['timezone' => 'Asia/Ho_Chi_Minh', 'is_default' => true],
        );

        if ($calendar->hours()->count() === 0) {
            foreach (range(1, 5) as $weekday) {
                $calendar->hours()->createMany([
                    ['weekday' => $weekday, 'start_time' => '08:00', 'end_time' => '12:00'],
                    ['weekday' => $weekday, 'start_time' => '13:00', 'end_time' => '17:00'],
                ]);
            }

            $calendar->hours()->create([
                'weekday' => 6,
                'start_time' => '08:00',
                'end_time' => '11:30',
            ]);
        }

        $holidays = [
            ['date' => '2026-01-01', 'name' => 'Tết Dương lịch'],
            ['date' => '2026-02-16', 'name' => 'Tết Nguyên đán'],
            ['date' => '2026-02-17', 'name' => 'Tết Nguyên đán'],
            ['date' => '2026-02-18', 'name' => 'Tết Nguyên đán'],
            ['date' => '2026-02-19', 'name' => 'Tết Nguyên đán'],
            ['date' => '2026-02-20', 'name' => 'Tết Nguyên đán'],
            ['date' => '2026-04-26', 'name' => 'Giỗ Tổ Hùng Vương'],
            ['date' => '2026-04-30', 'name' => 'Ngày Giải phóng miền Nam'],
            ['date' => '2026-05-01', 'name' => 'Ngày Quốc tế Lao động'],
            ['date' => '2026-09-02', 'name' => 'Quốc khánh'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::query()->updateOrCreate(
                ['business_calendar_id' => $calendar->id, 'date' => $holiday['date']],
                ['name' => $holiday['name']],
            );
        }

        $teams = [
            ['code' => 'helpdesk', 'name' => 'Hỗ trợ chung', 'description' => 'Tiếp nhận và xử lý yêu cầu chung', 'capacity' => 30],
            ['code' => 'infrastructure', 'name' => 'Hạ tầng – Mạng', 'description' => 'Mạng, wifi, VPN, máy chủ', 'capacity' => 15],
            ['code' => 'devices', 'name' => 'Thiết bị', 'description' => 'Máy tính, máy in, thiết bị ngoại vi', 'capacity' => 15],
            ['code' => 'applications', 'name' => 'Ứng dụng – ERP', 'description' => 'ERP, LMS, email, phần mềm nghiệp vụ', 'capacity' => 15],
            ['code' => 'security', 'name' => 'Bảo mật', 'description' => 'Virus, phishing, sự cố an toàn thông tin', 'capacity' => 10],
        ];

        foreach ($teams as $team) {
            Team::query()->updateOrCreate(
                ['code' => $team['code']],
                $team + [
                    'business_calendar_id' => $calendar->id,
                    'assignment_strategy' => 'skill_weighted',
                    'is_active' => true,
                ],
            );
        }

        $skills = [
            ['code' => 'windows', 'name' => 'Windows / Máy tính'],
            ['code' => 'network', 'name' => 'Mạng – Wifi'],
            ['code' => 'printer', 'name' => 'Máy in'],
            ['code' => 'hardware', 'name' => 'Phần cứng'],
            ['code' => 'email', 'name' => 'Email / Outlook'],
            ['code' => 'erp', 'name' => 'ERP – Phân hệ kế toán'],
            ['code' => 'lms', 'name' => 'Hệ thống học vụ (LMS)'],
            ['code' => 'security', 'name' => 'An toàn thông tin'],
            ['code' => 'account', 'name' => 'Tài khoản & Mật khẩu'],
        ];

        foreach ($skills as $skill) {
            Skill::query()->updateOrCreate(
                ['code' => $skill['code']],
                ['name' => $skill['name']],
            );
        }
    }
}
