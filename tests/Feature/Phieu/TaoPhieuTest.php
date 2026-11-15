<?php

namespace Tests\Feature\Phieu;

use App\Models\DanhMuc;
use App\Models\NguoiDung;
use App\Models\NhomXuLy;
use App\Models\PhieuYeuCau;
use App\Models\TepDinhKem;
use Database\Seeders\VaiTroQuyenSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaoPhieuTest extends TestCase
{
    use RefreshDatabase;

    private DanhMuc $danhMuc;

    private NhomXuLy $nhom;

    private NguoiDung $nguoiYeuCau;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(VaiTroQuyenSeeder::class);

        $this->nhom = NhomXuLy::factory()->create(['ten' => 'Thiết bị']);
        $this->danhMuc = DanhMuc::factory()->create([
            'ten' => 'Máy in',
            'nhom_mac_dinh_id' => $this->nhom->id,
        ]);
        $this->nguoiYeuCau = NguoiDung::factory()->requester()->create();
    }

    /**
     * @param  array<string, mixed>  $ghiDe
     * @return array<string, mixed>
     */
    private function duLieu(array $ghiDe = []): array
    {
        return array_merge([
            'tieu_de' => 'Không in được từ máy 305',
            'mo_ta' => 'Máy in không nhận lệnh in, đèn báo lỗi nhấp nháy từ sáng nay.',
            'danh_muc_id' => $this->danhMuc->id,
            'anh_huong' => 2,
            'khan_cap' => 2,
        ], $ghiDe);
    }

    public function test_nguoi_yeu_cau_tao_duoc_phieu(): void
    {
        Notification::fake();
        NguoiDung::factory()->admin()->create();

        $phanHoi = $this->actingAs($this->nguoiYeuCau)->post(route('phieu.store'), $this->duLieu());

        $phieu = PhieuYeuCau::query()->latest('id')->firstOrFail();

        $phanHoi->assertRedirect(route('phieu.show', $phieu));

        $this->assertMatchesRegularExpression('/^TK-\d{4}-\d{6}$/', $phieu->ma);
        $this->assertSame('P3', $phieu->uu_tien->value);
        $this->assertSame('moi', $phieu->trang_thai->value);
        $this->assertSame($this->nguoiYeuCau->id, $phieu->nguoi_yeu_cau_id);
        $this->assertSame($this->nhom->id, $phieu->nhom_xu_ly_id);

        $this->assertDatabaseHas('su_kien_phieu', [
            'phieu_yeu_cau_id' => $phieu->id,
            'loai' => 'tao_moi',
        ]);
        $this->assertDatabaseHas('lich_su_trang_thai', [
            'phieu_yeu_cau_id' => $phieu->id,
            'trang_thai_sau' => 'moi',
        ]);
        $this->assertDatabaseHas('nguoi_theo_doi', [
            'phieu_yeu_cau_id' => $phieu->id,
            'nguoi_dung_id' => $this->nguoiYeuCau->id,
        ]);

        Notification::assertCount(1);
    }

    public function test_ma_phieu_tang_dan_theo_tung_phieu(): void
    {
        $this->actingAs($this->nguoiYeuCau)->post(route('phieu.store'), $this->duLieu());
        $this->actingAs($this->nguoiYeuCau)->post(route('phieu.store'), $this->duLieu(['tieu_de' => 'Máy in bị kẹt giấy']));

        $maPhieus = PhieuYeuCau::query()->orderBy('id')->pluck('ma')->all();
        $nam = now()->format('Y');

        $this->assertSame(["TK-{$nam}-000001", "TK-{$nam}-000002"], $maPhieus);
    }

    public function test_muc_uu_tien_duoc_tinh_tu_anh_huong_va_khan_cap(): void
    {
        $cacTruongHop = [
            [3, 3, 'P1'],
            [3, 2, 'P2'],
            [2, 3, 'P2'],
            [2, 2, 'P3'],
            [1, 1, 'P4'],
        ];

        foreach ($cacTruongHop as [$anhHuong, $khanCap, $mongDoi]) {
            $this->actingAs($this->nguoiYeuCau)->post(route('phieu.store'), $this->duLieu([
                'anh_huong' => $anhHuong,
                'khan_cap' => $khanCap,
            ]));

            $this->assertSame(
                $mongDoi,
                PhieuYeuCau::query()->latest('id')->firstOrFail()->uu_tien->value,
                "Ảnh hưởng {$anhHuong} × Khẩn cấp {$khanCap} phải là {$mongDoi}",
            );
        }
    }

    public function test_phieu_bat_buoc_danh_muc_va_mo_ta_hop_le(): void
    {
        $phanHoi = $this->actingAs($this->nguoiYeuCau)->post(route('phieu.store'), [
            'tieu_de' => 'Thiếu danh mục',
            'mo_ta' => 'ngắn',
            'anh_huong' => 9,
            'khan_cap' => 2,
        ]);

        $phanHoi->assertSessionHasErrors(['danh_muc_id', 'mo_ta', 'anh_huong']);
        $this->assertDatabaseCount('phieu_yeu_cau', 0);
    }

    public function test_tep_dinh_kem_duoc_luu_cung_phieu(): void
    {
        Storage::fake('local');

        $tep = UploadedFile::fake()->create('loi-may-in.pdf', 120, 'application/pdf');

        $this->actingAs($this->nguoiYeuCau)->post(route('phieu.store'), $this->duLieu([
            'attachments' => [$tep],
        ]))->assertSessionHasNoErrors();

        $tepDinhKem = TepDinhKem::query()->firstOrFail();

        $this->assertSame('loi-may-in.pdf', $tepDinhKem->ten_goc);
        Storage::disk('local')->assertExists($tepDinhKem->duong_dan);
        $this->assertDatabaseHas('su_kien_phieu', [
            'phieu_yeu_cau_id' => $tepDinhKem->phieu_yeu_cau_id,
            'loai' => 'dinh_kem_tep',
        ]);
    }
}
