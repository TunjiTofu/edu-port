<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP Backup Codes
    |--------------------------------------------------------------------------
    |
    | These are admin-held backup codes that bypass the normal OTP flow.
    | Use case: a candidate contacts you saying they never received their SMS.
    | You verify their identity, then tell them to enter one of these codes.
    |
    | SECURITY RULES:
    |  - Never commit real codes to version control. Keep them in .env only.
    |  - Rotate these codes periodically (quarterly or after any suspected leak).
    |  - Each code works for ANY phone number and ANY purpose, so treat them
    |    like master keys — share them sparingly and over a secure channel.
    |  - All uses are logged at WARNING level so you can audit when backup
    |    codes are used.
    |
    | Set in .env as a comma-separated list of 6-digit codes:
    |   OTP_BACKUP_CODES=123456,789012,345678,901234,567890,123456,789012,345678,901234,567890
    |
    | Leave empty in .env to disable backup codes entirely:
    |   OTP_BACKUP_CODES=
    |
    */

    'backup_codes' => env('OTP_BACKUP_CODES', ''),

];
