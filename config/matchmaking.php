<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Use location in matchmaking
    |--------------------------------------------------------------------------
    |
    | When false, matching is "all India" — materials, specs, etc. matter but
    | location/distance does not affect who gets matched. distance_km is still
    | computed and returned for display. Set to true when you want location-
    | based matching (e.g. prefer nearby dealers).
    |
    */
    'use_location_in_matching' => env('MATCHMAKING_USE_LOCATION', false),

];
