<?php

namespace Database\Seeders;

use App\Models\DanhMuc;
use App\Models\NhomXuLy;
use Illuminate\Database\Seeder;

class DanhMucSeeder extends Seeder
{
    public function run(): void
    {
        $nhomXuLys = NhomXuLy::query()->pluck('id', 'ma');

        $danhMucs = [
            'account' => [
                'ten' => 'Tài khoản & Mật khẩu',
                'nhom' => 'ho-tro-chung',
                'con' => [
                    'account-forgot' => 'Quên mật khẩu',
                    'account-locked' => 'Tài khoản bị khoá',
                    'account-access' => 'Cấp quyền truy cập',
                ],
            ],
            'network' => [
                'ten' => 'Mạng – Wifi',
                'nhom' => 'ha-tang-mang',
                'con' => [
                    'network-down' => 'Mất kết nối mạng',
                    'network-wifi' => 'Wifi chậm / không kết nối',
                    'network-vpn' => 'Lỗi VPN',
                ],
            ],
            'printer' => [
                'ten' => 'Máy in',
                'nhom' => 'thiet-bi',
                'con' => [
                    'printer-not-printing' => 'Không in được',
                    'printer-paper' => 'Kẹt giấy / lỗi khay giấy',
                    'printer-driver' => 'Cài đặt driver máy in',
                ],
            ],
            'hardware' => [
                'ten' => 'Phần cứng',
                'nhom' => 'thiet-bi',
                'con' => [
                    'hardware-power' => 'Máy không lên nguồn',
                    'hardware-display' => 'Màn hình / hiển thị',
                    'hardware-peripheral' => 'Bàn phím, chuột, thiết bị ngoại vi',
                ],
            ],
            'email' => [
                'ten' => 'Email',
                'nhom' => 'ung-dung-erp',
                'con' => [
                    'email-send' => 'Không gửi được email',
                    'email-full' => 'Hộp thư đầy',
                    'email-outlook' => 'Cấu hình Outlook',
                ],
            ],
            'erp' => [
                'ten' => 'Phần mềm – ERP',
                'nhom' => 'ung-dung-erp',
                'con' => [
                    'erp-login' => 'Lỗi đăng nhập ERP',
                    'erp-accounting' => 'Lỗi phân hệ kế toán',
                    'erp-install' => 'Cài đặt / cập nhật phần mềm',
                ],
            ],
            'academic' => [
                'ten' => 'Hệ thống học vụ',
                'nhom' => 'ung-dung-erp',
                'con' => [
                    'academic-lms' => 'Lỗi LMS',
                    'academic-enroll' => 'Đăng ký môn học',
                ],
            ],
            'security' => [
                'ten' => 'Bảo mật',
                'nhom' => 'bao-mat',
                'con' => [
                    'security-virus' => 'Virus / Malware',
                    'security-phishing' => 'Email lừa đảo',
                ],
            ],
        ];

        foreach ($danhMucs as $maCha => $danhMuc) {
            $cha = DanhMuc::query()->updateOrCreate(
                ['ma' => $maCha],
                [
                    'danh_muc_cha_id' => null,
                    'ten' => $danhMuc['ten'],
                    'nhom_mac_dinh_id' => $nhomXuLys[$danhMuc['nhom']] ?? null,
                    'uu_tien_mac_dinh' => 'P3',
                    'dang_hoat_dong' => true,
                ],
            );

            foreach ($danhMuc['con'] as $maCon => $tenCon) {
                DanhMuc::query()->updateOrCreate(
                    ['ma' => $maCon],
                    [
                        'danh_muc_cha_id' => $cha->id,
                        'ten' => $tenCon,
                        'nhom_mac_dinh_id' => $nhomXuLys[$danhMuc['nhom']] ?? null,
                        'uu_tien_mac_dinh' => $maCon === 'network-down' ? 'P2' : 'P3',
                        'dang_hoat_dong' => true,
                    ],
                );
            }
        }
    }
}
