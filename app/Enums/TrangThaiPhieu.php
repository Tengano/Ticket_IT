<?php

namespace App\Enums;

enum TrangThaiPhieu: string
{
    case Moi = 'moi';
    case DaPhanCong = 'da_phan_cong';
    case DangXuLy = 'dang_xu_ly';
    case ChoNguoiDung = 'cho_nguoi_dung';
    case ChoNhaCungCap = 'cho_nha_cung_cap';
    case DaGiaiQuyet = 'da_giai_quyet';
    case DaDong = 'da_dong';
    case MoLai = 'mo_lai';

    public function label(): string
    {
        return match ($this) {
            self::Moi => 'Mới',
            self::DaPhanCong => 'Đã phân công',
            self::DangXuLy => 'Đang xử lý',
            self::ChoNguoiDung => 'Chờ người dùng',
            self::ChoNhaCungCap => 'Chờ nhà cung cấp',
            self::DaGiaiQuyet => 'Đã giải quyết',
            self::DaDong => 'Đã đóng',
            self::MoLai => 'Mở lại',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Moi => 'bg-sky-100 text-sky-800 ring-sky-200',
            self::DaPhanCong => 'bg-indigo-100 text-indigo-800 ring-indigo-200',
            self::DangXuLy => 'bg-blue-100 text-blue-800 ring-blue-200',
            self::ChoNguoiDung => 'bg-amber-100 text-amber-800 ring-amber-200',
            self::ChoNhaCungCap => 'bg-orange-100 text-orange-800 ring-orange-200',
            self::DaGiaiQuyet => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            self::DaDong => 'bg-slate-200 text-slate-700 ring-slate-300',
            self::MoLai => 'bg-rose-100 text-rose-800 ring-rose-200',
        };
    }

    /**
     * Các trạng thái được phép chuyển tới (máy trạng thái).
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Moi => [self::DaPhanCong, self::DangXuLy, self::ChoNguoiDung, self::ChoNhaCungCap, self::DaDong],
            self::DaPhanCong => [self::DangXuLy, self::ChoNguoiDung, self::ChoNhaCungCap, self::DaGiaiQuyet, self::DaDong],
            self::DangXuLy => [self::ChoNguoiDung, self::ChoNhaCungCap, self::DaGiaiQuyet, self::DaDong],
            self::ChoNguoiDung => [self::DangXuLy, self::DaGiaiQuyet, self::DaDong],
            self::ChoNhaCungCap => [self::DangXuLy, self::DaGiaiQuyet, self::DaDong],
            self::DaGiaiQuyet => [self::DaDong, self::MoLai],
            self::DaDong => [self::MoLai],
            self::MoLai => [self::DaPhanCong, self::DangXuLy, self::ChoNguoiDung, self::ChoNhaCungCap, self::DaGiaiQuyet, self::DaDong],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::DaGiaiQuyet, self::DaDong], true);
    }

    public function isClosed(): bool
    {
        return $this === self::DaDong;
    }

    public function requiresAssignee(): bool
    {
        return in_array($this, [self::DaPhanCong, self::DangXuLy, self::ChoNguoiDung, self::ChoNhaCungCap, self::DaGiaiQuyet], true);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }
}
