<?php

namespace App\Enums;

enum LoaiBinhLuan: string
{
    case CongKhai = 'cong_khai';
    case NoiBo = 'noi_bo';

    public function label(): string
    {
        return match ($this) {
            self::CongKhai => 'Bình luận công khai',
            self::NoiBo => 'Ghi chú nội bộ',
        };
    }

    public function isInternal(): bool
    {
        return $this === self::NoiBo;
    }
}
