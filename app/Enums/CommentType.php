<?php

namespace App\Enums;

enum CommentType: string
{
    case PublicComment = 'public';
    case InternalNote = 'internal';

    public function label(): string
    {
        return match ($this) {
            self::PublicComment => 'Bình luận công khai',
            self::InternalNote => 'Ghi chú nội bộ',
        };
    }

    public function isInternal(): bool
    {
        return $this === self::InternalNote;
    }
}
