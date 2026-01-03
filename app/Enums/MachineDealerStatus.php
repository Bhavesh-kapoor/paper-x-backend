<?php

namespace App\Enums;

enum MachineDealerStatus: string
{
    case PENDING = 'PENDING';
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
}
