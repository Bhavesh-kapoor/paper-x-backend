<?php

namespace App\Enums;

enum InquiryType: string
{
    case MATERIAL = 'material';
    case MACHINE = 'machine';
    case JOB = 'job';
}
