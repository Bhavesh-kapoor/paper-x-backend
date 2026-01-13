<?php

namespace App\Enums;

enum SessionStatus: string
{
    case ACTIVE = 'ACTIVE';
    case DEAL_WON = 'DEAL_WON';
    case DEAL_LOST = 'DEAL_LOST';
    case EXPIRED = 'EXPIRED';
    case CANCELLED = 'CANCELLED';
}





