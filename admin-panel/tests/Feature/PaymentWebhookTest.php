<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** No gateway can write money before a separately configured integration. */
class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_retired_gateway_callbacks_cannot_create_money_records(): void
    {
        $this->seedLikeInstaller();
        foreach (['stripe', 'razorpay', 'paystack', 'flutterwave'] as $gateway) {
            $this->postJson('/webhook/'.$gateway, ['type' => 'payment_intent.succeeded', 'amount' => 10000])->assertNotFound();
        }
        $this->assertSame(0, DB::table('payments')->count());
        $this->assertSame(0, DB::table('ledger_entries')->count());
        $this->assertSame(0, DB::table('payment_transactions')->count());
    }
}
