<?php

declare(strict_types=1);

namespace App\Services\Platform\Saas;

use App\Models\Platform\PlatformInvoice;
use App\Models\Platform\PlatformInvoiceLine;
use App\Models\Platform\PlatformPaymentAllocation;
use App\Models\Platform\School;
use App\Models\Platform\SchoolContract;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Navigate Ed billing its schools (Action Plan 1.17 and SA.8, Build 1): the
 * monthly invoice per school, from a draft HQ reviews to an issued invoice
 * that never changes, and credit notes that correct one.
 *
 * The price per learner is the school's current contract price
 * (school_contracts) when it has one on the first day of the month, because
 * that is what the school signed; it then covers everything, so no list
 * add-on or discount is added. Without a contract it is the list price of the
 * school's package on that day (PlatformPackages::quote): the package price,
 * the list discount as its own line, and an add-on line for each product
 * module of the school's package version that has an add-on price and that
 * the school has switched on.
 *
 * Learners are counted on the day the draft is computed: active Student
 * memberships of that school, not learners who have left. VAT is a rate from
 * config/platform_billing.php kept on each invoice; each line's VAT is rounded
 * half up on the line, then the invoice adds the lines. Everything is integer
 * cents.
 *
 * These tables are school-owned but only HQ reads them; every write runs
 * inside the school (CurrentSchool::run), so another school's id is not
 * found. Payments and statements: PlatformBillingAccounts.
 * engineering/specs/platform/hq-billing.md.
 */
class PlatformBilling
{
    public const TIMEZONE = 'Africa/Johannesburg';

    /** The earliest month a run may bill, counted back from this month. */
    public const MONTHS_BACK = 12;

    public function __construct(
        private readonly CurrentSchool $currentSchool,
        private readonly PlatformPackages $packages,
    ) {}

    // ---- rules (the FormRequest and the services use the same ones) ----

    public static function runRules(): array
    {
        return ['period' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/D']];
    }

    public static function creditRules(): array
    {
        return [
            'full' => ['nullable', 'boolean'],
            // Rands excluding VAT, as typed: never a float.
            'amount' => ['nullable', 'required_unless:full,1', 'string', 'regex:/^\d{1,8}(\.\d{1,2})?$/D'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    public static function today(): string
    {
        return CarbonImmutable::now(self::TIMEZONE)->toDateString();
    }

    /** The VAT rate in basis points (15 % is 1500) from config, read without floats. */
    public static function vatRateBp(): int
    {
        $percent = (string) config('platform_billing.vat_percent', '15');
        if (preg_match('/^(\d{1,2})(?:\.(\d{1,2}))?$/D', $percent, $match) !== 1) {
            throw new RuntimeException('platform_billing.vat_percent must be a percentage such as 15 or 15.5.');
        }

        return (int) $match[1] * 100 + (int) str_pad($match[2] ?? '0', 2, '0');
    }

    /** A line's VAT: amount × rate, rounded half up (away from zero) on the line. */
    public static function lineVat(int $amountCents, int $rateBp): int
    {
        return Money::ofFraction($amountCents * $rateBp, 10000)->cents;
    }

    public static function rands(int $cents): string
    {
        return Money::ofCents($cents, 'ZAR')->format('R');
    }

    /** "12.5" as a percentage label for a rate in basis points. */
    public static function percentLabel(int $bp): string
    {
        $fraction = rtrim(str_pad((string) ($bp % 100), 2, '0', STR_PAD_LEFT), '0');

        return intdiv($bp, 100).($fraction === '' ? '' : '.'.$fraction).' %';
    }

    /** The month a request names, or this month. */
    public static function month(?string $period): string
    {
        return is_string($period) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $period) === 1 ? $period : substr(self::today(), 0, 7);
    }

    // ---- learners ----

    /**
     * How many learners a school has on a date: active Student memberships
     * that day, on enabled accounts, less learners who have left (their
     * latest join or leave before that day is a leave, as
     * LearnerAcademicAccess::departed() decides). Counts only, never a name.
     */
    public function learnerCount(int $schoolId, string $on): int
    {
        return (int) DB::table('school_memberships as sm')->join('users as u', 'u.id', '=', 'sm.user_id')
            ->where('sm.school_id', $schoolId)->where('sm.role', SchoolRoles::STUDENT)->where('sm.status', SchoolMembership::ACTIVE)
            ->where(fn ($q) => $q->whereNull('sm.starts_on')->orWhere('sm.starts_on', '<=', $on))
            ->where(fn ($q) => $q->whereNull('sm.ends_on')->orWhere('sm.ends_on', '>=', $on))
            ->where('u.status', 1)->whereNull('u.deleted_at')
            ->whereNotExists(fn ($left) => $left->selectRaw('1')->from('students as s')
                ->join('academic_transitions as t', 't.learner_id', '=', 's.id')
                ->whereColumn('s.user_id', 'sm.user_id')->where('s.school_id', $schoolId)->where('t.school_id', $schoolId)
                ->where('t.kind', 'leave')->where('t.effective_on', '<', $on)
                ->whereNotExists(fn ($later) => $later->selectRaw('1')->from('academic_transitions as r')
                    ->whereColumn('r.learner_id', 't.learner_id')->where('r.school_id', $schoolId)->whereIn('r.kind', ['join', 'leave'])
                    ->where('r.effective_on', '<=', $on)
                    ->where(fn ($q) => $q->whereColumn('r.effective_on', '>', 't.effective_on')
                        ->orWhere(fn ($same) => $same->whereColumn('r.effective_on', 't.effective_on')->whereColumn('r.id', '>', 't.id')))))
            ->distinct()->count('sm.user_id');
    }

    // ---- runs and drafts ----

    /**
     * A draft for every active school that has no invoice for the month yet.
     * Running it again creates nothing new (the month's slot is unique per
     * school).
     *
     * @return array{created: int, existing: int, skipped: int}
     */
    public function run(array $input, User $actor): array
    {
        $this->authorise($actor);
        $period = $this->period(Validator::make($input, self::runRules())->validate()['period']);
        $slot = $period->format('Y-m');
        $result = ['created' => 0, 'existing' => 0, 'skipped' => 0];
        $schools = $this->currentSchool->acrossSchools(fn () => DB::table('schools')->orderBy('id')->get(['id', 'status']));
        foreach ($schools as $school) {
            if ($school->status !== School::ACTIVE) {
                $result['skipped']++;

                continue;
            }
            $created = $this->currentSchool->run((int) $school->id, function () use ($period, $slot, $actor): bool {
                if (PlatformInvoice::query()->where('slot', $slot)->exists()) {
                    return false;
                }
                try {
                    return DB::transaction(function () use ($period, $slot, $actor): bool {
                        $draft = PlatformInvoice::query()->create([
                            'kind' => PlatformInvoice::INVOICE, 'status' => PlatformInvoice::DRAFT, 'slot' => $slot,
                            'period_start' => $period->toDateString(), 'period_end' => $period->endOfMonth()->toDateString(),
                            'currency' => (string) config('platform_billing.currency', 'ZAR'), 'vat_rate_bp' => self::vatRateBp(),
                        ]);
                        $this->compute($draft, $actor);

                        return true;
                    }, 3);
                } catch (UniqueConstraintViolationException) {
                    return false;
                }
            });
            $result[$created ? 'created' : 'existing']++;
        }
        Audit::record('platform_billing_run', null, [], ['period' => $slot] + $result + ['changed_by' => $actor->id]);

        return $result;
    }

    /** Count again and price again, while it is a draft. */
    public function recompute(int $schoolId, int $invoiceId, User $actor): void
    {
        $this->authorise($actor);
        $this->inSchool($schoolId, function () use ($invoiceId, $actor): void {
            $draft = $this->lockDraft($invoiceId);
            $before = $draft->only(['learners', 'subtotal_cents', 'vat_cents', 'total_cents', 'price_source', 'blocked_reason']);
            $this->compute($draft, $actor);
            Audit::record('platform_invoice_recomputed', $draft, ['invoice_id' => $draft->id] + $before,
                ['invoice_id' => $draft->id, 'changed_by' => $actor->id] + $draft->only(array_keys($before)), $draft->school_id);
        });
    }

    /** Issue one draft: it gets its reference, issue date and due date, and never changes again. */
    public function issue(int $schoolId, int $invoiceId, User $actor): string
    {
        $this->authorise($actor);

        return $this->inSchool($schoolId, fn (): string => $this->issueDraft($this->lockDraft($invoiceId), $actor));
    }

    /**
     * Issue every draft of a month that can be issued. A draft with no price
     * or nothing to bill stays a draft.
     *
     * @return array{issued: int, left: int}
     */
    public function issueMonth(array $input, User $actor): array
    {
        $this->authorise($actor);
        $slot = $this->period(Validator::make($input, self::runRules())->validate()['period'])->format('Y-m');
        $drafts = $this->currentSchool->acrossSchools(fn () => PlatformInvoice::acrossSchools()->where('slot', $slot)->where('status', PlatformInvoice::DRAFT)->orderBy('school_id')->get(['id', 'school_id']));
        $result = ['issued' => 0, 'left' => 0];
        foreach ($drafts as $draft) {
            $issued = $this->inSchool((int) $draft->school_id, function () use ($draft, $actor): bool {
                $locked = $this->lockDraft((int) $draft->id);
                if ($this->issueProblem($locked) !== null) {
                    return false;
                }
                $this->issueDraft($locked, $actor);

                return true;
            });
            $result[$issued ? 'issued' : 'left']++;
        }

        return $result;
    }

    /** Decide not to bill a school for the month: the draft is kept, marked discarded, and a run does not make it again. */
    public function discard(int $schoolId, int $invoiceId, User $actor): void
    {
        $this->authorise($actor);
        $this->inSchool($schoolId, function () use ($invoiceId, $actor): void {
            $draft = $this->lockDraft($invoiceId);
            $draft->forceFill(['status' => PlatformInvoice::DISCARDED, 'discarded_at' => now(), 'discarded_by' => $actor->id])->save();
            Audit::record('platform_invoice_discarded', $draft, ['invoice_id' => $draft->id, 'status' => PlatformInvoice::DRAFT],
                ['invoice_id' => $draft->id, 'status' => PlatformInvoice::DISCARDED, 'period' => $draft->slot, 'changed_by' => $actor->id], $draft->school_id);
        });
    }

    /**
     * A credit note against an issued invoice, issued at once with a reason:
     * the whole invoice (every line negated) when nothing was credited yet, or
     * an amount excluding VAT, VAT at the invoice's rate. Never more than is
     * left to credit. Its credit settles what is unpaid on the invoice; any
     * rest is credit on the school's account.
     */
    public function creditNote(int $schoolId, int $invoiceId, array $input, User $actor): int
    {
        $this->authorise($actor);
        $data = Validator::make($input, self::creditRules())->validate();
        $full = filter_var($data['full'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $amount = $full ? null : Money::fromDecimalString((string) $data['amount'], 'ZAR')->cents;
        if ($amount === 0) {
            throw ValidationException::withMessages(['amount' => __('A credit note must be for more than R 0.00.')]);
        }

        return $this->inSchool($schoolId, function () use ($invoiceId, $data, $full, $amount, $actor): int {
            $original = PlatformInvoice::query()->whereKey($invoiceId)->lockForUpdate()->first();
            abort_if($original === null, 404);
            if ($original->kind !== PlatformInvoice::INVOICE || ! $original->isIssued()) {
                throw ValidationException::withMessages(['invoice' => __('Only an issued invoice can be credited.')]);
            }
            $credited = (int) -PlatformInvoice::query()->where('credits_invoice_id', $original->id)->where('status', PlatformInvoice::ISSUED)->sum('total_cents');
            $creditable = (int) $original->total_cents - $credited;
            if ($full && $credited > 0) {
                throw ValidationException::withMessages(['full' => __('Part of this invoice was credited already. Credit an amount instead.')]);
            }
            $lines = $full
                ? $original->lines->map(fn (PlatformInvoiceLine $line): array => [
                    'kind' => $line->kind, 'product_module' => $line->product_module, 'description' => $line->description, 'learners' => $line->learners,
                    'unit_cents' => $line->unit_cents === null ? null : -$line->unit_cents, 'amount_cents' => -$line->amount_cents, 'vat_cents' => -$line->vat_cents,
                ])->all()
                : [['kind' => 'credit', 'product_module' => null, 'description' => __('Credit: :reason', ['reason' => mb_substr($data['reason'], 0, 150)]),
                    'learners' => null, 'unit_cents' => null, 'amount_cents' => -(int) $amount, 'vat_cents' => self::lineVat(-(int) $amount, (int) $original->vat_rate_bp)]];
            $total = array_sum(array_column($lines, 'amount_cents')) + array_sum(array_column($lines, 'vat_cents'));
            if (-$total > $creditable) {
                throw ValidationException::withMessages(['amount' => __('Only :amount (including VAT) is left to credit on this invoice.', ['amount' => self::rands($creditable)])]);
            }
            $today = self::today();
            $note = PlatformInvoice::query()->create([
                'kind' => PlatformInvoice::CREDIT_NOTE, 'status' => PlatformInvoice::DRAFT, 'credits_invoice_id' => $original->id,
                'period_start' => $original->period_start->toDateString(), 'period_end' => $original->period_end->toDateString(),
                'package_key' => $original->package_key, 'package_version' => $original->package_version, 'currency' => $original->currency,
                'vat_rate_bp' => $original->vat_rate_bp, 'reason' => $data['reason'], 'computed_at' => now(), 'computed_by' => $actor->id,
            ]);
            $this->storeLines($note, $lines);
            $note->forceFill([
                'subtotal_cents' => array_sum(array_column($lines, 'amount_cents')), 'vat_cents' => array_sum(array_column($lines, 'vat_cents')), 'total_cents' => $total,
                'status' => PlatformInvoice::ISSUED, 'reference' => $this->nextReference((int) substr($today, 0, 4)),
                'issued_on' => $today, 'issued_at' => now(), 'issued_by' => $actor->id,
            ])->save();
            // The credit settles what is still unpaid on the original; the rest is account credit.
            $settles = min(-$total, app(PlatformBillingAccounts::class)->outstanding($original));
            if ($settles > 0) {
                PlatformPaymentAllocation::query()->create(['invoice_id' => $original->id, 'credit_note_id' => $note->id, 'amount_cents' => $settles, 'created_by' => $actor->id]);
            }
            Audit::record('platform_credit_note_issued', $note, [], [
                'credit_note_id' => $note->id, 'reference' => $note->reference, 'credits_invoice_id' => $original->id, 'credits_reference' => $original->reference,
                'full' => $full, 'subtotal_cents' => $note->subtotal_cents, 'vat_cents' => $note->vat_cents, 'total_cents' => $total,
                'settles_cents' => max(0, $settles), 'reason' => $data['reason'], 'changed_by' => $actor->id,
            ], $note->school_id);

            return (int) $note->id;
        });
    }

    // ---- reading ----

    /** One invoice or credit note of a school, for its page and the printable view. */
    public function show(int $schoolId, int $invoiceId): array
    {
        $school = $this->school($schoolId);

        return $this->currentSchool->run($schoolId, function () use ($school, $invoiceId): array {
            $invoice = PlatformInvoice::query()->find($invoiceId);
            abort_if($invoice === null, 404);
            $accounts = app(PlatformBillingAccounts::class);
            $account = DB::table('school_accounts')->where('school_id', $school->id)->first(['address']);

            return [
                'school' => ['id' => (int) $school->id, 'name' => (string) $school->name, 'status' => (string) $school->status, 'address' => $account?->address],
                'invoice' => $this->present($invoice) + [
                    'outstanding_cents' => $invoice->kind === PlatformInvoice::INVOICE && $invoice->isIssued() ? $accounts->outstanding($invoice) : null,
                    'credits_reference' => $invoice->credits_invoice_id === null ? null : PlatformInvoice::query()->whereKey($invoice->credits_invoice_id)->value('reference'),
                    'credit_notes' => PlatformInvoice::query()->where('credits_invoice_id', $invoice->id)->where('status', PlatformInvoice::ISSUED)->orderBy('id')
                        ->get(['id', 'reference', 'issued_on', 'total_cents'])->map(fn (PlatformInvoice $note): array => [
                            'id' => (int) $note->id, 'reference' => (string) $note->reference, 'issued_on' => $note->issued_on?->toDateString(), 'total_cents' => (int) $note->total_cents,
                        ])->all(),
                    'creditable_cents' => $invoice->kind === PlatformInvoice::INVOICE && $invoice->isIssued()
                        ? (int) $invoice->total_cents + (int) PlatformInvoice::query()->where('credits_invoice_id', $invoice->id)->where('status', PlatformInvoice::ISSUED)->sum('total_cents') : 0,
                ],
                'lines' => $invoice->lines->map(fn (PlatformInvoiceLine $line): array => $line->only(['kind', 'product_module', 'description', 'learners', 'unit_cents', 'amount_cents', 'vat_cents']))->all(),
                'problem' => $invoice->isDraft() ? $this->issueProblem($invoice) : null,
            ];
        });
    }

    /** The fields every list shows. */
    public function present(PlatformInvoice $invoice): array
    {
        return [
            'id' => (int) $invoice->id, 'school_id' => (int) $invoice->getAttribute('school_id'), 'kind' => (string) $invoice->kind, 'status' => (string) $invoice->status,
            'reference' => $invoice->reference, 'slot' => $invoice->slot,
            'period_start' => $invoice->period_start->toDateString(), 'period_end' => $invoice->period_end->toDateString(),
            'package_key' => $invoice->package_key, 'package_version' => $invoice->package_version, 'price_source' => $invoice->price_source,
            'contracted_learners' => $invoice->contracted_learners, 'learners' => (int) $invoice->learners,
            'learners_counted_on' => $invoice->learners_counted_on?->toDateString(), 'vat_rate_bp' => (int) $invoice->vat_rate_bp,
            'subtotal_cents' => (int) $invoice->subtotal_cents, 'vat_cents' => (int) $invoice->vat_cents, 'total_cents' => (int) $invoice->total_cents,
            'blocked_reason' => $invoice->blocked_reason, 'reason' => $invoice->reason,
            'issued_on' => $invoice->issued_on?->toDateString(), 'due_on' => $invoice->due_on?->toDateString(),
            'reminded_at' => $invoice->reminded_at?->toDateTimeString(), 'reminders' => (int) $invoice->reminders,
        ];
    }

    /** Why a draft cannot be issued, or null when it can. */
    public function issueProblem(PlatformInvoice $draft): ?string
    {
        if ($draft->blocked_reason !== null) {
            return (string) $draft->blocked_reason;
        }
        if ((int) $draft->total_cents <= 0) {
            return __('There is nothing to bill. Recompute it once the school has learners, or discard it.');
        }

        return null;
    }

    // ---- internals ----

    /**
     * Count and price a draft. Lines are replaced; the invoice keeps the
     * package, version, price source and VAT rate it used.
     */
    private function compute(PlatformInvoice $draft, User $actor): void
    {
        $school = School::query()->findOrFail($draft->getAttribute('school_id'));
        $countedOn = self::today();
        $learners = $this->learnerCount((int) $school->id, $countedOn);
        $periodStart = $draft->period_start->toDateString();
        $key = (string) ($school->package_key ?? PlatformPackages::LEGACY);
        $version = $school->package_version === null ? 1 : (int) $school->package_version;
        $rate = self::vatRateBp();
        $contract = SchoolContract::query()->where('starts_on', '<=', $periodStart)
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhere('ends_on', '>=', $periodStart))
            ->orderByDesc('starts_on')->orderByDesc('id')->first();
        $lines = [];
        $blocked = null;
        if ($contract !== null) {
            $lines[] = ['kind' => 'base', 'product_module' => null, 'learners' => $learners, 'unit_cents' => (int) $contract->price_per_learner_cents,
                'description' => __('Learners × contract price per learner (contract from :date)', ['date' => $contract->starts_on->toDateString()])];
        } else {
            $quote = $this->packages->quote($key, $periodStart);
            $package = $this->packages->package($key);
            if ($quote === null || $quote['base_cents'] === null) {
                $blocked = __('No contract and no list price for the :package package on :date. Add a price or record a contract, then recompute.', ['package' => $key, 'date' => $periodStart]);
            } else {
                $lines[] = ['kind' => 'base', 'product_module' => null, 'learners' => $learners, 'unit_cents' => (int) $quote['base_cents'],
                    'description' => __('Learners × :package package price per learner', ['package' => (string) ($package->name ?? $key)])];
                if ($quote['discount_cents'] > 0) {
                    $lines[] = ['kind' => 'discount', 'product_module' => null, 'learners' => $learners, 'unit_cents' => -(int) $quote['discount_cents'],
                        'description' => __('Discount per learner')];
                }
                foreach ($this->addOns($school, $package === null ? null : (int) $package->id, $version, $quote['modules']) as $product => $cents) {
                    $lines[] = ['kind' => 'module', 'product_module' => $product, 'learners' => $learners, 'unit_cents' => $cents,
                        'description' => __('Add-on: :module per learner', ['module' => (string) config('product_modules.'.$product.'.label', $product)])];
                }
            }
        }
        foreach ($lines as $i => $line) {
            $lines[$i]['amount_cents'] = $line['learners'] * $line['unit_cents'];
            $lines[$i]['vat_cents'] = self::lineVat($lines[$i]['amount_cents'], $rate);
        }
        $draft->lines()->get()->each(fn (PlatformInvoiceLine $line) => $line->delete());
        $this->storeLines($draft, $lines);
        $subtotal = array_sum(array_column($lines, 'amount_cents'));
        $vat = array_sum(array_column($lines, 'vat_cents'));
        $draft->forceFill([
            'package_key' => $key, 'package_version' => $version, 'price_source' => $contract !== null ? 'contract' : ($blocked === null ? 'list' : null),
            'contract_id' => $contract?->id, 'contracted_learners' => $contract?->contracted_learners,
            'learners' => $learners, 'learners_counted_on' => $countedOn, 'vat_rate_bp' => $rate,
            'subtotal_cents' => $subtotal, 'vat_cents' => $vat, 'total_cents' => $subtotal + $vat, 'blocked_reason' => $blocked,
            'computed_at' => now(), 'computed_by' => $actor->id,
        ])->save();
        $draft->unsetRelation('lines');
    }

    /**
     * Product modules of the school's package version (not the always-included
     * ones) that the school has switched on and that have an add-on price.
     *
     * @param  array<string, int>  $prices
     * @return array<string, int>
     */
    private function addOns(School $school, ?int $packageId, int $version, array $prices): array
    {
        if ($packageId === null || $prices === []) {
            return [];
        }
        $gate = app(ModuleGate::class);
        $gate->forget();
        $billed = [];
        foreach ($this->packages->productModules($packageId, $version) as $product) {
            $definition = (array) config('product_modules.'.$product, []);
            if (($definition['always_included'] ?? false) || ! array_key_exists($product, $prices)) {
                continue;
            }
            foreach ((array) ($definition['modules'] ?? []) as $module) {
                if ($gate->state($module)['enabled']) {
                    $billed[$product] = (int) $prices[$product];
                    break;
                }
            }
        }

        return $billed;
    }

    /** @param  list<array<string, mixed>>  $lines */
    private function storeLines(PlatformInvoice $invoice, array $lines): void
    {
        foreach (array_values($lines) as $position => $line) {
            PlatformInvoiceLine::query()->create(['invoice_id' => $invoice->id, 'position' => $position + 1] + $line);
        }
    }

    private function issueDraft(PlatformInvoice $draft, User $actor): string
    {
        $problem = $this->issueProblem($draft);
        if ($problem !== null) {
            throw ValidationException::withMessages(['invoice' => $problem]);
        }
        $today = CarbonImmutable::createFromFormat('!Y-m-d', self::today());
        $draft->forceFill([
            'status' => PlatformInvoice::ISSUED, 'reference' => $this->nextReference($today->year),
            'issued_on' => $today->toDateString(), 'due_on' => $today->addDays(max(0, (int) config('platform_billing.payment_terms_days', 30)))->toDateString(),
            'issued_at' => now(), 'issued_by' => $actor->id,
        ])->save();
        Audit::record('platform_invoice_issued', $draft, ['invoice_id' => $draft->id, 'status' => PlatformInvoice::DRAFT], [
            'invoice_id' => $draft->id, 'status' => PlatformInvoice::ISSUED, 'reference' => $draft->reference, 'period' => $draft->slot,
            'learners' => $draft->learners, 'price_source' => $draft->price_source, 'subtotal_cents' => $draft->subtotal_cents,
            'vat_rate_bp' => $draft->vat_rate_bp, 'vat_cents' => $draft->vat_cents, 'total_cents' => $draft->total_cents,
            'issued_on' => $draft->issued_on?->toDateString(), 'due_on' => $draft->due_on?->toDateString(), 'changed_by' => $actor->id,
        ], $draft->school_id);

        return (string) $draft->reference;
    }

    /**
     * The next reference of the year, NE-2026-0001, under a row lock on the
     * year's counter: issued references never repeat or skip.
     */
    private function nextReference(int $year): string
    {
        $name = 'platform_invoice_reference:'.$year;
        DB::table('platform_counters')->insertOrIgnore(['name' => $name, 'value' => 0]);
        $next = (int) DB::table('platform_counters')->where('name', $name)->lockForUpdate()->value('value') + 1;
        DB::table('platform_counters')->where('name', $name)->update(['value' => $next]);

        return (string) config('platform_billing.reference_prefix', 'NE').'-'.$year.'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function lockDraft(int $invoiceId): PlatformInvoice
    {
        $invoice = PlatformInvoice::query()->whereKey($invoiceId)->lockForUpdate()->first();
        abort_if($invoice === null, 404);
        if (! $invoice->isDraft()) {
            throw ValidationException::withMessages(['invoice' => $invoice->isIssued()
                ? __('An issued invoice never changes. Issue a credit note to correct it.')
                : __('This draft was discarded.')]);
        }

        return $invoice;
    }

    private function period(string $value): CarbonImmutable
    {
        $period = CarbonImmutable::createFromFormat('!Y-m-d', $value.'-01', self::TIMEZONE);
        $thisMonth = CarbonImmutable::now(self::TIMEZONE)->startOfMonth();
        if ($period->greaterThan($thisMonth->addMonth()) || $period->lessThan($thisMonth->subMonths(self::MONTHS_BACK))) {
            throw ValidationException::withMessages(['period' => __('Choose a month from :from to :to.', ['from' => $thisMonth->subMonths(self::MONTHS_BACK)->format('Y-m'), 'to' => $thisMonth->addMonth()->format('Y-m')])]);
        }

        return $period;
    }

    /** Work inside one school, in a transaction; an unknown school is not found. */
    public function inSchool(int $schoolId, callable $change): mixed
    {
        $this->school($schoolId);

        return $this->currentSchool->run($schoolId, fn () => DB::transaction(fn () => $change(), 3));
    }

    public function school(int $schoolId): object
    {
        $school = $this->currentSchool->acrossSchools(fn () => DB::table('schools')->where('id', $schoolId)->first(['id', 'name', 'status', 'timezone']));
        abort_if($school === null, 404);

        return $school;
    }

    public function authorise(User $actor): void
    {
        abort_unless(app(PlatformStaffAccess::class)->can($actor, 'billing.manage'), 403);
    }
}
