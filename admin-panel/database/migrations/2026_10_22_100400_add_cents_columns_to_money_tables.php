<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Integer cents beside every float money column (Build Bible step 6). The
 * models keep both in step from now on (HasMoneyColumns) and read the cents;
 * the floats stay until the ledger work in a later phase.
 *
 * Back-fill: each float is written as an exact decimal with six places
 * (CAST AS DECIMAL), multiplied by 100 and rounded half up (ROUND on a
 * DECIMAL rounds half away from zero; amounts are not negative), e.g. a
 * stored 2.675 (2.67499995 as a float) becomes 268. Rows are updated in id
 * ranges of 1000, each its own statement, so no long lock is held, and only
 * rows whose cents are still empty are touched (re-running is harmless; so
 * is php artisan money:backfill-cents).
 *
 * installment_fees.due_charges and session_years.fee_due_charges are
 * percentages, not money, and get no cents column.
 */
return new class extends Migration
{
    private const COLUMNS = [
        'fees_classes' => ['amount' => 'amount_cents'],
        'fees_paids' => ['total_amount' => 'total_amount_cents', 'due_charges' => 'due_charges_cents'],
        'fees_choiceables' => ['total_amount' => 'total_amount_cents'],
        'paid_installment_fees' => ['amount' => 'amount_cents', 'due_charges' => 'due_charges_cents'],
        'payment_transactions' => ['total_amount' => 'total_amount_cents'],
    ];

    private const CHUNK = 1000;

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $float => $cents) {
                    $blueprint->bigInteger($cents)->nullable()->after($float);
                }
            });

            $this->backfill($table, $columns);
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                $blueprint->dropColumn(array_values($columns));
            });
        }
    }

    /**
     * @param  array<string, string>  $columns
     */
    private function backfill(string $table, array $columns): void
    {
        $min = DB::table($table)->min('id');
        $max = DB::table($table)->max('id');
        if ($min === null) {
            return;
        }

        for ($from = (int) $min; $from <= (int) $max; $from += self::CHUNK) {
            foreach ($columns as $float => $cents) {
                DB::update(
                    "UPDATE `{$table}` SET `{$cents}` = ROUND(CAST(`{$float}` AS DECIMAL(20,6)) * 100)
                     WHERE id >= ? AND id < ? AND `{$float}` IS NOT NULL AND `{$cents}` IS NULL",
                    [$from, $from + self::CHUNK]
                );
            }
        }
    }
};
