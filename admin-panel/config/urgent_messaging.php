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
 *
 * Recipients are guardians (the parent portal) with a mobile number the
 * school office recorded on their parent record at this school.
 */
return [
    'types' => [
        'comms.urgent' => [
            'content' => 'broadcast_text',
            'whatsapp_notice_template' => env('TWILIO_WHATSAPP_TEMPLATE_NOTICE'),
            'whatsapp_text_template' => env('TWILIO_WHATSAPP_TEMPLATE_URGENT_NOTICE'),
        ],
        'attendance.absence' => [
            'content' => 'notice',
            'whatsapp_notice_template' => env('TWILIO_WHATSAPP_TEMPLATE_NOTICE'),
        ],
    ],

    // Who may receive texts. Staff numbers are not used in this step.
    'portals' => ['parent'],

    // Numbers recorded without a country code (0821234567) are South African.
    'default_country_code' => env('TWILIO_DEFAULT_COUNTRY_CODE', '27'),

    // How long after sending to look up Twilio's price for a message.
    'price_lookup' => [
        'after_minutes' => 10,
        'within_days' => 7,
        'batch' => 200,
    ],
];
