<?php

namespace App\Enums;

enum ResponseStatus: string
{
    case PENDING = 'PENDING';
    case SHORTLISTED = 'SHORTLISTED';
    case SELECTED = 'SELECTED';
    case REJECTED = 'REJECTED';
    case WITHDRAWN = 'WITHDRAWN';
}
