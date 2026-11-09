<?php

declare(strict_types=1);

/*
 * Learner subscriptions for the paid Mastery features (Action Plan 3.20) and
 * their revenue (SA.8, Build 3). engineering/specs/platform/learner-subscriptions.md.
 *
 * Daily practice stays free for every learner. The tutor (modules marked
 * 'paid' in config/modules.php) and the parent insights need an entitlement
 * once the platform feature flag below is on for the school. An unknown or
 * unset flag is off, so nothing changes for any school until HQ creates the
 * flag in /saas/flags and rolls it out.
 *
 * Money is integer cents. Prices are per period (one month), VAT inclusive.
 *
 * NEEDS THE OWNER'S DECISION: every price below is a placeholder. R89 for
 * the first child is the pricing hypothesis; the reduced price for each
 * further child, the school licence and a sponsored place are not decided.
 * Change them here (or through the env) once they are; a subscription keeps
 * the price it was asked for.
 */
return [
    'flag' => 'mastery_subscriptions',

    'pricing_confirmed' => false,

    'currency' => 'ZAR',

    'period_months' => 1,

    'prices' => [
        // The first child of a family (hypothesis: R89 a month).
        'per_child_cents' => (int) env('SUBSCRIPTION_PER_CHILD_CENTS', 8900),
        // Each further child of the same guardian at the same school (placeholder).
        'additional_child_cents' => (int) env('SUBSCRIPTION_ADDITIONAL_CHILD_CENTS', 6900),
        // A school licence, per learner per month, the default HQ starts from (placeholder; HQ records the agreed amount).
        'school_licence_per_learner_cents' => (int) env('SUBSCRIPTION_SCHOOL_LICENCE_PER_LEARNER_CENTS', 3500),
        // One sponsored place per month, the default HQ starts from (placeholder; HQ records the agreed amount).
        'sponsored_place_cents' => (int) env('SUBSCRIPTION_SPONSORED_PLACE_CENTS', 8900),
    ],

    // The reference families and sponsors quote on an EFT: NES-000001.
    'reference_prefix' => 'NES',

    // Shown to a guardian with the reference. Navigate Ed's own bank account,
    // the same one schools pay their invoices into.
    'eft_instructions' => (string) env('SUBSCRIPTION_EFT_INSTRUCTIONS', (string) env('PLATFORM_BILLING_BANK_DETAILS', '')),

    /*
     * The payment gateway is not chosen yet. Activation is manual: HQ Finance
     * records the EFT. A gateway plugs in as a SubscriptionGateway adapter
     * (App\Services\Platform\Subscriptions\Gateways) listed here by key and
     * named in 'gateway'. No adapter ships with this step.
     */
    'gateway' => env('SUBSCRIPTION_GATEWAY'),
    'gateways' => [],

    /*
     * Prompts to guardians (notification type subscription.prompt): off by
     * default on every channel; a school turns them on in its notification
     * rules. At most one every 'every_days' days per guardian and child, or
     * sooner (but not within 'min_gap_days') when the child's released
     * results moved into the support band. The notice never names the child
     * or says anything about marks.
     */
    'prompts' => [
        'every_days' => 30,
        'min_gap_days' => 7,
        'batch' => 200,
    ],
];
