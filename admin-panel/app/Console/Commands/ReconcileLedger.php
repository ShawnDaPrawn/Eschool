<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Platform\School;
use App\Services\Platform\Ledger\CentsColumns;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ReconcileLedger extends Command
{
    protected $signature = 'ledger:reconcile {--school= : Limit to one school} {--rebuild : Repair cached balances from immutable posted history} {--json : Output a machine-readable report}';

    protected $description = 'Verify posted ledger invariants, cached balances and legacy cents without importing or reposting historical money';

    public function handle(LedgerService $ledger, CurrentSchool $context): int
    {
        $selected = $this->option('school');
        if ($selected !== null && (! ctype_digit((string) $selected) || (int) $selected < 1)) {
            $this->error('School must be a positive integer.');

            return self::INVALID;
        }
        $schools = School::query()->when($selected !== null, fn ($q) => $q->whereKey((int) $selected))->orderBy('id')->pluck('id');
        if ($schools->isEmpty()) {
            $this->error('No matching school.');

            return self::INVALID;
        }
        $reports = [];
        $failed = false;
        foreach ($schools as $school) {
            $report = $context->run((int) $school, function () use ($school, $ledger): array {
                $unbalanced = DB::table('ledger_entries as e')->leftJoin('ledger_postings as p', function ($join) {
                    $join->on('p.entry_id', '=', 'e.id')->on('p.school_id', '=', 'e.school_id');
                })->where('e.school_id', $school)->groupBy('e.id')->havingRaw("COUNT(p.id) < 2 OR COALESCE(SUM(CASE p.direction WHEN 'debit' THEN p.cents ELSE -p.cents END),0) <> 0")
                    ->pluck('e.id')->count();
                $drafts = DB::table('ledger_entries')->where('school_id', $school)->whereNull('posted_at')->count();
                $legacy = [];
                foreach (CentsColumns::COLUMNS as $table => $columns) {
                    foreach ($columns as $float => $cents) {
                        $count = DB::table($table)->where('school_id', $school)
                            ->where(fn ($q) => $q->where(fn ($q) => $q->whereNotNull($float)->whereNull($cents))
                                ->orWhereRaw("ABS(CAST(`{$float}` AS DECIMAL(20,6)) * 100 - `{$cents}`) > 1 + ABS(`{$cents}`) * 0.0000002"))->count();
                        if ($count > 0) {
                            $legacy[$table.'.'.$float] = $count;
                        }
                    }
                }
                // Never repair from invalid/incomplete history. This switch
                // repairs projections only, never entries, receipts or floats.
                $repair = (bool) $this->option('rebuild') && $unbalanced === 0 && $drafts === 0;
                $differences = $ledger->projectionDifferences($repair);

                return ['school_id' => (int) $school, 'unbalanced_entries' => $unbalanced, 'incomplete_entries' => $drafts,
                    'projection_differences' => $differences, 'projections_rebuilt' => $repair && $differences !== [],
                    'legacy_cents_mismatches' => $legacy];
            });
            $bad = $report['unbalanced_entries'] > 0 || $report['incomplete_entries'] > 0
                || $report['legacy_cents_mismatches'] !== [] || ($report['projection_differences'] !== [] && ! $report['projections_rebuilt']);
            if ($bad) {
                Log::critical('money_ledger_reconciliation_failed', $report);
            }
            $failed = $failed || $bad;
            $reports[] = $report;
        }
        $this->line(json_encode(['ok' => ! $failed, 'schools' => $reports], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
