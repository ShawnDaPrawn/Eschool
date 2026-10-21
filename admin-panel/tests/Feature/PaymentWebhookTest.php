<?php

namespace Tests\Feature;

use App\Models\FeesChoiceable;
use App\Models\FeesPaid;
use App\Models\PaidInstallmentFee;
use App\Models\PaymentTransaction;
use App\Models\Settings;
use App\Models\User;
use App\Services\Payment\FeesPaymentService;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Online fee payments: the amount is worked out on the server, and the gateway
 * webhooks only record a payment that is signed, matches the transaction we
 * created, and hasn't been recorded already.
 *
 * Webhook controllers return normal responses, so these run in-process.
 */
class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRETS = [
        'razorpay_webhook_secret' => 'rzp-webhook-secret',
        'stripe_webhook_secret' => 'whsec_test_secret',
        'paystack_secret_key' => 'sk_test_paystack',
        'flutterwave_hash_key' => 'flw-secret-hash',
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


    public function test_flutterwave_cannot_be_enabled_without_its_webhook_secret_hash(): void
    {
        $response = $this->actingAs($this->superAdmin())->postJson('/fees-config/update', [
            'razorpay_status' => 0, 'stripe_status' => 0, 'paystack_status' => 0,
            'flutterwave_status' => 1,
            'flutterwave_public_key' => 'FLWPUBK-x', 'flutterwave_secret_key' => 'FLWSECK-x',
            'flutterwave_webhook_url' => 'https://school.test/webhook/flutterwave', 'flutterwave_currency_code' => 'NGN',
            'currency_code' => 'NGN', 'currency_symbol' => 'N', 'compulsory_fee_payment_mode' => 0, 'is_student_can_pay_fees' => 1,
        ]);

        // Without the hash every Flutterwave webhook would be refused and payments never confirmed.
        $response->assertJson(['error' => true]);
        $this->assertStringContainsString('flutterwave hash key', strtolower($response->json('message')));
    }
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
        $pending = $this->fees->createPendingTransaction($plan, $this->parentId, PaymentService::RAZORPAY);
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

    public function test_unsigned_or_wrongly_signed_razorpay_webhooks_are_refused(): void
    {
        $transaction = $this->pending(FeesPaymentService::COMPULSORY, gateway: PaymentService::RAZORPAY, orderId: 'order_1');
        $payload = $this->razorpayPayload('order_1', 100000);

        $this->razorpay($payload, null)->assertStatus(401);
        $this->razorpay($payload, hash_hmac('sha256', $payload, 'wrong-secret'))->assertStatus(401);

        // With no secret configured anywhere, even an HMAC made with an empty key is refused.
        Settings::where('type', 'razorpay_webhook_secret')->delete();
        config(['services.razorpay.webhook_secret' => null]);
        $this->razorpay($payload, hash_hmac('sha256', $payload, ''))->assertStatus(401);

        $this->assertSame(2, (int) $transaction->fresh()->payment_status);
        $this->assertSame(0, FeesPaid::count());
    }

    public function test_razorpay_payment_is_recorded_once_from_the_stored_transaction(): void
    {
        $transaction = $this->pending(FeesPaymentService::INSTALLMENTS, installments: [$this->installmentIds[1]], amount: 500, gateway: PaymentService::RAZORPAY, orderId: 'order_2');
        // Another student's pending installment: the notes point at it, but must not matter.
        $other = $this->pending(FeesPaymentService::INSTALLMENTS, installments: [$this->installmentIds[1]], amount: 500, gateway: PaymentService::RAZORPAY, orderId: 'order_other', studentId: $this->otherStudentId);
        $otherRow = PaidInstallmentFee::where('payment_transaction_id', $other->id)->first();

        $payload = $this->razorpayPayload('order_2', 50000, [
            'payment_transaction_id' => $other->id,
            'student_id' => $this->otherStudentId,
            'is_fully_paid' => 1,
            'installment_fees_paid' => json_encode([$otherRow->id]),
        ]);
        $signature = hash_hmac('sha256', $payload, self::SECRETS['razorpay_webhook_secret']);

        $this->razorpay($payload, $signature)->assertOk();
        $this->razorpay($payload, $signature)->assertOk(); // retried delivery

        $transaction->refresh();
        $this->assertSame(1, (int) $transaction->payment_status);
        $this->assertSame('pay_test', $transaction->payment_id);
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
        $transaction = $this->pending(FeesPaymentService::COMPULSORY, gateway: PaymentService::RAZORPAY, orderId: 'order_3');

        foreach ([$this->razorpayPayload('order_3', 100), $this->razorpayPayload('order_3', 100000, [], 'USD')] as $payload) {
            $this->razorpay($payload, hash_hmac('sha256', $payload, self::SECRETS['razorpay_webhook_secret']))->assertStatus(400);
        }

        $this->assertSame(2, (int) $transaction->fresh()->payment_status);
        $this->assertSame(0, FeesPaid::count());
    }

    public function test_late_failure_event_does_not_undo_a_payment(): void
    {
        $transaction = $this->pending(FeesPaymentService::OPTIONAL, optional: [$this->feeTypes['sports']], amount: 200, gateway: PaymentService::RAZORPAY, orderId: 'order_4');
        $paid = $this->razorpayPayload('order_4', 20000);
        $failed = str_replace('payment.captured', 'payment.failed', $paid);

        $this->razorpay($paid, hash_hmac('sha256', $paid, self::SECRETS['razorpay_webhook_secret']))->assertOk();
        $this->razorpay($failed, hash_hmac('sha256', $failed, self::SECRETS['razorpay_webhook_secret']))->assertOk();

        $this->assertSame(1, (int) $transaction->fresh()->payment_status);
        $this->assertSame(1, (int) FeesChoiceable::where('payment_transaction_id', $transaction->id)->value('status'));
    }

    public function test_failed_attempt_then_success_on_the_same_order_is_recorded(): void
    {
        $transaction = $this->pending(FeesPaymentService::OPTIONAL, optional: [$this->feeTypes['sports']], amount: 200, gateway: PaymentService::RAZORPAY, orderId: 'order_5');
        $paid = $this->razorpayPayload('order_5', 20000);
        $failed = str_replace('payment.captured', 'payment.failed', $paid);

        $this->razorpay($failed, hash_hmac('sha256', $failed, self::SECRETS['razorpay_webhook_secret']))->assertOk();
        $this->assertSame(0, (int) $transaction->fresh()->payment_status);
        $this->assertSame(0, FeesChoiceable::where('payment_transaction_id', $transaction->id)->count());

        $this->razorpay($paid, hash_hmac('sha256', $paid, self::SECRETS['razorpay_webhook_secret']))->assertOk();
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

    public function test_paystack_webhook_checks_signature_and_reference(): void
    {
        $transaction = $this->pending(FeesPaymentService::COMPULSORY, gateway: PaymentService::PAYSTACK, orderId: 'ref_123');
        $payload = json_encode(['event' => 'charge.success', 'data' => [
            'id' => 4242, 'reference' => 'ref_123', 'amount' => 100000, 'currency' => 'NGN', 'status' => 'success',
            'metadata' => ['payment_transaction_id' => $transaction->id, 'is_fully_paid' => 1, 'due_charges' => 5000],
        ]]);

        $this->paystack($payload, hash_hmac('sha512', $payload, 'not-the-key'))->assertStatus(401);
        $this->assertSame(2, (int) $transaction->fresh()->payment_status);

        $this->paystack($payload, hash_hmac('sha512', $payload, self::SECRETS['paystack_secret_key']))->assertOk();
        $this->assertSame(1, (int) $transaction->fresh()->payment_status);
        $this->assertSame('4242', $transaction->fresh()->payment_id);
        // Due charges from the metadata are ignored: none were charged.
        $this->assertSame(0, FeesChoiceable::where('is_due_charges', 1)->count());
    }

    public function test_flutterwave_webhook_requires_the_secret_hash_header(): void
    {
        $transaction = $this->pending(FeesPaymentService::COMPULSORY, gateway: PaymentService::FLUTTERWAVE, orderId: 'https://checkout.example/pay');
        $payload = json_encode([
            'event' => 'charge.completed',
            'data' => ['id' => 777, 'tx_ref' => 'abc', 'amount' => 1000, 'currency' => 'NGN', 'status' => 'successful'],
            'meta_data' => ['payment_transaction_id' => $transaction->id],
        ]);

        // The old check compared a server variable with itself: no header at all passed.
        $_SERVER['FLW_SECRET_HASH'] = self::SECRETS['flutterwave_hash_key'];
        try {
            $this->flutterwave($payload, null)->assertStatus(401);
        } finally {
            unset($_SERVER['FLW_SECRET_HASH']);
        }
        $this->flutterwave($payload, 'guess')->assertStatus(401);
        $this->assertSame(2, (int) $transaction->fresh()->payment_status);

        $this->flutterwave($payload, self::SECRETS['flutterwave_hash_key'])->assertOk();
        $this->flutterwave($payload, self::SECRETS['flutterwave_hash_key'])->assertOk();
        $this->assertSame(1, (int) $transaction->fresh()->payment_status);
        $this->assertEquals(1000, FeesPaid::where('student_id', $this->studentId)->sole()->total_amount);
    }

    public function test_compulsory_payment_with_due_charges_records_them_from_the_stored_total(): void
    {
        DB::table('session_years')->where('id', $this->sessionYearId)->update(['fee_due_date' => now()->subDays(10)->toDateString()]);
        $transaction = $this->pending(FeesPaymentService::COMPULSORY, amount: 1100, gateway: PaymentService::RAZORPAY, orderId: 'order_6');

        $payload = $this->razorpayPayload('order_6', 110000, ['due_charges' => 9999, 'is_due_charges' => 1]);
        $this->razorpay($payload, hash_hmac('sha256', $payload, self::SECRETS['razorpay_webhook_secret']))->assertOk();

        $this->assertSame(1, (int) $transaction->fresh()->payment_status);
        $due = FeesChoiceable::where('is_due_charges', 1)->sole();
        $this->assertEquals(100, $due->total_amount);
        $this->assertSame(1, (int) $due->status);
        $feesPaid = FeesPaid::where('student_id', $this->studentId)->sole();
        $this->assertEquals(1100, $feesPaid->total_amount);
        $this->assertEquals(100, $feesPaid->due_charges);
        $this->assertSame(1, (int) $feesPaid->is_fully_paid);
    }

    // ---- Helpers ------------------------------------------------------------------------------

    private function prepare(int $type, array $optional = [], array $installments = [], $amount = 1000, ?int $studentId = null): array
    {
        return $this->fees->prepare($studentId ?? $this->studentId, $this->sessionYearId, $type, $optional, $installments, $amount);
    }

    private function pending(int $type, array $optional = [], array $installments = [], $amount = 1000, int $gateway = PaymentService::RAZORPAY, ?string $orderId = null, ?int $studentId = null): PaymentTransaction
    {
        $plan = $this->prepare($type, $optional, $installments, $amount, $studentId);
        $transaction = $this->fees->createPendingTransaction($plan, $this->parentId, $gateway)['transaction'];
        $transaction->order_id = $orderId ?? 'order_'.uniqid();
        $transaction->save();

        return $transaction;
    }

    private function payThroughWebhook(PaymentTransaction $transaction): void
    {
        $payload = $this->razorpayPayload($transaction->order_id, (int) round($transaction->total_amount * 100));
        $this->razorpay($payload, hash_hmac('sha256', $payload, self::SECRETS['razorpay_webhook_secret']))->assertOk();
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

    private function razorpayPayload(string $orderId, int $amount, array $notes = [], string $currency = 'ZAR'): string
    {
        return json_encode([
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => [
                'id' => 'pay_test',
                'order_id' => $orderId,
                'amount' => $amount,
                'currency' => $currency,
                'notes' => $notes,
            ]]],
        ]);
    }

    private function razorpay(string $payload, ?string $signature): TestResponse
    {
        return $this->webhook('razorpay', $payload, $signature === null ? [] : ['HTTP_X_RAZORPAY_SIGNATURE' => $signature]);
    }

    private function stripe(string $payload, string $secret): TestResponse
    {
        $timestamp = time();
        $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return $this->webhook('stripe', $payload, ['HTTP_STRIPE_SIGNATURE' => $signature]);
    }

    private function paystack(string $payload, string $signature): TestResponse
    {
        return $this->webhook('paystack', $payload, ['HTTP_X_PAYSTACK_SIGNATURE' => $signature]);
    }

    private function flutterwave(string $payload, ?string $hash): TestResponse
    {
        return $this->webhook('flutterwave', $payload, $hash === null ? [] : ['HTTP_VERIF_HASH' => $hash]);
    }

    private function webhook(string $gateway, string $payload, array $headers): TestResponse
    {
        return $this->call('POST', '/webhook/'.$gateway, [], [], [], $headers + ['CONTENT_TYPE' => 'application/json'], $payload);
    }

    private function seedSchool(): void
    {
        $now = now();
        $this->sessionYearId = DB::table('session_years')->insertGetId([
            'name' => 'Current', 'start_date' => $now->copy()->subMonths(2)->toDateString(), 'end_date' => $now->copy()->addMonths(8)->toDateString(),
            'include_fee_installments' => 1, 'fee_due_date' => $now->copy()->addDays(30)->toDateString(), 'fee_due_charges' => 10,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $settings = self::SECRETS + [
            'session_year' => $this->sessionYearId,
            'currency_code' => 'ZAR',
            'razorpay_currency_code' => 'ZAR',
            'stripe_currency_code' => 'USD',
            'paystack_currency_code' => 'NGN',
            'flutterwave_currency_code' => 'NGN',
        ];
        foreach ($settings as $type => $message) {
            Settings::updateOrCreate(['type' => $type], ['message' => $message]);
        }

        $mediumId = DB::table('mediums')->insertGetId(['name' => 'English']);
        $this->classId = DB::table('classes')->insertGetId(['name' => 'Grade 5', 'medium_id' => $mediumId]);
        $otherClassId = DB::table('classes')->insertGetId(['name' => 'Grade 6', 'medium_id' => $mediumId]);
        $sectionId = DB::table('sections')->insertGetId(['name' => 'A']);
        $classSectionId = DB::table('class_sections')->insertGetId(['class_id' => $this->classId, 'section_id' => $sectionId]);
        $categoryId = DB::table('categories')->insertGetId(['name' => 'General']);

        $parentUser = $this->user('parent@example.com', 'Parent');
        $this->parentId = DB::table('parents')->insertGetId([
            'user_id' => $parentUser->id, 'first_name' => 'Pat', 'last_name' => 'Parent', 'gender' => 'Male',
            'email' => 'parent@example.com', 'mobile' => '0000000000', 'occupation' => 'None',
        ]);
        $this->studentId = $this->student('student1@example.com', 'GR1', $classSectionId, $categoryId);
        $this->otherStudentId = $this->student('student2@example.com', 'GR2', $classSectionId, $categoryId);

        foreach (['tuition' => [0, 600, $this->classId], 'transport' => [0, 400, $this->classId], 'sports' => [1, 200, $this->classId], 'other_class' => [1, 300, $otherClassId]] as $name => [$choiceable, $amount, $classId]) {
            $this->feeTypes[$name] = DB::table('fees_types')->insertGetId(['name' => $name]);
            DB::table('fees_classes')->insert([
                'session_year_id' => $this->sessionYearId, 'class_id' => $classId, 'fees_type_id' => $this->feeTypes[$name],
                'choiceable' => $choiceable, 'amount' => $amount,
            ]);
        }

        foreach ([[-10, 'First'], [60, 'Second']] as [$days, $name]) {
            $this->installmentIds[] = DB::table('installment_fees')->insertGetId([
                'name' => $name, 'due_date' => $now->copy()->addDays($days)->toDateString(), 'due_charges' => 5, 'session_year_id' => $this->sessionYearId,
            ]);
        }
    }

    private function student(string $email, string $grNumber, int $classSectionId, int $categoryId): int
    {
        $user = $this->user($email, 'Student');
        $studentId = DB::table('students')->insertGetId([
            'user_id' => $user->id, 'class_section_id' => $classSectionId, 'category_id' => $categoryId, 'admission_no' => $grNumber,
            'admission_date' => now()->toDateString(), 'father_id' => $this->parentId,
        ]);
        DB::table('student_sessions')->insert([
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
