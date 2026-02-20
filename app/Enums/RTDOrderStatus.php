<?php

namespace App\Enums;

enum RTDOrderStatus: string
{
    case REQUESTED = 'REQUESTED';
    case ACCEPTED = 'ACCEPTED';
    case PAID = 'PAID';
    case IN_PRODUCTION = 'IN_PRODUCTION';
    case DISPATCHED = 'DISPATCHED';
    case COMPLETED = 'COMPLETED';
    case DECLINED = 'DECLINED';
    case EXPIRED = 'EXPIRED';
    case CANCELLED = 'CANCELLED';
    case DISPUTED = 'DISPUTED';

    public function label(): string
    {
        return match ($this) {
            self::REQUESTED     => 'Requested',
            self::ACCEPTED      => 'Accepted – Awaiting Payment',
            self::PAID          => 'Paid',
            self::IN_PRODUCTION => 'In Production',
            self::DISPATCHED    => 'Dispatched',
            self::COMPLETED     => 'Completed',
            self::DECLINED      => 'Declined',
            self::EXPIRED       => 'Expired',
            self::CANCELLED     => 'Cancelled',
            self::DISPUTED      => 'Disputed',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::DECLINED,
            self::EXPIRED,
            self::CANCELLED,
            self::DISPUTED,
        ]);
    }
}
