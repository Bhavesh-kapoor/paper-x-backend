<?php

namespace App\Enums;

enum SessionStatus: string
{
    case DRAFT = 'DRAFT';
    case POSTED = 'POSTED';
    case MATCHING = 'MATCHING';
    case RESPONSES_RECEIVED = 'RESPONSES_RECEIVED';
    case LOCKED = 'LOCKED';
    case CHAT_ACTIVE = 'CHAT_ACTIVE';
    case DEAL_SUCCESS = 'DEAL_SUCCESS';
    case DEAL_FAILED = 'DEAL_FAILED';
    case EXPIRED = 'EXPIRED';
    case REPUBLISHED = 'REPUBLISHED';
    
    // Legacy support
    case ACTIVE = 'ACTIVE';
    case DEAL_WON = 'DEAL_WON';
    case DEAL_LOST = 'DEAL_LOST';
    case CANCELLED = 'CANCELLED';
}





