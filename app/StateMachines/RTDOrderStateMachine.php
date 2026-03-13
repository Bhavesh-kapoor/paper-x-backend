<?php

namespace App\StateMachines;

use App\Enums\RTDOrderStatus;
use App\Models\RtdOrder;
use InvalidArgumentException;

class RTDOrderStateMachine
{
    /**
     * Allowed transitions: from => [to, ...]
     */
    private const TRANSITIONS = [
        'REQUESTED'     => ['ACCEPTED', 'DECLINED', 'EXPIRED'],
        'ACCEPTED'      => ['PAID', 'CANCELLED'],
        'PAID'          => ['IN_PRODUCTION'],
        'IN_PRODUCTION' => ['DISPATCHED'],
        'DISPATCHED'    => ['COMPLETED'],
        'COMPLETED'     => ['DISPUTED'],
    ];

    public function canTransition(RTDOrderStatus $from, RTDOrderStatus $to): bool
    {
        $allowed = self::TRANSITIONS[$from->value] ?? [];

        return in_array($to->value, $allowed, true);
    }

    public function transition(RtdOrder $order, RTDOrderStatus $newStatus): void
    {
        $currentStatus = $order->status;

        if (!$this->canTransition($currentStatus, $newStatus)) {
            throw new InvalidArgumentException(
                "Invalid RTD order transition: {$currentStatus->value} → {$newStatus->value}"
            );
        }

        $order->status = $newStatus;
        $order->save();
    }

    public function getAllowedTransitions(RTDOrderStatus $from): array
    {
        $values = self::TRANSITIONS[$from->value] ?? [];

        return array_map(
            fn (string $v) => RTDOrderStatus::from($v),
            $values
        );
    }
}
