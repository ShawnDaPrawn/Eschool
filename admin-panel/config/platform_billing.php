<?php

declare(strict_types=1);

/*
 * Navigate Ed billing its schools (Action Plan 1.17 and SA.8, Build 1).
 * engineering/specs/platform/hq-billing.md.
 *
 * vat_percent         VAT as a decimal string ("15", "15.5"); each invoice
 *                     keeps the rate it was computed with.
 * payment_terms_days  days from the issue date to the due date.
 * grace_days          days after the due date before HQ lists the school as
 *                     due for suspension. HQ always suspends by hand.
 * reference_prefix    invoice and credit note references: NE-2026-0001.
 * seller              shown on the printable invoice.
 * messaging           SMS and WhatsApp pass-through (Action Plan 2.7): the
 *                     invoice for a month carries the texts sent in the
 *                     month before it, at what Twilio charged.
 *                     exchange_rates gives rands per unit of Twilio's
 *                     currency as a decimal string ("18.25"); without a rate
 *                     for a currency that was charged, the draft is blocked
 *                     until one is set and the draft is recomputed.
 */
return [
    'currency' => 'ZAR',
    'vat_percent' => (string) env('PLATFORM_BILLING_VAT_PERCENT', '15'),
    'payment_terms_days' => (int) env('PLATFORM_BILLING_TERMS_DAYS', 30),
    'grace_days' => (int) env('PLATFORM_BILLING_GRACE_DAYS', 14),
    'reference_prefix' => 'NE',
    'messaging' => [
        'exchange_rates' => [
            'USD' => (string) env('PLATFORM_BILLING_USD_ZAR_RATE', ''),
        ],
    ],
    'seller' => [
        'name' => (string) env('PLATFORM_BILLING_SELLER_NAME', 'Navigate Ed'),
        'address' => (string) env('PLATFORM_BILLING_SELLER_ADDRESS', ''),
        'vat_number' => (string) env('PLATFORM_BILLING_VAT_NUMBER', ''),
        'email' => (string) env('PLATFORM_BILLING_EMAIL', ''),
        'bank' => (string) env('PLATFORM_BILLING_BANK_DETAILS', ''),
    ],
];
