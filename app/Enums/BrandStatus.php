<?php

namespace App\Enums;

enum BrandStatus: string
{
    case PENDING = 'PENDING';
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
}
