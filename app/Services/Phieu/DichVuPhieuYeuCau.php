<?php

namespace App\Services\Phieu;

use App\Enums\LoaiSuKienPhieu;
use App\Enums\MucAnhHuong;
use App\Enums\MucKhanCap;
use App\Enums\TrangThaiPhieu;
use App\Enums\UuTienPhieu;
use App\Models\DanhMuc;
use App\Models\NguoiDung;
use App\Models\NhomXuLy;
use App\Models\PhieuYeuCau;
use App\Models\SuKienPhieu;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DichVuPhieuYeuCau
{
    public function __construct(private readonly DichVuThongBaoPhieu $notifier) {}

    /**
     * Tạo phiếu mới: sinh mã TK-YYYY-NNNNNN, suy ra ưu tiên từ Ảnh hưởng × Khẩn cấp,
     * gán nhóm mặc định theo danh mục, ghi sự kiện + lịch sử trạng thái.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(NguoiDung $requester, array $attributes): PhieuYeuCau
    {
        return DB::transaction(function () use ($requester, $attributes): PhieuYeuCau {
            $impact = MucAnhHuong::from((int) ($attributes['anh_huong'] ?? MucAnhHuong::TrungBinh->value));
            $urgency = MucKhanCap::from((int) ($attributes['khan_cap'] ?? MucKhanCap::TrungBinh->value));
            $category = isset($attributes['danh_muc_id'])
                ? DanhMuc::query()->find($attributes['danh_muc_id'])
                : null;

            $ticket = PhieuYeuCau::query()->create([
                'ma' => $this->nextCode(),
                'tieu_de' => $attributes['tieu_de'],
                'mo_ta' => $attributes['mo_ta'],
                'nguoi_yeu_cau_id' => $requester->id,
                'phong_ban_id' => $attributes['phong_ban_id'] ?? $requester->phong_ban_id,
                'vi_tri_id' => $attributes['vi_tri_id'] ?? $requester->vi_tri_id,
                'danh_muc_id' => $category?->id,
                'nhom_xu_ly_id' => $category?->nhom_mac_dinh_id,
                'trang_thai' => TrangThaiPhieu::Moi,
                'uu_tien' => UuTienPhieu::fromImpactUrgency($impact, $urgency),
                'anh_huong' => $impact,
                'khan_cap' => $urgency,
                'kenh' => $attributes['kenh'] ?? 'web',
            ]);

            $ticket->watchers()->syncWithoutDetaching([$requester->id]);

            $this->recordEvent($ticket, $requester, LoaiSuKienPhieu::TaoMoi, null, TrangThaiPhieu::Moi->value);
            $this->recordStatusHistory($ticket, null, TrangThaiPhieu::Moi, $requester, 'Khởi tạo phiếu');
            $this->notifier->ticketCreated($ticket);

            return $ticket->refresh();
        });
    }

    /**
     * Cập nhật thông tin cơ bản của phiếu (không gồm trạng thái/phân công).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(PhieuYeuCau $ticket, array $attributes, NguoiDung $actor): PhieuYeuCau
    {
        return DB::transaction(function () use ($ticket, $attributes, $actor): PhieuYeuCau {
            $changes = [];

            if (isset($attributes['tieu_de']) && $attributes['tieu_de'] !== $ticket->tieu_de) {
                $changes['tieu_de'] = $attributes['tieu_de'];
            }

            if (isset($attributes['mo_ta']) && $attributes['mo_ta'] !== $ticket->mo_ta) {
                $changes['mo_ta'] = $attributes['mo_ta'];
            }

            foreach (['danh_muc_id', 'phong_ban_id', 'vi_tri_id'] as $field) {
                if (array_key_exists($field, $attributes) && (int) $attributes[$field] !== (int) $ticket->{$field}) {
                    $changes[$field] = $attributes[$field] ?: null;
                }
            }

            if ($changes === []) {
                return $ticket;
            }

            $ticket->update($changes);
            $this->recordEvent($ticket, $actor, LoaiSuKienPhieu::CapNhat, null, null, ['fields' => array_keys($changes)]);

            return $ticket->refresh();
        });
    }

    public function changePriority(PhieuYeuCau $ticket, UuTienPhieu $priority, NguoiDung $actor): PhieuYeuCau
    {
        if ($ticket->uu_tien === $priority) {
            return $ticket;
        }

        return DB::transaction(function () use ($ticket, $priority, $actor): PhieuYeuCau {
            $from = $ticket->uu_tien;
            $ticket->update(['uu_tien' => $priority]);
            $this->recordEvent($ticket, $actor, LoaiSuKienPhieu::DoiUuTien, $from->value, $priority->value);

            return $ticket->refresh();
        });
    }

    public function assign(PhieuYeuCau $ticket, ?NguoiDung $assignee, ?NhomXuLy $team, NguoiDung $actor): PhieuYeuCau
    {
        return DB::transaction(function () use ($ticket, $assignee, $team, $actor): PhieuYeuCau {
            $updates = [
                'nguoi_xu_ly_id' => $assignee?->id,
            ];

            if ($team !== null) {
                $updates['nhom_xu_ly_id'] = $team->id;
            }

            if ($assignee !== null && $ticket->phan_cong_luc === null) {
                $updates['phan_cong_luc'] = now();
                $updates['trang_thai'] = TrangThaiPhieu::DaPhanCong;
            }

            $ticket->update($updates);

            if ($assignee !== null) {
                $assignee->update(['phan_cong_gan_nhat_luc' => now()]);
            }

            $this->recordEvent($ticket, $actor, LoaiSuKienPhieu::PhanCong, null, $assignee?->ten, [
                'nguoi_xu_ly' => $assignee?->ten,
                'nguoi_xu_ly_id' => $assignee?->id,
                'team' => $team?->ten ?? $ticket->assignedTeam?->ten,
                'nhom_xu_ly_id' => $team?->id ?? $ticket->nhom_xu_ly_id,
            ]);

            if ($assignee !== null) {
                $this->notifier->ticketAssigned($ticket->refresh(), $assignee, $actor);
            }

            return $ticket->refresh();
        });
    }

    public function changeStatus(PhieuYeuCau $ticket, TrangThaiPhieu $status, NguoiDung $actor, ?string $reason = null): PhieuYeuCau
    {
        if ($ticket->trang_thai === $status) {
            return $ticket;
        }

        if (! $ticket->trang_thai->canTransitionTo($status)) {
            throw ValidationException::withMessages([
                'trang_thai' => "Không thể chuyển phiếu từ \"{$ticket->trang_thai->label()}\" sang \"{$status->label()}\".",
            ]);
        }

        return DB::transaction(function () use ($ticket, $status, $actor, $reason): PhieuYeuCau {
            $from = $ticket->trang_thai;
            $now = now();
            $updates = ['trang_thai' => $status];

            $openHistory = $ticket->statusHistories()->whereNull('ket_thuc_luc')->latest()->first();

            if ($openHistory !== null) {
                $openHistory->update([
                    'ket_thuc_luc' => $now,
                ]);
            }

            if ($status === TrangThaiPhieu::DaGiaiQuyet) {
                $updates['giai_quyet_luc'] = $now;
            }

            if ($status === TrangThaiPhieu::DaDong) {
                $updates['dong_luc'] = $now;
                $updates['giai_quyet_luc'] = $ticket->giai_quyet_luc ?? $now;
            }

            if ($status === TrangThaiPhieu::MoLai) {
                $updates['giai_quyet_luc'] = null;
                $updates['dong_luc'] = null;
            }

            $ticket->update($updates);

            $this->recordStatusHistory($ticket, $from, $status, $actor, $reason);
            $this->recordEvent($ticket, $actor, LoaiSuKienPhieu::DoiTrangThai, $from->value, $status->value, [
                'ly_do' => $reason,
            ]);

            if ($status === TrangThaiPhieu::DaDong) {
                $this->recordEvent($ticket, $actor, LoaiSuKienPhieu::DongPhieu);
            }

            if ($status === TrangThaiPhieu::MoLai) {
                $this->recordEvent($ticket, $actor, LoaiSuKienPhieu::MoLai);
            }

            $this->notifier->statusChanged($ticket->refresh(), $actor, $from, $status);

            return $ticket->refresh();
        });
    }

    public function close(PhieuYeuCau $ticket, NguoiDung $actor, ?string $reason = null): PhieuYeuCau
    {
        return $this->changeStatus($ticket, TrangThaiPhieu::DaDong, $actor, $reason ?? 'Đóng phiếu');
    }

    public function reopen(PhieuYeuCau $ticket, NguoiDung $actor, ?string $reason = null): PhieuYeuCau
    {
        return $this->changeStatus($ticket, TrangThaiPhieu::MoLai, $actor, $reason ?? 'Người dùng mở lại phiếu');
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function recordEvent(
        PhieuYeuCau $ticket,
        ?NguoiDung $actor,
        LoaiSuKienPhieu $type,
        ?string $from = null,
        ?string $to = null,
        array $meta = [],
    ): SuKienPhieu {
        return SuKienPhieu::query()->create([
            'phieu_yeu_cau_id' => $ticket->id,
            'nguoi_thuc_hien_id' => $actor?->id,
            'loai' => $type,
            'tu_gia_tri' => $from,
            'den_gia_tri' => $to,
            'du_lieu_them' => $meta,
        ]);
    }

    private function recordStatusHistory(
        PhieuYeuCau $ticket,
        ?TrangThaiPhieu $from,
        TrangThaiPhieu $to,
        ?NguoiDung $actor,
        ?string $reason = null,
    ): void {
        $ticket->statusHistories()->create([
            'trang_thai_truoc' => $from,
            'trang_thai_sau' => $to,
            'nguoi_thuc_hien_id' => $actor?->id,
            'ly_do' => $reason,
            'bat_dau_luc' => now(),
        ]);
    }

    private function nextCode(): string
    {
        $year = now()->format('Y');
        $prefix = "TK-{$year}-";

        $latestCode = PhieuYeuCau::query()
            ->where('ma', 'like', $prefix.'%')
            ->lockForUpdate()
            ->max('ma');

        $sequence = $latestCode ? ((int) substr($latestCode, -6)) + 1 : 1;

        return sprintf('%s%06d', $prefix, $sequence);
    }
}
