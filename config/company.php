<?php

/*
|--------------------------------------------------------------------------
| Company / seller legal details printed on invoices
|--------------------------------------------------------------------------
| Fill the real values in .env before going live:
| COMPANY_LEGAL_NAME=, COMPANY_ADDRESS=, COMPANY_GSTIN=, COMPANY_EMAIL=, COMPANY_PHONE=
*/

return [
    'name'       => env('COMPANY_NAME', 'Zupply'),
    'legal_name' => env('COMPANY_LEGAL_NAME', 'SPNP PaperNpack Pvt. Ltd.'),
    'address'    => env('COMPANY_ADDRESS', 'India'),
    'gstin'      => env('COMPANY_GSTIN', ''),
    'email'      => env('COMPANY_EMAIL', 'support@zupply.in'),
    'phone'      => env('COMPANY_PHONE', ''),
];
