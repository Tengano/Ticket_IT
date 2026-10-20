<?php

namespace App\Enums;

enum Urgency: int
{
    case Low = 1;
    case Medium = 2;
    case High = 3;

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Thấp – có thể chờ',
            self::Medium => 'Trung bình – cần xử lý sớm',
            self::High => 'Cao – đang bị gián đoạn công việc',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $urgency) {
            $options[(string) $urgency->value] = $urgency->label();
        }

        return $options;
    }
}
