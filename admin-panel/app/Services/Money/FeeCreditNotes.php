<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Events\Money\FeeCreditPosted;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeeCreditNote;
use App\Models\Money\FeeCreditRelease;
use App\Models\Money\Invoice;
use App\Models\Money\InvoiceLine;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class FeeCreditNotes
{
    public const PERMISSION = 'fee-credits-manage';

    public const REASONS = ['billing_error', 'fee_reduction', 'leaver_credit', 'other'];

    public function preview(User $actor, array $input): array
    {
        app(MoneyPolicy::class)->authorize($actor, self::PERMISSION);
        $data = $this->validated($input);

        return DB::transaction(function () use ($actor, $data): array {
            DB::table('schools')->where('id', app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            app(MoneyPolicy::class)->authorize($actor, self::PERMISSION, locked: true);
            $snapshot = $this->snapshot($data);

            return $snapshot + ['fingerprint' => $this->fingerprint($snapshot)];
        });
    }

    public function post(User $actor, array $input): FeeCreditNote
    {
        app(MoneyPolicy::class)->authorize($actor, self::PERMISSION);
        $data = $this->validated($input, true);
        $receipt = app(BillingOperations::class)->once($actor, self::PERMISSION, 'fee_credit_note', $data, function () use ($actor, $data): array {
            $snapshot = $this->snapshot($data);
            abort_unless(hash_equals($this->fingerprint($snapshot), $data['fingerprint']), 409, 'Charges or allocations changed. Review a new credit note.');
            $money = fn (int $cents) => Money::ofCents($cents, $snapshot['currency']);
            $postings = [Posting::debit($snapshot['income_account_id'], $money($snapshot['amount_cents']))];
            if ($snapshot['unpaid_cents'] > 0) {
                $postings[] = Posting::credit($snapshot['receivable_account_id'], $money($snapshot['unpaid_cents']));
            }
            if ($snapshot['released_cents'] > 0) {
                $postings[] = Posting::credit($snapshot['credit_account_id'], $money($snapshot['released_cents']));
            }
            $source = 'fee-credit:'.$data['operation_id'];
            $entry = app(LedgerService::class)->post($source, $source, 'Posted fee credit note', $postings);
            $note = FeeCreditNote::create(['fee_account_id' => $snapshot['fee_account_id'], 'invoice_line_id' => $snapshot['invoice_line_id'], 'holder_user_id' => $snapshot['holder_user_id'],
                'currency' => $snapshot['currency'], 'amount_cents' => $snapshot['amount_cents'], 'unpaid_cents' => $snapshot['unpaid_cents'], 'released_cents' => $snapshot['released_cents'],
                'income_account_id' => $snapshot['income_account_id'], 'receivable_account_id' => $snapshot['receivable_account_id'], 'credit_account_id' => $snapshot['credit_account_id'],
                'reason_code' => $data['reason_code'], 'reason' => $data['reason'], 'fingerprint' => $data['fingerprint'], 'snapshot' => $snapshot, 'ledger_entry_id' => $entry, 'created_by' => $actor->id, 'issued_on' => $snapshot['issued_on'], 'created_at' => now('UTC')]);
            foreach ($snapshot['releases'] as $release) {
                FeeCreditRelease::create(['credit_note_id' => $note->id, 'allocation_id' => $release['allocation_id'], 'payment_id' => $release['payment_id'], 'amount_cents' => $release['amount_cents'], 'created_at' => now('UTC')]);
            }
            $note->update(['sealed_at' => now('UTC')]);
            app(FeeStatements::class)->complete(FeeAccount::findOrFail($snapshot['fee_account_id']));
            Audit::record('money.fee_credit_posted', $note, [], ['amount_cents' => $note->amount_cents, 'unpaid_cents' => $note->unpaid_cents, 'released_cents' => $note->released_cents, 'reason_code' => $note->reason_code, 'ledger_entry_id' => $entry]);
            Outbox::record(new FeeCreditPosted(app(CurrentSchool::class)->id(), (int) $note->id, $note->amount_cents));

            return ['id' => (int) $note->id];
        });

        return FeeCreditNote::findOrFail($receipt['id']);
    }

    private function snapshot(array $data): array
    {
        $line = InvoiceLine::lockForUpdate()->findOrFail($data['invoice_line_id']);
        $invoice = Invoice::whereNotNull('sealed_at')->lockForUpdate()->findOrFail($line->invoice_id);
        $account = FeeAccount::lockForUpdate()->findOrFail($invoice->fee_account_id);
        app(FeeStatements::class)->complete($account);
        $charge = collect(app(FeeStatements::class)->charges($account, $data['holder_user_id']))->firstWhere('invoice_line_id', (int) $line->id);
        abort_unless($charge !== null, 422, 'Choose a holder recorded on this invoice line.');
        $amount = $data['amount_cents'];
        abort_unless($amount <= $charge['charged_cents'], 422, 'The credit exceeds this holder remaining invoice share.');
        $unpaid = min($amount, $charge['outstanding_cents']);
        $left = $amount - $unpaid;
        $school = app(CurrentSchool::class)->id();
        $releases = [];
        $creditAccount = null;
        $released = FeeCreditHistory::releases()->where('a.invoice_line_id', $line->id)->where('p.holder_user_id', $data['holder_user_id'])
            ->selectRaw('c.allocation_id, SUM(c.amount_cents) AS cents')->groupBy('c.allocation_id')->pluck('cents', 'allocation_id');
        $allocations = DB::table('payment_allocations as a')->join('payments as p', fn ($j) => $j->on('p.school_id', '=', 'a.school_id')->on('p.id', '=', 'a.payment_id'))
            ->join('payment_allocation_runs as r', fn ($j) => $j->on('r.school_id', '=', 'a.school_id')->on('r.id', '=', 'a.allocation_run_id'))
            ->where('a.school_id', $school)->where('a.invoice_line_id', $line->id)->where('p.holder_user_id', $data['holder_user_id'])->whereNotNull('r.sealed_at')->orderByDesc('a.id')->lockForUpdate()
            ->get(['a.id', 'a.payment_id', 'a.amount_cents', 'p.credit_account_id']);
        foreach ($allocations as $allocation) {
            if ($left === 0) {
                break;
            }
            $available = (int) $allocation->amount_cents - (int) ($released[$allocation->id] ?? 0);
            abort_unless($available >= 0, 409, 'Released allocations need reconciliation.');
            $cents = min($available, $left);
            if ($cents === 0) {
                continue;
            }
            $creditAccount ??= (int) $allocation->credit_account_id;
            abort_unless($creditAccount === (int) $allocation->credit_account_id, 409, 'Holder credit accounts need reconciliation.');
            $releases[] = ['allocation_id' => (int) $allocation->id, 'payment_id' => (int) $allocation->payment_id, 'amount_cents' => $cents];
            $left -= $cents;
        }
        abort_unless($left === 0, 409, 'Paid invoice shares need reconciliation.');
        $income = DB::table('ledger_postings as p')->join('ledger_accounts as a', fn ($j) => $j->on('a.school_id', '=', 'p.school_id')->on('a.id', '=', 'p.account_id'))
            ->where('p.school_id', $school)->where('p.entry_id', $invoice->ledger_entry_id)->where('p.direction', 'credit')->where('a.type', 'fee_income')->value('a.id');
        abort_unless($income !== null && $account->ledger_account_id !== null, 409, 'Invoice accounts need reconciliation.');

        return ['fee_account_id' => (int) $account->id, 'invoice_line_id' => (int) $line->id, 'invoice_number' => $invoice->number, 'learner_id' => (int) $line->learner_id, 'learner_name' => $line->learner_name,
            'holder_user_id' => $data['holder_user_id'], 'holder_name' => $charge['holder_name'], 'currency' => $line->currency, 'gross_charged_cents' => $charge['gross_charged_cents'],
            'credited_before_cents' => $charge['credited_cents'], 'charged_before_cents' => $charge['charged_cents'], 'allocated_before_cents' => $charge['allocated_cents'], 'outstanding_before_cents' => $charge['outstanding_cents'],
            'amount_cents' => $amount, 'unpaid_cents' => $unpaid, 'released_cents' => $amount - $unpaid, 'charged_after_cents' => $charge['charged_cents'] - $amount,
            'allocated_after_cents' => $charge['allocated_cents'] - ($amount - $unpaid), 'outstanding_after_cents' => $charge['outstanding_cents'] - $unpaid,
            'income_account_id' => (int) $income, 'receivable_account_id' => (int) $account->ledger_account_id, 'credit_account_id' => $creditAccount, 'releases' => $releases,
            'reason_code' => $data['reason_code'], 'reason_hash' => hash_hmac('sha256', 'fee-credit-reason:'.$data['reason'], (string) config('app.key')),
            'issued_on' => now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->toDateString()];
    }

    private function validated(array $input, bool $posting = false): array
    {
        if (is_string($input['reason'] ?? null)) {
            $input['reason'] = trim($input['reason']);
        }
        $data = Validator::make($input, self::rules($posting))->validate();
        $data['invoice_line_id'] = (int) $data['invoice_line_id'];
        $data['holder_user_id'] = (int) $data['holder_user_id'];
        $data['amount_cents'] = Money::fromDecimalString($data['amount'])->cents;
        abort_unless($data['amount_cents'] > 0 && $data['amount_cents'] <= FeePlans::MAX_CENTS, 422, 'Use a positive supported credit amount.');
        unset($data['amount']);

        return $data;
    }

    private function fingerprint(array $snapshot): string
    {
        return hash_hmac('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR), (string) config('app.key'));
    }

    public static function reasonLabels(): array
    {
        return ['billing_error' => __('Billing correction'), 'fee_reduction' => __('Fee reduction'), 'leaver_credit' => __('Leaver credit'), 'other' => __('Other fee correction')];
    }

    public static function rules(bool $posting = false): array
    {
        return ['operation_id' => ['required', 'uuid'], 'invoice_line_id' => ['required', 'integer', 'min:1'], 'holder_user_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'string', 'regex:/\A\d{1,10}(?:\.\d{1,2})?\z/D'], 'reason_code' => ['required', 'in:'.implode(',', self::REASONS)], 'reason' => ['required', 'string', 'min:5', 'max:1200']]
            + ($posting ? ['fingerprint' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/D'], 'confirmed' => ['required', 'accepted']] : []);
    }
}
