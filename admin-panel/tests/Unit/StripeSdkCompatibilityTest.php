<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Payment\StripePayment;
use App\Services\Platform\Ledger\Money;
use PHPUnit\Framework\TestCase;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\CurlClient;

class StripeSdkCompatibilityTest extends TestCase
{
    public function test_create_and_retrieve_preserve_integer_amounts_and_explicit_reviewed_wire_version(): void
    {
        $transport = new class implements ClientInterface
        {
            public array $requests = [];

            public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
            {
                $this->requests[] = compact('method', 'absUrl', 'headers', 'params');

                return [json_encode(['id' => 'pi_synthetic_protocol', 'object' => 'payment_intent', 'amount' => 12345,
                    'currency' => 'zar', 'status' => 'requires_payment_method', 'metadata' => ['transaction' => 'synthetic-17']], JSON_THROW_ON_ERROR), 200, []];
            }
        };
        ApiRequestor::setHttpClient($transport);
        try {
            $payments = new StripePayment('no-network-synthetic-test-key', 'ZAR');
            $created = $payments->createAndFormatPaymentIntent(Money::ofCents(12345, 'ZAR'), ['transaction' => 'synthetic-17']);
            $retrieved = $payments->retrievePaymentIntent('pi_synthetic_protocol');
            foreach ([$created, $retrieved] as $result) {
                $this->assertSame(12345, $result['amount']);
                $this->assertSame('pending', $result['status']);
                $this->assertSame('zar', $result['currency']);
                $this->assertSame('synthetic-17', $result['metadata']['transaction']);
            }
            $this->assertCount(2, $transport->requests);
            $this->assertSame('post', $transport->requests[0]['method']);
            $this->assertSame(12345, $transport->requests[0]['params']['amount']);
            $this->assertSame(['transaction' => 'synthetic-17'], $transport->requests[0]['params']['metadata']);
            $this->assertArrayNotHasKey('payment_method_types', $transport->requests[0]['params']);
            $this->assertSame('get', $transport->requests[1]['method']);
            $this->assertStringEndsWith('/v1/payment_intents/pi_synthetic_protocol', $transport->requests[1]['absUrl']);
            foreach ($transport->requests as $request) {
                $this->assertContains('Stripe-Version: '.StripePayment::API_VERSION, $request['headers']);
            }
        } finally {
            ApiRequestor::setHttpClient(CurlClient::instance());
        }
    }
}
