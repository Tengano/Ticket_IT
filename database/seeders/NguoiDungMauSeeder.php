<?php

namespace Database\Seeders;

use App\Models\NguoiDung;
use App\Models\NhomXuLy;
use App\Models\PhongBan;
use App\Models\ViTri;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NguoiDungMauSeeder extends Seeder
{
    public function run(): void
    {
        $phongBans = PhongBan::query()->pluck('id', 'ma');
        $viTris = ViTri::query()->pluck('id', 'ten');
        $nhomXuLys = NhomXuLy::query()->pluck('id', 'ma');

        $nguoiDungs = [
            [
                'ten' => 'Quản trị hệ thống',
                'email' => 'admin@ticketit.test',
                'role' => 'admin',
                'phong_ban' => 'CNTT',
                'vi_tri' => 'Phòng 305',
                'nhom' => [],
            ],
            [
                'ten' => 'Trần Minh Khôi',
                'email' => 'lead@ticketit.test',
                'role' => 'lead',
                'phong_ban' => 'CNTT',
                'vi_tri' => 'Phòng 305',
                'nhom' => ['ho-tro-chung'],
            ],
            [
                'ten' => 'Nguyễn Văn An',
                'email' => 'agent1@ticketit.test',
                'role' => 'agent',
                'phong_ban' => 'CNTT',
                'vi_tri' => 'Phòng 305',
                'nhom' => ['ho-tro-chung', 'ha-tang-mang'],
            ],
            [
                'ten' => 'Lê Thị Bình',
                'email' => 'agent2@ticketit.test',
                'role' => 'agent',
                'phong_ban' => 'CNTT',
                'vi_tri' => 'Phòng 201',
                'nhom' => ['thiet-bi', 'ung-dung-erp'],
            ],
            [
                'ten' => 'Phạm Văn Sinh',
                'email' => 'requester@ticketit.test',
                'role' => 'requester',
                'phong_ban' => 'HCNS',
                'vi_tri' => 'Phòng 305',
                'nhom' => [],
            ],
            [
                'ten' => 'Hoàng Thị Lan',
                'email' => 'requester2@ticketit.test',
                'role' => 'requester',
                'phong_ban' => 'KETOAN',
                'vi_tri' => 'Phòng 201',
                'nhom' => [],
            ],
            [
                'ten' => 'Đặng Quốc Bảo',
                'email' => 'director@ticketit.test',
                'role' => 'requester',
                'phong_ban' => 'BGD',
                'vi_tri' => 'Phòng họp lớn',
                'nhom' => [],
                'la_vip' => true,
            ],
        ];

        DB::transaction(function () use ($nguoiDungs, $phongBans, $viTris, $nhomXuLys): void {
            foreach ($nguoiDungs as $duLieu) {
                $nguoiDung = NguoiDung::query()->updateOrCreate(
                    ['email' => $duLieu['email']],
                    [
                        'ten' => $duLieu['ten'],
                        'password' => 'password',
                        'phong_ban_id' => $phongBans[$duLieu['phong_ban']] ?? null,
                        'vi_tri_id' => $viTris[$duLieu['vi_tri']] ?? null,
                        'dang_hoat_dong' => true,
                        'la_vip' => $duLieu['la_vip'] ?? false,
                        'email_verified_at' => now(),
                    ],
                );

                $nguoiDung->syncRoles([$duLieu['role']]);

                $pivot = collect($duLieu['nhom'])
                    ->filter(fn (string $ma) => isset($nhomXuLys[$ma]))
                    ->mapWithKeys(fn (string $ma) => [
                        $nhomXuLys[$ma] => [
                            'la_truong_nhom' => $ma === 'ho-tro-chung' && $duLieu['role'] === 'lead',
                            'dinh_muc' => null,
                        ],
                    ])
                    ->all();

                $nguoiDung->teams()->sync($pivot);
            }

            $truongNhom = NguoiDung::query()->where('email', 'lead@ticketit.test')->first();

            if ($truongNhom !== null && isset($nhomXuLys['ho-tro-chung'])) {
                NhomXuLy::query()->whereKey($nhomXuLys['ho-tro-chung'])->update(['truong_nhom_id' => $truongNhom->id]);
            }
        });
    }
}
