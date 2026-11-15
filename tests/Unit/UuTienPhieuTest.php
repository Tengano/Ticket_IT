<?php

namespace Tests\Unit;

use App\Enums\MucAnhHuong;
use App\Enums\MucKhanCap;
use App\Enums\UuTienPhieu;
use PHPUnit\Framework\TestCase;

class UuTienPhieuTest extends TestCase
{
    public function test_ma_tran_uu_tien_anh_xa_anh_huong_va_khan_cap_thanh_p1_p4(): void
    {
        $maTran = [
            [MucAnhHuong::Cao, MucKhanCap::Cao, UuTienPhieu::P1],
            [MucAnhHuong::Cao, MucKhanCap::TrungBinh, UuTienPhieu::P2],
            [MucAnhHuong::TrungBinh, MucKhanCap::Cao, UuTienPhieu::P2],
            [MucAnhHuong::Cao, MucKhanCap::Thap, UuTienPhieu::P3],
            [MucAnhHuong::TrungBinh, MucKhanCap::TrungBinh, UuTienPhieu::P3],
            [MucAnhHuong::Thap, MucKhanCap::Cao, UuTienPhieu::P3],
            [MucAnhHuong::TrungBinh, MucKhanCap::Thap, UuTienPhieu::P4],
            [MucAnhHuong::Thap, MucKhanCap::TrungBinh, UuTienPhieu::P4],
            [MucAnhHuong::Thap, MucKhanCap::Thap, UuTienPhieu::P4],
        ];

        foreach ($maTran as [$anhHuong, $khanCap, $mongDoi]) {
            $this->assertSame(
                $mongDoi,
                UuTienPhieu::fromImpactUrgency($anhHuong, $khanCap),
                "Ảnh hưởng {$anhHuong->name} × Khẩn cấp {$khanCap->name}",
            );
        }
    }

    public function test_chi_p1_la_nghiem_trong(): void
    {
        $this->assertTrue(UuTienPhieu::P1->isCritical());
        $this->assertFalse(UuTienPhieu::P2->isCritical());
        $this->assertFalse(UuTienPhieu::P3->isCritical());
        $this->assertFalse(UuTienPhieu::P4->isCritical());
    }

    public function test_moc_thoi_gian_mac_dinh_dung_bang_sla(): void
    {
        $this->assertSame([15, 120], [
            UuTienPhieu::P1->firstResponseTargetMinutes(),
            UuTienPhieu::P1->resolutionTargetMinutes(),
        ]);

        $this->assertSame([30, 240], [
            UuTienPhieu::P2->firstResponseTargetMinutes(),
            UuTienPhieu::P2->resolutionTargetMinutes(),
        ]);

        $this->assertSame([120, 480], [
            UuTienPhieu::P3->firstResponseTargetMinutes(),
            UuTienPhieu::P3->resolutionTargetMinutes(),
        ]);

        $this->assertSame([480, 1440], [
            UuTienPhieu::P4->firstResponseTargetMinutes(),
            UuTienPhieu::P4->resolutionTargetMinutes(),
        ]);
    }

    public function test_danh_sach_lua_chon_tra_ve_du_bon_muc(): void
    {
        $this->assertSame(['P1', 'P2', 'P3', 'P4'], array_keys(UuTienPhieu::options()));
    }
}
