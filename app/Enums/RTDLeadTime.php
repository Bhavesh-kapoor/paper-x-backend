<?php

namespace App\Enums;

enum RTDLeadTime: string
{
    case SAME_DAY = 'SAME_DAY';
    case H24 = 'H24';
    case H48 = 'H48';
    case DAYS_3_5 = 'DAYS_3_5';

    public function acceptanceWindowMinutes(): int
    {
        return match ($this) {
            self::SAME_DAY => 10,
            self::H24      => 15,
            self::H48      => 30,
            self::DAYS_3_5 => 60,
        };
    }

    public function deliveryBufferDays(): int
    {
        return match ($this) {
            self::SAME_DAY => 2,
            self::H24      => 2,
            self::H48      => 3,
            self::DAYS_3_5 => 5,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::SAME_DAY => 'Within 24 hours',
            self::H24      => 'Within 24 hours',
            self::H48      => '24-48 hours',
            self::DAYS_3_5 => '48-72 hours',
        };
    }
}
