<?php

/*
|--------------------------------------------------------------------------
| Secrets
|--------------------------------------------------------------------------
|
| Secrets come from the environment: on Cloud Run, Secret Manager secrets are
| mounted as these variables. The matching settings row (saved from the panel)
| is only a fallback for local installs without them, and the settings pages
| neither show nor save a secret whose variable is set. Nothing ever writes
| database values into these keys, so they always say whether the
| environment provides the secret. See App\Services\Platform\Secrets\SecretSettings
| and `php artisan secrets:audit-settings`.
|
*/

return [

    // SMTP password (Email Settings, row mail_password).
    'mail_password' => env('MAIL_PASSWORD'),

    // Stripe secret key and webhook signing secret (Fees configuration).
    'stripe_secret_key' => env('STRIPE_SECRET_KEY'),
    'stripe_webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    // reCAPTCHA secret key (General Settings).
    'recaptcha_secret_key' => env('RECAPTCHA_SECRET_KEY'),

    // The Firebase service account JSON itself (the panel uploads a file instead).
    'fcm_service_account' => env('FCM_SERVICE_ACCOUNT_JSON'),

];
