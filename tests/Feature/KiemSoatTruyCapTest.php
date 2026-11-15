<?php

namespace Tests\Feature;

use App\Models\NguoiDung;
use App\Models\PhieuYeuCau;
use Database\Seeders\VaiTroQuyenSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KiemSoatTruyCapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(VaiTroQuyenSeeder::class);
    }

    public function test_nguoi_yeu_cau_khong_vao_duoc_quan_tri_nguoi_dung(): void
    {
        $nguoiYeuCau = NguoiDung::factory()->requester()->create();

        $this->actingAs($nguoiYeuCau)
            ->get(route('quan-tri.nguoi-dung.index'))
            ->assertForbidden();
    }

    public function test_nguoi_yeu_cau_khong_vao_duoc_quan_tri_danh_muc(): void
    {
        $nguoiYeuCau = NguoiDung::factory()->requester()->create();

        $this->actingAs($nguoiYeuCau)
            ->get(route('quan-tri.phong-ban.index'))
            ->assertForbidden();

        $this->actingAs($nguoiYeuCau)
            ->get(route('quan-tri.nhom-xu-ly.index'))
            ->assertForbidden();
    }

    public function test_ky_thuat_vien_xem_duoc_danh_sach_phieu_nhung_khong_quan_ly_nguoi_dung(): void
    {
        $kyThuatVien = NguoiDung::factory()->agent()->create();

        $this->actingAs($kyThuatVien)->get(route('phieu.index'))->assertOk();
        $this->actingAs($kyThuatVien)->get(route('tong-quan'))->assertOk();
        $this->actingAs($kyThuatVien)->get(route('quan-tri.nguoi-dung.index'))->assertForbidden();
    }

    public function test_truong_nhom_co_quyen_xem_bao_cao(): void
    {
        $truongNhom = NguoiDung::factory()->lead()->create();

        $this->assertTrue($truongNhom->can('report.view'));
        $this->assertFalse($truongNhom->can('user.manage'));
        $this->actingAs($truongNhom)->get(route('phieu.index'))->assertOk();
    }

    public function test_quan_tri_vao_duoc_moi_man_hinh_quan_tri(): void
    {
        $quanTri = NguoiDung::factory()->admin()->create();

        $this->actingAs($quanTri)->get(route('quan-tri.nguoi-dung.index'))->assertOk();
        $this->actingAs($quanTri)->get(route('quan-tri.phong-ban.index'))->assertOk();
        $this->actingAs($quanTri)->get(route('quan-tri.vi-tri.index'))->assertOk();
        $this->actingAs($quanTri)->get(route('quan-tri.nhom-xu-ly.index'))->assertOk();
    }

    public function test_quan_tri_bo_qua_chinh_sach_phieu(): void
    {
        $quanTri = NguoiDung::factory()->admin()->create();
        $nguoiYeuCau = NguoiDung::factory()->requester()->create();

        $phieu = PhieuYeuCau::factory()->create(['nguoi_yeu_cau_id' => $nguoiYeuCau->id]);

        $this->assertTrue($quanTri->can('view', $phieu));
        $this->assertTrue($quanTri->can('update', $phieu));
        $this->assertTrue($quanTri->can('assign', $phieu));
    }

    public function test_cac_bieu_mau_quan_tri_hien_thi_thanh_cong(): void
    {
        $quanTri = NguoiDung::factory()->admin()->create();

        $this->actingAs($quanTri)->get(route('quan-tri.nguoi-dung.create'))->assertOk();
        $this->actingAs($quanTri)->get(route('quan-tri.phong-ban.create'))->assertOk();
        $this->actingAs($quanTri)->get(route('quan-tri.vi-tri.create'))->assertOk();
        $this->actingAs($quanTri)->get(route('quan-tri.nhom-xu-ly.create'))->assertOk();

        $thanhVien = NguoiDung::factory()->agent()->create();
        $this->actingAs($quanTri)->get(route('quan-tri.nguoi-dung.edit', $thanhVien))->assertOk();
    }

    public function test_nguoi_yeu_cau_mo_duoc_bieu_mau_tao_phieu_va_thong_bao(): void
    {
        $nguoiYeuCau = NguoiDung::factory()->requester()->create();

        $this->actingAs($nguoiYeuCau)->get(route('phieu.create'))->assertOk();
        $this->actingAs($nguoiYeuCau)->get(route('thong-bao.index'))->assertOk();
    }
}
