<?php

namespace App\Enums;

enum ConverterStatus: string
{
    case PENDING = 'PENDING';
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
}
