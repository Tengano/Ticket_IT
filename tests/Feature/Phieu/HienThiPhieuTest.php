<?php

namespace Tests\Feature\Phieu;

use App\Models\BinhLuanPhieu;
use App\Models\NguoiDung;
use App\Models\PhieuYeuCau;
use Database\Seeders\VaiTroQuyenSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HienThiPhieuTest extends TestCase
{
    use RefreshDatabase;

    private NguoiDung $chuPhieu;

    private NguoiDung $nguoiKhac;

    private NguoiDung $kyThuatVien;

    private PhieuYeuCau $phieu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(VaiTroQuyenSeeder::class);

        $this->chuPhieu = NguoiDung::factory()->requester()->create();
        $this->nguoiKhac = NguoiDung::factory()->requester()->create();
        $this->kyThuatVien = NguoiDung::factory()->agent()->create();

        $this->phieu = PhieuYeuCau::factory()->create([
            'nguoi_yeu_cau_id' => $this->chuPhieu->id,
            'tieu_de' => 'Không in được từ máy 305',
        ]);
    }

    public function test_nguoi_yeu_cau_khong_xem_duoc_phieu_cua_nguoi_khac(): void
    {
        $this->actingAs($this->nguoiKhac)
            ->get(route('phieu.show', $this->phieu))
            ->assertForbidden();
    }

    public function test_ky_thuat_vien_xem_duoc_moi_phieu(): void
    {
        $this->actingAs($this->kyThuatVien)
            ->get(route('phieu.show', $this->phieu))
            ->assertOk()
            ->assertSee('Không in được từ máy 305');
    }

    public function test_danh_sach_phieu_chi_hien_thi_phieu_cua_minh_voi_nguoi_yeu_cau(): void
    {
        PhieuYeuCau::factory()->create([
            'nguoi_yeu_cau_id' => $this->nguoiKhac->id,
            'tieu_de' => 'Phiếu riêng của người khác',
        ]);

        $phanHoi = $this->actingAs($this->chuPhieu)->get(route('phieu.index'));

        $phanHoi->assertOk()
            ->assertSee('Không in được từ máy 305')
            ->assertDontSee('Phiếu riêng của người khác');
    }

    public function test_ghi_chu_noi_bo_bi_an_voi_nguoi_yeu_cau(): void
    {
        BinhLuanPhieu::factory()->create([
            'phieu_yeu_cau_id' => $this->phieu->id,
            'nguoi_dung_id' => $this->kyThuatVien->id,
            'noi_dung' => 'Ghi chú nội bộ: kiểm tra log máy chủ in.',
            'loai' => 'noi_bo',
        ]);

        BinhLuanPhieu::factory()->create([
            'phieu_yeu_cau_id' => $this->phieu->id,
            'nguoi_dung_id' => $this->kyThuatVien->id,
            'noi_dung' => 'Phản hồi công khai: IT đang kiểm tra.',
            'loai' => 'cong_khai',
        ]);

        $phanHoi = $this->actingAs($this->chuPhieu)->get(route('phieu.show', $this->phieu));

        $phanHoi->assertOk()
            ->assertSee('Phản hồi công khai: IT đang kiểm tra.')
            ->assertDontSee('Ghi chú nội bộ: kiểm tra log máy chủ in.')
            ->assertDontSee('đã thêm ghi chú nội bộ');
    }

    public function test_nguoi_theo_doi_xem_duoc_phieu(): void
    {
        $nguoiTheoDoi = NguoiDung::factory()->requester()->create();
        $this->phieu->watchers()->attach($nguoiTheoDoi->id);

        $this->actingAs($nguoiTheoDoi)
            ->get(route('phieu.show', $this->phieu))
            ->assertOk();
    }
}
