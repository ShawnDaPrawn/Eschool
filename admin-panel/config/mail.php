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
    | SES streams (Action Plan 2.3)
    |--------------------------------------------------------------------------
    |
    | Each school sends through two Amazon SES configuration sets: "account"
    | (sign-in, passwords, invitations, welcome mails and safety notices) and
    | "bulk" (every other notice), named <prefix>-s<school id>-<stream>, plus a
    | platform pair <prefix>-platform-<stream> for mail without a school. Each
    | set publishes deliveries, bounces, complaints and rejects to the SNS topic
    | POST /webhook/mail/ses already reads, and has Virtual Deliverability
    | Manager options on. Off by default: while off no SES client is built,
    | mail:ensure-streams does nothing and mail carries no configuration set.
    | With it on, leave AWS_SES_CONFIGURATION_SET unset.
    | engineering/specs/platform/ses-streams.md.
    |
    */

    'streams' => [
        'enabled' => (bool) env('MAIL_SES_STREAMS', false),

        // Keeps staging and production apart in one SES account (letters, digits, hyphens).
        'prefix' => (string) env('MAIL_SES_STREAMS_PREFIX', 'ne'),

        // The SNS topic the sets publish to; it must be one of
        // services.ses_notifications.topic_arns (the first one when unset).
        'event_topic_arn' => env('MAIL_SES_STREAMS_TOPIC_ARN'),

        // Notification types sent on the account stream. Every mail that is
        // not a notice (passwords, invitations, welcome mails, admissions
        // replies) is account mail too. Other notices are bulk.
        'account_types' => ['account.*', 'approval.*', 'safeguarding.alert', 'comms.urgent', 'attendance.absence'],

        // VDM engagement tracking (opens and clicks) per stream. Off for the
        // account stream, so sign-in, password and invitation links are never
        // rewritten through a tracking domain. Optimised shared delivery is on
        // for both.
        'engagement_tracking' => ['account' => false, 'bulk' => true],

        // The /saas mail streams page: rates over this many days, flagged from
        // this many messages, at SES's review and pause levels.
        'report_days' => 30,
        'report_minimum' => 50,
        'bounce_review' => 0.05,
        'bounce_pause' => 0.10,
        'complaint_review' => 0.001,
        'complaint_pause' => 0.005,
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
