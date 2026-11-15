<?php

namespace Database\Seeders;

use App\Enums\LoaiSuKienPhieu;
use App\Enums\TrangThaiPhieu;
use App\Models\DanhMuc;
use App\Models\NguoiDung;
use App\Models\NhomXuLy;
use App\Models\PhieuYeuCau;
use App\Services\Phieu\DichVuPhieuYeuCau;
use Illuminate\Database\Seeder;

class PhieuYeuCauMauSeeder extends Seeder
{
    public function run(DichVuPhieuYeuCau $dichVu): void
    {
        $danhMucs = DanhMuc::query()->pluck('id', 'ma');
        $nhomXuLys = NhomXuLy::query()->pluck('id', 'ma');
        $nguoiDungs = NguoiDung::query()->pluck('id', 'email');

        $mau = [
            [
                'tieu_de' => 'Không in được từ máy 305',
                'mo_ta' => 'Máy in HP LaserJet ở phòng 305 không nhận lệnh in, đèn báo lỗi nhấp nháy. Đã thử in từ 2 máy tính khác nhau.',
                'danh_muc' => 'printer-not-printing', 'anh_huong' => 2, 'khan_cap' => 2,
                'nguoi_yeu_cau' => 'requester@ticketit.test', 'nguoi_xu_ly' => 'agent2@ticketit.test',
                'nhom' => 'thiet-bi', 'tien_do' => 'dang_xu_ly', 'so_ngay_truoc' => 1,
            ],
            [
                'tieu_de' => 'Sập mạng toàn bộ phòng 305',
                'mo_ta' => 'Cả phòng 305 mất kết nối mạng và wifi từ 8h sáng. Khoảng 25 người không truy cập được ERP và email.',
                'danh_muc' => 'network-down', 'anh_huong' => 3, 'khan_cap' => 3,
                'nguoi_yeu_cau' => 'requester@ticketit.test', 'nguoi_xu_ly' => 'lead@ticketit.test',
                'nhom' => 'ha-tang-mang', 'tien_do' => 'dang_xu_ly', 'so_ngay_truoc' => 0,
            ],
            [
                'tieu_de' => 'Quên mật khẩu tài khoản LMS',
                'mo_ta' => 'Tôi quên mật khẩu đăng nhập LMS, cần cấp lại để kịp nhập điểm cho lớp CNTT K12.',
                'danh_muc' => 'account-forgot', 'anh_huong' => 1, 'khan_cap' => 2,
                'nguoi_yeu_cau' => 'requester2@ticketit.test', 'nguoi_xu_ly' => 'agent1@ticketit.test',
                'nhom' => 'ho-tro-chung', 'tien_do' => 'da_dong', 'so_ngay_truoc' => 4,
            ],
            [
                'tieu_de' => 'Email không gửi được cho sinh viên',
                'mo_ta' => 'Khi gửi email thông báo lịch thi, hệ thống trả về lỗi Message not delivered. Đã thử gửi lại nhiều lần.',
                'danh_muc' => 'email-send', 'anh_huong' => 2, 'khan_cap' => 2,
                'nguoi_yeu_cau' => 'requester2@ticketit.test', 'nguoi_xu_ly' => null,
                'nhom' => 'ung-dung-erp', 'tien_do' => 'moi', 'so_ngay_truoc' => 0,
            ],
            [
                'tieu_de' => 'Lỗi đăng nhập ERP phân hệ kế toán',
                'mo_ta' => 'Tài khoản kế toán báo Invalid company code sau khi đổi mật khẩu Windows. Cần hỗ trợ trước 14h để chốt số liệu.',
                'danh_muc' => 'erp-login', 'anh_huong' => 2, 'khan_cap' => 3,
                'nguoi_yeu_cau' => 'requester2@ticketit.test', 'nguoi_xu_ly' => null,
                'nhom' => 'ung-dung-erp', 'tien_do' => 'moi', 'so_ngay_truoc' => 0,
            ],
            [
                'tieu_de' => 'Máy tính phòng 201 không lên nguồn',
                'mo_ta' => 'Máy tính để bàn không lên nguồn, quạt nguồn không quay. Đã kiểm tra dây điện và ổ cắm.',
                'danh_muc' => 'hardware-power', 'anh_huong' => 1, 'khan_cap' => 3,
                'nguoi_yeu_cau' => 'requester2@ticketit.test', 'nguoi_xu_ly' => 'agent2@ticketit.test',
                'nhom' => 'thiet-bi', 'tien_do' => 'da_giai_quyet', 'so_ngay_truoc' => 3,
            ],
            [
                'tieu_de' => 'Email lừa đảo mạo danh phòng đào tạo',
                'mo_ta' => 'Tôi nhận được email yêu cầu đăng nhập lại cổng thông tin bằng liên kết lạ, nghi ngờ bị phishing.',
                'danh_muc' => 'security-phishing', 'anh_huong' => 2, 'khan_cap' => 2,
                'nguoi_yeu_cau' => 'director@ticketit.test', 'nguoi_xu_ly' => null,
                'nhom' => 'bao-mat', 'tien_do' => 'moi', 'so_ngay_truoc' => 0,
            ],
        ];

        foreach ($mau as $mauPhieu) {
            if (PhieuYeuCau::query()->where('tieu_de', $mauPhieu['tieu_de'])->exists()) {
                continue;
            }

            $nguoiYeuCau = NguoiDung::query()->findOrFail($nguoiDungs[$mauPhieu['nguoi_yeu_cau']]);
            $nguoiXuLy = $mauPhieu['nguoi_xu_ly'] !== null
                ? NguoiDung::query()->find($nguoiDungs[$mauPhieu['nguoi_xu_ly']])
                : null;

            $phieu = $dichVu->create($nguoiYeuCau, [
                'tieu_de' => $mauPhieu['tieu_de'],
                'mo_ta' => $mauPhieu['mo_ta'],
                'danh_muc_id' => $danhMucs[$mauPhieu['danh_muc']] ?? null,
                'anh_huong' => $mauPhieu['anh_huong'],
                'khan_cap' => $mauPhieu['khan_cap'],
                'kenh' => 'web',
            ]);

            $phieu->forceFill([
                'created_at' => now()->subDays($mauPhieu['so_ngay_truoc'])->subMinutes(37),
                'updated_at' => now()->subDays($mauPhieu['so_ngay_truoc'])->subMinutes(37),
            ])->save();

            if ($nguoiXuLy !== null) {
                $nhom = isset($nhomXuLys[$mauPhieu['nhom']])
                    ? NhomXuLy::query()->find($nhomXuLys[$mauPhieu['nhom']])
                    : null;

                $phieu = $dichVu->assign($phieu, $nguoiXuLy, $nhom, $nguoiXuLy);
            }

            if ($mauPhieu['tien_do'] !== 'moi') {
                $phieu = $dichVu->changeStatus($phieu, TrangThaiPhieu::DangXuLy, $nguoiXuLy ?? $nguoiYeuCau);
                $phieu->update(['phan_hoi_dau_luc' => now()->subDays($mauPhieu['so_ngay_truoc'])->subMinutes(20)]);

                $this->taoTraoDoi($dichVu, $phieu, $nguoiYeuCau, $nguoiXuLy);
            }

            if (in_array($mauPhieu['tien_do'], ['da_giai_quyet', 'da_dong'], true)) {
                $phieu = $dichVu->changeStatus($phieu, TrangThaiPhieu::DaGiaiQuyet, $nguoiXuLy ?? $nguoiYeuCau, 'Đã khắc phục sự cố');
            }

            if ($mauPhieu['tien_do'] === 'da_dong') {
                $dichVu->changeStatus($phieu, TrangThaiPhieu::DaDong, $nguoiXuLy ?? $nguoiYeuCau, 'Người dùng xác nhận đã ổn');
            }
        }
    }

    private function taoTraoDoi(DichVuPhieuYeuCau $dichVu, PhieuYeuCau $phieu, NguoiDung $nguoiYeuCau, ?NguoiDung $nguoiXuLy): void
    {
        $nhanVien = $nguoiXuLy ?? NguoiDung::query()->where('email', 'agent1@ticketit.test')->first();

        if ($nhanVien === null) {
            return;
        }

        $phieu->comments()->create([
            'nguoi_dung_id' => $nguoiYeuCau->id,
            'noi_dung' => 'Nhờ IT kiểm tra giúp, việc này đang ảnh hưởng đến công việc trong ngày.',
            'loai' => 'cong_khai',
        ]);

        $phanHoi = $phieu->comments()->create([
            'nguoi_dung_id' => $nhanVien->id,
            'noi_dung' => 'Chào bạn, IT đã tiếp nhận và đang kiểm tra. Mình sẽ cập nhật tiến độ trong phiếu này.',
            'loai' => 'cong_khai',
            'la_phan_hoi_dau' => true,
        ]);

        $dichVu->recordEvent($phieu, $nhanVien, LoaiSuKienPhieu::BinhLuan, null, null, ['comment_id' => $phanHoi->id]);

        $ghiChu = $phieu->comments()->create([
            'nguoi_dung_id' => $nhanVien->id,
            'noi_dung' => 'Đã ghi nhận thông tin từ người dùng, kiểm tra log thiết bị trước khi hẹn lịch xuống phòng.',
            'loai' => 'noi_bo',
        ]);

        $dichVu->recordEvent($phieu, $nhanVien, LoaiSuKienPhieu::GhiChuNoiBo, null, null, ['comment_id' => $ghiChu->id]);
    }
}
