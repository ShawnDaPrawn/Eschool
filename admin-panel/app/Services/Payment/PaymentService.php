<?php

declare(strict_types=1);

namespace App\Services\Payment;

use InvalidArgumentException;

class PaymentService
{
    /** Gateway ids, as stored in payment_transactions.payment_gateway. */
    public const STRIPE = 2;

    /**
     * Gateways that were removed (Build Bible, Phase 0). Their ids stay on old
     * transactions, so they still read correctly and are never reused.
     */
    public const RETIRED = [
        1 => 'Razorpay',
        3 => 'Paystack',
        4 => 'Flutterwave',
    ];

    /**
     * Where each gateway's webhook secret is kept: the settings table row the
     * fee settings page saves, then the .env key it also writes (via config()).
     */
    private const WEBHOOK_SECRETS = [
        'stripe' => ['stripe_webhook_secret', 'services.stripe.webhook_secret'],
    ];

    /**
     * The gateway adapter for a payment method the app sent.
     */
    public static function create($payment_method): PaymentInterface
    {
        $currency_code = self::currencyCode((int) $payment_method);

        switch ((int) $payment_method) {
            case self::STRIPE:
                $stripe_setting_secret_key = getSettings('stripe_secret_key');
                $stripe_secret_key = $stripe_setting_secret_key['stripe_secret_key'];

                return new StripePayment($stripe_secret_key, $currency_code);
            default:
                throw new \Exception('Invalid payment method selected');
        }
    }

    /**
     * The currency a gateway charges in: its own setting, else the school's.
     */
    public static function currencyCode(int $payment_method): string
    {
        $settings = getSettings();
        $default_currency_code = $settings['currency_code'] ?? '';

        $key = match ($payment_method) {
            self::STRIPE => 'stripe_currency_code',
            default => null,
        };

        $currency_code = ($key !== null && ! empty($settings[$key])) ? $settings[$key] : $default_currency_code;

        return strtoupper((string) $currency_code);
    }

    /**
     * The secret a gateway signs its webhooks with, or '' when none is set
     * (callers must then refuse the webhook).
     */
    public static function webhookSecret(string $gateway): string
    {
        if (! isset(self::WEBHOOK_SECRETS[$gateway])) {
            throw new InvalidArgumentException("Unknown payment gateway: $gateway");
        }
        [$setting, $config] = self::WEBHOOK_SECRETS[$gateway];

        $secret = trim((string) (getSettings($setting)[$setting] ?? ''));
        if ($secret === '') {
            $secret = trim((string) config($config, ''));
        }

        return $secret;
    }

    /**
     * Whether the amount a gateway reports matches what was asked for a
     * transaction of $total in $currency, in the units that gateway uses.
     */
    public static function amountMatches(int $payment_method, string $currency, $total, $reported_amount): bool
    {
        if (! is_numeric($reported_amount)) {
            return false;
        }

        try {
            return match ($payment_method) {
                self::STRIPE => StripePayment::amountInSubunits($currency, $total) === (int) $reported_amount,
                default => false,
            };
        } catch (\UnhandledMatchError) {
            // A currency the gateway class doesn't know: nothing was charged in it by us.
            return false;
        }
    }
}
