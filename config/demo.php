<?php

/*
|--------------------------------------------------------------------------
| Demo / app-review accounts
|--------------------------------------------------------------------------
| Map of mobile => fixed OTP. These numbers NEVER receive a real SMS and
| ALWAYS accept the mapped OTP. Used to give App Store / Play Store reviewers
| a way to log in, since OTP login can't deliver an SMS to a reviewer.
|
| Add or override via .env (comma-separated pairs), e.g.:
|   DEMO_OTP_ACCOUNTS="9090909090:542376,9080706050:112233"
|
| IMPORTANT: keep this list small and remove numbers you no longer need —
| any number listed here can log in with its fixed OTP.
*/

$accounts = [
    '9090909090' => '542376',
];

$envAccounts = env('DEMO_OTP_ACCOUNTS');
if (is_string($envAccounts) && trim($envAccounts) !== '') {
    foreach (explode(',', $envAccounts) as $pair) {
        $parts = array_pad(array_map('trim', explode(':', $pair)), 2, null);
        if (!empty($parts[0]) && !empty($parts[1])) {
            $accounts[$parts[0]] = $parts[1];
        }
    }
}

return [
    'otp_accounts' => $accounts,
];
