<?php

/*
|--------------------------------------------------------------------------
| Feature flags
|--------------------------------------------------------------------------
*/

return [
    /*
     | Master payments switch. Set PAYMENTS_ENABLED=false in .env for the
     | free-launch period: posting fees are not deducted, RTD orders can be
     | connected without paying the platform fee, and RTD products can be
     | listed without a listing pack. Set true (default) to enforce payments.
     |
     | Keep this in sync with the frontend flag PAYMENTS_ENABLED in
     | PaperXApp/src/shared/constants/config.ts.
     */
    'payments_enabled' => (bool) env('PAYMENTS_ENABLED', true),
];
