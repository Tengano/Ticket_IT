<?php

namespace App\Enums;

enum TicketPriority: string
{
    case P1 = 'P1';
    case P2 = 'P2';
    case P3 = 'P3';
    case P4 = 'P4';

    public function label(): string
    {
        return match ($this) {
            self::P1 => 'P1 – Nghiêm trọng',
            self::P2 => 'P2 – Cao',
            self::P3 => 'P3 – Trung bình',
            self::P4 => 'P4 – Thấp',
        };
    }

    public function shortLabel(): string
    {
        return $this->value;
    }

    public function color(): string
    {
        return match ($this) {
            self::P1 => 'bg-rose-100 text-rose-800 ring-rose-200',
            self::P2 => 'bg-orange-100 text-orange-800 ring-orange-200',
            self::P3 => 'bg-amber-100 text-amber-800 ring-amber-200',
            self::P4 => 'bg-slate-100 text-slate-700 ring-slate-200',
        };
    }

    /**
     * Ma trận Impact × Urgency → mức ưu tiên (Sprint 2).
     */
    public static function fromImpactUrgency(Impact $impact, Urgency $urgency): self
    {
        return match (true) {
            $impact === Impact::High && $urgency === Urgency::High => self::P1,
            $impact === Impact::High && $urgency === Urgency::Medium => self::P2,
            $impact === Impact::Medium && $urgency === Urgency::High => self::P2,
            $impact === Impact::High && $urgency === Urgency::Low => self::P3,
            $impact === Impact::Medium && $urgency === Urgency::Medium => self::P3,
            $impact === Impact::Low && $urgency === Urgency::High => self::P3,
            default => self::P4,
        };
    }

    public function firstResponseTargetMinutes(): int
    {
        return match ($this) {
            self::P1 => 15,
            self::P2 => 30,
            self::P3 => 120,
            self::P4 => 480,
        };
    }

    public function resolutionTargetMinutes(): int
    {
        return match ($this) {
            self::P1 => 120,
            self::P2 => 240,
            self::P3 => 480,
            self::P4 => 1440,
        };
    }

    public function isCritical(): bool
    {
        return $this === self::P1;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $priority) {
            $options[$priority->value] = $priority->label();
        }

        return $options;
    }
}
