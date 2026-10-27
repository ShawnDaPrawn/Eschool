<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

final class BankStatementReconciliation
{
    public function inspect(?array $accountIds = null): array
    {
        $school = app(CurrentSchool::class)->id();
        $incomplete = $accountIds === null ? DB::table('bank_statement_imports')->where('school_id', $school)->whereNull('sealed_at')->count() : 0;
        $orphaned = $accountIds === null ? DB::table('bank_statement_lines as l')->where('l.school_id', $school)->whereNotExists(function ($q) use ($school): void {
            $q->selectRaw('1')->from('bank_statement_import_rows as r')->join('bank_statement_imports as i', function ($j) {
                $j->on('i.school_id', '=', 'r.school_id')->on('i.id', '=', 'r.bank_statement_import_id');
            })->where('r.school_id', $school)->whereColumn('r.bank_statement_line_id', 'l.id')->whereNotNull('i.sealed_at');
        })->count() : 0;
        $bad = DB::table('bank_statement_resolutions as r')->join('bank_statement_lines as l', function ($j) {
            $j->on('l.school_id', '=', 'r.school_id')->on('l.id', '=', 'r.bank_statement_line_id');
        })->leftJoin('payments as p', function ($j) {
            $j->on('p.school_id', '=', 'r.school_id')->on('p.id', '=', 'r.payment_id');
        })->where('r.school_id', $school)->where('r.kind', 'matched')
            ->when($accountIds !== null, fn ($q) => $q->whereIn('p.fee_account_id', $accountIds))
            ->whereRaw("(p.id IS NULL OR p.method <> 'eft' OR l.direction <> 'credit' OR p.amount_cents <> l.amount_cents OR p.currency <> l.currency OR p.received_on <> l.received_on OR SHA2(p.source_reference,256) <> l.source_hash)")->count();

        return ['incomplete_bank_imports' => $incomplete, 'orphaned_bank_lines' => $orphaned, 'bank_receipt_mismatches' => $bad];
    }
}
