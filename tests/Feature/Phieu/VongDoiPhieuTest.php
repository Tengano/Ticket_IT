<?php

namespace Tests\Feature\Phieu;

use App\Enums\TrangThaiPhieu;
use App\Models\NguoiDung;
use App\Models\PhieuYeuCau;
use Database\Seeders\VaiTroQuyenSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VongDoiPhieuTest extends TestCase
{
    use RefreshDatabase;

    private NguoiDung $nguoiYeuCau;

    private NguoiDung $kyThuatVien;

    private PhieuYeuCau $phieu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(VaiTroQuyenSeeder::class);

        $this->nguoiYeuCau = NguoiDung::factory()->requester()->create();
        $this->kyThuatVien = NguoiDung::factory()->agent()->create();

        $this->phieu = PhieuYeuCau::factory()->create([
            'nguoi_yeu_cau_id' => $this->nguoiYeuCau->id,
            'trang_thai' => TrangThaiPhieu::Moi,
        ]);
    }

    public function test_ky_thuat_vien_di_het_vong_doi_phieu(): void
    {
        $this->actingAs($this->kyThuatVien)
            ->patch(route('phieu.update', $this->phieu), ['trang_thai' => 'dang_xu_ly'])
            ->assertSessionHasNoErrors();

        $this->assertSame(TrangThaiPhieu::DangXuLy, $this->phieu->refresh()->trang_thai);

        $this->actingAs($this->kyThuatVien)
            ->patch(route('phieu.update', $this->phieu), ['trang_thai' => 'da_giai_quyet'])
            ->assertSessionHasNoErrors();

        $this->assertSame(TrangThaiPhieu::DaGiaiQuyet, $this->phieu->refresh()->trang_thai);
        $this->assertNotNull($this->phieu->giai_quyet_luc);

        $this->actingAs($this->kyThuatVien)
            ->patch(route('phieu.update', $this->phieu), ['trang_thai' => 'da_dong'])
            ->assertSessionHasNoErrors();

        $this->phieu->refresh();

        $this->assertSame(TrangThaiPhieu::DaDong, $this->phieu->trang_thai);
        $this->assertNotNull($this->phieu->dong_luc);
        $this->assertSame(3, $this->phieu->statusHistories()->count());

        $this->assertDatabaseHas('su_kien_phieu', [
            'phieu_yeu_cau_id' => $this->phieu->id,
            'loai' => 'doi_trang_thai',
            'tu_gia_tri' => 'dang_xu_ly',
            'den_gia_tri' => 'da_giai_quyet',
        ]);
        $this->assertDatabaseHas('su_kien_phieu', [
            'phieu_yeu_cau_id' => $this->phieu->id,
            'loai' => 'dong_phieu',
        ]);
    }

    public function test_chuyen_trang_thai_khong_hop_le_bi_tu_choi(): void
    {
        $phanHoi = $this->actingAs($this->kyThuatVien)->patch(route('phieu.update', $this->phieu), [
            'trang_thai' => 'da_giai_quyet',
        ]);

        $phanHoi->assertSessionHasErrors('trang_thai');
        $this->assertSame(TrangThaiPhieu::Moi, $this->phieu->refresh()->trang_thai);
    }

    public function test_nguoi_yeu_cau_dong_duoc_phieu_cua_minh_nhung_khong_dong_phieu_nguoi_khac(): void
    {
        $this->actingAs($this->nguoiYeuCau)
            ->post(route('phieu.dong', $this->phieu))
            ->assertSessionHasNoErrors();

        $this->assertSame(TrangThaiPhieu::DaDong, $this->phieu->refresh()->trang_thai);

        $nguoiKhac = NguoiDung::factory()->requester()->create();
        $phieuKhac = PhieuYeuCau::factory()->create(['nguoi_yeu_cau_id' => $nguoiKhac->id]);

        $this->actingAs($this->nguoiYeuCau)
            ->post(route('phieu.dong', $phieuKhac))
            ->assertForbidden();
    }

    public function test_nguoi_yeu_cau_mo_lai_duoc_phieu_da_giai_quyet(): void
    {
        $this->actingAs($this->kyThuatVien)->patch(route('phieu.update', $this->phieu), ['trang_thai' => 'dang_xu_ly']);
        $this->actingAs($this->kyThuatVien)->patch(route('phieu.update', $this->phieu), ['trang_thai' => 'da_giai_quyet']);

        $this->actingAs($this->nguoiYeuCau)
            ->post(route('phieu.mo-lai', $this->phieu))
            ->assertSessionHasNoErrors();

        $phieu = $this->phieu->refresh();

        $this->assertSame(TrangThaiPhieu::MoLai, $phieu->trang_thai);
        $this->assertSame(1, $phieu->so_lan_mo_lai);
        $this->assertNull($phieu->giai_quyet_luc);
        $this->assertNull($phieu->dong_luc);
    }

    public function test_nguoi_yeu_cau_khong_doi_duoc_trang_thai_phieu(): void
    {
        $this->actingAs($this->nguoiYeuCau)
            ->patch(route('phieu.update', $this->phieu), ['trang_thai' => 'dang_xu_ly'])
            ->assertForbidden();
    }

    public function test_phan_cong_cap_nhat_nguoi_xu_ly_va_trang_thai(): void
    {
        $this->actingAs($this->kyThuatVien)
            ->post(route('phieu.phan-cong', $this->phieu), ['nguoi_xu_ly_id' => $this->kyThuatVien->id])
            ->assertSessionHasNoErrors();

        $phieu = $this->phieu->refresh();

        $this->assertSame($this->kyThuatVien->id, $phieu->nguoi_xu_ly_id);
        $this->assertSame(TrangThaiPhieu::DaPhanCong, $phieu->trang_thai);
        $this->assertNotNull($phieu->phan_cong_luc);
        $this->assertDatabaseHas('su_kien_phieu', [
            'phieu_yeu_cau_id' => $phieu->id,
            'loai' => 'phan_cong',
        ]);
    }

    public function test_nguoi_yeu_cau_khong_phan_cong_duoc_phieu(): void
    {
        $this->actingAs($this->nguoiYeuCau)
            ->post(route('phieu.phan-cong', $this->phieu), ['nguoi_xu_ly_id' => $this->nguoiYeuCau->id])
            ->assertForbidden();
    }
}
