<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send any email
    | messages sent by your application. Alternative mailers may be setup
    | and used as needed; however, this mailer will be used by default.
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers to be used while
    | sending an e-mail. You will specify which one you are using for your
    | mailers below. You are free to add additional mailers as required.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses",
    |            "postmark", "log", "array", "failover"
    |
    */

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', 'smtp.gmail.com'),
            'port' => (int) env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME', ''),
            'password' => env('MAIL_PASSWORD', ''),
            'timeout' => null,
            'auth_mode' => null,
            'verify_peer' => false,
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        // The Email Configuration page has offered "amazon_ses" as a mailer;
        // it is the same SES transport.
        'amazon_ses' => [
            'transport' => 'ses',
        ],

        'mailgun' => [
            'transport' => 'mailgun',
        ],

        'postmark' => [
            'transport' => 'postmark',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -t -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all e-mails sent by your application to be sent from
    | the same address. Here, you may specify a name and address that is
    | used globally for all e-mails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],

    /*
    |--------------------------------------------------------------------------
    | School sending domains (Action Plan 2.2)
    |--------------------------------------------------------------------------
    |
    | A school connects its own domain under Settings, adds the DNS records the
    | page shows, and once Amazon SES has verified it the school's notices are
    | sent From "<school name> <notices@that domain>". Account mail (passwords,
    | invitations) keeps the platform address above. Off by default: while off
    | the page only explains it, nothing is sent to SES and no SES client is
    | built. The SES keys and region are services.ses.
    | engineering/specs/platform/sending-domains.md.
    |
    */

    'sending_domains' => [
        'enabled' => (bool) env('MAIL_SENDING_DOMAINS', false),

        // The address a school's notices come from, at its own domain.
        'local_part' => 'notices',

        // The custom MAIL FROM (bounce) subdomain of the school's domain.
        'mail_from_label' => 'bounce',

        // Domains no school may connect, besides the platform's own (the hosts
        // of APP_URL and the learner portal, and the platform From address),
        // comma separated. Their subdomains and parents are refused too.
        'reserved' => array_values(array_filter(array_map('trim', explode(',', (string) env('MAIL_SENDING_DOMAINS_RESERVED', 'navigateed.co.za,amazonses.com'))))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Markdown Mail Settings
    |--------------------------------------------------------------------------
    |
    | If you are using Markdown based email rendering, you may configure your
    | theme and component paths here, allowing you to customize the design
    | of the emails. Or, you may simply stick with the Laravel defaults!
    |
    */

    'markdown' => [
        'theme' => 'default',

        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],
];
