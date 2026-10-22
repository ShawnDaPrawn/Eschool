<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Services\Platform\Ledger\CentsColumns;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The Phase 0 kernel migrations: each down() undoes its up(), and the cents
 * back-fill gives the same totals as the floats it converts.
 *
 * Schema changes commit at once in MySQL, so this class works on the real
 * test schema (no RefreshDatabase transaction), puts it back as it found it
 * and deletes the rows it adds.
 */
class KernelMigrationsTest extends TestCase
{
    private const MIGRATIONS = [
        '2026_10_22_100000_create_notification_deliveries_table',
        '2026_10_22_100100_create_domain_events_table',
        '2026_10_22_100200_create_audit_log_table',
        '2026_10_22_100300_add_school_id_to_settings_table',
        '2026_10_22_100400_add_cents_columns_to_money_tables',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
    }

    protected function tearDown(): void
    {
        // Whatever happened above, the next class starts from a fresh migration.
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_every_kernel_migration_can_be_rolled_back_and_run_again(): void
    {
        try {
            foreach (array_reverse(self::MIGRATIONS) as $name) {
                $this->migration($name)->down();
            }

            $this->assertFalse(Schema::hasTable('notification_deliveries'));
            $this->assertFalse(Schema::hasTable('webhook_receipts'));
            $this->assertFalse(Schema::hasTable('domain_events'));
            $this->assertFalse(Schema::hasTable('audit_log'));
            $this->assertFalse(Schema::hasColumn('settings', 'school_id'));
            foreach (CentsColumns::COLUMNS as $table => $columns) {
                foreach ($columns as $float => $cents) {
                    $this->assertTrue(Schema::hasColumn($table, $float));
                    $this->assertFalse(Schema::hasColumn($table, $cents), "{$table}.{$cents}");
                }
            }
        } finally {
            foreach (self::MIGRATIONS as $name) {
                try {
                    $this->migration($name)->up();
                } catch (\Throwable) {
                    // Already there: a failure above stopped before its down().
                }
            }
        }

        $this->assertTrue(Schema::hasTable('notification_deliveries'));
        $this->assertTrue(Schema::hasTable('webhook_receipts'));
        $this->assertTrue(Schema::hasTable('domain_events'));
        $this->assertTrue(Schema::hasTable('audit_log'));
        $this->assertTrue(Schema::hasColumn('settings', 'school_id'));
        foreach (CentsColumns::COLUMNS as $table => $columns) {
            foreach ($columns as $cents) {
                $this->assertTrue(Schema::hasColumn($table, $cents), "{$table}.{$cents}");
            }
        }
    }

    public function test_settings_school_id_is_nullable_and_get_settings_still_works(): void
    {
        $id = DB::table('settings')->insertGetId(['type' => 'kernel_test_setting', 'message' => 'kept']);
        try {
            $this->assertNull(DB::table('settings')->where('id', $id)->value('school_id'));
            $this->assertSame(['kernel_test_setting' => 'kept'], getSettings('kernel_test_setting'));
            $this->assertNotEmpty(DB::select("SHOW INDEX FROM settings WHERE Key_name = 'settings_school_id_type_index'"));
        } finally {
            DB::table('settings')->where('id', $id)->delete();
        }
    }

    public function test_the_back_fill_gives_the_same_totals_as_the_floats(): void
    {
        $amounts = [1500, 1234.56, 99.99, 0.1, 0.125, 2.675, 1.005, 83333.33, 12345.67, 0];
        $ids = [];
        $migration = $this->migration('2026_10_22_100400_add_cents_columns_to_money_tables');

        try {
            $migration->down();
            // Fee rows only; the classes, students and years they point at don't matter here.
            Schema::disableForeignKeyConstraints();
            foreach ($amounts as $i => $amount) {
                $ids['fees_classes'][] = SchoolTable::query('fees_classes')->insertGetId(['class_id' => 900, 'fees_type_id' => 900, 'choiceable' => 0, 'amount' => $amount, 'session_year_id' => 900]);
                $ids['fees_paids'][] = SchoolTable::query('fees_paids')->insertGetId(['student_id' => 900, 'class_id' => 900, 'total_amount' => $amount, 'due_charges' => $i % 2 ? $amount : null, 'is_fully_paid' => 0, 'date' => '2026-01-01', 'session_year_id' => 900]);
                $ids['fees_choiceables'][] = SchoolTable::query('fees_choiceables')->insertGetId(['student_id' => 900, 'class_id' => 900, 'is_due_charges' => 0, 'total_amount' => $amount, 'session_year_id' => 900]);
                $ids['paid_installment_fees'][] = SchoolTable::query('paid_installment_fees')->insertGetId(['class_id' => 900, 'student_id' => 900, 'parent_id' => 900, 'installment_fee_id' => 900, 'session_year_id' => 900, 'amount' => $amount, 'due_charges' => $i % 2 ? null : $amount, 'date' => '2026-01-01', 'payment_transaction_id' => 900, 'status' => 1]);
                $ids['payment_transactions'][] = SchoolTable::query('payment_transactions')->insertGetId(['student_id' => 900, 'class_id' => 900, 'parent_id' => 900, 'payment_status' => 1, 'total_amount' => $amount, 'session_year_id' => 900]);
            }
            $migration->up();

            // Each value rounded half up from its exact decimal: 0.125 -> 13, 2.675 -> 268.
            $expected = [150000, 123456, 9999, 10, 13, 268, 101, 8333333, 1234567, 0];
            $this->assertSame($expected, DB::table('fees_classes')->whereIn('id', $ids['fees_classes'])->orderBy('id')->pluck('amount_cents')->map(fn ($c) => (int) $c)->all());

            foreach (CentsColumns::COLUMNS as $table => $columns) {
                foreach ($columns as $float => $cents) {
                    $totals = DB::table($table)->whereIn('id', $ids[$table])
                        ->selectRaw("SUM(ROUND(CAST(`{$float}` AS DECIMAL(20,6)), 2)) AS rounded_total, SUM(CAST(`{$float}` AS DECIMAL(20,6))) AS float_total,
                            SUM(`{$cents}`) AS cents_total, COUNT(`{$float}`) AS amounts, SUM(`{$float}` IS NULL) AS float_nulls, SUM(`{$cents}` IS NULL) AS cents_nulls")
                        ->first();
                    // The float total and the cents total agree: exactly once each
                    // float is rounded to the cent, and to within half a cent per
                    // row before that.
                    $this->assertSame(DecimalTotal::cents($totals->rounded_total), (int) $totals->cents_total, "{$table}.{$float}");
                    $this->assertLessThanOrEqual((int) $totals->amounts * 0.5 + 0.001, abs((float) $totals->float_total * 100 - (int) $totals->cents_total), "{$table}.{$float}");
                    $this->assertSame((int) $totals->float_nulls, (int) $totals->cents_nulls, "{$table}.{$float} nulls");
                }
            }
            $this->assertSame([], array_filter(CentsColumns::mismatches()));
            // Running it again changes nothing.
            $this->assertSame(0, CentsColumns::backfill());
        } finally {
            foreach ($ids as $table => $tableIds) {
                DB::table($table)->whereIn('id', $tableIds)->delete();
            }
            Schema::enableForeignKeyConstraints();
            if (! Schema::hasColumn('fees_classes', 'amount_cents')) {
                $migration->up();
            }
        }
    }

    private function migration(string $name): object
    {
        return require database_path("migrations/{$name}.php");
    }
}

/** A SUM() of decimals, as cents. */
final class DecimalTotal
{
    public static function cents(?string $total): int
    {
        return $total === null ? 0 : Money::fromDecimalString($total)->cents;
    }
}
