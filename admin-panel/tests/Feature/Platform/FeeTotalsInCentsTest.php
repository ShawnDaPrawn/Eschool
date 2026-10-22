<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\FeesChoiceable;
use App\Models\FeesPaid;
use App\Models\PaidInstallmentFee;
use App\Services\Payment\FeesPaymentService;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Fee payments are worked out in cents and come to the same totals the float
 * code did, including installments that don't divide evenly and due charges
 * on a share, and the rows they write hold equal floats and cents.
 */
class FeeTotalsInCentsTest extends TestCase
{
    use RefreshDatabase;

    private const STUDENT = 700;

    private const CLASS_ID = 500;

    private int $sessionYearId;

    /** @var list<int> */
    private array $installments = [];

    protected function setUp(): void
    {
        parent::setUp();
        // Fee rows only; the people and classes they point at don't matter here.
        Schema::disableForeignKeyConstraints();

        $this->sessionYearId = DB::table('session_years')->insertGetId([
            'name' => '2026', 'default' => 1, 'start_date' => now()->subMonths(2)->toDateString(), 'end_date' => now()->addMonths(10)->toDateString(),
            'include_fee_installments' => 1, 'fee_due_date' => now()->subDays(5)->toDateString(), 'fee_due_charges' => 7,
        ]);
        $sectionId = DB::table('class_sections')->insertGetId(['class_id' => self::CLASS_ID, 'section_id' => 1]);
        DB::table('students')->insert(['id' => self::STUDENT, 'user_id' => 1, 'category_id' => 1, 'admission_no' => 'GR700', 'admission_date' => '2026-01-10']);
        DB::table('student_sessions')->insert(['student_id' => self::STUDENT, 'class_section_id' => $sectionId, 'session_year_id' => $this->sessionYearId, 'status' => 1, 'result' => 1]);
        // 1000.01 does not split into thirds; the optional fee has odd cents.
        foreach ([[1, 0, 600.01], [2, 0, 400], [3, 1, 333.33]] as [$type, $choiceable, $amount]) {
            DB::table('fees_classes')->insert(['class_id' => self::CLASS_ID, 'fees_type_id' => $type, 'choiceable' => $choiceable, 'amount' => $amount, 'session_year_id' => $this->sessionYearId]);
        }
        foreach ([[-3, 5], [20, 5], [50, 0]] as [$days, $percent]) {
            $this->installments[] = DB::table('installment_fees')->insertGetId([
                'name' => 'I'.$days, 'due_date' => now()->addDays($days)->toDateString(), 'due_charges' => $percent, 'session_year_id' => $this->sessionYearId,
            ]);
        }
    }

    protected function tearDown(): void
    {
        Schema::enableForeignKeyConstraints();
        parent::tearDown();
    }

    public function test_installments_that_do_not_divide_evenly_total_what_the_float_code_charged(): void
    {
        // The float code: share = 1000.01 / 3, due = share * 5 / 100, totals rounded once.
        $share = 1000.01 / 3;
        $overdue = $share * 5 / 100;
        $expected = round($share + $overdue + $share, 2);

        $plan = app(FeesPaymentService::class)->prepare(self::STUDENT, $this->sessionYearId, FeesPaymentService::INSTALLMENTS, [], [['id' => $this->installments[0]], ['id' => $this->installments[1]]], (string) $expected);

        $this->assertSame($expected, $plan['total_amount']);
        $this->assertSame((int) round($expected * 100), $plan['total_amount_cents']);
        // The rows add up to the charged total: the cent lost to rounding comes
        // off the row where the running total crosses it (16.6668 is stored as 16.66).
        $this->assertSame([
            ['installment_fee_id' => $this->installments[0], 'amount' => round($share, 2), 'due_charges' => 16.66],
            ['installment_fee_id' => $this->installments[1], 'amount' => round($share, 2), 'due_charges' => null],
        ], $plan['installments']);
        $this->assertEqualsWithDelta(round($overdue, 2), 16.66, 0.011, 'Each row stays within a cent of its own rounding');

        $pending = app(FeesPaymentService::class)->createPendingTransaction($plan, 1, PaymentService::STRIPE);
        $this->assertSame($plan['total_amount_cents'], $pending['transaction']->fresh()->money('total_amount')->cents);
        $this->assertSame(68334, $plan['total_amount_cents']);
        $rows = PaidInstallmentFee::whereIn('id', $pending['installment_ids'])->get();
        foreach ($rows as $row) {
            $this->assertSame(33334, $row->money('amount')->cents);
            $this->assertSame((float) DB::table('paid_installment_fees')->where('id', $row->id)->value('amount'), round($row->money('amount')->cents / 100, 2));
        }
        $this->assertSame($plan['total_amount_cents'], $rows->sum(fn ($row) => $row->money('amount')->cents + ($row->money('due_charges')?->cents ?? 0)), 'The stored rows add up to what the payment charges');
    }

    public function test_a_full_compulsory_payment_with_due_charges_is_recorded_in_cents(): void
    {
        $fees = app(FeesPaymentService::class);
        // 7 % of 1000.01 is 70.0007: the float code charged 1070.01.
        $plan = $fees->prepare(self::STUDENT, $this->sessionYearId, FeesPaymentService::COMPULSORY, [], [], '1070.01');
        $this->assertSame(1070.01, $plan['total_amount']);
        $this->assertSame(70.0, $plan['due_charges']);
        $this->assertSame(107001, $plan['total_amount_cents']);
        $this->assertSame(7000, $plan['due_charges_cents']);

        $transaction = $fees->createPendingTransaction($plan, 1, PaymentService::STRIPE)['transaction'];
        $fees->markPaid($transaction->id);

        $paid = FeesPaid::where('student_id', self::STUDENT)->sole();
        $this->assertSame(107001, $paid->money('total_amount')->cents);
        $this->assertSame(7000, $paid->money('due_charges')->cents);
        $this->assertSame(1070.01, $paid->total_amount);
        $this->assertSame(7000, FeesChoiceable::where('payment_transaction_id', $transaction->id)->where('is_due_charges', 1)->sole()->money('total_amount')->cents);
        $this->assertEquals(1070.01, DB::table('fees_paids')->where('id', $paid->id)->value('total_amount'));
    }

    public function test_an_optional_fee_and_the_app_amount_check_use_cents(): void
    {
        $fees = app(FeesPaymentService::class);

        $plan = $fees->prepare(self::STUDENT, $this->sessionYearId, FeesPaymentService::OPTIONAL, [['id' => 3]], [], 333.33);
        $this->assertSame([['fees_type_id' => 3, 'amount' => 333.33]], $plan['optional_fees']);
        $this->assertSame(33333, $plan['total_amount_cents']);

        // Two cents of rounding slack, as before; three is a changed amount.
        $fees->prepare(self::STUDENT, $this->sessionYearId, FeesPaymentService::OPTIONAL, [['id' => 3]], [], '333.35');
        $this->expectException(ValidationException::class);
        $fees->prepare(self::STUDENT, $this->sessionYearId, FeesPaymentService::OPTIONAL, [['id' => 3]], [], '333.36');
    }
}
