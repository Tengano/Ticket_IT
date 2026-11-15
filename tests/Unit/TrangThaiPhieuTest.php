<?php

namespace Tests\Unit;

use App\Enums\TrangThaiPhieu;
use PHPUnit\Framework\TestCase;

class TrangThaiPhieuTest extends TestCase
{
    public function test_phieu_moi_co_the_bat_dau_xu_ly_nhung_khong_giai_quyet_truc_tiep(): void
    {
        $this->assertTrue(TrangThaiPhieu::Moi->canTransitionTo(TrangThaiPhieu::DangXuLy));
        $this->assertTrue(TrangThaiPhieu::Moi->canTransitionTo(TrangThaiPhieu::DaPhanCong));
        $this->assertFalse(TrangThaiPhieu::Moi->canTransitionTo(TrangThaiPhieu::DaGiaiQuyet));
        $this->assertFalse(TrangThaiPhieu::Moi->canTransitionTo(TrangThaiPhieu::MoLai));
    }

    public function test_phieu_da_dong_chi_co_the_mo_lai(): void
    {
        $this->assertTrue(TrangThaiPhieu::DaDong->canTransitionTo(TrangThaiPhieu::MoLai));
        $this->assertFalse(TrangThaiPhieu::DaDong->canTransitionTo(TrangThaiPhieu::DangXuLy));
        $this->assertFalse(TrangThaiPhieu::DaDong->canTransitionTo(TrangThaiPhieu::DaGiaiQuyet));
    }

    public function test_phieu_da_giai_quyet_co_the_dong_hoac_mo_lai(): void
    {
        $this->assertTrue(TrangThaiPhieu::DaGiaiQuyet->canTransitionTo(TrangThaiPhieu::DaDong));
        $this->assertTrue(TrangThaiPhieu::DaGiaiQuyet->canTransitionTo(TrangThaiPhieu::MoLai));
        $this->assertFalse(TrangThaiPhieu::DaGiaiQuyet->canTransitionTo(TrangThaiPhieu::DangXuLy));
    }

    public function test_phieu_dang_cho_co_the_tiep_tuc_xu_ly(): void
    {
        $this->assertTrue(TrangThaiPhieu::ChoNguoiDung->canTransitionTo(TrangThaiPhieu::DangXuLy));
        $this->assertTrue(TrangThaiPhieu::ChoNhaCungCap->canTransitionTo(TrangThaiPhieu::DangXuLy));
    }

    public function test_co_dang_mo_dung_khong(): void
    {
        $this->assertTrue(TrangThaiPhieu::Moi->isOpen());
        $this->assertTrue(TrangThaiPhieu::DangXuLy->isOpen());
        $this->assertTrue(TrangThaiPhieu::ChoNguoiDung->isOpen());
        $this->assertFalse(TrangThaiPhieu::DaGiaiQuyet->isOpen());
        $this->assertFalse(TrangThaiPhieu::DaDong->isOpen());
        $this->assertTrue(TrangThaiPhieu::DaDong->isClosed());
    }

    public function test_danh_sach_lua_chon_hien_thi_tieng_viet(): void
    {
        $luaChon = TrangThaiPhieu::options();

        $this->assertSame('Mới', $luaChon['moi']);
        $this->assertSame('Đang xử lý', $luaChon['dang_xu_ly']);
        $this->assertSame('Đã đóng', $luaChon['da_dong']);
    }
}
