<?php

return [
    /*
    | Which payment driver to use.
    |   'test'   -> completes instantly, no credentials needed (LOCAL ONLY)
    |   'stripe' -> real Stripe Checkout
    */
    'payment_driver' => env('PAYMENT_DRIVER', 'test'),

    'currency' => env('MARKETPLACE_CURRENCY', 'USD'),

    /*
    | When true, uploaded product files are marked 'clean' immediately.
    | Set to FALSE in production and run a real malware scan instead.
    */
    'auto_clean_uploads' => env('AUTO_CLEAN_UPLOADS', true),

    'max_upload_mb' => env('MAX_UPLOAD_MB', 200),
];
