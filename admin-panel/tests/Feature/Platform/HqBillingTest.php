<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\AuditLog;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformInvoice;
use App\Models\Platform\PlatformInvoiceLine;
use App\Models\Platform\PlatformPayment;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\User;
use App\Services\Platform\Notifications\DeliveryLog;
use App\Services\Platform\Saas\PlatformBilling;
use App\Services\Platform\Saas\PlatformBillingAccounts;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * Navigate Ed billing its schools (Action Plan 1.17 and SA.8, Build 1):
 * monthly drafts from a learner snapshot and the contract or list price,
 * issued invoices that never change, credit notes, EFT payments with part,
 * over-payment and reversal, overdue reminders and the due-for-suspension
 * list. engineering/specs/platform/hq-billing.md.
 */
class HqBillingTest extends TestCase
{
    use UsesTwoSchools;

    private const TABLES = ['platform_invoices', 'platform_invoice_lines', 'platform_payments', 'platform_payment_allocations'];

    private int $staffCount = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->forget();
    }

    private function staff(string $role): User
    {
        $this->staffCount++;
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => ucfirst($role), 'last_name' => 'Operator', 'email' => $role.$this->staffCount.'@billing.example', 'mobile' => '', 'password' => 'Synthetic-billing-password-731!', 'status' => 1]));
        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => $role, 'expires_at' => now()->addYear()]);

        return $user;
    }

    private function month(): string
    {
        return substr(PlatformBilling::today(), 0, 7);
    }

    /** A list price on the legacy package (both schools are on it), in force since January. */
    private function price(string $kind, int $cents, ?string $module = null): void
    {
        DB::table('platform_prices')->insert(['package_id' => DB::table('platform_packages')->where('package_key', 'legacy')->value('id'), 'kind' => $kind,
            'product_module' => $module, 'amount_cents' => $cents, 'effective_from' => '2026-01-01', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function contract(int $school, int $cents, string $from = '2026-01-01'): void
    {
        app(CurrentSchool::class)->run($school, fn () => DB::table('school_contracts')->insert(['school_id' => $school, 'starts_on' => $from, 'contracted_learners' => 400,
            'price_per_learner_cents' => $cents, 'signatory' => 'Synthetic signatory', 'operator_agreement_version' => 'v1', 'operator_agreement_signed_on' => '2026-01-01', 'created_at' => now(), 'updated_at' => now()]));
    }

    /** Leave a school exactly one current learner. */
    private function oneLearner(int $school): void
    {
        $keep = DB::table('school_memberships')->where('school_id', $school)->where('role', SchoolRoles::STUDENT)->orderBy('id')->value('id');
        DB::table('school_memberships')->where('school_id', $school)->where('role', SchoolRoles::STUDENT)->where('id', '!=', $keep)
            ->update(['ends_on' => CarbonImmutable::parse(PlatformBilling::today())->subDays(2)->toDateString()]);
    }

    private function modules(int $school, array $states): void
    {
        app(CurrentSchool::class)->run($school, function () use ($states): void {
            foreach ($states as $module => $enabled) {
                SchoolModule::query()->updateOrCreate(['module' => $module], ['in_package' => true, 'enabled' => $enabled, 'read_only' => false]);
            }
        });
    }

    private function invoice(int $school, ?string $slot = null): PlatformInvoice
    {
        return PlatformInvoice::acrossSchools()->where('school_id', $school)->where('slot', $slot ?? $this->month())->sole();
    }

    /** @return array<string, int> */
    private function counts(): array
    {
        return array_combine(self::TABLES, array_map(fn (string $table): int => DB::table($table)->count(), self::TABLES))
            + ['audit' => AuditLog::query()->where(fn ($q) => $q->where('action', 'like', 'platform_invoice%')->orWhere('action', 'like', 'platform_payment%')
                ->orWhere('action', 'like', 'platform_billing%')->orWhere('action', 'like', 'platform_credit%'))->count(),
                'outbox' => OutboxEvent::query()->count()];
    }

    private function snapshot(): array
    {
        return array_map(fn (string $table): array => DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(), self::TABLES);
    }

    /** Both schools issued for this month: School A on a contract, School B on the list price. */
    private function issued(User $finance): array
    {
        $this->price('base', 5000);
        $this->contract(1, 24500);
        $this->actingAs($finance)->post('/saas/billing/runs', ['period' => $this->month()])->assertRedirect();
        $this->post('/saas/billing/runs/issue', ['period' => $this->month()])->assertRedirect();

        return [$this->invoice(1), $this->invoice(2)];
    }

    public function test_a_run_drafts_every_active_school_once_with_the_contract_price_winning_over_the_list_price(): void
    {
        $finance = $this->staff('finance');
        $this->price('base', 5000);
        $this->price('discount', 500);
        $this->price('module', 1000, 'ai_tutor');
        $this->modules(1, ['tutor' => true]);
        $this->modules(2, ['tutor' => true]);
        $this->contract(1, 2450);
        $learnersA = app(PlatformBilling::class)->learnerCount(1, PlatformBilling::today());
        $learnersB = app(PlatformBilling::class)->learnerCount(2, PlatformBilling::today());
        $this->assertGreaterThan(0, $learnersA);
        $this->assertGreaterThan(0, $learnersB);

        $this->actingAs($finance)->post('/saas/billing/runs', ['period' => $this->month()])->assertRedirect('/saas/billing?month='.$this->month());
        $a = $this->invoice(1);
        $b = $this->invoice(2);
        $this->assertSame(['draft', 'contract', $learnersA, 400, null], [$a->status, $a->price_source, $a->learners, $a->contracted_learners, $a->reference]);
        // The contract is what School A signed: one line at its price, no list discount or add-on.
        $lines = PlatformInvoiceLine::acrossSchools()->where('invoice_id', $a->id)->orderBy('position')->get();
        $this->assertSame([['base', 2450, $learnersA * 2450]], $lines->map(fn ($l) => [$l->kind, $l->unit_cents, $l->amount_cents])->all());
        $this->assertSame($learnersA * 2450, $a->subtotal_cents);
        $this->assertSame(1500, $a->vat_rate_bp);
        // School B has no contract: the list price on the period's first day, its discount and its add-on.
        $this->assertSame(['list', ['base', 'discount', 'module']], [$b->price_source, PlatformInvoiceLine::acrossSchools()->where('invoice_id', $b->id)->orderBy('position')->pluck('kind')->all()]);
        $this->assertSame($learnersB * (5000 - 500 + 1000), $b->subtotal_cents);
        $this->assertSame(1, AuditLog::query()->where('action', 'platform_billing_run')->count());

        // Running again creates nothing; a suspended school is skipped.
        $before = $this->snapshot();
        $this->post('/saas/billing/runs', ['period' => $this->month()])->assertRedirect();
        $this->assertSame($before, $this->snapshot());
        School::query()->whereKey(2)->update(['status' => School::SUSPENDED]);
        $next = CarbonImmutable::parse(PlatformBilling::today())->startOfMonth()->addMonth()->format('Y-m');
        $this->post('/saas/billing/runs', ['period' => $next])->assertRedirect();
        $this->assertSame(1, PlatformInvoice::acrossSchools()->where('slot', $next)->count());
        $this->assertSame(0, PlatformInvoice::acrossSchools()->where('slot', $next)->where('school_id', 2)->count());

        // A month outside the window or malformed is refused.
        $this->post('/saas/billing/runs', ['period' => '2020-01'])->assertSessionHasErrors('period');
        $this->post('/saas/billing/runs', ['period' => '2026-13'])->assertSessionHasErrors('period');

        // A school with neither a contract nor a list price cannot be issued until it has one.
        DB::table('platform_prices')->delete();
        $this->post('/saas/billing/schools/2/invoices/'.$b->id.'/recompute')->assertRedirect();
        $this->assertNotNull($b->fresh()->blocked_reason);
        $this->post('/saas/billing/schools/2/invoices/'.$b->id.'/issue')->assertSessionHasErrors('invoice');
        $this->get('/saas/billing?month='.$this->month())->assertOk()->assertSee('No contract and no list price');
    }

    public function test_add_ons_and_the_discount_are_lines_and_vat_is_rounded_per_line_then_totalled(): void
    {
        $finance = $this->staff('finance');
        $this->oneLearner(2);
        $this->assertSame(1, app(PlatformBilling::class)->learnerCount(2, PlatformBilling::today()));
        // 15 % of R 10.10 is 151.5 cents: 152 on each of two lines; R 0.04 off is -0.6: -1.
        $this->price('base', 1010);
        $this->price('discount', 4);
        $this->price('module', 1010, 'ai_tutor');
        $this->price('module', 777, 'futures');
        // Tutor is switched on; Futures (pathway, transfers) is off, so it is not billed.
        $this->modules(2, ['tutor' => true, 'pathway' => false, 'school_transfers' => false]);

        $this->actingAs($finance)->post('/saas/billing/runs', ['period' => $this->month()])->assertRedirect();
        $b = $this->invoice(2);
        $lines = PlatformInvoiceLine::acrossSchools()->where('invoice_id', $b->id)->orderBy('position')->get();
        $this->assertSame([
            ['base', null, 1, 1010, 1010, 152],
            ['discount', null, 1, -4, -4, -1],
            ['module', 'ai_tutor', 1, 1010, 1010, 152],
        ], $lines->map(fn ($l) => [$l->kind, $l->product_module, $l->learners, $l->unit_cents, $l->amount_cents, $l->vat_cents])->all());
        // Per line, then the total: 303 cents of VAT, where 15 % of the R 20.16 subtotal would round to 302.
        $this->assertSame([2016, 303, 2319], [$b->subtotal_cents, $b->vat_cents, $b->total_cents]);
        $this->assertSame(302, PlatformBilling::lineVat(2016, 1500));
        $this->assertSame([2, -2, 0, 1500], [PlatformBilling::lineVat(10, 1500), PlatformBilling::lineVat(-10, 1500), PlatformBilling::lineVat(0, 1500), PlatformBilling::vatRateBp()]);

        // The rate is configurable and kept on each invoice.
        config(['platform_billing.vat_percent' => '15.5']);
        $this->assertSame(1550, PlatformBilling::vatRateBp());
        $this->post('/saas/billing/schools/2/invoices/'.$b->id.'/recompute')->assertRedirect();
        $this->assertSame(1550, $b->fresh()->vat_rate_bp);
        config(['platform_billing.vat_percent' => '15']);
        $this->get('/saas/billing/schools/2/invoices/'.$b->id)->assertOk()->assertSee('Add-on: AI tutor', false)->assertSee('VAT 15.5 %')->assertSee('Rounding');
    }

    public function test_learners_are_counted_on_the_run_date_without_leavers_or_other_schools_learners(): void
    {
        $billing = app(PlatformBilling::class);
        $today = PlatformBilling::today();
        $students = DB::table('school_memberships')->where('school_id', 1)->where('role', SchoolRoles::STUDENT)->orderBy('id')->get(['id', 'user_id']);
        $this->assertGreaterThanOrEqual(3, $students->count());
        $a = $billing->learnerCount(1, $today);
        $b = $billing->learnerCount(2, $today);
        $this->assertSame($students->pluck('user_id')->unique()->count(), $a);

        // A learner who has left (latest transition a leave before today) is not counted; one who came back is.
        $learner = DB::table('students')->where('school_id', 1)->where('user_id', $students[0]->user_id)->value('id');
        $transition = fn (string $kind, string $on) => DB::table('academic_transitions')->insert(['school_id' => 1, 'learner_id' => $learner,
            'session_year_id' => $this->schoolA->ids['session_year'], 'actor_id' => $this->superAdmin()->id, 'operation_id' => (string) Str::uuid(),
            'request_hash' => str_repeat('a', 64), 'kind' => $kind, 'effective_on' => $on, 'reason' => 'Synthetic', 'payload' => '{}', 'created_at' => now(), 'updated_at' => now()]);
        $transition('leave', $today);
        $this->assertSame($a, $billing->learnerCount(1, $today), 'still counted on the day they leave');
        DB::table('academic_transitions')->where('learner_id', $learner)->update(['effective_on' => CarbonImmutable::parse($today)->subDays(3)->toDateString()]);
        $this->assertSame($a - 1, $billing->learnerCount(1, $today));
        $transition('join', CarbonImmutable::parse($today)->subDay()->toDateString());
        $this->assertSame($a, $billing->learnerCount(1, $today));

        // An ended membership, a disabled account and a Parent are not learners.
        DB::table('school_memberships')->where('id', $students[1]->id)->update(['ends_on' => CarbonImmutable::parse($today)->subDay()->toDateString()]);
        User::acrossSchools()->whereKey($students[2]->user_id)->update(['status' => 0]);
        $this->assertSame($a - 2, $billing->learnerCount(1, $today));

        // Another school's learners never count, even a learner at both.
        SchoolMembership::query()->create(['user_id' => $students[0]->user_id, 'school_id' => 2, 'role' => SchoolRoles::STUDENT, 'status' => SchoolMembership::ACTIVE]);
        $this->assertSame($a - 2, $billing->learnerCount(1, $today));
        $this->assertSame($b + 1, $billing->learnerCount(2, $today));

        // The draft keeps the count and the day it was taken; recompute counts again.
        $this->price('base', 1000);
        $finance = $this->staff('finance');
        $this->actingAs($finance)->post('/saas/billing/runs', ['period' => $this->month()])->assertRedirect();
        $draft = $this->invoice(1);
        $this->assertSame([$a - 2, $today], [$draft->learners, $draft->learners_counted_on->toDateString()]);
        User::acrossSchools()->whereKey($students[2]->user_id)->update(['status' => 1]);
        $this->post('/saas/billing/schools/1/invoices/'.$draft->id.'/recompute')->assertRedirect();
        $this->assertSame($a - 1, $draft->fresh()->learners);
        $this->assertSame(1, AuditLog::query()->where('action', 'platform_invoice_recomputed')->count());
    }

    public function test_issued_invoices_get_sequential_references_and_never_change(): void
    {
        $finance = $this->staff('finance');
        [$a, $b] = $this->issued($finance);
        $year = substr(PlatformBilling::today(), 0, 4);
        $this->assertSame(['NE-'.$year.'-0001', 'NE-'.$year.'-0002'], [$a->reference, $b->reference]);
        $this->assertSame(['issued', PlatformBilling::today(), CarbonImmutable::parse(PlatformBilling::today())->addDays(30)->toDateString()], [$a->status, $a->issued_on->toDateString(), $a->due_on->toDateString()]);
        $issuedAudit = AuditLog::query()->where('action', 'platform_invoice_issued')->orderBy('id')->get();
        $this->assertSame([[1, $a->reference, $a->total_cents], [2, $b->reference, $b->total_cents]], $issuedAudit->map(fn ($r) => [$r->school_id, $r->after['reference'], $r->after['total_cents']])->all());

        // Recompute, issue again or discard: refused, nothing changes.
        $before = $this->snapshot();
        $this->post('/saas/billing/schools/1/invoices/'.$a->id.'/recompute')->assertSessionHasErrors('invoice');
        $this->post('/saas/billing/schools/1/invoices/'.$a->id.'/issue')->assertSessionHasErrors('invoice');
        $this->post('/saas/billing/schools/1/invoices/'.$a->id.'/discard')->assertSessionHasErrors('invoice');
        $this->assertSame($before, $this->snapshot());
        // The models refuse too, whatever writes them.
        foreach ([fn () => $a->fresh()->forceFill(['total_cents' => 1])->save(), fn () => $a->fresh()->delete(),
            fn () => PlatformInvoiceLine::acrossSchools()->where('invoice_id', $a->id)->first()->forceFill(['amount_cents' => 1])->save(),
            fn () => PlatformInvoiceLine::acrossSchools()->where('invoice_id', $a->id)->first()->delete()] as $write) {
            try {
                app(CurrentSchool::class)->run(1, $write);
                $this->fail('An issued invoice changed');
            } catch (LogicException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->assertSame($before, $this->snapshot());

        // A discarded draft is kept, is not billed and is not made again; references carry on with no gap or repeat.
        $next = CarbonImmutable::parse(PlatformBilling::today())->startOfMonth()->addMonth()->format('Y-m');
        $this->post('/saas/billing/runs', ['period' => $next])->assertRedirect();
        $this->post('/saas/billing/schools/2/invoices/'.$this->invoice(2, $next)->id.'/discard')->assertRedirect('/saas/billing/schools/2');
        $this->post('/saas/billing/runs', ['period' => $next])->assertRedirect();
        $this->assertSame('discarded', $this->invoice(2, $next)->status);
        $this->post('/saas/billing/runs/issue', ['period' => $next])->assertRedirect();
        $this->assertSame('NE-'.$year.'-0003', $this->invoice(1, $next)->reference);
        $this->assertNull($this->invoice(2, $next)->reference);
        $references = PlatformInvoice::acrossSchools()->whereNotNull('reference')->pluck('reference');
        $this->assertSame($references->count(), $references->unique()->count());
        $this->assertSame(3, (int) DB::table('platform_counters')->where('name', 'platform_invoice_reference:'.$year)->value('value'));

        // The printable invoice shows it.
        $this->get('/saas/billing/schools/1/invoices/'.$a->id)->assertOk()->assertSee('Tax invoice')->assertSee($a->reference)
            ->assertSee('Pay by EFT using the reference '.$a->reference)->assertDontSee('Baobab');
    }

    public function test_a_credit_note_corrects_an_issued_invoice_and_refers_to_it(): void
    {
        $finance = $this->staff('finance');
        [$a] = $this->issued($finance);
        $year = substr(PlatformBilling::today(), 0, 4);

        // R 100 excluding VAT: R 115 credit, which settles R 115 of the unpaid invoice.
        $this->actingAs($finance)->post('/saas/billing/schools/1/invoices/'.$a->id.'/credit-notes', ['idempotency_key' => (string) Str::uuid(), 'amount' => '100', 'reason' => 'Two learners left in the first week'])->assertRedirect();
        $note = PlatformInvoice::acrossSchools()->where('kind', 'credit_note')->sole();
        $this->assertSame(['issued', 'NE-'.$year.'-0003', $a->id, -10000, -1500, -11500], [$note->status, $note->reference, $note->credits_invoice_id, $note->subtotal_cents, $note->vat_cents, $note->total_cents]);
        $this->assertSame($a->total_cents - 11500, app(CurrentSchool::class)->run(1, fn () => app(PlatformBillingAccounts::class)->outstanding($a)));
        $audit = AuditLog::query()->where('action', 'platform_credit_note_issued')->sole();
        $this->assertSame([1, $a->reference, -11500, 11500], [$audit->school_id, $audit->after['credits_reference'], $audit->after['total_cents'], $audit->after['settles_cents']]);
        $this->assertSame([$a->total_cents, $a->vat_cents], [$a->fresh()->total_cents, $a->fresh()->vat_cents], 'the original never changes');

        // Not more than is left; not the whole invoice once part was credited; not a credit note itself; not zero.
        $before = $this->snapshot();
        $this->post('/saas/billing/schools/1/invoices/'.$a->id.'/credit-notes', ['idempotency_key' => (string) Str::uuid(), 'amount' => (string) intdiv($a->total_cents, 100), 'reason' => 'Far too much credit asked'])->assertSessionHasErrors('amount');
        $this->post('/saas/billing/schools/1/invoices/'.$a->id.'/credit-notes', ['idempotency_key' => (string) Str::uuid(), 'full' => '1', 'reason' => 'Credit the whole invoice please'])->assertSessionHasErrors('full');
        $this->post('/saas/billing/schools/1/invoices/'.$note->id.'/credit-notes', ['idempotency_key' => (string) Str::uuid(), 'amount' => '1', 'reason' => 'Credit of a credit note'])->assertSessionHasErrors('invoice');
        $this->post('/saas/billing/schools/1/invoices/'.$a->id.'/credit-notes', ['idempotency_key' => (string) Str::uuid(), 'amount' => '0', 'reason' => 'Nothing at all here'])->assertSessionHasErrors('amount');
        $this->post('/saas/billing/schools/1/invoices/'.$a->id.'/credit-notes', ['idempotency_key' => (string) Str::uuid(), 'amount' => '5'])->assertSessionHasErrors('reason');
        $this->assertSame($before, $this->snapshot());

        // School B's invoice credited in full: every line negated exactly, paid in full by the credit.
        $b = $this->invoice(2);
        $this->post('/saas/billing/schools/2/invoices/'.$b->id.'/credit-notes', ['idempotency_key' => (string) Str::uuid(), 'full' => '1', 'reason' => 'Billed in error this month'])->assertRedirect();
        $full = PlatformInvoice::acrossSchools()->where('credits_invoice_id', $b->id)->sole();
        $this->assertSame([-$b->subtotal_cents, -$b->vat_cents, -$b->total_cents], [$full->subtotal_cents, $full->vat_cents, $full->total_cents]);
        $this->assertSame(0, app(CurrentSchool::class)->run(2, fn () => app(PlatformBillingAccounts::class)->outstanding($b)));
        $this->get('/saas/billing/schools/2/invoices/'.$full->id)->assertOk()->assertSee('Credit note')->assertSee('Credits invoice '.$b->reference)->assertSee('Billed in error this month');
        $this->get('/saas/billing/schools/2')->assertOk()->assertSee($full->reference)->assertSee('R 0.00');
    }

    public function test_credit_note_retries_reuse_one_note_and_reject_a_changed_request(): void
    {
        $finance = $this->staff('finance');
        [$invoice] = $this->issued($finance);
        $url = '/saas/billing/schools/1/invoices/'.$invoice->id.'/credit-notes';
        $input = ['idempotency_key' => (string) Str::uuid(), 'amount' => '100', 'reason' => 'Correct the duplicate learner charge'];
        $this->get('/saas/billing/schools/1/invoices/'.$invoice->id)->assertOk()->assertSee('name="idempotency_key"', false);
        $first = $this->post($url, $input)->assertRedirect()->headers->get('Location');
        $before = $this->snapshot();
        $audit = AuditLog::query()->where('action', 'platform_credit_note_issued')->count();

        $this->post($url, $input)->assertRedirect($first);
        $this->assertSame($before, $this->snapshot(), 'replay changes no invoice, line, payment or allocation');
        $this->assertSame($audit, AuditLog::query()->where('action', 'platform_credit_note_issued')->count());
        $this->post($url, array_replace($input, ['amount' => '200']))->assertSessionHasErrors('idempotency_key');
        $this->post($url, ['amount' => '100', 'reason' => $input['reason']])->assertSessionHasErrors('idempotency_key');
        $this->assertSame($before, $this->snapshot());
    }

    public function test_locked_credit_balances_see_allocations_committed_after_the_repeatable_read_snapshot(): void
    {
        // Use a second actual MySQL connection, not a mock: this reproduces
        // the stale snapshot held by a request waiting for a source lock.
        config(['database.connections.billing_peer' => config('database.connections.mysql')]);
        $peer = DB::connection('billing_peer');
        [$invoiceId, $noteId, $paymentId] = $peer->transaction(function () use ($peer): array {
            $invoice = ['school_id' => 1, 'kind' => 'invoice', 'status' => 'issued', 'period_start' => '2026-10-01',
                'period_end' => '2026-10-31', 'vat_rate_bp' => 0, 'total_cents' => 20000, 'issued_on' => '2026-10-01'];
            $invoiceId = $peer->table('platform_invoices')->insertGetId($invoice);
            $noteId = $peer->table('platform_invoices')->insertGetId(array_replace($invoice, ['kind' => 'credit_note', 'total_cents' => -10000]));
            $paymentId = $peer->table('platform_payments')->insertGetId(['school_id' => 1, 'received_on' => '2026-10-01',
                'amount_cents' => 10000, 'bank_reference' => 'Concurrent credit fixture']);

            return [$invoiceId, $noteId, $paymentId];
        });
        DB::rollBack();
        DB::beginTransaction();
        try {
            app(CurrentSchool::class)->run(1, function () use ($peer, $invoiceId, $noteId, $paymentId): void {
                $accounts = app(PlatformBillingAccounts::class);
                $invoice = PlatformInvoice::query()->findOrFail($invoiceId);
                $this->assertSame(20000, array_sum(array_column($accounts->creditSources(), 'available_cents')));
                $peer->table('platform_payment_allocations')->insert([
                    ['school_id' => 1, 'invoice_id' => $invoiceId, 'payment_id' => $paymentId, 'credit_note_id' => null, 'amount_cents' => 10000],
                    ['school_id' => 1, 'invoice_id' => $invoiceId, 'payment_id' => null, 'credit_note_id' => $noteId, 'amount_cents' => 10000],
                ]);
                $this->assertSame(20000, $accounts->outstanding($invoice), 'ordinary reads still hold the old snapshot');
                $this->assertSame([], $accounts->creditSources(true), 'neither spent source can be allocated again');
                $this->assertSame(0, $accounts->outstanding($invoice, true), 'money writes see the committed allocations');
            });
        } finally {
            // Release current-read locks before cleaning the committed fixtures.
            DB::rollBack();
            $peer->table('platform_payment_allocations')->where('invoice_id', $invoiceId)->delete();
            $peer->table('platform_payments')->where('id', $paymentId)->delete();
            $peer->table('platform_invoices')->whereIn('id', [$invoiceId, $noteId])->delete();
            DB::disconnect('billing_peer');
            DB::beginTransaction();
        }
    }

    public function test_payments_settle_part_or_all_an_over_payment_is_credit_and_a_reversal_keeps_the_record(): void
    {
        $finance = $this->staff('finance');
        [$a] = $this->issued($finance);
        $accounts = app(PlatformBillingAccounts::class);
        $outstanding = fn () => app(CurrentSchool::class)->run(1, fn () => $accounts->outstanding($a));
        $total = $a->total_cents;
        $today = PlatformBilling::today();

        // A part-payment.
        $this->actingAs($finance)->post('/saas/billing/schools/1/payments', ['amount' => '100.00', 'received_on' => $today, 'bank_reference' => 'FNB 1001', 'allocations' => [$a->id => '100']])->assertRedirect('/saas/billing/schools/1');
        $this->assertSame($total - 10000, $outstanding());
        // The same bank reference again is refused (one bank line, one payment).
        $before = $this->snapshot();
        $this->post('/saas/billing/schools/1/payments', ['amount' => '100.00', 'received_on' => $today, 'bank_reference' => 'FNB 1001'])->assertSessionHasErrors('bank_reference');
        // More than the payment, more than is unpaid, a future date, a float-ish amount: refused, nothing written.
        $this->post('/saas/billing/schools/1/payments', ['amount' => '10', 'received_on' => $today, 'bank_reference' => 'FNB 1002', 'allocations' => [$a->id => '20']])->assertSessionHasErrors('allocations');
        $this->post('/saas/billing/schools/1/payments', ['amount' => '99999999', 'received_on' => $today, 'bank_reference' => 'FNB 1003', 'allocations' => [$a->id => '99999999']])->assertSessionHasErrors('allocations.'.$a->id);
        $this->post('/saas/billing/schools/1/payments', ['amount' => '10', 'received_on' => CarbonImmutable::parse($today)->addDay()->toDateString(), 'bank_reference' => 'FNB 1004'])->assertSessionHasErrors('received_on');
        $this->post('/saas/billing/schools/1/payments', ['amount' => '1e3', 'received_on' => $today, 'bank_reference' => 'FNB 1005'])->assertSessionHasErrors('amount');
        $this->assertSame($before, $this->snapshot());

        // An over-payment: settles the rest, R 50 is account credit.
        $rest = $total - 10000;
        $paid = $rest + 5000;
        $rands = fn (int $cents): string => intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
        $this->post('/saas/billing/schools/1/payments', ['amount' => $rands($paid), 'received_on' => $today, 'bank_reference' => 'FNB 2002', 'allocations' => [$a->id => $rands($rest)]])->assertRedirect();
        $this->assertSame(0, $outstanding());
        $statement = $accounts->statement(1);
        $this->assertSame([5000, -5000], [$statement['credit_cents'], $statement['balance_cents']]);
        $this->assertSame(5000, AuditLog::query()->where('action', 'platform_payment_recorded')->orderByDesc('id')->first()->after['credit_cents']);

        // Next month's invoice is settled from the credit.
        $next = CarbonImmutable::parse($today)->startOfMonth()->addMonth()->format('Y-m');
        $this->post('/saas/billing/runs', ['period' => $next]);
        $this->post('/saas/billing/runs/issue', ['period' => $next]);
        $later = $this->invoice(1, $next);
        $this->post('/saas/billing/schools/1/invoices/'.$later->id.'/apply-credit')->assertRedirect('/saas/billing/schools/1');
        $this->assertSame($later->total_cents - 5000, app(CurrentSchool::class)->run(1, fn () => $accounts->outstanding($later)));
        $this->post('/saas/billing/schools/1/invoices/'.$later->id.'/apply-credit')->assertSessionHasErrors('invoice');

        // Reverse the first payment: kept, marked reversed with the reason, and the invoice is unpaid by R 100 again.
        $first = PlatformPayment::acrossSchools()->where('bank_reference', 'FNB 1001')->sole();
        $this->post('/saas/billing/schools/1/payments/'.$first->id.'/reverse', ['reason' => 'short'])->assertSessionHasErrors('reason');
        $this->post('/saas/billing/schools/1/payments/'.$first->id.'/reverse', ['reason' => 'Bank recalled this deposit'])->assertRedirect();
        $first->refresh();
        $this->assertNotNull($first->reversed_at);
        $this->assertSame('Bank recalled this deposit', $first->reversal_reason);
        $this->assertSame(10000, $outstanding());
        $this->assertSame(2, PlatformPayment::acrossSchools()->where('school_id', 1)->count(), 'nothing deleted');
        $this->post('/saas/billing/schools/1/payments/'.$first->id.'/reverse', ['reason' => 'Bank recalled this deposit'])->assertSessionHasErrors('payment');
        $reversal = AuditLog::query()->where('action', 'platform_payment_reversed')->sole();
        $this->assertSame([1, 'Bank recalled this deposit', [['invoice_id' => $a->id, 'amount_cents' => 10000]]], [$reversal->school_id, $reversal->after['reason'], $reversal->after['unsettled']]);
        // A reversed bank reference may be recorded again correctly.
        $this->post('/saas/billing/schools/1/payments', ['amount' => '100', 'received_on' => $today, 'bank_reference' => 'FNB 1001', 'allocations' => [$a->id => '100']])->assertRedirect();
        $this->assertSame(0, $outstanding());
        try {
            app(CurrentSchool::class)->run(1, fn () => $first->forceFill(['amount_cents' => 1])->save());
            $this->fail('A payment was edited');
        } catch (LogicException) {
            $this->addToAssertionCount(1);
        }

        // The balance is what was issued less what stands; the statement and page agree.
        $statement = $accounts->statement(1);
        $this->assertSame($a->total_cents + $later->total_cents - (10000 + $paid), $statement['balance_cents']);
        $this->get('/saas/billing/schools/1')->assertOk()->assertSee('FNB 2002')->assertSee('Reversed: Bank recalled this deposit')->assertDontSee('Baobab');
        $page = $this->get('/saas/billing?month='.$this->month())->assertOk();
        $page->assertViewHas('summary', fn (array $s) => $s['invoiced_cents'] === $a->total_cents + $this->invoice(2)->total_cents
            && $s['collected_cents'] === 10000 + $paid && $s['mrr_cents'] === $a->subtotal_cents + $this->invoice(2)->subtotal_cents && $s['overdue_cents'] === 0);
    }

    public function test_overdue_invoices_go_from_grace_to_due_for_suspension_and_suspending_stays_a_decision(): void
    {
        $finance = $this->staff('finance');
        [$a, $b] = $this->issued($finance);
        $accounts = app(PlatformBillingAccounts::class);
        $this->actingAs($finance)->post('/saas/billing/schools/2/payments', ['amount' => '1', 'received_on' => PlatformBilling::today(), 'bank_reference' => 'PART-1', 'allocations' => [$b->id => '1']]);
        $this->assertSame([], $accounts->overdue(), 'not overdue on the due date');

        // The day after the due date: overdue, in grace.
        $this->travelTo(CarbonImmutable::parse($a->due_on->toDateString(), PlatformBilling::TIMEZONE)->addDay()->setTime(10, 0));
        // School A pays in full and drops off; School B is overdue by what is unpaid.
        $this->post('/saas/billing/schools/1/payments', ['amount' => intdiv($a->total_cents, 100).'.'.str_pad((string) ($a->total_cents % 100), 2, '0', STR_PAD_LEFT), 'received_on' => PlatformBilling::today(), 'bank_reference' => 'PAID-A', 'allocations' => [$a->id => intdiv($a->total_cents, 100).'.'.str_pad((string) ($a->total_cents % 100), 2, '0', STR_PAD_LEFT)]])->assertRedirect();
        $overdue = $accounts->overdue();
        $this->assertSame([[2, 'grace', $b->total_cents - 100, 1]], array_map(fn ($r) => [$r['school_id'], $r['stage'], $r['overdue_cents'], $r['days_overdue']], $overdue));
        $this->get('/saas/billing')->assertOk()->assertSee('In grace until')->assertSee('No school is past its grace period.');

        // After the grace period: due for suspension, still active. Finance sees it but cannot suspend.
        $this->travelTo(CarbonImmutable::parse($b->due_on->toDateString(), PlatformBilling::TIMEZONE)->addDays(15)->setTime(10, 0));
        $this->assertSame('due_for_suspension', $accounts->overdue()[0]['stage']);
        $this->assertSame(School::ACTIVE, School::query()->findOrFail(2)->status, 'never suspended automatically');
        $this->get('/saas/billing')->assertOk()->assertSee('Due for suspension')->assertSee('Ask an Owner or Operations')->assertDontSee('Suspend this school');
        $this->put('/saas/schools/2/status', ['status' => 'suspended', 'reason' => 'Account overdue past grace'])->assertForbidden();

        // Operations sees the form; suspending uses the existing flow, with its reason in the audit log.
        $operations = $this->staff('operations');
        $this->actingAs($operations)->get('/saas/billing')->assertOk()->assertSee('Suspend this school')->assertDontSee('Create drafts')->assertDontSee('Send a reminder');
        $this->put('/saas/schools/2/status', ['status' => 'suspended', 'reason' => 'Account overdue past grace'])->assertRedirect();
        $this->assertSame(School::SUSPENDED, School::query()->findOrFail(2)->status);
        $this->assertSame('Account overdue past grace', AuditLog::query()->where('action', 'platform_school_status_changed')->sole()->after['reason']);
        $this->assertSame('not_active', $accounts->overdue()[0]['stage']);
    }

    public function test_a_reminder_goes_to_that_schools_super_admins_and_bursar_only(): void
    {
        $finance = $this->staff('finance');
        [$a, $b] = $this->issued($finance);
        // School B has a Super Admin of its own, and each school a bursar contact.
        $baobabAdmin = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => 'Baobab', 'last_name' => 'Admin', 'email' => 'baobab-admin@billing.example', 'mobile' => '', 'password' => 'Synthetic-billing-password-731!', 'status' => 1]));
        SchoolMembership::query()->create(['user_id' => $baobabAdmin->id, 'school_id' => 2, 'role' => SchoolRoles::SUPER_ADMIN, 'status' => SchoolMembership::ACTIVE]);
        foreach ([1 => 'bursar-a@school.example', 2 => 'bursar-baobab@school.example'] as $school => $email) {
            DB::table('school_contacts')->insert(['school_id' => $school, 'role' => 'bursar', 'name' => 'Synthetic bursar', 'email' => $email, 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->actingAs($finance)->post('/saas/billing/schools/1/reminders')->assertSessionHasErrors('school');
        $this->assertSame(0, OutboxEvent::query()->where('type', 'notification.requested')->count(), 'nothing overdue yet, nothing sent');

        $this->travelTo(CarbonImmutable::parse($a->due_on->toDateString(), PlatformBilling::TIMEZONE)->addDays(2)->setTime(10, 0));
        $this->post('/saas/billing/schools/1/reminders')->assertRedirect();
        $admins = app(CurrentSchool::class)->run(1, fn () => app(PlatformBillingAccounts::class)->superAdmins(1));
        $this->assertContains($this->superAdmin()->id, $admins);
        $this->assertNotContains($baobabAdmin->id, $admins);
        $event = OutboxEvent::query()->where('type', 'notification.requested')->sole();
        $this->assertSame([1, $admins], [(int) $event->school_id, $event->payload['user_ids']]);
        $notice = DB::table('notifications')->where('id', $event->payload['notification_id'])->first();
        $this->assertSame([1, 'billing.overdue'], [(int) $notice->school_id, $notice->type]);
        $this->assertStringContainsString($a->reference, $notice->message);
        $this->assertStringNotContainsString('Baobab', $notice->message.$notice->title);
        // Only School A's bursar is emailed.
        $mails = DB::table('notification_deliveries')->where('type', 'mail.billing.overdue')->get();
        $this->assertSame([[1, DeliveryLog::hash('bursar-a@school.example')]], $mails->map(fn ($m) => [(int) $m->school_id, $m->recipient_hash])->all());
        $this->assertSame([1, 1], [$a->fresh()->reminders, $a->fresh()->reminded_at !== null ? 1 : 0]);
        $this->assertSame(0, $b->fresh()->reminders);
        $audit = AuditLog::query()->where('action', 'platform_billing_reminder_sent')->sole();
        $this->assertSame([1, [$a->id], count($admins)], [$audit->school_id, $audit->after['invoices'], $audit->after['super_admins']]);
        $this->assertArrayHasKey('billing.overdue', (array) config('notification_types'));
    }

    public function test_sales_content_and_support_cannot_manage_billing_and_nothing_is_written(): void
    {
        $finance = $this->staff('finance');
        [$a] = $this->issued($finance);
        $this->actingAs($finance)->post('/saas/billing/schools/1/payments', ['amount' => '1', 'received_on' => PlatformBilling::today(), 'bank_reference' => 'ROLE-1']);
        $payment = PlatformPayment::acrossSchools()->sole();
        $before = $this->counts();
        $rows = $this->snapshot();
        foreach (['sales', 'content', 'support', 'operations'] as $role) {
            $user = $this->staff($role);
            $this->actingAs($user);
            foreach ($this->writes($a->id, $payment->id) as [$uri, $input]) {
                $this->post($uri, $input)->assertForbidden();
            }
            if ($role === 'operations') {
                // Operations reads billing without any form that changes it.
                $this->get('/saas/billing')->assertOk()->assertDontSee('Create drafts')->assertDontSee('Issue every ready draft');
                $this->get('/saas/billing/schools/1')->assertOk()->assertDontSee('Record the payment')->assertDontSee('Reverse this payment');
                $this->get('/saas/billing/schools/1/invoices/'.$a->id)->assertOk()->assertDontSee('Issue the credit note');
            } else {
                $this->get('/saas/billing')->assertForbidden();
                $this->get('/saas/billing/schools/1')->assertForbidden();
                $this->get('/saas/billing/schools/1/invoices/'.$a->id)->assertForbidden();
            }
            // The services refuse too, whatever calls them.
            foreach ([
                fn () => app(PlatformBilling::class)->run(['period' => $this->month()], $user),
                fn () => app(PlatformBilling::class)->creditNote(1, $a->id, ['idempotency_key' => (string) Str::uuid(), 'amount' => '1', 'reason' => 'Not allowed to do this'], $user),
                fn () => app(PlatformBillingAccounts::class)->record(1, ['amount' => '1', 'received_on' => PlatformBilling::today(), 'bank_reference' => 'X-1'], $user),
                fn () => app(PlatformBillingAccounts::class)->reverse(1, $payment->id, ['reason' => 'Not allowed to do this'], $user),
                fn () => app(PlatformBillingAccounts::class)->remind(1, $user),
            ] as $call) {
                try {
                    $call();
                    $this->fail($role.' was allowed');
                } catch (HttpException $e) {
                    $this->assertSame(403, $e->getStatusCode());
                }
            }
        }
        $this->assertSame($before, $this->counts());
        $this->assertSame($rows, $this->snapshot());
    }

    /** @return list<array{0: string, 1: array<string, mixed>}> every billing write endpoint */
    private function writes(int $invoice, int $payment, int $school = 1): array
    {
        return [
            ['/saas/billing/runs', ['period' => $this->month()]],
            ['/saas/billing/runs/issue', ['period' => $this->month()]],
            ["/saas/billing/schools/{$school}/invoices/{$invoice}/recompute", []],
            ["/saas/billing/schools/{$school}/invoices/{$invoice}/issue", []],
            ["/saas/billing/schools/{$school}/invoices/{$invoice}/discard", []],
            ["/saas/billing/schools/{$school}/invoices/{$invoice}/credit-notes", ['idempotency_key' => (string) Str::uuid(), 'amount' => '1', 'reason' => 'Not allowed to do this']],
            ["/saas/billing/schools/{$school}/invoices/{$invoice}/apply-credit", []],
            ["/saas/billing/schools/{$school}/payments", ['amount' => '1', 'received_on' => PlatformBilling::today(), 'bank_reference' => 'X-2']],
            ["/saas/billing/schools/{$school}/payments/{$payment}/reverse", ['reason' => 'Not allowed to do this']],
            ["/saas/billing/schools/{$school}/reminders", []],
        ];
    }

    public function test_school_accounts_are_refused(): void
    {
        $finance = $this->staff('finance');
        [$a] = $this->issued($finance);
        $this->actingAs($finance)->post('/saas/billing/schools/1/payments', ['amount' => '1', 'received_on' => PlatformBilling::today(), 'bank_reference' => 'SCHOOL-1']);
        $payment = PlatformPayment::acrossSchools()->sole();
        $before = $this->counts();
        foreach ([$this->superAdmin(), User::findOrFail($this->schoolA->ids['tara_user'])] as $user) {
            $this->actingAs($user)->get('/saas/billing')->assertForbidden();
            $this->get('/saas/billing/schools/1')->assertForbidden();
            $this->get('/saas/billing/schools/1/invoices/'.$a->id)->assertForbidden();
            foreach ($this->writes($a->id, $payment->id) as [$uri, $input]) {
                $this->post($uri, $input)->assertForbidden();
            }
        }
        $this->assertSame($before, $this->counts());
    }

    public function test_another_schools_invoice_or_payment_is_not_found(): void
    {
        $finance = $this->staff('finance');
        [$a, $b] = $this->issued($finance);
        $this->actingAs($finance)->post('/saas/billing/schools/2/payments', ['amount' => '1', 'received_on' => PlatformBilling::today(), 'bank_reference' => 'BAOBAB-1']);
        $baobabPayment = PlatformPayment::acrossSchools()->where('school_id', 2)->sole();
        $before = $this->counts();
        $rows = $this->snapshot();

        // School B's invoice and payment through School A's URLs.
        $this->get('/saas/billing/schools/1/invoices/'.$b->id)->assertNotFound();
        foreach (['recompute', 'issue', 'discard', 'apply-credit'] as $action) {
            $this->post('/saas/billing/schools/1/invoices/'.$b->id.'/'.$action)->assertNotFound();
        }
        $this->post('/saas/billing/schools/1/invoices/'.$b->id.'/credit-notes', ['idempotency_key' => (string) Str::uuid(), 'amount' => '1', 'reason' => 'Through the wrong school'])->assertNotFound();
        $this->post('/saas/billing/schools/1/payments/'.$baobabPayment->id.'/reverse', ['reason' => 'Through the wrong school'])->assertNotFound();
        // A payment at School A naming School B's invoice is refused.
        $this->post('/saas/billing/schools/1/payments', ['amount' => '1', 'received_on' => PlatformBilling::today(), 'bank_reference' => 'WRONG-1', 'allocations' => [$b->id => '1']])->assertSessionHasErrors('allocations');
        // An unknown school.
        $this->get('/saas/billing/schools/987654')->assertNotFound();
        $this->post('/saas/billing/schools/987654/payments', ['amount' => '1', 'received_on' => PlatformBilling::today(), 'bank_reference' => 'NOWHERE-1'])->assertNotFound();
        $this->assertSame($before, $this->counts());
        $this->assertSame($rows, $this->snapshot());

        // School A's statement shows nothing of School B.
        $this->get('/saas/billing/schools/1')->assertOk()->assertSee($a->reference)->assertDontSee($b->reference)->assertDontSee('BAOBAB-1');
        // The right URLs work.
        $this->get('/saas/billing/schools/2/invoices/'.$b->id)->assertOk()->assertSee($b->reference);
    }
}
