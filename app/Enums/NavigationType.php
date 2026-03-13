<?php

namespace App\Enums;

enum NavigationType: string
{
    case CHAT_THREAD = 'CHAT_THREAD';
    case SESSION = 'SESSION';
    case INQUIRY = 'INQUIRY';
    case RTD_ORDER = 'RTD_ORDER';
    case PAYMENT = 'PAYMENT';
}

