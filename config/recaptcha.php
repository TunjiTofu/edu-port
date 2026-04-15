<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google reCAPTCHA v3 Keys
    |--------------------------------------------------------------------------
    | Get your keys at: https://www.google.com/recaptcha/admin/create
    | Choose "reCAPTCHA v3" when creating the site.
    |
    | Add these to your .env file:
    |   RECAPTCHA_SITE_KEY=your_site_key_here
    |   RECAPTCHA_SECRET_KEY=your_secret_key_here
    */

    'site_key'   => env('RECAPTCHA_SITE_KEY'),
    'secret_key' => env('RECAPTCHA_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Score Threshold
    |--------------------------------------------------------------------------
    | reCAPTCHA v3 returns a score between 0.0 (bot) and 1.0 (human).
    | 0.5 is Google's recommended default. Raise it (e.g. 0.7) to be
    | stricter, lower it (e.g. 0.3) if legitimate users are being blocked.
    */

    'threshold' => env('RECAPTCHA_THRESHOLD', 0.5),

];
