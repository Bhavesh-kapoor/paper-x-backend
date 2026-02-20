<?php

namespace App\Enums;

enum RTDPayoutStatus: string
{
    case HELD = 'HELD';
    case RELEASED = 'RELEASED';
    case HOLD_DISPUTE = 'HOLD_DISPUTE';

    public function label(): string
    {
        return match ($this) {
            self::HELD          => 'Held',
            self::RELEASED      => 'Released',
            self::HOLD_DISPUTE  => 'Held – Dispute',
        };
    }
}
