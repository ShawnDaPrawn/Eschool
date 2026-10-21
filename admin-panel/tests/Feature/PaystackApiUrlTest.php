<?php

namespace Tests\Feature;

use App\Models\Settings;
use App\Services\Payment\PaystackPayment;
use App\Services\SettingsConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every Paystack call carries the school's secret key, so it may only go to
 * Paystack's own API, whatever the fee settings form sends.
 */
class PaystackApiUrlTest extends TestCase
{
    use RefreshDatabase;

    private const ATTACKER = 'https://collector.example';

    public function test_the_fee_settings_form_cannot_change_the_paystack_api_url(): void
    {
        $this->seedLikeInstaller();

        $this->actingAs($this->superAdmin())->postJson('/fees-config/update', [
            'razorpay_status' => 0, 'stripe_status' => 0, 'flutterwave_status' => 0,
            'paystack_status' => 1, 'paystack_public_key' => 'pk_test_x', 'paystack_secret_key' => 'sk_test_x',
            'paystack_webhook_url' => 'https://school.test/webhook/paystack', 'paystack_currency_code' => 'NGN',
            'paystack_payment_url' => self::ATTACKER,
            'currency_code' => 'NGN', 'currency_symbol' => 'N', 'compulsory_fee_payment_mode' => 0, 'is_student_can_pay_fees' => 1,
        ])->assertJson(['error' => false]);

        $this->assertSame('sk_test_x', schoolSetting('paystack_secret_key'));
        $this->assertDatabaseMissing('settings', ['message' => self::ATTACKER]);
    }

    public function test_paystack_calls_always_go_to_the_official_api(): void
    {
        $this->seedLikeInstaller();
        // Saved by an older version, or written to the settings table some other way.
        Settings::updateOrInsert(['type' => 'paystack_payment_url'], ['message' => self::ATTACKER]);

        SettingsConfigService::apply();
        $this->assertNotSame(self::ATTACKER, config('paystack.paymentUrl'));

        config(['paystack.paymentUrl' => self::ATTACKER]);
        $paystack = new PaystackPayment('NGN');
        $this->assertSame('https://api.paystack.co', (fn () => $this->baseUrl)->call($paystack));
        $this->assertSame('api.paystack.co', (fn () => $this->client->getConfig('base_uri')->getHost())->call($paystack));
    }
}
