<?php

namespace App\Enums;

enum InquiryStatus: string
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
    case SESSION_LOCKED = 'SESSION_LOCKED';
    case DEAL_WON = 'DEAL_WON';
    case DEAL_LOST = 'DEAL_LOST';
    case SESSION_EXPIRED = 'SESSION_EXPIRED';
    case BRAND_CANCELLED = 'BRAND_CANCELLED';
}





