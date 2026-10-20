<?php

namespace App\Enums;

enum Impact: int
{
    case Low = 1;
    case Medium = 2;
    case High = 3;

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Thấp – ảnh hưởng 1 người',
            self::Medium => 'Trung bình – ảnh hưởng 1 phòng ban',
            self::High => 'Cao – ảnh hưởng hệ thống dùng chung',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $impact) {
            $options[(string) $impact->value] = $impact->label();
        }

        return $options;
    }
}
