<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\FeesChoiceable;
use App\Models\FeesClass;
use App\Models\FeesPaid;
use App\Models\PaidInstallmentFee;
use App\Models\PaymentTransaction;
use App\Services\Platform\Ledger\LegacyAmount;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\MoneyCast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Money is integer cents with a currency, rounded half up and never a float;
 * the fee models keep their float columns and *_cents twins equal on every
 * write and serve the v1 API the same fields from the cents.
 */
class MoneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_decimal_strings_become_cents_rounded_half_up(): void
    {
        $this->assertSame(150000, Money::fromDecimalString('1500')->cents);
        $this->assertSame(123456, Money::fromDecimalString('1234.56')->cents);
        $this->assertSame(10, Money::fromDecimalString('0.1')->cents);
        $this->assertSame(13, Money::fromDecimalString('0.125')->cents);
        $this->assertSame(12, Money::fromDecimalString('0.1249')->cents);
        $this->assertSame(-13, Money::fromDecimalString('-0.125')->cents);
        $this->assertSame(50, Money::fromDecimalString('.5')->cents);
        $this->assertSame('ZAR', Money::fromDecimalString('1')->currency);
        $this->assertSame('USD', Money::fromDecimalString('1', 'usd')->currency);

        foreach (['', 'abc', '1,000.00', '1e3', '12.3.4'] as $bad) {
            try {
                Money::fromDecimalString($bad);
                $this->fail("Accepted \"{$bad}\"");
            } catch (InvalidArgumentException) {
            }
        }
    }

    public function test_arithmetic_stays_in_cents_and_currency(): void
    {
        $fee = Money::ofCents(100001);

        $this->assertSame(100101, $fee->plus(Money::ofCents(100))->cents);
        $this->assertSame(99901, $fee->minus(Money::ofCents(100))->cents);
        $this->assertSame(300003, $fee->times(3)->cents);
        $this->assertSame(15000, $fee->percent(15)->cents);       // 15 % of 1000.01 = 150.0015
        $this->assertSame(12500, $fee->percent('12.5')->cents);   // 125.000125
        $this->assertSame(33334, $fee->dividedBy(3)->cents);      // 333.33666
        $this->assertSame([33334, 33334, 33333], array_map(fn (Money $m) => $m->cents, $fee->allocate(3)));
        $this->assertSame(33333, Money::ofFraction(100000, 3)->cents);
        $this->assertSame(1, Money::ofFraction(1, 2)->cents);
        $this->assertSame(-1, Money::ofFraction(-1, 2)->cents);
        $this->assertTrue($fee->greaterThan(Money::ofCents(100000)));
        $this->assertTrue(Money::zero()->isZero());
        $this->assertTrue(Money::ofCents(5)->equals(Money::ofCents(5)));

        $this->expectException(InvalidArgumentException::class);
        Money::ofCents(1, 'ZAR')->plus(Money::ofCents(1, 'USD'));
    }

    public function test_formatting_and_serialising(): void
    {
        $this->assertSame('1234.56', Money::ofCents(123456)->toDecimalString());
        $this->assertSame('-0.05', Money::ofCents(-5)->toDecimalString());
        $this->assertSame('R 1,234,567.08', Money::ofCents(123456708)->format('R'));
        $this->assertSame('ZAR 0.50', Money::ofCents(50)->format());
        $this->assertSame('{"cents":123456,"currency":"ZAR"}', json_encode(Money::ofCents(123456)));
        $this->assertSame('12.00', (string) Money::ofCents(1200));
    }

    public function test_the_cast_takes_money_or_whole_cents_never_floats(): void
    {
        $cast = new MoneyCast;
        $model = new FeesClass;

        $this->assertSame(['amount_cents' => 1250], $cast->set($model, 'amount_cents', Money::ofCents(1250), []));
        $this->assertSame(['amount_cents' => 1250], $cast->set($model, 'amount_cents', 1250, []));
        $this->assertSame(['amount_cents' => null], $cast->set($model, 'amount_cents', null, []));
        $this->assertEquals(Money::ofCents(1250), $cast->get($model, 'amount_cents', '1250', []));
        $this->assertNull($cast->get($model, 'amount_cents', null, []));

        $this->expectException(InvalidArgumentException::class);
        $cast->set($model, 'amount_cents', 12.5, []);
    }

    public function test_writes_keep_the_float_and_cents_columns_equal(): void
    {
        // Fee rows only; the classes, students and years they point at don't matter here.
        Schema::disableForeignKeyConstraints();
        $fee = FeesClass::forceCreate(['class_id' => 1, 'fees_type_id' => 1, 'choiceable' => 0, 'amount' => '1234.56', 'session_year_id' => 1]);
        $this->assertRow('fees_classes', $fee->id, ['amount' => 1234.56, 'amount_cents' => 123456]);

        $fee->amount = 99.99;
        $fee->save();
        $this->assertRow('fees_classes', $fee->id, ['amount' => 99.99, 'amount_cents' => 9999]);

        $fee->amount_cents = Money::ofCents(150000);
        $fee->save();
        $this->assertRow('fees_classes', $fee->id, ['amount' => 1500.0, 'amount_cents' => 150000]);
        $this->assertEquals(Money::ofCents(150000), $fee->fresh()->money('amount'));

        $paid = FeesPaid::forceCreate(['student_id' => 1, 'class_id' => 1, 'mode' => 0, 'total_amount' => 0, 'is_fully_paid' => 0, 'date' => '2026-02-01', 'session_year_id' => 1]);
        $paid->total_amount += 250.5;
        $paid->due_charges = '12.345';
        $paid->save();
        $this->assertRow('fees_paids', $paid->id, ['total_amount' => 250.5, 'total_amount_cents' => 25050, 'due_charges_cents' => 1235]);

        $choiceable = FeesChoiceable::create(['student_id' => 1, 'class_id' => 1, 'fees_type_id' => 1, 'is_due_charges' => 0, 'total_amount' => 200, 'session_year_id' => 1, 'status' => 1]);
        $this->assertRow('fees_choiceables', $choiceable->id, ['total_amount_cents' => 20000]);

        $installment = PaidInstallmentFee::create(['student_id' => 1, 'class_id' => 1, 'parent_id' => 1, 'installment_fee_id' => 1, 'session_year_id' => 1,
            'amount_cents' => 33333, 'due_charges' => null, 'date' => '2026-02-01', 'payment_transaction_id' => 1, 'status' => 1]);
        $this->assertRow('paid_installment_fees', $installment->id, ['amount' => 333.33, 'amount_cents' => 33333, 'due_charges_cents' => null]);

        // A row written by a raw insert gets its cents on the next save.
        $id = DB::table('payment_transactions')->insertGetId(['student_id' => 1, 'class_id' => 1, 'parent_id' => 1, 'payment_status' => 1, 'total_amount' => 525.25, 'session_year_id' => 1]);
        $transaction = PaymentTransaction::find($id);
        $this->assertSame(525.25, $transaction->total_amount);
        $transaction->payment_status = 0;
        $transaction->save();
        $this->assertRow('payment_transactions', $id, ['total_amount_cents' => 52525]);
    }

    public function test_the_v1_payload_is_unchanged_and_derived_from_the_cents(): void
    {
        Schema::disableForeignKeyConstraints();
        $fee = FeesClass::forceCreate(['class_id' => 1, 'fees_type_id' => 1, 'choiceable' => 1, 'amount' => '200', 'session_year_id' => 1]);
        // The cents are the truth: a float the column garbled reads back exactly.
        DB::table('fees_classes')->where('id', $fee->id)->update(['amount' => 12345.67, 'amount_cents' => 1234567]);

        $array = FeesClass::find($fee->id)->toArray();

        $this->assertSame(['id', 'session_year_id', 'class_id', 'fees_type_id', 'choiceable', 'amount'], array_keys($array));
        $this->assertSame(12345.67, $array['amount']);
        $this->assertSame(12345.67, FeesClass::find($fee->id)->amount);
        $this->assertSame('{"amount":200}', json_encode(['amount' => LegacyAmount::fromCents(20000)]));
    }

    protected function tearDown(): void
    {
        Schema::enableForeignKeyConstraints();
        parent::tearDown();
    }

    private function assertRow(string $table, int $id, array $expected): void
    {
        $row = (array) DB::table($table)->where('id', $id)->first();
        foreach ($expected as $column => $value) {
            $this->assertEquals($value, $row[$column], "{$table}.{$column}");
        }
    }
}
