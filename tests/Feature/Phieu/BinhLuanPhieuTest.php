<?php

namespace Tests\Feature\Phieu;

use App\Models\BinhLuanPhieu;
use App\Models\NguoiDung;
use App\Models\PhieuYeuCau;
use App\Models\TepDinhKem;
use App\Notifications\ThongBaoPhieu;
use Database\Seeders\VaiTroQuyenSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BinhLuanPhieuTest extends TestCase
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
        $this->phieu = PhieuYeuCau::factory()->create(['nguoi_yeu_cau_id' => $this->nguoiYeuCau->id]);
    }

    public function test_phan_hoi_cong_khai_dau_tien_duoc_danh_dau_va_gui_thong_bao(): void
    {
        Notification::fake();

        $this->actingAs($this->kyThuatVien)
            ->post(route('phieu.binh-luan.store', $this->phieu), [
                'noi_dung' => 'Xin chào, IT đã tiếp nhận và đang kiểm tra máy in.',
                'loai' => 'cong_khai',
            ])
            ->assertSessionHasNoErrors();

        $this->phieu->refresh();
        $binhLuan = BinhLuanPhieu::query()->firstOrFail();

        $this->assertNotNull($this->phieu->phan_hoi_dau_luc);
        $this->assertTrue($binhLuan->la_phan_hoi_dau);
        $this->assertDatabaseHas('su_kien_phieu', [
            'phieu_yeu_cau_id' => $this->phieu->id,
            'loai' => 'binh_luan',
        ]);

        Notification::assertSentTo($this->nguoiYeuCau, ThongBaoPhieu::class);
    }

    public function test_phan_hoi_thu_hai_khong_phai_phan_hoi_dau(): void
    {
        $this->actingAs($this->kyThuatVien)->post(route('phieu.binh-luan.store', $this->phieu), [
            'noi_dung' => 'Phản hồi đầu tiên.',
            'loai' => 'cong_khai',
        ]);

        $this->actingAs($this->kyThuatVien)->post(route('phieu.binh-luan.store', $this->phieu), [
            'noi_dung' => 'Phản hồi thứ hai.',
            'loai' => 'cong_khai',
        ]);

        $this->assertSame(1, BinhLuanPhieu::query()->where('la_phan_hoi_dau', true)->count());
    }

    public function test_nguoi_yeu_cau_khong_them_duoc_ghi_chu_noi_bo(): void
    {
        $this->actingAs($this->nguoiYeuCau)
            ->post(route('phieu.binh-luan.store', $this->phieu), [
                'noi_dung' => 'Cố gắng thêm ghi chú nội bộ.',
                'loai' => 'noi_bo',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('binh_luan_phieu', 0);
    }

    public function test_ky_thuat_vien_them_duoc_ghi_chu_noi_bo(): void
    {
        $this->actingAs($this->kyThuatVien)
            ->post(route('phieu.binh-luan.store', $this->phieu), [
                'noi_dung' => 'Đã kiểm tra driver máy in, cần thay cáp mạng.',
                'loai' => 'noi_bo',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('binh_luan_phieu', [
            'phieu_yeu_cau_id' => $this->phieu->id,
            'loai' => 'noi_bo',
        ]);
        $this->assertDatabaseHas('su_kien_phieu', [
            'phieu_yeu_cau_id' => $this->phieu->id,
            'loai' => 'ghi_chu_noi_bo',
        ]);
    }

    public function test_nguoi_yeu_cau_khong_binh_luan_duoc_vao_phieu_nguoi_khac(): void
    {
        $nguoiKhac = NguoiDung::factory()->requester()->create();

        $this->actingAs($nguoiKhac)
            ->post(route('phieu.binh-luan.store', $this->phieu), [
                'noi_dung' => 'Tôi muốn bình luận vào phiếu người khác.',
                'loai' => 'cong_khai',
            ])
            ->assertForbidden();
    }

    public function test_nguoi_yeu_cau_khong_tai_duoc_tep_noi_bo(): void
    {
        $binhLuan = BinhLuanPhieu::factory()->internal()->create([
            'phieu_yeu_cau_id' => $this->phieu->id,
            'nguoi_dung_id' => $this->kyThuatVien->id,
        ]);

        $tepDinhKem = TepDinhKem::factory()->create([
            'phieu_yeu_cau_id' => $this->phieu->id,
            'binh_luan_id' => $binhLuan->id,
            'nguoi_tai_len_id' => $this->kyThuatVien->id,
        ]);

        $this->actingAs($this->nguoiYeuCau)
            ->get(route('phieu.tep.tai', [$this->phieu, $tepDinhKem]))
            ->assertForbidden();
    }
}
