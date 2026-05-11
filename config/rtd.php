<?php

return [

    /*
    | When true, POST /rtd/orders/{id}/confirm-payment marks the order PAID without Razorpay.
    | Disable in production. Tests set RTD_ALLOW_DIRECT_CONFIRM_PAYMENT=true in phpunit.xml.
    */
    'allow_direct_confirm_payment' => (bool) env('RTD_ALLOW_DIRECT_CONFIRM_PAYMENT', false),

];
