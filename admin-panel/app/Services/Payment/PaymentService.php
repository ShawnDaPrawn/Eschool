<?php

namespace App\Services\Payment;

use InvalidArgumentException;
use App\Models\PaymentConfiguration;
use App\Services\Payment\StripePayment;
use App\Services\Payment\PaystackPayment;
use App\Services\Payment\RazorpayPayment;
use App\Services\Payment\FlutterwavePayment;

class PaymentService {
    public const RAZORPAY = 1;
    public const STRIPE = 2;
    public const PAYSTACK = 3;
    public const FLUTTERWAVE = 4;

    /**
     * Where each gateway's webhook secret is kept: the settings table row the
     * fee settings page saves, then the .env key it also writes (via config()).
     */
    private const WEBHOOK_SECRETS = [
        'razorpay' => ['razorpay_webhook_secret', 'services.razorpay.webhook_secret'],
        'stripe' => ['stripe_webhook_secret', 'services.stripe.webhook_secret'],
        'paystack' => ['paystack_secret_key', 'services.paystack.secret_key'],
        'flutterwave' => ['flutterwave_hash_key', 'services.flutterwave.secret_hash'],
    ];

    /**
     * @param string $paymentGateway - Stripe
     * @return StripePayment
     */
    public static function create($payment_method) {
        $currency_code = self::currencyCode((int) $payment_method);

        switch ($payment_method) {
            case 1: // Razorpay
                $razorpay_setting_api_key = getSettings('razorpay_api_key');
                $razorpay_api_key = $razorpay_setting_api_key['razorpay_api_key'];
                $razorpay_setting_secret_key = getSettings('razorpay_secret_key');
                $razorpay_secret_key = $razorpay_setting_secret_key['razorpay_secret_key'];

                return new RazorpayPayment($razorpay_secret_key, $razorpay_api_key, $currency_code);
            case 2: // Stripe
                $stripe_setting_secret_key = getSettings('stripe_secret_key');
                $stripe_secret_key = $stripe_setting_secret_key['stripe_secret_key'];

                return new StripePayment($stripe_secret_key, $currency_code);
            case 3: // Paystack
                return new PaystackPayment($currency_code);
            case 4: //Flutterwave
                $flutterwave_setting_secret_key = getSettings('flutterwave_secret_key');
                $flutterwave_secret_key = $flutterwave_setting_secret_key['flutterwave_secret_key'];

                return new FlutterwavePayment($currency_code, $flutterwave_secret_key);
            default:
                throw new \Exception("Invalid payment method selected");
        }
    }

    /**
     * The currency a gateway charges in: its own setting, else the school's.
     */
    public static function currencyCode(int $payment_method): string {
        $settings = getSettings();
        $default_currency_code = $settings['currency_code'] ?? '';

        $key = match ($payment_method) {
            self::RAZORPAY => 'razorpay_currency_code',
            self::STRIPE => 'stripe_currency_code',
            self::PAYSTACK => 'paystack_currency_code',
            self::FLUTTERWAVE => 'flutterwave_currency_code',
            default => null,
        };

        $currency_code = ($key !== null && ! empty($settings[$key])) ? $settings[$key] : $default_currency_code;

        return strtoupper((string) $currency_code);
    }

    /**
     * The secret a gateway signs its webhooks with, or '' when none is set
     * (callers must then refuse the webhook).
     */
    public static function webhookSecret(string $gateway): string {
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
    public static function amountMatches(int $payment_method, string $currency, $total, $reported_amount): bool {
        if (! is_numeric($reported_amount)) {
            return false;
        }

        try {
            return match ($payment_method) {
                self::RAZORPAY => RazorpayPayment::amountInSubunits($currency, $total) === (int) $reported_amount,
                self::STRIPE => StripePayment::amountInSubunits($currency, $total) === (int) $reported_amount,
                self::PAYSTACK => PaystackPayment::amountInSubunits($total) === (int) $reported_amount,
                self::FLUTTERWAVE => abs(FlutterwavePayment::chargedAmount($total) - (float) $reported_amount) < 0.01,
                default => false,
            };
        } catch (\UnhandledMatchError) {
            // A currency the gateway class doesn't know: nothing was charged in it by us.
            return false;
        }
    }
}
