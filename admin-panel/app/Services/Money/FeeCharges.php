<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Events\Money\FeeItemCharged;
use App\Models\Money\BillRun;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeeAccountHolder;
use App\Models\Money\FeeAccountLearner;
use App\Models\Money\FeeCharge;
use App\Models\Money\FeeItem;
use App\Models\Money\Invoice;
use App\Models\Money\InvoiceLine;
use App\Models\People\Enrolment;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Ledger\AccountType;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class FeeCharges
{
    public function preview(User $actor, array $input): array
    {
        app(MoneyPolicy::class)->authorize($actor, FeeItems::PERMISSION, true);
        $data = $this->validated($input);

        return DB::transaction(function () use ($actor, $data): array {
            DB::table('schools')->where('id', app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            app(MoneyPolicy::class)->authorize($actor, FeeItems::PERMISSION, true);
            $snapshot = $this->snapshot($data);

            return $snapshot + ['fingerprint' => $this->fingerprint($snapshot)];
        });
    }

    public function post(User $actor, array $input): FeeCharge
    {
        app(MoneyPolicy::class)->authorize($actor, FeeItems::PERMISSION, true);
        $data = $this->validated($input, true);
        $receipt = app(BillingOperations::class)->once($actor, FeeItems::PERMISSION, 'fee_item_charge', $data, function () use ($actor, $data): array {
            $s = $this->snapshot($data);
            abort_unless(hash_equals($this->fingerprint($s), $data['fingerprint']), 409, 'The item, payer or learner roster changed. Review a new charge.');
            $runSnapshot = ['year_id' => $s['session_year_id'], 'year_start' => $s['year_start'], 'year_end' => $s['year_end'], 'as_of' => $s['charged_on'], 'due_on' => $s['due_on'], 'tax_treatment' => 'no_vat', 'rows' => [], 'totals' => [$s['currency'] => $s['amount_cents']], 'exceptions' => [], 'learners' => count($s['learners']), 'billable' => count($s['lines']), 'skipped' => 0, 'account_count' => 1];
            $run = BillRun::create(['kind' => 'fee_item', 'session_year_id' => $s['session_year_id'], 'as_of' => $s['charged_on'], 'due_on' => $s['due_on'], 'tax_treatment' => 'no_vat', 'state' => 'preview', 'revision' => 1, 'fingerprint' => $data['fingerprint'], 'snapshot' => $runSnapshot, 'prepared_by' => $actor->id]);
            $charge = FeeCharge::create(['fee_item_id' => $s['fee_item_id'], 'fee_account_id' => $s['fee_account_id'], 'session_year_id' => $s['session_year_id'], 'learner_id' => $s['learner_id'], 'bill_run_id' => $run->id, 'item_revision' => $s['item_revision'], 'scope' => $s['scope'], 'target_id' => $s['target_id'], 'source_reference' => $data['source_reference'], 'reference_hash' => $s['reference_hash'], 'fingerprint' => $data['fingerprint'], 'currency' => $s['currency'], 'amount_cents' => $s['amount_cents'], 'snapshot' => $s, 'created_by' => $actor->id, 'created_at' => now('UTC')]);
            $run->update(['state' => 'approved', 'revision' => 2, 'approved_by' => $actor->id, 'approved_at' => now('UTC')]);
            $account = FeeAccount::lockForUpdate()->findOrFail($s['fee_account_id']);
            $ledger = app(LedgerService::class);
            $receivable = $ledger->openAccount(AccountType::FeeReceivable, 'fees:account:'.$account->id.':receivable', $s['currency']);
            $income = $ledger->openAccount(AccountType::FeeIncome, 'fees:income:'.$s['currency'], $s['currency']);
            $account->update(['ledger_account_id' => $receivable]);
            $identity = 'fee-charge:'.$charge->id;
            $entry = $ledger->post($identity, $identity, 'One-off fee item invoice', [Posting::debit($receivable, Money::ofCents($s['amount_cents'], $s['currency'])), Posting::credit($income, Money::ofCents($s['amount_cents'], $s['currency']))]);
            $holders = array_map(function ($h) {
                $h['total_cents'] = $h['amount_cents'];
                unset($h['amount_cents']);

                return $h;
            }, $s['holder_totals']);
            $invoice = Invoice::create(['fee_account_id' => $account->id, 'bill_run_id' => $run->id, 'session_year_id' => $s['session_year_id'], 'ledger_entry_id' => $entry, 'number' => 'ITEM-'.$charge->id, 'account_name' => $s['account_name'], 'currency' => $s['currency'], 'total_cents' => $s['amount_cents'], 'holders' => $holders, 'issued_on' => $s['issued_on'], 'due_on' => $s['due_on'], 'created_by' => $actor->id, 'created_at' => now('UTC')]);
            foreach ($s['lines'] as $line) {
                InvoiceLine::create(['invoice_id' => $invoice->id, 'session_year_id' => $s['session_year_id'], 'fee_charge_id' => $charge->id, 'fee_plan_id' => null, 'plan_revision' => null, 'learner_id' => $line['learner_id'], 'learner_name' => $line['learner_name'], 'description' => $s['item_name'], 'period_start' => $s['charged_on'], 'period_end' => $s['charged_on'], 'currency' => $s['currency'], 'amount_cents' => $line['amount_cents'], 'vat_cents' => 0, 'holder_shares' => $line['holder_shares']]);
            }
            $invoice->update(['sealed_at' => now('UTC')]);
            $run->update(['state' => 'posted', 'revision' => 3, 'posted_at' => now('UTC')]);
            $charge->update(['sealed_at' => now('UTC')]);
            app(FeeStatements::class)->complete($account);
            Audit::record('money.fee_item_charged', $charge, [], ['fee_item_id' => $charge->fee_item_id, 'amount_cents' => $charge->amount_cents, 'invoice_id' => $invoice->id, 'ledger_entry_id' => $entry]);
            Outbox::record(new FeeItemCharged(app(CurrentSchool::class)->id(), (int) $charge->id, $charge->amount_cents));

            return ['id' => (int) $charge->id];
        });

        return FeeCharge::findOrFail($receipt['id']);
    }

    private function snapshot(array $data): array
    {
        $item = FeeItem::lockForUpdate()->findOrFail($data['fee_item_id']);
        $year = SessionYear::lockForUpdate()->findOrFail($data['session_year_id']);
        app(FeePlans::class)->year($year);
        $account = FeeAccount::lockForUpdate()->findOrFail($data['fee_account_id']);
        abort_unless($item->state === 'active' && $account->active, 422, 'Choose an active fee item and family account.');
        abort_unless((int) $year->getAttribute('default') === 1, 422, 'Only the current school year can post new fee item charges.');
        $today = now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->toDateString();
        $start = substr((string) $year->getRawOriginal('start_date'), 0, 10);
        $end = substr((string) $year->getRawOriginal('end_date'), 0, 10);
        abort_unless($data['charged_on'] >= $start && $data['charged_on'] <= $end && $data['charged_on'] <= $today, 422, 'Choose a charge date in the current school year that is not in the future.');
        abort_unless($item->currency === $account->currency && $item->tax_treatment === 'no_vat', 422, 'The fee item and account must use the same supported currency and tax treatment.');
        $ids = FeeAccountLearner::where('fee_account_id', $account->id)->where('session_year_id', $year->id)->orderBy('learner_id')->lockForUpdate()->pluck('learner_id')->map(fn ($id) => (int) $id)->all();
        abort_unless($ids !== [], 422, 'Choose a family account with assigned learners.');
        if ($item->scope === 'learner') {
            abort_unless($data['learner_id'] !== null && in_array($data['learner_id'], $ids, true), 422, 'Choose a learner assigned to this family in the selected year.');
            $ids = [$data['learner_id']];
        } else {
            abort_unless($data['learner_id'] === null, 422, 'A family item charges the family once, using its reviewed learner roster.');
        }
        $members = Enrolment::where('session_year_id', $year->id)->onDate($data['charged_on'])->whereIn('learner_id', $ids)->lockForUpdate()->get()->groupBy('learner_id');
        foreach ($ids as $id) {
            abort_unless($members->get($id, collect())->count() === 1, 422, 'Each charged learner needs one dated enrolment on the charge date.');
        }
        $holderRows = FeeAccountHolder::where('fee_account_id', $account->id)->where('session_year_id', $year->id)->orderBy('user_id')->lockForUpdate()->get();
        $holders = app(FeeAccounts::class)->holders($holderRows->map(fn ($h) => ['user_id' => (int) $h->user_id, 'share_bp' => (int) $h->share_bp])->all(), $ids);
        $learners = Students::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
        abort_unless($learners->count() === count($ids), 404);
        $people = User::whereIn('id', $learners->pluck('user_id'))->orderBy('id')->lockForUpdate()->get(['id', 'first_name', 'last_name'])->keyBy('id');
        $amount = $item->amount_cents;
        $grand = BillingPreview::shares($amount, $holders);
        $shares = $item->scope === 'family' ? FeeItemShares::family($amount, $ids, $holders) : [$ids[0] => $grand];
        $lines = [];
        $roster = [];
        foreach ($learners as $learner) {
            $person = $people->get($learner->user_id);
            abort_unless($person !== null, 422, 'The learner record needs reconciliation.');
            $id = (int) $learner->id;
            $name = trim($person->first_name.' '.$person->last_name);
            $roster[] = ['id' => $id, 'name' => $name];
            $cents = array_sum(array_column($shares[$id], 'amount_cents'));
            if ($cents > 0) {
                $lines[] = ['learner_id' => $id, 'learner_name' => $name, 'amount_cents' => $cents, 'holder_shares' => $shares[$id]];
            }
        }
        $hash = hash('sha256', 'fee-occurrence:'.$data['source_reference']);
        $target = $item->scope === 'family' ? (int) $account->id : $data['learner_id'];
        abort_unless(! FeeCharge::where('fee_item_id', $item->id)->where('session_year_id', $year->id)->where('scope', $item->scope)->where('target_id', $target)->where('reference_hash', $hash)->lockForUpdate()->exists(), 409, 'This occurrence is already charged. Open its recorded invoice instead.');
        app(FeeStatements::class)->complete($account);

        return ['fee_item_id' => (int) $item->id, 'item_revision' => $item->revision, 'item_name' => $item->name, 'scope' => $item->scope, 'fee_account_id' => (int) $account->id, 'account_revision' => $account->revision, 'account_name' => $account->name, 'session_year_id' => (int) $year->id, 'year_start' => $start, 'year_end' => $end, 'learner_id' => $data['learner_id'], 'target_id' => $target, 'reference_hash' => $hash, 'charged_on' => $data['charged_on'], 'due_on' => $data['due_on'], 'issued_on' => $today, 'currency' => $item->currency, 'amount_cents' => $amount, 'learners' => $roster, 'holder_totals' => $grand, 'lines' => $lines];
    }

    private function validated(array $input, bool $posting = false): array
    {
        if (is_string($input['source_reference'] ?? null)) {
            $input['source_reference'] = mb_strtoupper(trim($input['source_reference']));
        }
        $data = Validator::make($input, self::rules($posting))->validate();
        foreach (['fee_item_id', 'fee_account_id', 'session_year_id'] as $field) {
            $data[$field] = (int) $data[$field];
        }
        $data['learner_id'] = isset($data['learner_id']) ? (int) $data['learner_id'] : null;

        return $data;
    }

    private function fingerprint(array $snapshot): string
    {
        return hash_hmac('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR), (string) config('app.key'));
    }

    public static function rules(bool $posting = false): array
    {
        return ['operation_id' => ['required', 'uuid'], 'fee_item_id' => ['required', 'integer', 'min:1'], 'fee_account_id' => ['required', 'integer', 'min:1'], 'session_year_id' => ['required', 'integer', 'min:1'], 'learner_id' => ['nullable', 'integer', 'min:1'], 'charged_on' => ['required', 'date_format:Y-m-d'], 'due_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:charged_on'], 'source_reference' => ['required', 'string', 'max:80', 'regex:/\A[\pL\pN][\pL\pN ._:\/-]*\z/uD']] + ($posting ? ['fingerprint' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/D'], 'confirmed' => ['required', 'accepted']] : []);
    }
}
