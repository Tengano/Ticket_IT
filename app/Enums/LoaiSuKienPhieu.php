<?php

namespace App\Enums;

enum LoaiSuKienPhieu: string
{
    case TaoMoi = 'tao_moi';
    case CapNhat = 'cap_nhat';
    case DoiTrangThai = 'doi_trang_thai';
    case DoiUuTien = 'doi_uu_tien';
    case PhanCong = 'phan_cong';
    case BinhLuan = 'binh_luan';
    case GhiChuNoiBo = 'ghi_chu_noi_bo';
    case DinhKemTep = 'dinh_kem_tep';
    case MoLai = 'mo_lai';
    case DongPhieu = 'dong_phieu';
    case ThemNguoiTheoDoi = 'them_nguoi_theo_doi';

    public function label(): string
    {
        return match ($this) {
            self::TaoMoi => 'Tạo phiếu',
            self::CapNhat => 'Cập nhật thông tin phiếu',
            self::DoiTrangThai => 'Đổi trạng thái',
            self::DoiUuTien => 'Đổi mức ưu tiên',
            self::PhanCong => 'Phân công',
            self::BinhLuan => 'Bình luận',
            self::GhiChuNoiBo => 'Ghi chú nội bộ',
            self::DinhKemTep => 'Đính kèm tệp',
            self::MoLai => 'Mở lại phiếu',
            self::DongPhieu => 'Đóng phiếu',
            self::ThemNguoiTheoDoi => 'Thêm người theo dõi',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::TaoMoi => '＋',
            self::CapNhat => '✎',
            self::DoiTrangThai => '⇄',
            self::DoiUuTien => '⚑',
            self::PhanCong => '➜',
            self::BinhLuan => '💬',
            self::GhiChuNoiBo => '🔒',
            self::DinhKemTep => '📎',
            self::MoLai => '↺',
            self::DongPhieu => '✔',
            self::ThemNguoiTheoDoi => '👁',
        };
    }
}
