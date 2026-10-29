<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\BillRun;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeeCharge;
use App\Models\Money\Invoice;
use App\Models\Money\InvoiceLine;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
use Illuminate\Support\Facades\DB;

/** Internal builder; callers retain their own reviewed source/authority and school lock. */
final class FeeAdjustmentInvoices
{
    public function post(User $actor, string $kind, array $s, string $operation, string $fingerprint, callable $lineage): FeeCharge
    {
        abort_unless(DB::transactionLevel() > 0 && in_array($kind, ['payer_correction', 'late_charge'], true), 409);
        app(MoneyPolicy::class)->authorize($actor, 'fee-billing-manage', true, locked: true);
        $account = FeeAccount::lockForUpdate()->findOrFail($s['fee_account_id']);
        $money = fn (int $cents) => Money::ofCents($cents, $s['currency']);
        $postings = [Posting::debit($s['receivable_account_id'], $money($s['amount_cents']))];
        if ($s['net_cents'] > 0) {
            $postings[] = Posting::credit($s['income_account_id'], $money($s['net_cents']));
        }
        if ($s['vat_cents'] > 0) {
            $postings[] = Posting::credit($s['vat_account_id'], $money($s['vat_cents']));
        }
        $source = ($kind === 'payer_correction' ? 'fee-payer-correction:' : 'fee-late-charge:').$operation;
        $entry = app(LedgerService::class)->post($source, $source, $s['description'], $postings);
        $runSnapshot = ['year_id' => $s['session_year_id'], 'as_of' => $s['issued_on'], 'due_on' => $s['due_on'], 'tax_treatment' => $s['tax_treatment'], 'rows' => [], 'totals' => [$s['currency'] => $s['amount_cents']], 'exceptions' => [], 'learners' => 1, 'billable' => 1, 'skipped' => 0, 'account_count' => 1];
        $run = BillRun::create(['kind' => $kind, 'session_year_id' => $s['session_year_id'], 'as_of' => $s['issued_on'], 'due_on' => $s['due_on'], 'tax_treatment' => $s['tax_treatment'], 'state' => 'preview', 'revision' => 1, 'fingerprint' => $fingerprint, 'snapshot' => $runSnapshot, 'prepared_by' => $actor->id]);
        $line = ['learner_id' => $s['learner_id'], 'learner_name' => $s['learner_name'], 'amount_cents' => $s['amount_cents'], 'vat_cents' => $s['vat_cents'], 'holder_shares' => [$s['holder']]];
        $charge = FeeCharge::create(['kind' => $kind, 'fee_item_id' => null, 'fee_account_id' => $account->id, 'session_year_id' => $s['session_year_id'], 'learner_id' => $s['learner_id'], 'bill_run_id' => $run->id, 'item_revision' => $s['policy_revision'] ?? 1, 'scope' => 'learner', 'target_id' => $s['learner_id'], 'source_reference' => $source, 'reference_hash' => hash_hmac('sha256', $source, (string) config('app.key')), 'fingerprint' => $fingerprint, 'currency' => $s['currency'], 'amount_cents' => $s['amount_cents'], 'vat_cents' => $s['vat_cents'], 'income_account_id' => $s['income_account_id'], 'vat_account_id' => $s['vat_account_id'], 'snapshot' => $s + ['lines' => [$line]], 'created_by' => $actor->id, 'created_at' => now('UTC')]);
        $run->update(['state' => 'approved', 'revision' => 2, 'approved_by' => $actor->id, 'approved_at' => now('UTC')]);
        $holder = $s['holder'];
        $holder['total_cents'] = $holder['amount_cents'];
        unset($holder['amount_cents']);
        $invoice = Invoice::create(['fee_account_id' => $account->id, 'bill_run_id' => $run->id, 'session_year_id' => $s['session_year_id'], 'ledger_entry_id' => $entry, 'number' => ($kind === 'payer_correction' ? 'CORR-' : 'LATE-').$charge->id, 'account_name' => $account->name, 'currency' => $s['currency'], 'total_cents' => $s['amount_cents'], 'holders' => [$holder], 'issued_on' => $s['issued_on'], 'due_on' => $s['due_on'], 'created_by' => $actor->id, 'created_at' => now('UTC')]);
        InvoiceLine::create(['invoice_id' => $invoice->id, 'session_year_id' => $s['session_year_id'], 'fee_charge_id' => $charge->id, 'fee_plan_id' => null, 'plan_revision' => null, 'learner_id' => $s['learner_id'], 'learner_name' => $s['learner_name'], 'description' => $s['description'], 'period_start' => $s['period_start'], 'period_end' => $s['period_end'], 'currency' => $s['currency'], 'amount_cents' => $s['amount_cents'], 'vat_cents' => $s['vat_cents'], 'holder_shares' => [$s['holder']]]);
        $invoice->update(['sealed_at' => now('UTC')]);
        $run->update(['state' => 'posted', 'revision' => 3, 'posted_at' => now('UTC')]);
        $lineage($charge);
        $charge->update(['sealed_at' => now('UTC')]);
        app(FeeStatements::class)->complete($account);

        return $charge;
    }
}
