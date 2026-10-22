<?php

namespace Tests\Feature;

use App\Models\FeesChoiceable;
use App\Models\FeesPaid;
use App\Models\PaidInstallmentFee;
use App\Models\PaymentTransaction;
use App\Models\Platform\School;
use App\Models\Settings;
use App\Models\User;
use App\Services\Payment\FeesPaymentService;
use App\Services\Payment\PaymentService;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Online fee payments: the amount is worked out on the server, and the gateway
 * webhook only records a payment that is signed, matches the transaction we
 * created, and hasn't been recorded already. Stripe is the only gateway;
 * Razorpay, Paystack and Flutterwave were removed in Phase 0.
 *
 * Webhook controllers return normal responses, so these run in-process.
 */
class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRETS = [
        'stripe_webhook_secret' => 'whsec_test_secret',
    ];

    private int $sessionYearId;

    private int $classId;

    private int $parentId;

    private int $studentId;

    private int $otherStudentId;

    /** @var array<string, int> */
    private array $feeTypes = [];

    /** @var list<int> */
    private array $installmentIds = [];

    private FeesPaymentService $fees;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
        $this->seedSchool();
        $this->fees = app(FeesPaymentService::class);
    }

    // ---- Server-side fee computation -------------------------------------------------

    public function test_compulsory_fees_are_priced_on_the_server(): void
    {
        $this->assertRejected(fn () => $this->prepare(FeesPaymentService::COMPULSORY, amount: 1));

        $plan = $this->prepare(FeesPaymentService::COMPULSORY, amount: '1000.00');
        $this->assertSame(1000.0, $plan['total_amount']);
        $this->assertSame(1, $plan['is_fully_paid']);
        $this->assertNull($plan['due_charges']);
    }

    public function test_due_charges_are_required_once_the_due_date_has_passed(): void
    {
        DB::table('session_years')->where('id', $this->sessionYearId)->update(['fee_due_date' => now()->subDays(10)->toDateString()]);

        $this->assertRejected(fn () => $this->prepare(FeesPaymentService::COMPULSORY, amount: 1000));
        $plan = $this->prepare(FeesPaymentService::COMPULSORY, amount: 1100);
        $this->assertSame(1100.0, $plan['total_amount']);
        $this->assertSame(100.0, $plan['due_charges']);

        // On the due date itself the app may be in another timezone: either total is accepted.
        DB::table('session_years')->where('id', $this->sessionYearId)->update(['fee_due_date' => now()->toDateString()]);
        $this->assertNull($this->prepare(FeesPaymentService::COMPULSORY, amount: 1000)['due_charges']);
        $this->assertSame(100.0, $this->prepare(FeesPaymentService::COMPULSORY, amount: 1100)['due_charges']);
    }

    public function test_installments_are_priced_and_checked_on_the_server(): void
    {
        [$overdue, $upcoming] = $this->installmentIds;

        // 1000 split in two; the first is overdue with 5% charges.
        $this->assertRejected(fn () => $this->prepare(FeesPaymentService::INSTALLMENTS, installments: [$overdue], amount: 500));
        $plan = $this->prepare(FeesPaymentService::INSTALLMENTS, installments: [$overdue], amount: 525);
        $this->assertSame(0, $plan['is_fully_paid']);
        $this->assertSame([['installment_fee_id' => $overdue, 'amount' => 500.0, 'due_charges' => 25.0]], $plan['installments']);

        $this->assertSame(1, $this->prepare(FeesPaymentService::INSTALLMENTS, installments: [$overdue, $upcoming], amount: 1025)['is_fully_paid']);

        $this->assertRejected(fn () => $this->prepare(FeesPaymentService::INSTALLMENTS, installments: [], amount: 0));
        $this->assertRejected(fn () => $this->prepare(FeesPaymentService::INSTALLMENTS, installments: [$overdue, $overdue], amount: 1050));
        $this->assertRejected(fn () => $this->prepare(FeesPaymentService::INSTALLMENTS, installments: [999999], amount: 500));

        // An installment already paid can't be paid again, nor can the full compulsory amount.
        $this->payThroughWebhook($this->pending(FeesPaymentService::INSTALLMENTS, installments: [$overdue], amount: 525));
        $this->assertRejected(fn () => $this->prepare(FeesPaymentService::INSTALLMENTS, installments: [$overdue], amount: 525));
        $this->assertRejected(fn () => $this->prepare(FeesPaymentService::COMPULSORY, amount: 1000));
        $this->assertSame(1, $this->prepare(FeesPaymentService::INSTALLMENTS, installments: [$upcoming], amount: 500)['is_fully_paid']);
    }

    public function test_optional_fees_must_belong_to_the_class_and_not_be_paid(): void
    {
        $plan = $this->prepare(FeesPaymentService::OPTIONAL, optional: [$this->feeTypes['sports']], amount: 200);
        $this->assertSame([['fees_type_id' => $this->feeTypes['sports'], 'amount' => 200.0]], $plan['optional_fees']);
        $this->assertSame(0, $plan['is_fully_paid']);

        // Client-sent amounts per fee are ignored, the total must match.
        $this->assertRejected(fn () => $this->prepare(FeesPaymentService::OPTIONAL, optional: [['id' => $this->feeTypes['sports'], 'amount' => 1]], amount: 1));
        // A fee type set up for another class only.
        $this->assertRejected(fn () => $this->prepare(FeesPaymentService::OPTIONAL, optional: [$this->feeTypes['other_class']], amount: 300));

        $this->payThroughWebhook($this->pending(FeesPaymentService::OPTIONAL, optional: [$this->feeTypes['sports']], amount: 200));
        $this->assertRejected(fn () => $this->prepare(FeesPaymentService::OPTIONAL, optional: [$this->feeTypes['sports']], amount: 200));
    }

    public function test_pending_transaction_and_gateway_metadata_use_server_values(): void
    {
        $plan = $this->prepare(FeesPaymentService::INSTALLMENTS, installments: [$this->installmentIds[0]], amount: '525.00');
        $pending = $this->fees->createPendingTransaction($plan, $this->parentId, PaymentService::STRIPE);
        $metadata = $this->fees->gatewayMetadata($plan, $pending, $this->parentId, ['email' => 'parent@example.com', 'name' => 'Pat', 'mobile' => '1']);

        $this->assertEquals(525, $pending['transaction']->total_amount);
        $this->assertSame(2, (int) $pending['transaction']->payment_status);
        $this->assertSame($pending['transaction']->id, $metadata['payment_transaction_id']);
        $this->assertSame(0, $metadata['is_fully_paid']);
        $this->assertSame(json_encode($pending['installment_ids']), $metadata['installment_fees_paid']);

        $row = PaidInstallmentFee::findOrFail($pending['installment_ids'][0]);
        $this->assertEquals(500, $row->amount);
        $this->assertEquals(25, $row->due_charges);
        $this->assertSame(0, (int) $row->status);
    }

    // ---- Webhooks --------------------------------------------------------------------------

    public function test_payment_is_recorded_once_from_the_stored_transaction(): void
    {
        $transaction = $this->pending(FeesPaymentService::INSTALLMENTS, installments: [$this->installmentIds[1]], amount: 500, orderId: 'pi_2');
        // Another student's pending installment: the metadata points at it, but must not matter.
        $other = $this->pending(FeesPaymentService::INSTALLMENTS, installments: [$this->installmentIds[1]], amount: 500, orderId: 'pi_other', studentId: $this->otherStudentId);
        $otherRow = PaidInstallmentFee::where('payment_transaction_id', $other->id)->first();

        $payload = $this->stripeEvent('pi_2', 50000, metadata: [
            'payment_transaction_id' => $other->id,
            'student_id' => $this->otherStudentId,
            'is_fully_paid' => 1,
            'installment_fees_paid' => json_encode([$otherRow->id]),
        ]);

        $this->stripe($payload, self::SECRETS['stripe_webhook_secret'])->assertOk();
        $this->stripe($payload, self::SECRETS['stripe_webhook_secret'])->assertOk(); // retried delivery

        $transaction->refresh();
        $this->assertSame(1, (int) $transaction->payment_status);
        $this->assertSame(1, (int) PaidInstallmentFee::where('payment_transaction_id', $transaction->id)->value('status'));
        $this->assertSame(0, (int) $otherRow->fresh()->status);
        $this->assertSame(2, (int) $other->fresh()->payment_status);

        $feesPaid = FeesPaid::where('student_id', $this->studentId)->sole();
        $this->assertEquals(500, $feesPaid->total_amount); // added once
        $this->assertSame(0, (int) $feesPaid->is_fully_paid); // one of two installments
        $this->assertSame(0, FeesPaid::where('student_id', $this->otherStudentId)->count());
    }

    public function test_payment_with_a_different_amount_or_currency_is_not_recorded(): void
    {
        $transaction = $this->pending(FeesPaymentService::COMPULSORY, orderId: 'pi_3');

        foreach ([$this->stripeEvent('pi_3', 100), $this->stripeEvent('pi_3', 100000, currency: 'eur')] as $payload) {
            $this->stripe($payload, self::SECRETS['stripe_webhook_secret'])->assertStatus(400);
        }

        $this->assertSame(2, (int) $transaction->fresh()->payment_status);
        $this->assertSame(0, FeesPaid::count());
    }

    public function test_late_failure_event_does_not_undo_a_payment(): void
    {
        $transaction = $this->pending(FeesPaymentService::OPTIONAL, optional: [$this->feeTypes['sports']], amount: 200, orderId: 'pi_4');
        $paid = $this->stripeEvent('pi_4', 20000);
        $failed = $this->stripeEvent('pi_4', 0, 'payment_intent.payment_failed');

        $this->stripe($paid, self::SECRETS['stripe_webhook_secret'])->assertOk();
        $this->stripe($failed, self::SECRETS['stripe_webhook_secret'])->assertOk();

        $this->assertSame(1, (int) $transaction->fresh()->payment_status);
        $this->assertSame(1, (int) FeesChoiceable::where('payment_transaction_id', $transaction->id)->value('status'));
    }

    public function test_failed_attempt_then_success_on_the_same_order_is_recorded(): void
    {
        $transaction = $this->pending(FeesPaymentService::OPTIONAL, optional: [$this->feeTypes['sports']], amount: 200, orderId: 'pi_5');
        $paid = $this->stripeEvent('pi_5', 20000);
        $failed = $this->stripeEvent('pi_5', 0, 'payment_intent.payment_failed');

        $this->stripe($failed, self::SECRETS['stripe_webhook_secret'])->assertOk();
        $this->assertSame(0, (int) $transaction->fresh()->payment_status);
        $this->assertSame(0, FeesChoiceable::where('payment_transaction_id', $transaction->id)->count());

        $this->stripe($paid, self::SECRETS['stripe_webhook_secret'])->assertOk();
        $this->assertSame(1, (int) $transaction->fresh()->payment_status);
        $this->assertSame(1, (int) FeesChoiceable::where('payment_transaction_id', $transaction->id)->value('status'));
    }

    public function test_stripe_webhook_needs_a_configured_secret_and_valid_signature(): void
    {
        $transaction = $this->pending(FeesPaymentService::COMPULSORY, gateway: PaymentService::STRIPE, orderId: 'pi_test_1');
        $payload = json_encode([
            'id' => 'evt_1',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => [
                'id' => 'pi_test_1', 'object' => 'payment_intent', 'amount' => 100000, 'amount_received' => 100000,
                'currency' => 'usd', 'metadata' => ['payment_transaction_id' => 999, 'is_fully_paid' => 0],
            ]],
        ]);

        $this->stripe($payload, 'wrong-secret')->assertStatus(401);
        $this->assertSame(2, (int) $transaction->fresh()->payment_status);

        $this->stripe($payload, self::SECRETS['stripe_webhook_secret'])->assertOk();
        $this->assertSame(1, (int) $transaction->fresh()->payment_status);
        $feesPaid = FeesPaid::where('student_id', $this->studentId)->sole();
        $this->assertSame(1, (int) $feesPaid->is_fully_paid);

        Settings::where('type', 'stripe_webhook_secret')->delete();
        config(['services.stripe.webhook_secret' => null]);
        $this->stripe($payload, '')->assertStatus(401);
    }

    public function test_the_signature_is_checked_with_the_secret_of_the_payments_school(): void
    {
        $transaction = $this->pending(FeesPaymentService::COMPULSORY, gateway: PaymentService::STRIPE, orderId: 'pi_school_two');
        School::query()->forceCreate(['id' => 2, 'name' => 'Second School', 'slug' => 'second-school', 'timezone' => 'Africa/Johannesburg']);
        app(CurrentSchool::class)->run(2, fn () => Settings::query()->updateOrCreate(['type' => 'stripe_webhook_secret'], ['message' => 'whsec_school_two']));
        DB::table('payment_transactions')->where('id', $transaction->id)->update(['school_id' => 2]);
        $payload = $this->stripeEvent('pi_school_two', 100000, 'payment_intent.payment_failed');

        // The first school's secret does not sign the second school's payments.
        $this->stripe($payload, self::SECRETS['stripe_webhook_secret'])->assertStatus(401);
        $this->assertSame(2, (int) $transaction->fresh()->payment_status);

        $this->stripe($payload, 'whsec_school_two')->assertOk();
    }

    public function test_compulsory_payment_with_due_charges_records_them_from_the_stored_total(): void
    {
        DB::table('session_years')->where('id', $this->sessionYearId)->update(['fee_due_date' => now()->subDays(10)->toDateString()]);
        $transaction = $this->pending(FeesPaymentService::COMPULSORY, amount: 1100, orderId: 'pi_6');

        $payload = $this->stripeEvent('pi_6', 110000, metadata: ['due_charges' => 9999, 'is_due_charges' => 1]);
        $this->stripe($payload, self::SECRETS['stripe_webhook_secret'])->assertOk();

        $this->assertSame(1, (int) $transaction->fresh()->payment_status);
        $due = FeesChoiceable::where('is_due_charges', 1)->sole();
        $this->assertEquals(100, $due->total_amount);
        $this->assertSame(1, (int) $due->status);
        $feesPaid = FeesPaid::where('student_id', $this->studentId)->sole();
        $this->assertEquals(1100, $feesPaid->total_amount);
        $this->assertEquals(100, $feesPaid->due_charges);
        $this->assertSame(1, (int) $feesPaid->is_fully_paid);
    }

    public function test_removed_gateways_have_no_webhooks_and_their_old_transactions_still_read(): void
    {
        $old = [];
        foreach (array_keys(PaymentService::RETIRED) as $gateway) {
            $transaction = $this->pending(FeesPaymentService::COMPULSORY, gateway: $gateway, orderId: 'old_'.$gateway);
            $transaction->payment_status = 1;
            $transaction->save();
            $old[$gateway] = $transaction;
        }

        foreach (['razorpay', 'paystack', 'flutterwave'] as $gateway) {
            $this->webhook($gateway, '{}', [])->assertNotFound();
        }
        $this->get('/response/paystack/success')->assertNotFound();
        $this->get('/response/flutterwave/success')->assertNotFound();

        // The fee transaction log keeps showing which gateway took each payment.
        $rows = collect($this->actingAs($this->superAdmin())->getJson('/fees/transaction-logs/list?limit=50')->assertOk()->json('rows'))->keyBy('id');
        foreach ($old as $gateway => $transaction) {
            $this->assertSame($gateway, (int) $rows[$transaction->id]['payment_gateway']);
            $this->assertSame(1, (int) $rows[$transaction->id]['payment_status']);
        }
    }

    // ---- Helpers ------------------------------------------------------------------------------

    private function prepare(int $type, array $optional = [], array $installments = [], $amount = 1000, ?int $studentId = null): array
    {
        return $this->fees->prepare($studentId ?? $this->studentId, $this->sessionYearId, $type, $optional, $installments, $amount);
    }

    private function pending(int $type, array $optional = [], array $installments = [], $amount = 1000, int $gateway = PaymentService::STRIPE, ?string $orderId = null, ?int $studentId = null): PaymentTransaction
    {
        $plan = $this->prepare($type, $optional, $installments, $amount, $studentId);
        $transaction = $this->fees->createPendingTransaction($plan, $this->parentId, $gateway)['transaction'];
        $transaction->order_id = $orderId ?? 'order_'.uniqid();
        $transaction->save();

        return $transaction;
    }

    private function payThroughWebhook(PaymentTransaction $transaction): void
    {
        $payload = $this->stripeEvent($transaction->order_id, (int) round($transaction->total_amount * 100));
        $this->stripe($payload, self::SECRETS['stripe_webhook_secret'])->assertOk();
        $this->assertSame(1, (int) $transaction->fresh()->payment_status);
    }

    private function assertRejected(callable $callback): void
    {
        try {
            $callback();
        } catch (ValidationException) {
            $this->addToAssertionCount(1);

            return;
        }
        $this->fail('The fee payment request was accepted.');
    }

    /** A Stripe event for a payment intent, amount in cents of the school's Stripe currency (USD here). */
    private function stripeEvent(string $intentId, int $amountReceived, string $type = 'payment_intent.succeeded', array $metadata = [], string $currency = 'usd'): string
    {
        return json_encode([
            'id' => 'evt_'.uniqid(),
            'object' => 'event',
            'type' => $type,
            'data' => ['object' => [
                'id' => $intentId, 'object' => 'payment_intent', 'amount' => $amountReceived, 'amount_received' => $amountReceived,
                'currency' => $currency, 'metadata' => $metadata,
            ]],
        ]);
    }

    private function stripe(string $payload, string $secret): TestResponse
    {
        $timestamp = time();
        $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return $this->webhook('stripe', $payload, ['HTTP_STRIPE_SIGNATURE' => $signature]);
    }

    private function webhook(string $gateway, string $payload, array $headers): TestResponse
    {
        return $this->call('POST', '/webhook/'.$gateway, [], [], [], $headers + ['CONTENT_TYPE' => 'application/json'], $payload);
    }

    private function seedSchool(): void
    {
        $now = now();
        $this->sessionYearId = SchoolTable::query('session_years')->insertGetId([
            'name' => 'Current', 'start_date' => $now->copy()->subMonths(2)->toDateString(), 'end_date' => $now->copy()->addMonths(8)->toDateString(),
            'include_fee_installments' => 1, 'fee_due_date' => $now->copy()->addDays(30)->toDateString(), 'fee_due_charges' => 10,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $settings = self::SECRETS + [
            'session_year' => $this->sessionYearId,
            'currency_code' => 'ZAR',
            'stripe_currency_code' => 'USD',
        ];
        foreach ($settings as $type => $message) {
            Settings::updateOrCreate(['type' => $type], ['message' => $message]);
        }

        $mediumId = SchoolTable::query('mediums')->insertGetId(['name' => 'English']);
        $this->classId = SchoolTable::query('classes')->insertGetId(['name' => 'Grade 5', 'medium_id' => $mediumId]);
        $otherClassId = SchoolTable::query('classes')->insertGetId(['name' => 'Grade 6', 'medium_id' => $mediumId]);
        $sectionId = SchoolTable::query('sections')->insertGetId(['name' => 'A']);
        $classSectionId = SchoolTable::query('class_sections')->insertGetId(['class_id' => $this->classId, 'section_id' => $sectionId]);
        $categoryId = SchoolTable::query('categories')->insertGetId(['name' => 'General']);

        $parentUser = $this->user('parent@example.com', 'Parent');
        $this->parentId = SchoolTable::query('parents')->insertGetId([
            'user_id' => $parentUser->id, 'first_name' => 'Pat', 'last_name' => 'Parent', 'gender' => 'Male',
            'email' => 'parent@example.com', 'mobile' => '0000000000', 'occupation' => 'None',
        ]);
        $this->studentId = $this->student('student1@example.com', 'GR1', $classSectionId, $categoryId);
        $this->otherStudentId = $this->student('student2@example.com', 'GR2', $classSectionId, $categoryId);

        foreach (['tuition' => [0, 600, $this->classId], 'transport' => [0, 400, $this->classId], 'sports' => [1, 200, $this->classId], 'other_class' => [1, 300, $otherClassId]] as $name => [$choiceable, $amount, $classId]) {
            $this->feeTypes[$name] = SchoolTable::query('fees_types')->insertGetId(['name' => $name]);
            SchoolTable::query('fees_classes')->insert([
                'session_year_id' => $this->sessionYearId, 'class_id' => $classId, 'fees_type_id' => $this->feeTypes[$name],
                'choiceable' => $choiceable, 'amount' => $amount,
            ]);
        }

        foreach ([[-10, 'First'], [60, 'Second']] as [$days, $name]) {
            $this->installmentIds[] = SchoolTable::query('installment_fees')->insertGetId([
                'name' => $name, 'due_date' => $now->copy()->addDays($days)->toDateString(), 'due_charges' => 5, 'session_year_id' => $this->sessionYearId,
            ]);
        }
    }

    private function student(string $email, string $grNumber, int $classSectionId, int $categoryId): int
    {
        $user = $this->user($email, 'Student');
        $studentId = SchoolTable::query('students')->insertGetId([
            'user_id' => $user->id, 'class_section_id' => $classSectionId, 'category_id' => $categoryId, 'admission_no' => $grNumber,
            'admission_date' => now()->toDateString(), 'father_id' => $this->parentId,
        ]);
        SchoolTable::query('student_sessions')->insert([
            'student_id' => $studentId, 'class_section_id' => $classSectionId, 'session_year_id' => $this->sessionYearId, 'status' => 1,
        ]);

        return $studentId;
    }

    private function user(string $email, string $role): User
    {
        $user = User::forceCreate([
            'first_name' => 'Test', 'last_name' => $role, 'email' => $email, 'password' => Hash::make('secret123'),
            'gender' => 'Male', 'mobile' => '0000000000', 'status' => 1,
        ]);
        $user->assignRole($role);

        return $user;
    }
}
