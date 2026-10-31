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

    /*
    | Twilio: SMS and WhatsApp for urgent notices (Action Plan 2.7). One
    | NavigateEd account for the whole platform; never per school, never in a
    | settings row. The account SID and auth token come from Secret Manager
    | (navigateed-twilio-account-sid, navigateed-twilio-auth-token). Messages
    | go through the messaging service when it is set, otherwise from the SMS
    | sender; WhatsApp from the WhatsApp sender (or the messaging service).
    */

    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'messaging_service_sid' => env('TWILIO_MESSAGING_SERVICE_SID'),
        'sms_from' => env('TWILIO_SMS_FROM'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'),
        // The status callback URL Twilio is given and signs; empty means
        // APP_URL + /webhook/messaging/twilio.
        'status_callback_url' => env('TWILIO_STATUS_CALLBACK_URL'),
        'base_url' => 'https://api.twilio.com/2010-04-01',
    ],

    // The learner diary's own encryption root (engineering/specs/learning/learner-diary.md).
    // Set it to a separate 32-byte secret ("base64:..."); rotating it makes existing entries unreadable.
    'diary' => [
        'key' => env('DIARY_KEY'),
    ],

];
