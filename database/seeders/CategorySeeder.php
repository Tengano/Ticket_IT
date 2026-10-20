<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Team;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $teams = Team::query()->pluck('id', 'code');

        $categories = [
            'account' => [
                'name' => 'Tài khoản & Mật khẩu',
                'team' => 'helpdesk',
                'children' => [
                    'account-forgot' => 'Quên mật khẩu',
                    'account-locked' => 'Tài khoản bị khoá',
                    'account-access' => 'Cấp quyền truy cập',
                ],
            ],
            'network' => [
                'name' => 'Mạng – Wifi',
                'team' => 'infrastructure',
                'children' => [
                    'network-down' => 'Mất kết nối mạng',
                    'network-wifi' => 'Wifi chậm / không kết nối',
                    'network-vpn' => 'Lỗi VPN',
                ],
            ],
            'printer' => [
                'name' => 'Máy in',
                'team' => 'devices',
                'children' => [
                    'printer-not-printing' => 'Không in được',
                    'printer-paper' => 'Kẹt giấy / lỗi khay giấy',
                    'printer-driver' => 'Cài đặt driver máy in',
                ],
            ],
            'hardware' => [
                'name' => 'Phần cứng',
                'team' => 'devices',
                'children' => [
                    'hardware-power' => 'Máy không lên nguồn',
                    'hardware-display' => 'Màn hình / hiển thị',
                    'hardware-peripheral' => 'Bàn phím, chuột, thiết bị ngoại vi',
                ],
            ],
            'email' => [
                'name' => 'Email',
                'team' => 'applications',
                'children' => [
                    'email-send' => 'Không gửi được email',
                    'email-full' => 'Hộp thư đầy',
                    'email-outlook' => 'Cấu hình Outlook',
                ],
            ],
            'erp' => [
                'name' => 'Phần mềm – ERP',
                'team' => 'applications',
                'children' => [
                    'erp-login' => 'Lỗi đăng nhập ERP',
                    'erp-accounting' => 'Lỗi phân hệ kế toán',
                    'erp-install' => 'Cài đặt / cập nhật phần mềm',
                ],
            ],
            'academic' => [
                'name' => 'Hệ thống học vụ',
                'team' => 'applications',
                'children' => [
                    'academic-lms' => 'Lỗi LMS',
                    'academic-enroll' => 'Đăng ký môn học',
                ],
            ],
            'security' => [
                'name' => 'Bảo mật',
                'team' => 'security',
                'children' => [
                    'security-virus' => 'Virus / Malware',
                    'security-phishing' => 'Email lừa đảo',
                ],
            ],
        ];

        foreach ($categories as $parentCode => $parent) {
            $parentModel = Category::query()->updateOrCreate(
                ['code' => $parentCode],
                [
                    'parent_id' => null,
                    'name' => $parent['name'],
                    'default_team_id' => $teams[$parent['team']] ?? null,
                    'default_priority' => 'P3',
                    'required_skills' => [],
                    'is_active' => true,
                ],
            );

            foreach ($parent['children'] as $childCode => $childName) {
                Category::query()->updateOrCreate(
                    ['code' => $childCode],
                    [
                        'parent_id' => $parentModel->id,
                        'name' => $childName,
                        'default_team_id' => $teams[$parent['team']] ?? null,
                        'default_priority' => $childCode === 'network-down' ? 'P2' : 'P3',
                        'required_skills' => [],
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
