<?php

declare(strict_types=1);

namespace App\Services\Platform\Ledger;

use Illuminate\Support\Facades\DB;

/**
 * The seed's float money columns and their cents twins, with the back-fill
 * and the check that both still agree. The migration that added the cents
 * columns runs the same back-fill; php artisan money:backfill-cents runs it
 * again for rows written outside the models since.
 */
final class CentsColumns
{
    /** table => [float column => cents column] */
    public const COLUMNS = [
        'fees_classes' => ['amount' => 'amount_cents'],
        'fees_paids' => ['total_amount' => 'total_amount_cents', 'due_charges' => 'due_charges_cents'],
        'fees_choiceables' => ['total_amount' => 'total_amount_cents'],
        'paid_installment_fees' => ['amount' => 'amount_cents', 'due_charges' => 'due_charges_cents'],
        'payment_transactions' => ['total_amount' => 'total_amount_cents'],
    ];

    /**
     * SQL for a row's amount in cents: its cents, or for a row whose cents are
     * still empty (written by a raw insert) its float rounded the way the
     * back-fill rounds it. Use it in SUM() so such rows are not counted as 0.
     */
    public static function sql(string $float, string $cents): string
    {
        return "COALESCE(`{$cents}`, ROUND(CAST(`{$float}` AS DECIMAL(20,6)) * 100))";
    }

    /**
     * Fill empty cents from the floats, half up, in id ranges.
     *
     * @return int rows changed
     */
    public static function backfill(int $chunk = 1000): int
    {
        $changed = 0;
        foreach (self::COLUMNS as $table => $columns) {
            $min = DB::table($table)->min('id');
            $max = DB::table($table)->max('id');
            if ($min === null) {
                continue;
            }
            for ($from = (int) $min; $from <= (int) $max; $from += $chunk) {
                foreach ($columns as $float => $cents) {
                    $changed += DB::update(
                        "UPDATE `{$table}` SET `{$cents}` = ROUND(CAST(`{$float}` AS DECIMAL(20,6)) * 100)
                         WHERE id >= ? AND id < ? AND `{$float}` IS NOT NULL AND `{$cents}` IS NULL",
                        [$from, $from + $chunk]
                    );
                }
            }
        }

        return $changed;
    }

    /**
     * Rows whose cents are missing, or further from their float than the
     * float's own precision allows (soft-deleted rows included). A FLOAT
     * keeps about seven digits, so above roughly R100,000 the float itself
     * is off by a few cents and only the cents are exact.
     *
     * @return array<string, int> "table.column" => rows
     */
    public static function mismatches(): array
    {
        $result = [];
        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $float => $cents) {
                $result["{$table}.{$float}"] = DB::table($table)
                    ->where(fn ($query) => $query
                        ->where(fn ($q) => $q->whereNotNull($float)->whereNull($cents))
                        ->orWhereRaw("ABS(CAST(`{$float}` AS DECIMAL(20,6)) * 100 - `{$cents}`) > 1 + ABS(`{$cents}`) * 0.0000002"))
                    ->count();
            }
        }

        return $result;
    }
}
