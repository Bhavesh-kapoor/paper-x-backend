<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Engine version: v1 (legacy) or v2 (Domain\MatchEngine)
    |--------------------------------------------------------------------------
    */
    'engine_version' => env('MATCH_ENGINE', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | Use location in matchmaking
    |--------------------------------------------------------------------------
    |
    | When false, matching is "all India" — materials, specs, etc. matter but
    | location/distance does not affect who gets matched. Default true so
    | radius filtering applies; set to false for nationwide matching.
    |
    */
    'use_location_in_matching' => env('MATCHMAKING_USE_LOCATION', true),

    /*
    |--------------------------------------------------------------------------
    | Radius limits (kilometres)
    |--------------------------------------------------------------------------
    */
    'normal_radius_km' => env('MATCH_NORMAL_RADIUS_KM', 50),
    'urgent_radius_km' => env('MATCH_URGENT_RADIUS_KM', 100),

    /*
    |--------------------------------------------------------------------------
    | Lifecycle: auto-expiry & response cap
    |--------------------------------------------------------------------------
    */
    'auto_expiry_days' => env('MATCH_AUTO_EXPIRY_DAYS', 2),
    'max_responses'    => env('MATCH_MAX_RESPONSES', 10),
    'response_limit'   => env('MATCH_RESPONSE_LIMIT', 10),

    /*
    |--------------------------------------------------------------------------
    | Top-N matches to persist per inquiry
    |--------------------------------------------------------------------------
    */
    'normal_top_n' => 10,
    'urgent_top_n' => 50,

    /*
    |--------------------------------------------------------------------------
    | Scoring weights (must sum to 100)
    |--------------------------------------------------------------------------
    */
    'weights' => [
        'spec'      => 45,
        'distance'  => 20,
        'activity'  => 15,
        'freshness' => 10,
        'capacity'  => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Lazy / dynamic matching (V2 only)
    |--------------------------------------------------------------------------
    |
    | When a user opens Sourcing Hub, ensure they are matched against active
    | inquiries they qualify for (even if the inquiry was posted before they
    | registered). Prevents full-table scan by limiting evaluations per request.
    |
    */
    'auto_match_on_login'    => env('MATCH_AUTO_MATCH_ON_LOGIN', true),
    'max_lazy_evaluations'   => (int) env('MATCH_MAX_LAZY_EVALUATIONS', 50),

];
