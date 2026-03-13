<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Allowed couriers / transporters for dispatch proof
    |--------------------------------------------------------------------------
    */
    'allowed_couriers' => [
        'Delhivery',
        'BlueDart',
        'Ecom Express',
        'DTDC',
        'Xpressbees',
        'Shadowfax',
        'India Post',
        'VRL',
        'Local Tempo',
        'Other',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracking number validation
    |--------------------------------------------------------------------------
    | Pattern: alphanumeric, spaces, hyphens; min 5, max 100 chars.
    | Couriers can have specific patterns later via tracking_patterns keyed by name.
    */
    'tracking_pattern' => '/^[A-Za-z0-9\s\-]{5,100}$/',

    'tracking_patterns' => [
        // 'Delhivery' => '/^...$/',
    ],
];
