<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Services\Platform\Ledger\LegacyAmount;
use App\Services\Platform\Ledger\Money;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

class StripePayment implements PaymentInterface
{
    // Reviewed against stripe-php 22 and the public Stripe versioning guide.
    // Keep wire behavior explicit when a future SDK changes its default.
    public const API_VERSION = '2026-09-30.endive';

    private StripeClient $stripe;

    private string $currencyCode;

    /**
     * StripePayment constructor.
     */
    public function __construct($secretKey, $currencyCode)
    {
        // Call Stripe Class and Create Payment Intent
        $this->stripe = new StripeClient([
            'api_key' => $secretKey,
            'stripe_version' => self::API_VERSION,
        ]);
        $this->currencyCode = $currencyCode;
    }

    /**
     * @return PaymentIntent
     *
     * @throws ApiErrorException
     */
    public function createPaymentIntent($amount, $customMetaData)
    {
        try {
            $amount = self::amountInSubunits($this->currencyCode, $amount);

            return $this->stripe->paymentIntents->create(
                [
                    'amount' => $amount,
                    'currency' => $this->currencyCode,
                    'metadata' => $customMetaData,
                ]
            );

        } catch (ApiErrorException $e) {
            throw $e;
        }
    }

    /**
     * @throws ApiErrorException
     */
    public function createAndFormatPaymentIntent($amount, $customMetaData): array
    {
        $paymentIntent = $this->createPaymentIntent($amount, $customMetaData);

        return $this->format($paymentIntent);
    }

    /**
     * @throws ApiErrorException
     */
    public function retrievePaymentIntent($paymentId): array
    {
        try {
            return $this->format($this->stripe->paymentIntents->retrieve($paymentId));
        } catch (ApiErrorException $e) {
            throw $e;
        }
    }

    /**
     * @return array
     */
    public function format($paymentIntent)
    {
        return $this->formatPaymentIntent($paymentIntent->id, $paymentIntent->amount, $paymentIntent->currency, $paymentIntent->status, $paymentIntent->metadata, $paymentIntent);
    }

    public function formatPaymentIntent($id, $amount, $currency, $status, $metadata, $paymentIntent): array
    {
        return [
            'id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount,
            'currency' => $paymentIntent->currency,
            'metadata' => $paymentIntent->metadata,
            'status' => match ($paymentIntent->status) {
                'canceled' => 'failed',
                'succeeded' => 'succeed',
                'processing', 'requires_action', 'requires_capture', 'requires_confirmation', 'requires_payment_method' => 'pending',
            },
            'payment_gateway_response' => $paymentIntent,
        ];
    }

    /**
     * The amount Stripe is asked for (and reports back in webhooks), in the
     * currency's smallest unit, after raising it to Stripe's minimum charge.
     */
    /**
     * The amount in the currency's smallest unit, as Stripe takes it, raised to
     * Stripe's minimum charge. Takes Money (cents); an old float or string
     * amount is converted to cents first.
     */
    public static function amountInSubunits(string $currency, $amount): int
    {
        $zeroDecimalCurrencies = [
            'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG',
            'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
        ];
        $currency = strtoupper($currency);
        $cents = $amount instanceof Money ? $amount->cents : (int) LegacyAmount::toCents($amount);
        $minimum = LegacyAmount::toCents(self::minimumAmount($currency, null));
        if ($minimum !== null && $cents <= $minimum) {
            $cents = $minimum;
        }

        if (in_array($currency, $zeroDecimalCurrencies)) {
            return Money::ofFraction($cents, 100)->cents;
        }

        return $cents;
    }

    /**
     * @return float|int
     */
    public function minimumAmountValidation($currency, $amount)
    {
        return self::minimumAmount(strtoupper((string) $currency), $amount);
    }

    private static function minimumAmount(string $currency, $amount)
    {
        $minimumAmount = match ($currency) {
            'USD', 'EUR', 'INR', 'NZD', 'SGD', 'BRL', 'CAD', 'AUD', 'CHF' => 0.50,
            'AED', 'PLN', 'RON' => 2.00,
            'BGN' => 1.00,
            'CZK' => 15.00,
            'DKK' => 2.50,
            'GBP' => 0.30,
            'HKD' => 4.00,
            'HUF' => 175.00,
            'JPY' => 50,
            'MXN', 'THB' => 10,
            'MYR' => 2,
            'NOK', 'SEK' => 3.00,
            'XAF' => 100,
            default => null,
        };
        if (! empty($minimumAmount)) {
            if ($amount === null) {
                return $minimumAmount;
            }
            if ($amount > $minimumAmount) {
                return $amount;
            }

            return $minimumAmount;
        }

        return $amount;
    }
}
