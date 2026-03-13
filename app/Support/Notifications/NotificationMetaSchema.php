<?php

namespace App\Support\Notifications;

use App\Enums\NotificationType;
use InvalidArgumentException;

class NotificationMetaSchema
{
    /**
     * Required meta keys per notification type.
     *
     * @var array<string, array<int, string>>
     */
    public const REQUIRED_KEYS = [
        NotificationType::MATCH_FOUND->value => ['inquiry_id', 'material_name', 'counterparty_name'],
        NotificationType::FIRST_RESPONSE->value => ['inquiry_id', 'responder_name'],
        NotificationType::OFFER_ACCEPTED->value => ['offer_id', 'inquiry_id'],
        NotificationType::OFFER_REJECTED->value => ['offer_id', 'inquiry_id'],
        NotificationType::RTD_STARTED->value => ['rtd_order_id', 'counterparty_name'],
        NotificationType::PAYMENT_STATUS_CHANGED->value => ['payment_id', 'status', 'reference_id'],
    ];

    public static function validate(NotificationType $type, array $meta): void
    {
        $requiredKeys = self::REQUIRED_KEYS[$type->value] ?? [];

        $missingKeys = [];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $meta)) {
                $missingKeys[] = $key;
            }
        }

        if ($missingKeys !== []) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid notification meta for type %s. Missing keys: %s',
                    $type->value,
                    implode(', ', $missingKeys)
                )
            );
        }
    }
}

