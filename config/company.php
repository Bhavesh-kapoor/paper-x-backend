<?php

/*
|--------------------------------------------------------------------------
| Company / seller legal details printed on invoices (GST Tax Invoice)
|--------------------------------------------------------------------------
| Override any value in .env for production. Defaults are SPNP's real details.
*/

return [
    'name'        => env('COMPANY_NAME', 'Zupply'),
    'legal_name'  => env('COMPANY_LEGAL_NAME', 'SPNP Paper and Pack Pvt Ltd'),

    // Address lines (printed one per line in the seller block)
    'address_lines' => array_values(array_filter([
        env('COMPANY_ADDR_1', '2010, 9. Business Bay, Off to Link Road'),
        env('COMPANY_ADDR_2', 'Behind Evershine Mall'),
        env('COMPANY_ADDR_3', 'Malad West- 400064'),
    ])),
    // Kept for backward-compat (single-line address, older templates)
    'address'     => env('COMPANY_ADDRESS', 'Malad West, Mumbai - 400064'),

    'gstin'       => env('COMPANY_GSTIN', '27ABFCS6841Q1ZJ'),
    'pan'         => env('COMPANY_PAN', 'ABFCS6841Q'),
    'cin'         => env('COMPANY_CIN', 'U21099MH2021PTC356974'),
    'udyam'       => env('COMPANY_UDYAM', 'UDYAM-MH-19-0057133'),
    'state'       => env('COMPANY_STATE', 'Maharashtra'),
    'state_code'  => env('COMPANY_STATE_CODE', '27'),

    'email'       => env('COMPANY_EMAIL', 'support@zupply.in'),
    'phone'       => env('COMPANY_PHONE', ''),

    // SAC (service accounting code) shown against the invoice line item.
    'sac_code'    => env('COMPANY_SAC', '998319'),

    // Bank details (override in .env if they change).
    'bank' => [
        'name'        => env('COMPANY_BANK_NAME', 'Yes Bank Ltd'),
        'account'     => env('COMPANY_BANK_ACCOUNT', '022463200000691'),
        'branch_ifsc' => env('COMPANY_BANK_IFSC', 'YESB0000224, Shop No-10 & 101, Link Road, Malad West'),
    ],
];
