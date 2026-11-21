<?php

namespace Database\Seeders;

use App\Models\NhomXuLy;
use App\Models\PhongBan;
use App\Models\ViTri;
use Illuminate\Database\Seeder;

class ToChucSeeder extends Seeder
{
    public function run(): void
    {
        $phongBans = [
            ['ma' => 'CNTT', 'ten' => 'Phòng Công nghệ thông tin'],
            ['ma' => 'HCNS', 'ten' => 'Phòng Hành chính – Nhân sự'],
            ['ma' => 'KETOAN', 'ten' => 'Phòng Kế toán'],
            ['ma' => 'DAOTAO', 'ten' => 'Phòng Đào tạo'],
            ['ma' => 'BGD', 'ten' => 'Ban Giám hiệu'],
        ];

        foreach ($phongBans as $phongBan) {
            PhongBan::query()->updateOrCreate(
                ['ma' => $phongBan['ma']],
                ['ten' => $phongBan['ten'], 'dang_hoat_dong' => true],
            );
        }

        $viTris = [
            ['ten' => 'Phòng 305'],
            ['ten' => 'Phòng 201'],
            ['ten' => 'Phòng Lab 1'],
            ['ten' => 'Thư viện'],
            ['ten' => 'Phòng họp lớn'],
        ];

        foreach ($viTris as $viTri) {
            ViTri::query()->updateOrCreate(
                ['ten' => $viTri['ten']],
                $viTri + ['dang_hoat_dong' => true],
            );
        }

        $nhomXuLys = [
            ['ma' => 'ho-tro-chung', 'ten' => 'Hỗ trợ chung', 'mo_ta' => 'Tiếp nhận và xử lý yêu cầu chung', 'dinh_muc' => 30],
            ['ma' => 'ha-tang-mang', 'ten' => 'Hạ tầng – Mạng', 'mo_ta' => 'Mạng, wifi, VPN, máy chủ', 'dinh_muc' => 15],
            ['ma' => 'thiet-bi', 'ten' => 'Thiết bị', 'mo_ta' => 'Máy tính, máy in, thiết bị ngoại vi', 'dinh_muc' => 15],
            ['ma' => 'ung-dung-erp', 'ten' => 'Ứng dụng – ERP', 'mo_ta' => 'ERP, LMS, email, phần mềm nghiệp vụ', 'dinh_muc' => 15],
            ['ma' => 'bao-mat', 'ten' => 'Bảo mật', 'mo_ta' => 'Virus, phishing, sự cố an toàn thông tin', 'dinh_muc' => 10],
        ];

        foreach ($nhomXuLys as $nhomXuLy) {
            NhomXuLy::query()->updateOrCreate(
                ['ma' => $nhomXuLy['ma']],
                $nhomXuLy + [
                    'chien_luoc_phan_cong' => 'khop_ky_nang',
                    'dang_hoat_dong' => true,
                ],
            );
        }
    }
}
