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

    /**
     * Statuses that represent an active/in-progress order.
     * A brand is blocked from placing a new order for a product
     * while any order in one of these statuses exists.
     */
    public static function activeStatuses(): array
    {
        return [
            self::REQUESTED,
            self::ACCEPTED,
            self::PAID,
            self::IN_PRODUCTION,
            self::DISPATCHED,
            self::DISPUTED,
        ];
    }
}
