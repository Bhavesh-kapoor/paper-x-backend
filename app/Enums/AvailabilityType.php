<?php

namespace App\Enums;

enum AvailabilityType: string
{
    case BUSINESS_HOURS = 'business_hours';
    case LATE_EVENING = 'late_evening';
    case NIGHT_EARLY_MORNING = 'night_early_morning';
}
