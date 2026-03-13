<?php

namespace App\Enums;

enum NotificationType: string
{
    case MATCH_FOUND = 'MATCH_FOUND';
    case FIRST_RESPONSE = 'FIRST_RESPONSE';
    case OFFER_ACCEPTED = 'OFFER_ACCEPTED';
    case OFFER_REJECTED = 'OFFER_REJECTED';
    case RTD_STARTED = 'RTD_STARTED';
    case PAYMENT_STATUS_CHANGED = 'PAYMENT_STATUS_CHANGED';
}





