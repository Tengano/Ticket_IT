<?php

namespace App\Enums;

enum MucKhanCap: int
{
    case Thap = 1;
    case TrungBinh = 2;
    case Cao = 3;

    public function label(): string
    {
        return match ($this) {
            self::Thap => 'Thấp – có thể chờ',
            self::TrungBinh => 'Trung bình – cần xử lý sớm',
            self::Cao => 'Cao – đang bị gián đoạn công việc',
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
