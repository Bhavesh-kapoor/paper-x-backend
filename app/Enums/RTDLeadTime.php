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
            self::SAME_DAY => 'Same Day',
            self::H24      => '24 Hours',
            self::H48      => '48 Hours',
            self::DAYS_3_5 => '3-5 Days',
        };
    }
}
