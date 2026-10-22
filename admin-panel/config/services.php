<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    /*
    | Amazon SES, the transactional mail provider (ADR 0004): Cape Town region so
    | mail data stays in South Africa. Bounces, complaints and deliveries come
    | back through SNS to POST /webhook/mail/ses, which accepts only the topics
    | listed in ses_notifications.topic_arns.
    */

    'ses' => [
        // SES keys of their own (Secret Manager: navigateed-ses-access-key-id,
        // navigateed-ses-secret-access-key), else the general AWS keys.
        'key' => env('SES_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID')),
        'secret' => env('SES_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY')),
        'region' => env('AWS_SES_REGION', 'af-south-1'),
        'options' => array_filter([
            'ConfigurationSetName' => env('AWS_SES_CONFIGURATION_SET'),
        ]),
    ],

    'ses_notifications' => [
        'topic_arns' => array_values(array_filter(array_map('trim', explode(',', (string) env('SES_SNS_TOPIC_ARNS', ''))))),
    ],

    /*
    | Payment gateway (Stripe; Razorpay, Paystack and Flutterwave were removed).
    | The settings table holds the keys the fee settings page saves; these .env
    | keys are the fallback. Code reads them through config() so they still
    | work after `php artisan config:cache`.
    */

    'stripe' => [
        'key' => env('STRIPE_PUBLISHABLE_KEY'),
        'secret' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

];
