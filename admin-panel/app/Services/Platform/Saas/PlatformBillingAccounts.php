<?php

declare(strict_types=1);

namespace App\Services\Platform\Saas;

use App\Mail\Platform\NotificationMail;
use App\Models\Platform\PlatformInvoice;
use App\Models\Platform\PlatformPayment;
use App\Models\Platform\PlatformPaymentAllocation;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\MailService;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * A school's account with Navigate Ed (Action Plan 1.17 and SA.8, Build 1):
 * EFT payments and what they settle, account credit, the statement, overdue
 * invoices, reminders and the list of schools due for suspension.
 *
 * Money in: a payment is recorded with its amount, date, the bank's reference
 * and the invoices it settles; any amount left over is credit on the school's
 * account, which HQ applies to a later invoice. A credit note's credit works
 * the same way. Nothing is deleted: a mistaken payment is reversed with a
 * reason, and what it settled is unpaid again.
 *
 * An issued invoice is overdue once its due date has passed with something
 * unpaid. After the grace period (config/platform_billing.php) the school is
 * listed as due for suspension; suspending stays a deliberate HQ action
 * through the existing school status form, never automatic.
 *
 * This is not the school's own fee ledger: nothing here posts to it.
 * engineering/specs/platform/hq-billing.md.
 */
class PlatformBillingAccounts
{
    public const NOTICE_TYPE = 'billing.overdue';

    public function __construct(
        private readonly CurrentSchool $currentSchool,
        private readonly PlatformBilling $billing,
    ) {}

    public static function paymentRules(): array
    {
        return [
            'amount' => ['required', 'string', 'regex:/^\d{1,8}(\.\d{1,2})?$/D'],
            'received_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.PlatformBilling::today()],
            'bank_reference' => ['required', 'string', 'min:2', 'max:96', 'regex:/^[\pL\pN .\/#:_-]+$/uD'],
            'note' => ['nullable', 'string', 'max:500'],
            // invoice id => rands settled on it.
            'allocations' => ['nullable', 'array', 'max:100'],
            'allocations.*' => ['nullable', 'string', 'regex:/^\d{1,8}(\.\d{1,2})?$/D'],
        ];
    }

    public static function reversalRules(): array
    {
        return ['reason' => ['required', 'string', 'min:10', 'max:500']];
    }

    // ---- settling ----

    /** What is unpaid on an issued invoice: its total less what payments and credit notes settle on it. */
    public function outstanding(PlatformInvoice $invoice, bool $lock = false): int
    {
        // Current reads are essential on money writes: a prior REPEATABLE READ
        // snapshot can predate another request's committed allocation.
        $allocations = PlatformPaymentAllocation::query()->where('invoice_id', $invoice->id)->whereNull('reversed_at');
        $settled = $lock ? $allocations->lockForUpdate()->get(['amount_cents'])->sum('amount_cents') : $allocations->sum('amount_cents');

        return max(0, (int) $invoice->total_cents - (int) $settled);
    }

    /**
     * Record an EFT payment for a school and what it settles. Over-payment is
     * account credit. The bank reference may not repeat on another payment of
     * the school that stands.
     */
    public function record(int $schoolId, array $input, User $actor): int
    {
        $this->billing->authorise($actor);
        $data = Validator::make($input, self::paymentRules())->validate();
        $amount = Money::fromDecimalString($data['amount'], 'ZAR')->cents;
        if ($amount === 0) {
            throw ValidationException::withMessages(['amount' => __('A payment must be more than R 0.00.')]);
        }
        $reference = trim((string) $data['bank_reference']);
        $wanted = [];
        foreach ((array) ($data['allocations'] ?? []) as $invoiceId => $rands) {
            if ($rands === null || $rands === '') {
                continue;
            }
            if (! is_int($invoiceId) && ! ctype_digit((string) $invoiceId)) {
                throw ValidationException::withMessages(['allocations' => __('Choose invoices from the list.')]);
            }
            $cents = Money::fromDecimalString((string) $rands, 'ZAR')->cents;
            if ($cents > 0) {
                $wanted[(int) $invoiceId] = $cents;
            }
        }
        if (array_sum($wanted) > $amount) {
            throw ValidationException::withMessages(['allocations' => __('The invoices settled add up to more than the payment.')]);
        }

        return $this->billing->inSchool($schoolId, function () use ($data, $amount, $reference, $wanted, $actor): int {
            $repeat = PlatformPayment::query()->whereNull('reversed_at')->where('bank_reference', $reference)->lockForUpdate()->first();
            if ($repeat !== null) {
                throw ValidationException::withMessages(['bank_reference' => __('A payment with this bank reference is already recorded (:amount on :date). Reverse it first if it was a mistake.', [
                    'amount' => PlatformBilling::rands((int) $repeat->amount_cents), 'date' => $repeat->received_on->toDateString()])]);
            }
            $invoices = $this->lockOpen(array_keys($wanted));
            foreach ($wanted as $invoiceId => $cents) {
                if ($cents > $this->outstanding($invoices[$invoiceId], true)) {
                    throw ValidationException::withMessages(['allocations.'.$invoiceId => __('Only :amount is unpaid on :reference.', ['amount' => PlatformBilling::rands($this->outstanding($invoices[$invoiceId], true)), 'reference' => $invoices[$invoiceId]->reference])]);
                }
            }
            $payment = PlatformPayment::query()->create([
                'received_on' => $data['received_on'], 'amount_cents' => $amount, 'currency' => (string) config('platform_billing.currency', 'ZAR'),
                'bank_reference' => $reference, 'note' => ($data['note'] ?? null) ?: null, 'recorded_by' => $actor->id,
            ]);
            foreach ($wanted as $invoiceId => $cents) {
                PlatformPaymentAllocation::query()->create(['invoice_id' => $invoiceId, 'payment_id' => $payment->id, 'amount_cents' => $cents, 'created_by' => $actor->id]);
            }
            Audit::record('platform_payment_recorded', $payment, [], [
                'payment_id' => $payment->id, 'received_on' => $data['received_on'], 'amount_cents' => $amount, 'bank_reference' => $reference,
                'allocations' => array_map(fn (int $cents, int $invoice): array => ['invoice_id' => $invoice, 'reference' => $invoices[$invoice]->reference, 'amount_cents' => $cents], $wanted, array_keys($wanted)),
                'credit_cents' => $amount - array_sum($wanted), 'changed_by' => $actor->id,
            ], $payment->school_id);

            return (int) $payment->id;
        });
    }

    /** Reverse a mistaken payment with a reason. It stays on the statement; what it settled is unpaid again. */
    public function reverse(int $schoolId, int $paymentId, array $input, User $actor): void
    {
        $this->billing->authorise($actor);
        $reason = Validator::make($input, self::reversalRules())->validate()['reason'];
        $this->billing->inSchool($schoolId, function () use ($paymentId, $reason, $actor): void {
            $payment = PlatformPayment::query()->whereKey($paymentId)->lockForUpdate()->first();
            abort_if($payment === null, 404);
            if ($payment->reversed_at !== null) {
                throw ValidationException::withMessages(['payment' => __('This payment was reversed already.')]);
            }
            $allocations = PlatformPaymentAllocation::query()->where('payment_id', $payment->id)->whereNull('reversed_at')->lockForUpdate()->get();
            $payment->forceFill(['reversed_at' => now(), 'reversed_by' => $actor->id, 'reversal_reason' => $reason])->save();
            foreach ($allocations as $allocation) {
                $allocation->forceFill(['reversed_at' => now(), 'reversed_by' => $actor->id])->save();
            }
            Audit::record('platform_payment_reversed', $payment, ['payment_id' => $payment->id, 'reversed' => false], [
                'payment_id' => $payment->id, 'reversed' => true, 'amount_cents' => (int) $payment->amount_cents, 'bank_reference' => $payment->bank_reference,
                'unsettled' => $allocations->map(fn (PlatformPaymentAllocation $a): array => ['invoice_id' => (int) $a->invoice_id, 'amount_cents' => (int) $a->amount_cents])->all(),
                'reason' => $reason, 'changed_by' => $actor->id,
            ], $payment->school_id);
        });
    }

    /**
     * Settle an issued invoice from the school's account credit: credit notes'
     * credit first, then over-payments, oldest first.
     */
    public function applyCredit(int $schoolId, int $invoiceId, User $actor): int
    {
        $this->billing->authorise($actor);

        return $this->billing->inSchool($schoolId, function () use ($invoiceId, $actor): int {
            $invoice = PlatformInvoice::query()->whereKey($invoiceId)->lockForUpdate()->first();
            abort_if($invoice === null, 404);
            if ($invoice->kind !== PlatformInvoice::INVOICE || ! $invoice->isIssued()) {
                throw ValidationException::withMessages(['invoice' => __('Account credit settles issued invoices only.')]);
            }
            $due = $this->outstanding($invoice, true);
            if ($due === 0) {
                throw ValidationException::withMessages(['invoice' => __('Nothing is unpaid on this invoice.')]);
            }
            $sources = $this->creditSources(true);
            if ($sources === []) {
                throw ValidationException::withMessages(['invoice' => __('This school has no account credit.')]);
            }
            $applied = [];
            foreach ($sources as $source) {
                if ($due === 0) {
                    break;
                }
                $cents = min($due, $source['available_cents']);
                PlatformPaymentAllocation::query()->create(['invoice_id' => $invoice->id, $source['column'] => $source['id'], 'amount_cents' => $cents, 'created_by' => $actor->id]);
                $applied[] = [$source['column'] => $source['id'], 'amount_cents' => $cents];
                $due -= $cents;
            }
            $total = array_sum(array_column($applied, 'amount_cents'));
            Audit::record('platform_billing_credit_applied', $invoice, [], ['invoice_id' => $invoice->id, 'reference' => $invoice->reference,
                'applied' => $applied, 'amount_cents' => $total, 'changed_by' => $actor->id], $invoice->school_id);

            return $total;
        });
    }

    /**
     * The school's account credit, by source: credit notes and payments that
     * stand, with what they have not settled yet. Oldest first.
     *
     * @return list<array{column: string, id: int, date: string, label: string, available_cents: int}>
     */
    public function creditSources(bool $lock = false): array
    {
        // Lock the allocation rows as a current read, then aggregate in memory.
        // A grouped snapshot SUM can otherwise spend credit a second time
        // after waiting for the payment or credit-note source lock.
        $settled = fn (string $column): array => PlatformPaymentAllocation::query()->whereNotNull($column)->whereNull('reversed_at')
            ->when($lock, fn ($q) => $q->lockForUpdate())->get([$column, 'amount_cents'])
            ->groupBy($column)->map(fn ($rows) => (int) $rows->sum('amount_cents'))->all();
        $notes = PlatformInvoice::query()->where('kind', PlatformInvoice::CREDIT_NOTE)->where('status', PlatformInvoice::ISSUED)->orderBy('issued_on')->orderBy('id')
            ->when($lock, fn ($q) => $q->lockForUpdate())->get(['id', 'reference', 'issued_on', 'total_cents']);
        $payments = PlatformPayment::query()->whereNull('reversed_at')->orderBy('received_on')->orderBy('id')
            ->when($lock, fn ($q) => $q->lockForUpdate())->get(['id', 'bank_reference', 'received_on', 'amount_cents']);
        $fromNotes = $settled('credit_note_id');
        $fromPayments = $settled('payment_id');
        $sources = [];
        foreach ($notes as $note) {
            $available = -(int) $note->total_cents - ($fromNotes[$note->id] ?? 0);
            if ($available > 0) {
                $sources[] = ['column' => 'credit_note_id', 'id' => (int) $note->id, 'date' => (string) $note->issued_on?->toDateString(), 'label' => __('Credit note :reference', ['reference' => $note->reference]), 'available_cents' => $available];
            }
        }
        foreach ($payments as $payment) {
            $available = (int) $payment->amount_cents - ($fromPayments[$payment->id] ?? 0);
            if ($available > 0) {
                $sources[] = ['column' => 'payment_id', 'id' => (int) $payment->id, 'date' => $payment->received_on->toDateString(), 'label' => __('Payment :reference', ['reference' => $payment->bank_reference]), 'available_cents' => $available];
            }
        }

        return $sources;
    }

    // ---- the statement ----

    /** One school's statement: every issued invoice, credit note and payment, with the balance after each. */
    public function statement(int $schoolId): array
    {
        $school = $this->billing->school($schoolId);

        return $this->currentSchool->run($schoolId, function () use ($school): array {
            $documents = PlatformInvoice::query()->where('status', PlatformInvoice::ISSUED)->orderBy('issued_on')->orderBy('id')->get();
            $payments = PlatformPayment::query()->orderBy('received_on')->orderBy('id')->get();
            $entries = [];
            foreach ($documents as $doc) {
                $entries[] = ['date' => $doc->issued_on?->toDateString(), 'order' => 1, 'kind' => $doc->kind, 'id' => (int) $doc->id, 'reference' => $doc->reference,
                    'description' => $doc->kind === PlatformInvoice::INVOICE ? __('Invoice for :month', ['month' => $doc->period_start->format('F Y')]) : __('Credit note: :reason', ['reason' => (string) $doc->reason]),
                    'amount_cents' => (int) $doc->total_cents];
            }
            foreach ($payments as $payment) {
                $entries[] = ['date' => $payment->received_on->toDateString(), 'order' => 2, 'kind' => 'payment', 'id' => (int) $payment->id, 'reference' => $payment->bank_reference,
                    'description' => __('EFT payment'), 'amount_cents' => -(int) $payment->amount_cents];
                if ($payment->reversed_at !== null) {
                    $entries[] = ['date' => CarbonImmutable::parse($payment->reversed_at)->setTimezone(PlatformBilling::TIMEZONE)->toDateString(), 'order' => 3, 'kind' => 'reversal', 'id' => (int) $payment->id, 'reference' => $payment->bank_reference,
                        'description' => __('Payment reversed: :reason', ['reason' => (string) $payment->reversal_reason]), 'amount_cents' => (int) $payment->amount_cents];
                }
            }
            usort($entries, fn (array $a, array $b): int => [$a['date'], $a['order'], $a['id']] <=> [$b['date'], $b['order'], $b['id']]);
            $balance = 0;
            foreach ($entries as $i => $entry) {
                $balance += $entry['amount_cents'];
                $entries[$i]['balance_cents'] = $balance;
            }
            $today = PlatformBilling::today();
            $open = $documents->where('kind', PlatformInvoice::INVOICE)->map(fn (PlatformInvoice $invoice): array => $this->billing->present($invoice) + [
                'outstanding_cents' => $this->outstanding($invoice),
                'overdue' => $invoice->due_on !== null && $invoice->due_on->toDateString() < $today,
            ])->filter(fn (array $invoice): bool => $invoice['outstanding_cents'] > 0)->values()->all();
            $credit = array_sum(array_column($this->creditSources(), 'available_cents'));

            return [
                'school' => ['id' => (int) $school->id, 'name' => (string) $school->name, 'status' => (string) $school->status],
                'entries' => $entries,
                'balance_cents' => $balance,
                'open' => $open,
                'credit_cents' => $credit,
                'credit_sources' => $this->creditSources(),
                'payments' => $payments->map(fn (PlatformPayment $p): array => [
                    'id' => (int) $p->id, 'received_on' => $p->received_on->toDateString(), 'amount_cents' => (int) $p->amount_cents, 'bank_reference' => $p->bank_reference,
                    'note' => $p->note, 'reversed' => $p->reversed_at !== null, 'reversal_reason' => $p->reversal_reason,
                ])->all(),
                'drafts' => PlatformInvoice::query()->where('status', PlatformInvoice::DRAFT)->orderBy('period_start')->get()->map(fn (PlatformInvoice $d): array => $this->billing->present($d))->all(),
                'contacts' => DB::table('school_contacts')->where('school_id', $school->id)->where('role', 'bursar')->orderBy('name')->get(['name', 'email', 'phone'])->map(fn ($c) => (array) $c)->all(),
            ];
        });
    }

    // ---- overdue, reminders, suspension ----

    /**
     * Schools with an overdue invoice: the amount overdue, the oldest due
     * date, days past it, and whether the grace period has run out.
     *
     * @return list<array<string, mixed>>
     */
    public function overdue(): array
    {
        $today = PlatformBilling::today();
        $grace = max(0, (int) config('platform_billing.grace_days', 14));

        return $this->currentSchool->acrossSchools(function () use ($today, $grace): array {
            $rows = $this->unpaid(DB::table('platform_invoices as i'))->where('i.due_on', '<', $today)
                ->join('schools as s', 's.id', '=', 'i.school_id')
                ->groupBy('i.school_id', 's.name', 's.status')
                ->selectRaw('i.school_id, s.name, s.status, COUNT(*) AS invoices, MIN(i.due_on) AS oldest_due, MAX(i.reminded_at) AS reminded_at,
                    SUM(i.total_cents - COALESCE((SELECT SUM(a.amount_cents) FROM platform_payment_allocations a WHERE a.invoice_id = i.id AND a.reversed_at IS NULL), 0)) AS overdue_cents')
                ->orderBy('oldest_due')->get();

            return $rows->map(function (object $row) use ($today, $grace): array {
                $days = (int) CarbonImmutable::createFromFormat('!Y-m-d', (string) $row->oldest_due)->diffInDays(CarbonImmutable::createFromFormat('!Y-m-d', $today), true);

                return [
                    'school_id' => (int) $row->school_id, 'name' => (string) $row->name, 'status' => (string) $row->status,
                    'invoices' => (int) $row->invoices, 'oldest_due' => (string) $row->oldest_due, 'days_overdue' => $days,
                    'overdue_cents' => (int) $row->overdue_cents, 'reminded_at' => $row->reminded_at,
                    'stage' => $row->status !== School::ACTIVE ? 'not_active' : ($days > $grace ? 'due_for_suspension' : 'grace'),
                    'grace_ends' => CarbonImmutable::createFromFormat('!Y-m-d', (string) $row->oldest_due)->addDays($grace)->toDateString(),
                ];
            })->all();
        });
    }

    /**
     * Remind a school that invoices are overdue: a notice to its active Super
     * Admins (Notifier, billing.overdue) and an email to the bursar contact
     * HQ holds for it. Recorded on each overdue invoice and audited.
     *
     * @return array{super_admins: int, bursars: int}
     */
    public function remind(int $schoolId, User $actor): array
    {
        $this->billing->authorise($actor);
        $school = $this->billing->school($schoolId);
        $today = PlatformBilling::today();

        return $this->currentSchool->run($schoolId, function () use ($school, $today, $actor): array {
            $overdue = PlatformInvoice::query()->where('kind', PlatformInvoice::INVOICE)->where('status', PlatformInvoice::ISSUED)
                ->where('due_on', '<', $today)->orderBy('due_on')->get()->filter(fn (PlatformInvoice $invoice): bool => $this->outstanding($invoice) > 0)->values();
            if ($overdue->isEmpty()) {
                throw ValidationException::withMessages(['school' => __('Nothing is overdue for this school.')]);
            }
            $amount = $overdue->sum(fn (PlatformInvoice $invoice): int => $this->outstanding($invoice));
            $references = $overdue->pluck('reference')->implode(', ');
            $title = __('Navigate Ed account overdue');
            $body = __(':amount is overdue on :references. Please pay by EFT with the invoice reference.', ['amount' => PlatformBilling::rands($amount), 'references' => $references]);
            $admins = $this->superAdmins((int) $school->id);
            $bursars = DB::table('school_contacts')->where('school_id', $school->id)->where('role', 'bursar')->whereNotNull('email')->orderBy('id')->get(['id', 'email']);

            DB::transaction(function () use ($overdue, $admins, $bursars, $title, $body, $school, $amount, $actor): void {
                $notifier = app(Notifier::class);
                if ($admins !== []) {
                    $notifier->assign($notifier->makeNotice(self::NOTICE_TYPE, $title, $body, '/home'), $admins);
                }
                foreach ($bursars as $bursar) {
                    MailService::queue(new NotificationMail((string) $bursar->email, self::NOTICE_TYPE, $title, $body, rtrim((string) config('identity.portal_url', config('app.url')), '/').'/', (string) $school->name), null);
                }
                foreach ($overdue as $invoice) {
                    $invoice->forceFill(['reminded_at' => now(), 'reminders' => (int) $invoice->reminders + 1])->save();
                }
                Audit::record('platform_billing_reminder_sent', null, [], [
                    'invoices' => $overdue->pluck('id')->map(fn ($id) => (int) $id)->all(), 'overdue_cents' => $amount,
                    'super_admins' => count($admins), 'bursar_contacts' => $bursars->pluck('id')->map(fn ($id) => (int) $id)->all(), 'changed_by' => $actor->id,
                ], (int) $school->id);
            }, 3);

            return ['super_admins' => count($admins), 'bursars' => $bursars->count()];
        });
    }

    /** @return list<int> the school's Super Admins with an active membership today */
    public function superAdmins(int $schoolId): array
    {
        return SchoolMembership::query()->active()->where('school_id', $schoolId)->where('role', SchoolRoles::SUPER_ADMIN)
            ->join('users', 'users.id', '=', 'school_memberships.user_id')->where('users.status', 1)->whereNull('users.deleted_at')
            ->orderBy('school_memberships.user_id')->distinct()->pluck('school_memberships.user_id')->map(fn ($id) => (int) $id)->all();
    }

    // ---- the billing page ----

    /** The month's drafts and issued invoices, the summary, overdue schools and every school's balance. */
    public function overview(string $month): array
    {
        $today = PlatformBilling::today();

        return $this->currentSchool->acrossSchools(function () use ($month, $today): array {
            $names = DB::table('schools')->orderBy('name')->get(['id', 'name', 'status']);
            $invoices = PlatformInvoice::acrossSchools()->where('slot', $month)->orderBy('school_id')->get();
            $present = fn (PlatformInvoice $invoice): array => $this->billing->present($invoice) + ['school' => (string) ($names->firstWhere('id', $invoice->getAttribute('school_id'))->name ?? '')];
            $start = $month.'-01';
            $end = CarbonImmutable::createFromFormat('!Y-m-d', $start)->endOfMonth()->toDateString();
            $issued = $invoices->where('status', PlatformInvoice::ISSUED);
            $credited = (int) PlatformInvoice::acrossSchools()->where('kind', PlatformInvoice::CREDIT_NOTE)->where('status', PlatformInvoice::ISSUED)->whereBetween('issued_on', [$start, $end])->sum('total_cents');
            $creditedForMonth = (int) DB::table('platform_invoices as c')->join('platform_invoices as o', 'o.id', '=', 'c.credits_invoice_id')
                ->where('c.status', PlatformInvoice::ISSUED)->where('o.slot', $month)->sum('c.subtotal_cents');
            $overdue = $this->overdue();
            $balances = $this->balances();

            return [
                'month' => $month,
                'drafts' => $invoices->where('status', PlatformInvoice::DRAFT)->map(fn (PlatformInvoice $i): array => $present($i) + ['problem' => $this->billing->issueProblem($i)])->values()->all(),
                'issued' => $issued->map(fn (PlatformInvoice $i): array => $present($i) + ['outstanding_cents' => $this->outstanding($i)])->values()->all(),
                'discarded' => $invoices->where('status', PlatformInvoice::DISCARDED)->map($present)->values()->all(),
                'summary' => [
                    'invoiced_cents' => (int) $issued->sum('total_cents'),
                    'credited_cents' => -$credited,
                    'collected_cents' => (int) PlatformPayment::acrossSchools()->whereNull('reversed_at')->whereBetween('received_on', [$start, $end])->sum('amount_cents'),
                    'overdue_cents' => array_sum(array_column($overdue, 'overdue_cents')),
                    // Simple monthly recurring revenue: the month's invoiced amounts excluding VAT, less credit notes against them.
                    'mrr_cents' => (int) $issued->sum('subtotal_cents') + $creditedForMonth,
                    'schools_invoiced' => $issued->count(),
                ],
                'overdue' => $overdue,
                'schools' => $names->map(fn (object $s): array => ['id' => (int) $s->id, 'name' => (string) $s->name, 'status' => (string) $s->status] + ($balances[(int) $s->id] ?? ['balance_cents' => 0]))->all(),
                'today' => $today,
                'grace_days' => max(0, (int) config('platform_billing.grace_days', 14)),
            ];
        });
    }

    /** @return array<int, array{balance_cents: int}> each school's balance: issued documents less payments that stand */
    private function balances(): array
    {
        $documents = DB::table('platform_invoices')->where('status', PlatformInvoice::ISSUED)->groupBy('school_id')->selectRaw('school_id, SUM(total_cents) AS cents')->pluck('cents', 'school_id');
        $paid = DB::table('platform_payments')->whereNull('reversed_at')->groupBy('school_id')->selectRaw('school_id, SUM(amount_cents) AS cents')->pluck('cents', 'school_id');
        $balances = [];
        foreach ($documents->keys()->merge($paid->keys())->unique() as $schoolId) {
            $balances[(int) $schoolId] = ['balance_cents' => (int) ($documents[$schoolId] ?? 0) - (int) ($paid[$schoolId] ?? 0)];
        }

        return $balances;
    }

    /** Issued invoices with something unpaid. */
    private function unpaid(Builder $query): Builder
    {
        return $query->where('i.kind', PlatformInvoice::INVOICE)->where('i.status', PlatformInvoice::ISSUED)
            ->whereRaw('i.total_cents > COALESCE((SELECT SUM(x.amount_cents) FROM platform_payment_allocations x WHERE x.invoice_id = i.id AND x.reversed_at IS NULL), 0)');
    }

    /**
     * Lock this school's issued invoices by id. An id that is not one of
     * them (another school's, a draft, a credit note) is refused.
     *
     * @param  list<int>  $ids
     * @return array<int, PlatformInvoice>
     */
    private function lockOpen(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $invoices = PlatformInvoice::query()->whereKey($ids)->where('kind', PlatformInvoice::INVOICE)->where('status', PlatformInvoice::ISSUED)
            ->orderBy('id')->lockForUpdate()->get()->keyBy('id')->all();
        if (count($invoices) !== count($ids)) {
            throw ValidationException::withMessages(['allocations' => __('Choose this school\'s issued invoices.')]);
        }

        return $invoices;
    }
}
