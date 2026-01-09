<?php

namespace App\Enums;

enum InquiryStatus: string
{
    case DRAFT = 'DRAFT';
    case MATCHING = 'MATCHING';
    case SESSION_LOCKED = 'SESSION_LOCKED';
    case DEAL_WON = 'DEAL_WON';
    case DEAL_LOST = 'DEAL_LOST';
    case SESSION_EXPIRED = 'SESSION_EXPIRED';
    case BRAND_CANCELLED = 'BRAND_CANCELLED';
}




