<?php

namespace App\Models;

use App\Enums\LoaiSuKienPhieu;
use App\Enums\TrangThaiPhieu;
use App\Enums\UuTienPhieu;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['phieu_yeu_cau_id', 'nguoi_thuc_hien_id', 'loai', 'tu_gia_tri', 'den_gia_tri', 'du_lieu_them'])]
class SuKienPhieu extends Model
{
    protected $table = 'su_kien_phieu';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'loai' => LoaiSuKienPhieu::class,
            'du_lieu_them' => 'array',
        ];
    }

    /**
     * @return BelongsTo<PhieuYeuCau, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(PhieuYeuCau::class, 'phieu_yeu_cau_id');
    }

    /**
     * @return BelongsTo<NguoiDung, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_thuc_hien_id');
    }

    public function summary(): string
    {
        return match ($this->loai) {
            LoaiSuKienPhieu::TaoMoi => 'đã tạo phiếu yêu cầu',
            LoaiSuKienPhieu::CapNhat => 'đã cập nhật thông tin phiếu',
            LoaiSuKienPhieu::DoiTrangThai => "đã chuyển trạng thái từ \"{$this->statusLabel($this->tu_gia_tri)}\" sang \"{$this->statusLabel($this->den_gia_tri)}\"",
            LoaiSuKienPhieu::DoiUuTien => "đã đổi mức ưu tiên từ {$this->priorityLabel($this->tu_gia_tri)} sang {$this->priorityLabel($this->den_gia_tri)}",
            LoaiSuKienPhieu::PhanCong => $this->assignmentSummary(),
            LoaiSuKienPhieu::BinhLuan => 'đã thêm bình luận công khai',
            LoaiSuKienPhieu::GhiChuNoiBo => 'đã thêm ghi chú nội bộ',
            LoaiSuKienPhieu::DinhKemTep => 'đã đính kèm tệp "'.($this->du_lieu_them['ten'] ?? 'tệp tin').'"',
            LoaiSuKienPhieu::MoLai => 'đã mở lại phiếu',
            LoaiSuKienPhieu::DongPhieu => 'đã đóng phiếu',
            LoaiSuKienPhieu::ThemNguoiTheoDoi => 'đã thêm người theo dõi',
        };
    }

    private function assignmentSummary(): string
    {
        $assignee = $this->du_lieu_them['nguoi_xu_ly'] ?? null;
        $team = $this->du_lieu_them['team'] ?? null;

        return match (true) {
            $assignee !== null && $team !== null => "đã phân công phiếu cho {$assignee} (nhóm {$team})",
            $assignee !== null => "đã phân công phiếu cho {$assignee}",
            $team !== null => "đã chuyển phiếu sang nhóm {$team}",
            default => 'đã cập nhật phân công',
        };
    }

    private function statusLabel(?string $value): string
    {
        return $value ? (TrangThaiPhieu::tryFrom($value)?->label() ?? $value) : '—';
    }

    private function priorityLabel(?string $value): string
    {
        return $value ? (UuTienPhieu::tryFrom($value)?->shortLabel() ?? $value) : '—';
    }
}
