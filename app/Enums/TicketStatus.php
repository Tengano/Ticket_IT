<?php

namespace App\Enums;

enum TicketStatus: string
{
    case New = 'new';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case WaitingOnUser = 'waiting_on_user';
    case WaitingOnVendor = 'waiting_on_vendor';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Reopened = 'reopened';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Mới',
            self::Assigned => 'Đã phân công',
            self::InProgress => 'Đang xử lý',
            self::WaitingOnUser => 'Chờ người dùng',
            self::WaitingOnVendor => 'Chờ nhà cung cấp',
            self::Resolved => 'Đã giải quyết',
            self::Closed => 'Đã đóng',
            self::Reopened => 'Mở lại',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'bg-sky-100 text-sky-800 ring-sky-200',
            self::Assigned => 'bg-indigo-100 text-indigo-800 ring-indigo-200',
            self::InProgress => 'bg-blue-100 text-blue-800 ring-blue-200',
            self::WaitingOnUser => 'bg-amber-100 text-amber-800 ring-amber-200',
            self::WaitingOnVendor => 'bg-orange-100 text-orange-800 ring-orange-200',
            self::Resolved => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            self::Closed => 'bg-slate-200 text-slate-700 ring-slate-300',
            self::Reopened => 'bg-rose-100 text-rose-800 ring-rose-200',
        };
    }

    /**
     * Các trạng thái được phép chuyển tới (state machine).
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::Assigned, self::InProgress, self::WaitingOnUser, self::WaitingOnVendor, self::Closed],
            self::Assigned => [self::InProgress, self::WaitingOnUser, self::WaitingOnVendor, self::Resolved, self::Closed],
            self::InProgress => [self::WaitingOnUser, self::WaitingOnVendor, self::Resolved, self::Closed],
            self::WaitingOnUser => [self::InProgress, self::Resolved, self::Closed],
            self::WaitingOnVendor => [self::InProgress, self::Resolved, self::Closed],
            self::Resolved => [self::Closed, self::Reopened],
            self::Closed => [self::Reopened],
            self::Reopened => [self::Assigned, self::InProgress, self::WaitingOnUser, self::WaitingOnVendor, self::Resolved, self::Closed],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Resolved, self::Closed], true);
    }

    public function isClosed(): bool
    {
        return $this === self::Closed;
    }

    public function requiresAssignee(): bool
    {
        return in_array($this, [self::Assigned, self::InProgress, self::WaitingOnUser, self::WaitingOnVendor, self::Resolved], true);
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
