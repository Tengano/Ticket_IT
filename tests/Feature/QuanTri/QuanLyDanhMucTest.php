<?php

namespace Tests\Feature\QuanTri;

use App\Models\NguoiDung;
use App\Models\NhomXuLy;
use App\Models\PhongBan;
use Database\Seeders\VaiTroQuyenSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuanLyDanhMucTest extends TestCase
{
    use RefreshDatabase;

    private NguoiDung $quanTri;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(VaiTroQuyenSeeder::class);

        $this->quanTri = NguoiDung::factory()->admin()->create();
    }

    public function test_quan_tri_tao_va_cap_nhat_phong_ban(): void
    {
        $this->actingAs($this->quanTri)->post(route('quan-tri.phong-ban.store'), [
            'ma' => 'CNTT',
            'ten' => 'Phòng Công nghệ thông tin',
            'dang_hoat_dong' => '1',
        ])->assertSessionHasNoErrors();

        $phongBan = PhongBan::query()->where('ma', 'CNTT')->firstOrFail();

        $this->actingAs($this->quanTri)->put(route('quan-tri.phong-ban.update', $phongBan), [
            'ma' => 'CNTT',
            'ten' => 'Phòng CNTT & Chuyển đổi số',
            'dang_hoat_dong' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Phòng CNTT & Chuyển đổi số', $phongBan->refresh()->ten);
    }

    public function test_khong_xoa_duoc_phong_ban_dang_co_nguoi_dung(): void
    {
        $phongBan = PhongBan::factory()->create();
        NguoiDung::factory()->create(['phong_ban_id' => $phongBan->id]);

        $this->actingAs($this->quanTri)
            ->delete(route('quan-tri.phong-ban.destroy', $phongBan))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('phong_ban', ['id' => $phongBan->id]);
    }

    public function test_quan_tri_tao_duoc_vi_tri(): void
    {
        $this->actingAs($this->quanTri)->post(route('quan-tri.vi-tri.store'), [
            'ten' => 'Phòng 305',
            'toa_nha' => 'Tòa A',
            'tang' => 'Tầng 3',
            'khu_vuc' => 'Khu A',
            'dang_hoat_dong' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('vi_tri', ['ten' => 'Phòng 305', 'khu_vuc' => 'Khu A']);
    }

    public function test_quan_tri_tao_nhom_xu_ly_kem_thanh_vien(): void
    {
        $kyThuatVien = NguoiDung::factory()->agent()->create();

        $this->actingAs($this->quanTri)->post(route('quan-tri.nhom-xu-ly.store'), [
            'ma' => 'ho-tro-chung',
            'ten' => 'Hỗ trợ chung',
            'truong_nhom_id' => $kyThuatVien->id,
            'chien_luoc_phan_cong' => 'khop_ky_nang',
            'dinh_muc' => 20,
            'member_ids' => [$kyThuatVien->id],
            'dang_hoat_dong' => '1',
        ])->assertSessionHasNoErrors();

        $nhom = NhomXuLy::query()->where('ma', 'ho-tro-chung')->firstOrFail();

        $this->assertSame($kyThuatVien->id, $nhom->truong_nhom_id);
        $this->assertDatabaseHas('nhom_xu_ly_nguoi_dung', [
            'nhom_xu_ly_id' => $nhom->id,
            'nguoi_dung_id' => $kyThuatVien->id,
            'la_truong_nhom' => true,
        ]);
    }

    public function test_ky_thuat_vien_khong_quan_ly_duoc_danh_muc(): void
    {
        $kyThuatVien = NguoiDung::factory()->agent()->create();

        $this->actingAs($kyThuatVien)->get(route('quan-tri.vi-tri.index'))->assertForbidden();
    }
}
