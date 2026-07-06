<?php

return [

    'inr_per_credit' => (float) env('WALLET_INR_PER_CREDIT', 1.0),

    'exact_credits_max' => (int) env('WALLET_EXACT_CREDITS_MAX', 5000),

];
