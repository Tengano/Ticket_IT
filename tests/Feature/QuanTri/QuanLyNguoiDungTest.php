<?php

namespace Tests\Feature\QuanTri;

use App\Models\NguoiDung;
use App\Models\NhomXuLy;
use App\Models\PhieuYeuCau;
use App\Models\PhongBan;
use Database\Seeders\VaiTroQuyenSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class QuanLyNguoiDungTest extends TestCase
{
    use RefreshDatabase;

    private NguoiDung $quanTri;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(VaiTroQuyenSeeder::class);

        $this->quanTri = NguoiDung::factory()->admin()->create();
    }

    public function test_quan_tri_tao_nguoi_dung_kem_vai_tro_va_nhom(): void
    {
        $phongBan = PhongBan::factory()->create();
        $nhom = NhomXuLy::factory()->create();

        $this->actingAs($this->quanTri)->post(route('quan-tri.nguoi-dung.store'), [
            'ten' => 'Nguyễn Văn An',
            'email' => 'an.nguyen@ticketit.test',
            'dien_thoai' => '0901234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'agent',
            'phong_ban_id' => $phongBan->id,
            'team_ids' => [$nhom->id],
            'dang_hoat_dong' => '1',
            'la_vip' => '0',
        ])->assertRedirect(route('quan-tri.nguoi-dung.index'))->assertSessionHasNoErrors();

        $nguoiDung = NguoiDung::query()->where('email', 'an.nguyen@ticketit.test')->firstOrFail();

        $this->assertTrue($nguoiDung->hasRole('agent'));
        $this->assertTrue($nguoiDung->teams->contains($nhom->id));
        $this->assertTrue(Hash::check('password123', $nguoiDung->password));
        $this->assertSame($phongBan->id, $nguoiDung->phong_ban_id);
    }

    public function test_quan_tri_cap_nhat_thong_tin_va_vai_tro_nguoi_dung(): void
    {
        $nguoiDung = NguoiDung::factory()->requester()->create();

        $this->actingAs($this->quanTri)->put(route('quan-tri.nguoi-dung.update', $nguoiDung), [
            'ten' => 'Trần Minh Khôi',
            'email' => 'khoi.tran@ticketit.test',
            'role' => 'lead',
            'dang_hoat_dong' => '1',
            'la_vip' => '1',
        ])->assertSessionHasNoErrors();

        $nguoiDung->refresh();

        $this->assertSame('Trần Minh Khôi', $nguoiDung->ten);
        $this->assertTrue($nguoiDung->hasRole('lead'));
        $this->assertTrue($nguoiDung->la_vip);
    }

    public function test_quan_tri_bat_tat_trang_thai_tai_khoan(): void
    {
        $nguoiDung = NguoiDung::factory()->requester()->create(['dang_hoat_dong' => true]);

        $this->actingAs($this->quanTri)
            ->post(route('quan-tri.nguoi-dung.kich-hoat', $nguoiDung))
            ->assertSessionHasNoErrors();

        $this->assertFalse($nguoiDung->refresh()->dang_hoat_dong);

        $this->actingAs($this->quanTri)->post(route('quan-tri.nguoi-dung.kich-hoat', $nguoiDung));

        $this->assertTrue($nguoiDung->refresh()->dang_hoat_dong);
    }

    public function test_quan_tri_khong_tu_khoa_tai_khoan_cua_minh(): void
    {
        $this->actingAs($this->quanTri)
            ->post(route('quan-tri.nguoi-dung.kich-hoat', $this->quanTri))
            ->assertForbidden();
    }

    public function test_ky_thuat_vien_khong_quan_ly_duoc_nguoi_dung(): void
    {
        $kyThuatVien = NguoiDung::factory()->agent()->create();

        $this->actingAs($kyThuatVien)
            ->get(route('quan-tri.nguoi-dung.index'))
            ->assertForbidden();
    }

    public function test_quan_tri_xoa_duoc_nguoi_dung_chua_co_phieu(): void
    {
        $nguoiDung = NguoiDung::factory()->requester()->create();

        $this->actingAs($this->quanTri)
            ->delete(route('quan-tri.nguoi-dung.destroy', $nguoiDung))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('nguoi_dung', ['id' => $nguoiDung->id]);
    }

    public function test_khong_xoa_duoc_nguoi_dung_da_co_phieu(): void
    {
        $nguoiDung = NguoiDung::factory()->requester()->create();
        PhieuYeuCau::factory()->create(['nguoi_yeu_cau_id' => $nguoiDung->id]);

        $this->actingAs($this->quanTri)
            ->delete(route('quan-tri.nguoi-dung.destroy', $nguoiDung))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('nguoi_dung', ['id' => $nguoiDung->id]);
    }
}
