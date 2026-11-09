<?php

declare(strict_types=1);

namespace App\Services\Platform\Groups;

use App\Models\Platform\PlatformInvoice;
use App\Models\Platform\School;
use App\Models\Platform\SchoolGroup;
use App\Models\Platform\SchoolGroupInvoice;
use App\Models\Platform\SchoolGroupInvoiceLine;
use App\Models\Platform\SchoolGroupInvoiceSchool;
use App\Models\Platform\SchoolGroupMember;
use App\Models\Platform\SchoolGroupPayment;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Saas\MessagingCharges;
use App\Services\Platform\Saas\PlatformBilling;
use App\Services\Platform\Saas\PlatformStaffAccess;
use App\Services\Platform\Subscriptions\LearnerSubscriptions;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Group billing (Action Plan 5.7, SA.18): one invoice per school group and
 * month that rolls up each member school's own monthly draft
 * (platform_invoices, hq-billing.md), with the SA.8 rules kept: integer
 * cents, VAT rounded on each line and kept per invoice, drafts that HQ
 * recomputes, issued invoices that never change, references from the same
 * yearly sequence, EFT payments that are reversed and never deleted, and an
 * audit of every step.
 *
 * Billed alone or through the group, never both for the same month:
 *  - A school whose membership says "billed through the group" is not issued
 *    alone (PlatformBilling::issueProblem refuses it, and a month's issue run
 *    leaves it a draft).
 *  - The group draft claims the school's own draft for the month (one
 *    standing claim per school and month, a unique index). A claimed draft
 *    is never issued alone.
 *  - Issuing the group invoice turns each claimed school draft into
 *    "grouped": frozen like an issued invoice, never issued alone, and the
 *    school's month slot stays taken, so no second invoice is made for it.
 *  - A school whose month was already issued alone, or discarded, is left
 *    off the group invoice and listed as such.
 *
 * engineering/specs/platform/school-groups.md.
 */
class SchoolGroupBilling
{
    public function __construct(private readonly CurrentSchool $currentSchool, private readonly PlatformBilling $billing) {}

    public static function paymentRules(): array
    {
        return [
            'amount' => ['required', 'string', 'regex:/^\d{1,9}(\.\d{1,2})?$/D'],
            'received_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.PlatformBilling::today()],
            'bank_reference' => ['required', 'string', 'min:3', 'max:96'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public static function reverseRules(): array
    {
        return ['reason' => ['required', 'string', 'min:10', 'max:500']];
    }

    public function authorise(?User $actor, string $ability = 'billing.manage'): void
    {
        abort_unless($actor !== null && app(PlatformStaffAccess::class)->can($actor, $ability), 403);
    }

    // ---- the school side of "never both" ----

    /**
     * Why a school's own draft cannot be issued alone because of its group,
     * or null. Read with the school's id explicitly, so it works from HQ's
     * cross-school pages too; $locking reads the claim with a lock (issuing).
     */
    public function billedThroughGroup(PlatformInvoice $draft, bool $locking = false): ?string
    {
        if ($draft->kind !== PlatformInvoice::INVOICE || ! $draft->isDraft()) {
            return null;
        }
        $schoolId = (int) $draft->getAttribute('school_id');
        $claim = DB::table('school_group_invoice_schools as c')->join('school_group_invoices as g', 'g.id', '=', 'c.group_invoice_id')
            ->join('school_groups as sg', 'sg.id', '=', 'g.school_group_id')
            ->where('c.school_id', $schoolId)->where('c.platform_invoice_id', $draft->id)->whereNotNull('c.open_key');
        $claim = ($locking ? $claim->lockForUpdate() : $claim)->first(['sg.name']);
        if ($claim !== null) {
            return __('This month is on the draft invoice of the school group :group. Issue the group\'s invoice, or recompute the group\'s draft without this school.', ['group' => $claim->name]);
        }
        $member = DB::table('school_group_members as m')->join('school_groups as sg', 'sg.id', '=', 'm.school_group_id')
            ->where('m.school_id', $schoolId)->where('m.status', SchoolGroupMember::ACTIVE)->whereNotNull('m.open_key')
            ->where('m.billing', SchoolGroupMember::BILLED_THROUGH_GROUP)->first(['sg.name']);

        return $member === null ? null : __('This school is billed through the school group :group. Create the group\'s draft for the month, or change the school to be billed alone.', ['group' => $member->name]);
    }

    /**
     * A school left (or was removed from) its group: every standing claim on
     * a group draft is released and that draft's totals worked out again. A
     * claim on an issued group invoice stays: that month is billed.
     *
     * @return int how many group drafts changed
     */
    public function releaseSchool(int $schoolId): int
    {
        $drafts = $this->currentSchool->acrossSchools(fn () => DB::table('school_group_invoice_schools as c')->join('school_group_invoices as g', 'g.id', '=', 'c.group_invoice_id')
            ->where('c.school_id', $schoolId)->whereNotNull('c.open_key')->where('g.status', SchoolGroupInvoice::DRAFT)->pluck('g.id')->map(fn ($id) => (int) $id)->unique()->all());
        foreach ($drafts as $groupInvoiceId) {
            DB::transaction(function () use ($groupInvoiceId, $schoolId): void {
                $invoice = SchoolGroupInvoice::query()->whereKey($groupInvoiceId)->lockForUpdate()->first();
                if ($invoice === null || ! $invoice->isDraft()) {
                    return;
                }
                $this->currentSchool->run($schoolId, function () use ($invoice): void {
                    SchoolGroupInvoiceLine::query()->where('group_invoice_id', $invoice->id)->get()->each->delete();
                    SchoolGroupInvoiceSchool::query()->where('group_invoice_id', $invoice->id)->get()->each->delete();
                });
                $this->total($invoice, null);
            }, 3);
        }

        return count($drafts);
    }

    // ---- group drafts ----

    /** The month's draft for a group: every member school billed through the group, rolled up. */
    public function compose(User $actor, int $groupId, array $input): SchoolGroupInvoice
    {
        $this->authorise($actor);
        $period = $this->billing->period(Validator::make($input, PlatformBilling::runRules())->validate()['period']);

        return $this->currentSchool->acrossSchools(fn (): SchoolGroupInvoice => DB::transaction(function () use ($actor, $groupId, $period): SchoolGroupInvoice {
            $group = SchoolGroup::query()->whereKey($groupId)->lockForUpdate()->first();
            abort_if($group === null, 404);
            abort_unless($group->status === SchoolGroup::ACTIVE, 409);
            if (SchoolGroupInvoice::query()->where('school_group_id', $groupId)->where('slot', $period->format('Y-m'))->whereNotNull('open_key')->exists()) {
                throw ValidationException::withMessages(['period' => __('This group already has an invoice for this month. Recompute its draft instead.')]);
            }
            try {
                $invoice = SchoolGroupInvoice::query()->create([
                    'school_group_id' => $groupId, 'status' => SchoolGroupInvoice::DRAFT, 'open_key' => 1, 'slot' => $period->format('Y-m'),
                    'period_start' => $period->toDateString(), 'period_end' => $period->endOfMonth()->toDateString(),
                    'currency' => (string) config('platform_billing.currency', 'ZAR'), 'vat_rate_bp' => PlatformBilling::vatRateBp(),
                ]);
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['period' => __('This group already has an invoice for this month. Recompute its draft instead.')]);
            }
            $left = $this->fill($invoice, $period, $actor);
            Audit::record('platform_group_invoice_drafted', $invoice, [], ['group_invoice_id' => $invoice->id, 'school_group_id' => $groupId, 'period' => $invoice->slot,
                'schools' => $invoice->schools, 'total_cents' => $invoice->total_cents, 'left_off' => $left, 'changed_by' => $actor->id]);

            return $invoice;
        }, 3));
    }

    /** Count, price and roll up again, while it is a draft (also after a school left or changed how it is billed). */
    public function recompute(User $actor, int $groupId, int $invoiceId): SchoolGroupInvoice
    {
        $this->authorise($actor);

        return $this->currentSchool->acrossSchools(fn (): SchoolGroupInvoice => DB::transaction(function () use ($actor, $groupId, $invoiceId): SchoolGroupInvoice {
            $invoice = $this->lockDraft($groupId, $invoiceId);
            $before = $invoice->only(['schools', 'learners', 'subtotal_cents', 'vat_cents', 'total_cents', 'blocked_reason']);
            $left = $this->fill($invoice, CarbonImmutable::parse($invoice->period_start->toDateString(), PlatformBilling::TIMEZONE), $actor);
            Audit::record('platform_group_invoice_recomputed', $invoice, $before, ['group_invoice_id' => $invoice->id, 'left_off' => $left, 'changed_by' => $actor->id]
                + $invoice->only(array_keys($before)));

            return $invoice;
        }, 3));
    }

    /** Issue the group invoice: its reference and dates, and each school's month becomes "grouped". */
    public function issue(User $actor, int $groupId, int $invoiceId): string
    {
        $this->authorise($actor);

        return $this->currentSchool->acrossSchools(fn (): string => DB::transaction(function () use ($actor, $groupId, $invoiceId): string {
            $invoice = $this->lockDraft($groupId, $invoiceId);
            $problem = $this->issueProblem($invoice);
            if ($problem !== null) {
                throw ValidationException::withMessages(['invoice' => $problem]);
            }
            $claims = SchoolGroupInvoiceSchool::acrossSchools()->where('group_invoice_id', $invoice->id)->whereNotNull('open_key')->orderBy('school_id')->get();
            foreach ($claims as $claim) {
                $this->currentSchool->run((int) $claim->getAttribute('school_id'), function () use ($claim, $groupId): void {
                    DB::table('schools')->where('id', $claim->getAttribute('school_id'))->lockForUpdate()->first(['id']);
                    $own = PlatformInvoice::query()->whereKey($claim->platform_invoice_id)->lockForUpdate()->first();
                    $member = SchoolGroupMember::query()->where('school_group_id', $groupId)->where('status', SchoolGroupMember::ACTIVE)->whereNotNull('open_key')
                        ->where('billing', SchoolGroupMember::BILLED_THROUGH_GROUP)->lockForUpdate()->first();
                    $same = $own !== null && $own->isDraft() && $member !== null
                        && $own->computed_at?->equalTo($claim->invoice_computed_at) && (int) $own->total_cents === (int) $claim->total_cents;
                    if (! $same) {
                        throw ValidationException::withMessages(['invoice' => __('A school\'s own draft or its place in the group changed since this draft was computed. Recompute the group\'s draft, then issue it.')]);
                    }
                    $texts = app(MessagingCharges::class)->issueProblem((int) $own->getAttribute('school_id'), $own->period_start->toDateString(), $own->computed_at, true);
                    if ($texts !== null) {
                        throw ValidationException::withMessages(['invoice' => $texts]);
                    }
                    $own->forceFill(['status' => PlatformInvoice::GROUPED])->save();
                });
            }
            $today = CarbonImmutable::createFromFormat('!Y-m-d', PlatformBilling::today());
            $invoice->forceFill([
                'status' => SchoolGroupInvoice::ISSUED, 'reference' => $this->billing->nextReference($today->year), 'issued_on' => $today->toDateString(),
                'due_on' => $today->addDays(max(0, (int) config('platform_billing.payment_terms_days', 30)))->toDateString(), 'issued_at' => now(), 'issued_by' => $actor->id,
            ])->save();
            Audit::record('platform_group_invoice_issued', $invoice, ['status' => SchoolGroupInvoice::DRAFT], [
                'group_invoice_id' => $invoice->id, 'school_group_id' => $groupId, 'reference' => $invoice->reference, 'period' => $invoice->slot,
                'schools' => $claims->map(fn ($c) => (int) $c->getAttribute('school_id'))->all(), 'school_invoice_ids' => $claims->pluck('platform_invoice_id')->all(),
                'subtotal_cents' => $invoice->subtotal_cents, 'vat_cents' => $invoice->vat_cents, 'total_cents' => $invoice->total_cents, 'vat_rate_bp' => $invoice->vat_rate_bp,
                'issued_on' => $invoice->issued_on?->toDateString(), 'due_on' => $invoice->due_on?->toDateString(), 'changed_by' => $actor->id,
            ]);
            // A grouped school's month appears in each school's audit history too.
            foreach ($claims as $claim) {
                Audit::record('platform_invoice_grouped', null, [], ['invoice_id' => $claim->platform_invoice_id, 'group_invoice_id' => $invoice->id, 'reference' => $invoice->reference, 'changed_by' => $actor->id], (int) $claim->getAttribute('school_id'));
            }

            return (string) $invoice->reference;
        }, 3));
    }

    /** Do not bill the group for the month: its schools' own drafts are free again (billed alone if their membership allows it). */
    public function discard(User $actor, int $groupId, int $invoiceId): void
    {
        $this->authorise($actor);
        $this->currentSchool->acrossSchools(fn () => DB::transaction(function () use ($actor, $groupId, $invoiceId): void {
            $invoice = $this->lockDraft($groupId, $invoiceId);
            foreach (SchoolGroupInvoiceSchool::acrossSchools()->where('group_invoice_id', $invoice->id)->whereNotNull('open_key')->get() as $claim) {
                $claim->forceFill(['open_key' => null])->save();
            }
            $invoice->forceFill(['status' => SchoolGroupInvoice::DISCARDED, 'open_key' => null, 'discarded_at' => now(), 'discarded_by' => $actor->id])->save();
            Audit::record('platform_group_invoice_discarded', $invoice, ['status' => SchoolGroupInvoice::DRAFT], ['group_invoice_id' => $invoice->id, 'period' => $invoice->slot, 'changed_by' => $actor->id]);
        }, 3));
    }

    // ---- payments ----

    public function recordPayment(User $actor, int $groupId, int $invoiceId, array $input): SchoolGroupPayment
    {
        $this->authorise($actor);
        $data = Validator::make($input, self::paymentRules())->validate();
        $amount = Money::fromDecimalString((string) $data['amount'], 'ZAR')->cents;
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => __('A payment must be more than R 0.00.')]);
        }

        return $this->currentSchool->acrossSchools(fn (): SchoolGroupPayment => DB::transaction(function () use ($actor, $groupId, $invoiceId, $data, $amount): SchoolGroupPayment {
            $invoice = $this->lock($groupId, $invoiceId);
            abort_unless($invoice->isIssued(), 409, __('Only an issued group invoice can be paid.'));
            $outstanding = $this->outstanding($invoice, true);
            if ($amount > $outstanding) {
                throw ValidationException::withMessages(['amount' => __('Only :amount is unpaid on this invoice.', ['amount' => PlatformBilling::rands($outstanding)])]);
            }
            $reference = trim((string) $data['bank_reference']);
            if (SchoolGroupPayment::query()->where('school_group_id', $groupId)->where('bank_reference', $reference)->whereNull('reversed_at')->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['bank_reference' => __('This bank reference is already on a payment of this group.')]);
            }
            // The same bank account takes learner subscription EFTs (3.20): a bank line registered there is not this group's payment.
            if (DB::table('platform_bank_receipts')->where('method', 'eft')->where('source_reference', LearnerSubscriptions::normaliseReference($reference))->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['bank_reference' => __('This bank line is already recorded as a learning plan payment.')]);
            }
            $payment = SchoolGroupPayment::query()->create(['school_group_id' => $groupId, 'group_invoice_id' => $invoice->id, 'received_on' => $data['received_on'],
                'amount_cents' => $amount, 'currency' => $invoice->currency, 'bank_reference' => $reference, 'note' => $data['note'] ?? null, 'recorded_by' => $actor->id]);
            Audit::record('platform_group_payment_recorded', $payment, [], ['payment_id' => $payment->id, 'group_invoice_id' => $invoice->id, 'reference' => $invoice->reference,
                'amount_cents' => $amount, 'received_on' => $data['received_on'], 'bank_reference' => $reference, 'changed_by' => $actor->id]);

            return $payment;
        }, 3));
    }

    public function reversePayment(User $actor, int $groupId, int $invoiceId, int $paymentId, array $input): SchoolGroupPayment
    {
        $this->authorise($actor);
        $data = Validator::make($input, self::reverseRules())->validate();

        return $this->currentSchool->acrossSchools(fn (): SchoolGroupPayment => DB::transaction(function () use ($actor, $groupId, $invoiceId, $paymentId, $data): SchoolGroupPayment {
            $invoice = $this->lock($groupId, $invoiceId);
            $payment = SchoolGroupPayment::query()->where('group_invoice_id', $invoice->id)->whereKey($paymentId)->lockForUpdate()->first();
            abort_if($payment === null, 404);
            abort_if($payment->reversed_at !== null, 409, __('This payment was already reversed.'));
            $payment->forceFill(['reversed_at' => now(), 'reversed_by' => $actor->id, 'reversal_reason' => $data['reason']])->save();
            Audit::record('platform_group_payment_reversed', $payment, [], ['payment_id' => $payment->id, 'group_invoice_id' => $invoice->id, 'amount_cents' => $payment->amount_cents,
                'reason' => $data['reason'], 'changed_by' => $actor->id]);

            return $payment;
        }, 3));
    }

    public function outstanding(SchoolGroupInvoice $invoice, bool $locking = false): int
    {
        if (! $invoice->isIssued()) {
            return 0;
        }
        $paid = SchoolGroupPayment::query()->where('group_invoice_id', $invoice->id)->whereNull('reversed_at');

        return (int) $invoice->total_cents - (int) ($locking ? $paid->lockForUpdate()->get(['amount_cents'])->sum('amount_cents') : $paid->sum('amount_cents'));
    }

    // ---- reading ----

    /** A group's invoices, newest month first, and the schools it bills through the group. */
    public function overview(User $actor, int $groupId): array
    {
        $this->authorise($actor, 'billing.view');

        return $this->currentSchool->acrossSchools(function () use ($groupId): array {
            $group = SchoolGroup::query()->find($groupId);
            abort_if($group === null, 404);
            $names = DB::table('schools')->pluck('name', 'id');

            return [
                'group' => ['id' => (int) $group->id, 'name' => (string) $group->name, 'status' => (string) $group->status],
                'invoices' => SchoolGroupInvoice::query()->where('school_group_id', $groupId)->orderByDesc('slot')->orderByDesc('id')->get()
                    ->map(fn (SchoolGroupInvoice $i): array => $this->present($i) + ['outstanding_cents' => $this->outstanding($i), 'problem' => $i->isDraft() ? $this->issueProblem($i) : null])->all(),
                'billed_through_group' => SchoolGroupMember::acrossSchools()->where('school_group_id', $groupId)->where('status', SchoolGroupMember::ACTIVE)
                    ->where('billing', SchoolGroupMember::BILLED_THROUGH_GROUP)->get()->map(fn (SchoolGroupMember $m): string => (string) ($names[$m->getAttribute('school_id')] ?? ''))->sort()->values()->all(),
                'month' => substr(PlatformBilling::today(), 0, 7),
            ];
        });
    }

    /** One group invoice with its lines by school, the schools left off, and its payments. */
    public function show(User $actor, int $groupId, int $invoiceId): array
    {
        $this->authorise($actor, 'billing.view');

        return $this->currentSchool->acrossSchools(function () use ($groupId, $invoiceId): array {
            $invoice = SchoolGroupInvoice::query()->where('school_group_id', $groupId)->whereKey($invoiceId)->first();
            abort_if($invoice === null, 404);
            $group = SchoolGroup::query()->findOrFail($groupId);
            $names = DB::table('schools')->pluck('name', 'id');
            $lines = $invoice->lines()->get()->groupBy(fn (SchoolGroupInvoiceLine $l) => (int) $l->getAttribute('school_id'));

            return [
                'group' => ['id' => (int) $group->id, 'name' => (string) $group->name],
                'invoice' => $this->present($invoice) + ['outstanding_cents' => $this->outstanding($invoice)],
                'schools' => $invoice->schoolRows()->get()->filter(fn (SchoolGroupInvoiceSchool $c) => $c->open_key !== null || $invoice->status === SchoolGroupInvoice::DISCARDED)
                    ->map(fn (SchoolGroupInvoiceSchool $c): array => [
                        'school_id' => (int) $c->getAttribute('school_id'), 'school' => (string) ($names[$c->getAttribute('school_id')] ?? ''), 'learners' => (int) $c->learners,
                        'subtotal_cents' => (int) $c->subtotal_cents, 'vat_cents' => (int) $c->vat_cents, 'total_cents' => (int) $c->total_cents,
                        'lines' => ($lines->get((int) $c->getAttribute('school_id')) ?? collect())->map(fn (SchoolGroupInvoiceLine $l): array => $l->only(['kind', 'description', 'learners', 'unit_cents', 'amount_cents', 'vat_cents']))->values()->all(),
                    ])->values()->all(),
                'payments' => SchoolGroupPayment::query()->where('group_invoice_id', $invoice->id)->orderBy('received_on')->orderBy('id')->get()->map(fn (SchoolGroupPayment $p): array => [
                    'id' => (int) $p->id, 'received_on' => $p->received_on->toDateString(), 'amount_cents' => (int) $p->amount_cents, 'bank_reference' => (string) $p->bank_reference,
                    'note' => $p->note, 'reversed_at' => $p->reversed_at?->timezone(PlatformBilling::TIMEZONE)->format('Y-m-d H:i'), 'reversal_reason' => $p->reversal_reason,
                ])->all(),
                'problem' => $invoice->isDraft() ? $this->issueProblem($invoice) : null,
            ];
        });
    }

    public function present(SchoolGroupInvoice $i): array
    {
        return [
            'id' => (int) $i->id, 'status' => (string) $i->status, 'reference' => $i->reference, 'slot' => (string) $i->slot,
            'period_start' => $i->period_start->toDateString(), 'period_end' => $i->period_end->toDateString(), 'vat_rate_bp' => (int) $i->vat_rate_bp,
            'schools' => (int) $i->schools, 'learners' => (int) $i->learners, 'subtotal_cents' => (int) $i->subtotal_cents, 'vat_cents' => (int) $i->vat_cents, 'total_cents' => (int) $i->total_cents,
            'blocked_reason' => $i->blocked_reason, 'issued_on' => $i->issued_on?->toDateString(), 'due_on' => $i->due_on?->toDateString(),
        ];
    }

    public function issueProblem(SchoolGroupInvoice $draft): ?string
    {
        if ($draft->blocked_reason !== null) {
            return (string) $draft->blocked_reason;
        }
        if ((int) $draft->total_cents <= 0) {
            return __('There is nothing to bill. Recompute it once a school is billed through the group, or discard it.');
        }
        foreach (SchoolGroupInvoiceSchool::acrossSchools()->where('group_invoice_id', $draft->id)->whereNotNull('open_key')->get() as $claim) {
            $texts = app(MessagingCharges::class)->issueProblem((int) $claim->getAttribute('school_id'), $draft->period_start->toDateString(), $claim->invoice_computed_at);
            if ($texts !== null) {
                return $texts;
            }
        }

        return null;
    }

    // ---- internals ----

    /**
     * Rolls up the month: every school of the group billed through it now,
     * active on the platform, whose own month is still a draft and on no
     * other group's invoice. Its own draft is counted and priced again, then
     * copied line by line. Returns the schools left off and why.
     *
     * @return list<array{school_id: int, why: string}>
     */
    private function fill(SchoolGroupInvoice $invoice, CarbonImmutable $period, User $actor): array
    {
        // Start again from nothing (draft only, so the models and the database allow it).
        foreach (SchoolGroupInvoiceLine::acrossSchools()->where('group_invoice_id', $invoice->id)->get() as $line) {
            $line->delete();
        }
        foreach (SchoolGroupInvoiceSchool::acrossSchools()->where('group_invoice_id', $invoice->id)->get() as $claim) {
            $claim->delete();
        }
        $slot = $invoice->slot;
        $members = DB::table('school_group_members as m')->join('schools as s', 's.id', '=', 'm.school_id')
            ->where('m.school_group_id', $invoice->school_group_id)->where('m.status', SchoolGroupMember::ACTIVE)->whereNotNull('m.open_key')
            ->where('m.billing', SchoolGroupMember::BILLED_THROUGH_GROUP)->where('s.status', School::ACTIVE)->orderBy('s.id')->get(['s.id', 's.name']);
        $left = [];
        $blocked = [];
        $position = 0;
        foreach ($members as $school) {
            $schoolId = (int) $school->id;
            $this->billing->draftSchool($schoolId, $period, $actor);
            $outcome = $this->currentSchool->run($schoolId, function () use ($invoice, $slot, $actor, $schoolId, &$position): string {
                DB::table('schools')->where('id', $schoolId)->lockForUpdate()->first(['id']);
                $own = PlatformInvoice::query()->where('slot', $slot)->lockForUpdate()->first();
                if ($own === null || $own->status === PlatformInvoice::ISSUED) {
                    return 'issued alone';
                }
                if ($own->status !== PlatformInvoice::DRAFT) {
                    return $own->status === PlatformInvoice::DISCARDED ? 'not billed this month' : 'billed through a group';
                }
                if (SchoolGroupInvoiceSchool::query()->where('slot', $slot)->whereNotNull('open_key')->lockForUpdate()->exists()) {
                    return 'on another group\'s draft';
                }
                $this->billing->recompute($schoolId, (int) $own->id, $actor);
                $own = PlatformInvoice::query()->whereKey($own->id)->lockForUpdate()->firstOrFail();
                SchoolGroupInvoiceSchool::query()->create([
                    'group_invoice_id' => $invoice->id, 'platform_invoice_id' => $own->id, 'slot' => $slot, 'open_key' => 1, 'learners' => (int) $own->learners,
                    'subtotal_cents' => (int) $own->subtotal_cents, 'vat_cents' => (int) $own->vat_cents, 'total_cents' => (int) $own->total_cents, 'invoice_computed_at' => $own->computed_at,
                ]);
                foreach ($own->lines()->get() as $line) {
                    SchoolGroupInvoiceLine::query()->create(['group_invoice_id' => $invoice->id, 'source_invoice_id' => $own->id, 'position' => ++$position] + $line->only(['kind', 'product_module', 'description', 'learners', 'unit_cents', 'amount_cents', 'vat_cents']));
                }

                return $own->blocked_reason === null ? 'included' : 'blocked';
            });
            if ($outcome === 'blocked') {
                $blocked[] = (string) $school->name;
            } elseif ($outcome !== 'included') {
                $left[] = ['school_id' => $schoolId, 'why' => $outcome];
            }
        }
        $this->total($invoice, $actor, $blocked === [] ? null : __('No price for :schools this month. Add a price or contract, then recompute the group\'s draft.', ['schools' => implode(', ', $blocked)]));

        return $left;
    }

    /** The draft's totals from the lines and schools it holds now. */
    private function total(SchoolGroupInvoice $invoice, ?User $actor, ?string $blocked = null): void
    {
        $lines = SchoolGroupInvoiceLine::acrossSchools()->where('group_invoice_id', $invoice->id)->get(['amount_cents', 'vat_cents']);
        $claims = SchoolGroupInvoiceSchool::acrossSchools()->where('group_invoice_id', $invoice->id)->whereNotNull('open_key')->get(['learners']);
        $subtotal = (int) $lines->sum('amount_cents');
        $vat = (int) $lines->sum('vat_cents');
        $invoice->forceFill([
            'schools' => $claims->count(), 'learners' => (int) $claims->sum('learners'), 'subtotal_cents' => $subtotal, 'vat_cents' => $vat, 'total_cents' => $subtotal + $vat,
            'blocked_reason' => $actor === null ? $invoice->blocked_reason : $blocked, 'vat_rate_bp' => $actor === null ? $invoice->vat_rate_bp : PlatformBilling::vatRateBp(),
            'computed_at' => now(), 'computed_by' => $actor?->id ?? $invoice->computed_by,
        ])->save();
    }

    private function lock(int $groupId, int $invoiceId): SchoolGroupInvoice
    {
        $invoice = SchoolGroupInvoice::query()->where('school_group_id', $groupId)->whereKey($invoiceId)->lockForUpdate()->first();
        abort_if($invoice === null, 404);

        return $invoice;
    }

    private function lockDraft(int $groupId, int $invoiceId): SchoolGroupInvoice
    {
        $invoice = $this->lock($groupId, $invoiceId);
        if (! $invoice->isDraft()) {
            throw ValidationException::withMessages(['invoice' => $invoice->isIssued() ? __('An issued group invoice never changes.') : __('This group draft was discarded.')]);
        }

        return $invoice;
    }
}
