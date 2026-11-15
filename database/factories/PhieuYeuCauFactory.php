<?php

namespace Database\Factories;

use App\Enums\MucAnhHuong;
use App\Enums\MucKhanCap;
use App\Enums\TrangThaiPhieu;
use App\Enums\UuTienPhieu;
use App\Models\DanhMuc;
use App\Models\NguoiDung;
use App\Models\NhomXuLy;
use App\Models\PhieuYeuCau;
use App\Models\PhongBan;
use App\Models\ViTri;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PhieuYeuCau>
 */
class PhieuYeuCauFactory extends Factory
{
    protected $model = PhieuYeuCau::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ma' => 'TK-'.now()->format('Y').'-'.fake()->unique()->numerify('######'),
            'tieu_de' => fake()->sentence(6),
            'mo_ta' => fake()->paragraphs(2, true),
            'nguoi_yeu_cau_id' => NguoiDung::factory(),
            'phong_ban_id' => PhongBan::factory(),
            'vi_tri_id' => ViTri::factory(),
            'danh_muc_id' => DanhMuc::factory(),
            'nhom_xu_ly_id' => null,
            'nguoi_xu_ly_id' => null,
            'trang_thai' => TrangThaiPhieu::Moi,
            'uu_tien' => UuTienPhieu::P3,
            'anh_huong' => MucAnhHuong::TrungBinh,
            'khan_cap' => MucKhanCap::TrungBinh,
            'kenh' => 'web',
            'so_lan_mo_lai' => 0,
        ];
    }

    public function status(TrangThaiPhieu $status): static
    {
        return $this->state(fn (array $attributes) => [
            'trang_thai' => $status,
        ]);
    }

    public function priority(UuTienPhieu $priority): static
    {
        return $this->state(fn (array $attributes) => [
            'uu_tien' => $priority,
        ]);
    }

    public function assignedTo(NguoiDung $agent, ?NhomXuLy $team = null): static
    {
        return $this->state(fn (array $attributes) => [
            'nguoi_xu_ly_id' => $agent->id,
            'nhom_xu_ly_id' => $team?->id ?? $attributes['nhom_xu_ly_id'] ?? null,
            'trang_thai' => TrangThaiPhieu::DangXuLy,
            'phan_cong_luc' => now(),
        ]);
    }
}
