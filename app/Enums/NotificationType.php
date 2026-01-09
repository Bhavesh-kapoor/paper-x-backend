<?php

namespace App\Enums;

enum NotificationType: string
{
    case NEW_OPPORTUNITY = 'NEW_OPPORTUNITY';
    case SESSION_LOCKED = 'SESSION_LOCKED';
    case NEW_MESSAGE = 'NEW_MESSAGE';
    case DEAL_RESULT = 'DEAL_RESULT';
    case SESSION_EXPIRED = 'SESSION_EXPIRED';
}




