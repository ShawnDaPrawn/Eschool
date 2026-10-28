<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeeAccount;
use App\Models\Money\FeeAccountHolder;
use App\Models\Money\FeeCharge;
use App\Models\Money\FeeLateCharge;
use App\Models\Money\FeeLateChargePolicy;
use App\Models\Money\FeeLatePolicyRevision;
use App\Models\Money\FeePayerCorrection;
use App\Models\Money\Invoice;
use App\Models\Money\InvoiceLine;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Ledger\AccountType;
use App\Services\Platform\Ledger\CentsRatio;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/** Reviewed one-off invoices from actual original overdue debt; no automatic compounding. */
final class FeeLateCharges
{
    public const PERMISSION = 'fee-billing-manage';

    private function original(int $lineId, array &$memo): InvoiceLine
    {
        if (isset($memo[$lineId])) {
            return $memo[$lineId];
        }
        $line = InvoiceLine::lockForUpdate()->findOrFail($lineId);
        $seen = [];
        $path = [];
        while ($line->fee_charge_id !== null) {
            abort_if(isset($seen[$line->id]), 409, 'Original correction lineage needs reconciliation.');
            $seen[$line->id] = true;
            $path[] = (int) $line->id;
            $charge = FeeCharge::whereNotNull('sealed_at')->lockForUpdate()->findOrFail($line->fee_charge_id);
            if ($charge->kind !== 'payer_correction') {
                break;
            }
            $parent = FeePayerCorrection::where('replacement_charge_id', $charge->id)->lockForUpdate()->sole();
            $line = InvoiceLine::lockForUpdate()->findOrFail($parent->original_invoice_line_id);
        }
        foreach ([...$path, $lineId, (int) $line->id] as $id) {
            $memo[$id] = $line;
        }

        return $line;
    }

    public function sources(User $actor, FeeAccount $account): array
    {
        app(MoneyPolicy::class)->authorize($actor, self::PERMISSION, locked: true);
        abort_unless((int) $account->school_id === app(CurrentSchool::class)->id(), 404);
        app(FeeStatements::class)->complete($account);
        $groups = [];
        $memo = [];
        foreach (app(FeeStatements::class)->charges($account) as $row) {
            if ($row['outstanding_cents'] === 0) {
                continue;
            }
            $root = $this->original($row['invoice_line_id'], $memo);
            if ($root->fee_charge_id !== null && FeeCharge::findOrFail($root->fee_charge_id)->kind !== 'fee_item') {
                continue;
            }
            $key = $root->id.':'.$row['holder_user_id'];
            if (! isset($groups[$key])) {
                $invoice = Invoice::whereNotNull('sealed_at')->findOrFail($root->invoice_id);
                $groups[$key] = ['original_invoice_line_id' => (int) $root->id, 'fee_account_id' => (int) $account->id, 'session_year_id' => (int) $root->session_year_id, 'holder_user_id' => $row['holder_user_id'], 'holder_name' => $row['holder_name'], 'learner_id' => (int) $root->learner_id, 'learner_name' => $root->learner_name, 'invoice_number' => $invoice->number, 'currency' => $root->currency, 'due_on' => $root->getAttribute('installment_due_on') ?? $invoice->due_on->toDateString(), 'period_start' => $root->period_start->toDateString(), 'period_end' => $root->period_end->toDateString(), 'annual' => $root->fee_charge_id === null, 'basis_cents' => 0, 'basis_rows' => []];
            }
            $groups[$key]['basis_cents'] += $row['outstanding_cents'];
            $groups[$key]['basis_rows'][] = $row;
        }
        foreach ($groups as &$group) {
            abort_unless($group['basis_cents'] > 0 && $group['basis_cents'] <= FeePlans::MAX_CENTS, 409, 'Original overdue debt needs reconciliation.');
            usort($group['basis_rows'], fn ($a, $b) => $a['invoice_line_id'] <=> $b['invoice_line_id']);
            $group['already_charged'] = FeeLateCharge::where('original_invoice_line_id', $group['original_invoice_line_id'])->where('holder_user_id', $group['holder_user_id'])->exists();
        }unset($group);
        ksort($groups);

        return array_values($groups);
    }

    public function preview(User $actor, array $input): array
    {
        $data = $this->validated($input);

        return DB::transaction(function () use ($actor, $data): array {
            DB::table('schools')->where('id', app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            app(MoneyPolicy::class)->authorize($actor, self::PERMISSION, locked: true);
            $snapshot = $this->snapshot($actor, $data);

            return $snapshot + ['fingerprint' => $this->fingerprint($snapshot)];
        });
    }

    public function post(User $actor, array $input): FeeLateCharge
    {
        $data = $this->validated($input, true);
        $result = app(BillingOperations::class)->once($actor, self::PERMISSION, 'late_charge', $data, function () use ($actor, $data): array {
            $s = $this->snapshot($actor, $data);
            abort_unless(hash_equals($data['fingerprint'], $this->fingerprint($s)), 409, 'Debt, credits or the school late-charge rule changed. Review a fresh charge.');
            $ledger = app(LedgerService::class);
            $income = $ledger->openAccount(AccountType::FeeIncome, 'fees:income:'.$s['currency'], $s['currency']);
            $vat = $s['vat_cents'] > 0 ? $ledger->openAccount(AccountType::FeeVat, 'fees:vat:'.$s['currency'], $s['currency']) : null;
            $invoiceSnapshot = $s + ['income_account_id' => $income, 'vat_account_id' => $vat];
            $charge = app(FeeAdjustmentInvoices::class)->post($actor, 'late_charge', $invoiceSnapshot, $data['operation_id'], $data['fingerprint'], function (FeeCharge $charge) use ($actor, $s, $data): void {
                FeeLateCharge::create(['fee_account_id' => $s['fee_account_id'], 'original_invoice_line_id' => $s['original_invoice_line_id'], 'holder_user_id' => $data['holder_user_id'], 'policy_revision_id' => $s['policy_revision_id'], 'replacement_charge_id' => $charge->id, 'currency' => $s['currency'], 'basis_cents' => $s['basis_cents'], 'amount_cents' => $s['amount_cents'], 'vat_cents' => $s['vat_cents'], 'reason' => $data['reason'], 'actor_id' => $actor->id, 'created_at' => now('UTC')]);
            });
            $row = FeeLateCharge::where('replacement_charge_id', $charge->id)->sole();
            Audit::record('money.late_charge_posted', $row, [], ['amount_cents' => $row->amount_cents, 'policy_revision_id' => $row->policy_revision_id, 'replacement_charge_id' => $charge->id]);

            return ['id' => (int) $row->id];
        });

        return FeeLateCharge::findOrFail($result['id']);
    }

    private function snapshot(User $actor, array $data): array
    {
        $memo = [];
        $root = $this->original($data['original_invoice_line_id'], $memo);
        $invoice = Invoice::whereNotNull('sealed_at')->lockForUpdate()->findOrFail($root->invoice_id);
        $account = FeeAccount::where('active', true)->lockForUpdate()->findOrFail($invoice->fee_account_id);
        $source = collect($this->sources($actor, $account))->first(fn ($row) => $row['original_invoice_line_id'] === (int) $root->id && $row['holder_user_id'] === $data['holder_user_id']);
        abort_unless($source !== null, 422, 'Choose original unpaid debt that is not a late charge.');
        abort_if($source['already_charged'], 409, 'This original payer debt already has its one-off late charge.');
        $policy = FeeLateChargePolicy::where('session_year_id', $invoice->session_year_id)->where('currency', $account->currency)->lockForUpdate()->first();
        abort_unless($policy !== null && $policy->enabled, 422, 'Configure and enable the explicit school late-charge rule first.');
        abort_unless($policy->scope === 'all_original_charges' || $source['annual'], 422, 'The configured rule applies only to original annual tuition.');
        $revision = FeeLatePolicyRevision::where('policy_id', $policy->id)->where('revision', $policy->revision)->lockForUpdate()->sole();
        $today = now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->toDateString();
        $days = FeeAgeing::days($source['due_on'], $today);
        abort_unless($days > $policy->grace_days, 422, 'The original debt is not beyond the configured overdue grace period.');
        abort_unless(FeeAccountHolder::where('fee_account_id', $account->id)->where('session_year_id', $invoice->session_year_id)->where('user_id', $data['holder_user_id'])->lockForUpdate()->exists(), 422, 'Review this debt with its current configured family payer.');
        $holder = app(FeeAccounts::class)->holders([['user_id' => $data['holder_user_id'], 'share_bp' => 10000]], [(int) $root->learner_id])[0];
        $uncapped = $policy->basis === 'flat' ? $policy->flat_cents : CentsRatio::portion($source['basis_cents'], $policy->rate_bp, 10000);
        $amount = min($uncapped, $policy->cap_cents);
        abort_unless($amount > 0, 422, 'The configured rule produces no positive late charge for this debt.');
        $tax = app(FeeTaxes::class)->forPrice($amount, $policy->tax_treatment, $policy->vat_rate_bp);
        $holder += ['amount_cents' => $amount, 'vat_cents' => $tax['vat_cents']];
        $held = 0;
        foreach (app(FeeStatements::class)->receipts($account, $data['holder_user_id']) as $receipt) {
            if ($receipt['pinned_learner_id'] === null || $receipt['pinned_learner_id'] === (int) $root->learner_id) {
                $held += $receipt['credit_cents'];
            }
        }

        return array_replace($source, ['original_due_on' => $source['due_on'], 'policy_id' => (int) $policy->id, 'policy_revision' => $policy->revision, 'policy_revision_id' => (int) $revision->id, 'policy' => $revision->snapshot, 'account_revision' => (int) $account->revision, 'holder' => $holder, 'held_credit_cents' => $held, 'days_overdue' => $days, 'uncapped_gross_cents' => $uncapped, 'amount_cents' => $amount, 'net_cents' => $tax['net_cents'], 'vat_cents' => $tax['vat_cents'], 'tax_treatment' => $tax['tax_treatment'], 'tax_details' => $tax['tax_details'], 'receivable_account_id' => (int) $account->ledger_account_id, 'issued_on' => $today, 'due_on' => now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->addDays($policy->due_days)->toDateString(), 'description' => 'One-off late charge for original overdue debt', 'reason_hash' => hash_hmac('sha256', $data['reason'], (string) config('app.key'))]);
    }

    private function validated(array $input, bool $post = false): array
    {
        if (is_string($input['reason'] ?? null)) {
            $input['reason'] = trim($input['reason']);
        }
        $data = Validator::make($input, self::rules($post))->validate();
        foreach (['original_invoice_line_id', 'holder_user_id'] as $key) {
            $data[$key] = (int) $data[$key];
        }

        return $data;
    }

    private function fingerprint(array $snapshot): string
    {
        return hash_hmac('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR), (string) config('app.key'));
    }

    public static function rules(bool $post = false): array
    {
        return ['operation_id' => ['required', 'uuid'], 'original_invoice_line_id' => ['required', 'integer', 'min:1'], 'holder_user_id' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'min:10', 'max:2000']] + ($post ? ['fingerprint' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/D'], 'confirmed' => ['required', 'accepted']] : []);
    }
}
