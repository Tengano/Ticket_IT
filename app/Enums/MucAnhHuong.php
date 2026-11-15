<?php

namespace App\Enums;

enum MucAnhHuong: int
{
    case Thap = 1;
    case TrungBinh = 2;
    case Cao = 3;

    public function label(): string
    {
        return match ($this) {
            self::Thap => 'Thấp – ảnh hưởng 1 người',
            self::TrungBinh => 'Trung bình – ảnh hưởng 1 phòng ban',
            self::Cao => 'Cao – ảnh hưởng hệ thống dùng chung',
        };
    }

    public function tenNgan(): string
    {
        return match ($this) {
            self::Thap => 'Thấp',
            self::TrungBinh => 'Trung bình',
            self::Cao => 'Cao',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $muc) {
            $options[(string) $muc->value] = $muc->label();
        }

        return $options;
    }
}
