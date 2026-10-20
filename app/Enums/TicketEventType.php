<?php

namespace App\Enums;

enum TicketEventType: string
{
    case Created = 'created';
    case Updated = 'updated';
    case StatusChanged = 'status_changed';
    case PriorityChanged = 'priority_changed';
    case Assigned = 'assigned';
    case Commented = 'commented';
    case InternalNoteAdded = 'internal_note_added';
    case AttachmentAdded = 'attachment_added';
    case Reopened = 'reopened';
    case Closed = 'closed';
    case WatcherAdded = 'watcher_added';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Tạo ticket',
            self::Updated => 'Cập nhật ticket',
            self::StatusChanged => 'Đổi trạng thái',
            self::PriorityChanged => 'Đổi mức ưu tiên',
            self::Assigned => 'Phân công',
            self::Commented => 'Bình luận',
            self::InternalNoteAdded => 'Ghi chú nội bộ',
            self::AttachmentAdded => 'Đính kèm tệp',
            self::Reopened => 'Mở lại ticket',
            self::Closed => 'Đóng ticket',
            self::WatcherAdded => 'Theo dõi ticket',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Created => '＋',
            self::Updated => '✎',
            self::StatusChanged => '⇄',
            self::PriorityChanged => '⚑',
            self::Assigned => '➜',
            self::Commented => '💬',
            self::InternalNoteAdded => '🔒',
            self::AttachmentAdded => '📎',
            self::Reopened => '↺',
            self::Closed => '✔',
            self::WatcherAdded => '👁',
        };
    }
}
