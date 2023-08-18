<?php

return [
    'secret'            => env('HCAPTCHA_SECRET'),
    'sitekey'           => env('HCAPTCHA_SITEKEY'),
    'server-get-config' => false,
    'options'           => [
        'timeout' => 30,
    ],
    'score_verification_enabled' => false, // This is an exclusive Enterprise feature
    'score_threshold' => 0.7 // Any requests above this score will be considered as spam
];
