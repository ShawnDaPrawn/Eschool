<?php

declare(strict_types=1);

/*
 * SMS and WhatsApp for urgent and safety notices (Action Plan 2.7).
 * engineering/specs/platform/sms-whatsapp.md.
 *
 * One NavigateEd Twilio account sends for every school: its credentials are
 * in config/services.php (twilio) from the environment, never in a school's
 * settings. A school switches each channel on, and chooses which of these
 * types use it, on Settings > SMS and WhatsApp; both are off until it does.
 *
 * Only the types listed here can ever be texted. For each:
 *  - content: "notice" sends only "You have an urgent message from <school>.
 *    Open the NavigateEd app." (never the body: the notice may be about one
 *    child); "broadcast_text" also sends the school's own title and message,
 *    but only for a notice sent to the whole school, otherwise the notice
 *    text is used.
 *  - whatsapp_notice_template: the approved notice-only WhatsApp template
 *    (Twilio Content SID, HX...), {{1}} = the school's name. Business-
 *    initiated WhatsApp messages need an approved template, so a type without
 *    one cannot be switched on for WhatsApp.
 *  - whatsapp_text_template (broadcast_text only, optional): the approved
 *    template with {{1}} = the school's name and {{2}} = the message; without
 *    it a whole-school notice also goes as the notice-only template.
 *    "school_text" always sends the school's title and message: only for
 *    types that are never about one child (emergency alerts).
 *  - every_enabled_channel (optional): the type goes by each channel the
 *    school has switched on, without being ticked for that channel.
 *
 *  - audiences: who may be texted it. "guardian" (the parent portal) is
 *    texted on the number the school office recorded on their parent record
 *    at this school; "staff" (teacher and admin portals) on the number on
 *    their own account. A school opts guardians and staff in separately.
 *
 * A number is texted only once its owner has verified it with a one-time
 * code (verification below), and each person chooses SMS, WhatsApp, both or
 * neither for each type, within what the school has switched on.
 */
return [
    'types' => [
        'comms.urgent' => [
            'content' => 'broadcast_text',
            'whatsapp_notice_template' => env('TWILIO_WHATSAPP_TEMPLATE_NOTICE'),
            'whatsapp_text_template' => env('TWILIO_WHATSAPP_TEMPLATE_URGENT_NOTICE'),
            'audiences' => ['guardian', 'staff'],
        ],
        'attendance.absence' => [
            'content' => 'notice',
            'whatsapp_notice_template' => env('TWILIO_WHATSAPP_TEMPLATE_NOTICE'),
            'audiences' => ['guardian'],
        ],
        'safeguarding.alert' => [
            'content' => 'notice',
            'whatsapp_notice_template' => env('TWILIO_WHATSAPP_TEMPLATE_NOTICE'),
            'audiences' => ['staff'],
        ],
        // Emergency alerts (Action Plan 5.8): never about one child, so the
        // text always carries the school's words, and they go by every
        // channel the school has switched on, without a per-type choice.
        'safety.critical' => [
            'audiences' => ['guardian', 'staff'],
            'content' => 'school_text',
            'every_enabled_channel' => true,
            'whatsapp_notice_template' => env('TWILIO_WHATSAPP_TEMPLATE_NOTICE'),
            'whatsapp_text_template' => env('TWILIO_WHATSAPP_TEMPLATE_URGENT_NOTICE'),
        ],
    ],

    // The portals of each audience.
    'audiences' => [
        'guardian' => ['parent'],
        'staff' => ['teacher', 'admin'],
    ],

    // One-time codes that prove a person holds the number they are texted on.
    // Sent by SMS through the same Twilio account; billed to the school like
    // any other text. Codes are six digits, kept only as a keyed hash.
    'verification' => [
        'code_minutes' => 10,
        'max_attempts' => 5,
        'lockout_minutes' => 60,
        'resend_seconds' => 60,
        'codes_per_hour' => 3,
    ],

    // Numbers recorded without a country code (0821234567) are South African.
    'default_country_code' => env('TWILIO_DEFAULT_COUNTRY_CODE', '27'),

    // How long after sending to look up Twilio's price for a message.
    'price_lookup' => [
        'after_minutes' => 10,
        'within_days' => 7,
        'batch' => 200,
    ],
];
