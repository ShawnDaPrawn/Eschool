<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Platform\School;
use App\Services\Money\CashControlReconciliation;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ReconcileCash extends Command
{
    protected $signature = 'cash:reconcile {--school= : Limit to one school} {--json : Output a machine-readable report}';

    protected $description = 'Verify actual cash source postings and custody without adjusting physical counts or money';

    public function handle(CurrentSchool $context, CashControlReconciliation $service): int
    {
        $selected = $this->option('school');
        if ($selected !== null && (! ctype_digit((string) $selected) || (int) $selected < 1)) {
            $this->error('School must be a positive integer.');

            return self::INVALID;
        }
        $schools = School::when($selected !== null, fn ($q) => $q->whereKey((int) $selected))->orderBy('id')->pluck('id');
        if ($schools->isEmpty()) {
            $this->error('No matching school.');

            return self::INVALID;
        }
        $reports = [];
        $ok = true;
        foreach ($schools as $id) {
            $report = $context->run((int) $id, fn (): array => DB::transaction(function () use ($id, $service): array {
                School::whereKey($id)->lockForUpdate()->firstOrFail();

                return ['school_id' => (int) $id, ...$service->inspect()];
            }));
            if (! $report['cash_control_healthy']) {
                Log::critical('money_cash_control_reconciliation_failed', $report);
            }
            $ok = $ok && $report['cash_control_healthy'];
            $reports[] = $report;
        }
        $this->line(json_encode(['ok' => $ok, 'schools' => $reports], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
