<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Money\BillingOperation;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeeCharge;
use App\Models\Money\FeeItem;
use App\Models\Money\FeeItemConsent;
use App\Models\Money\InvoiceLine;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Money\FeeAccounts;
use App\Services\Money\FeeCharges;
use App\Services\Money\FeeItemConsents;
use App\Services\Money\FeeItemRules;
use App\Services\Money\FeeItems;
use App\Services\Money\FeePaymentReconciliation;
use App\Services\Money\FeeTaxSettings;
use App\Services\ParentPolicyService;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class FeeItemConsentsTest extends TestCase
{
    use UsesTwoSchools,ValidatesOpenApiResponses;

    private User $admin;

    private FeeAccount $account;

    private int $year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDateTime(2026, 6, 15, 12, 0, 0));
        $this->setUpTwoSchools();
        $this->admin = $this->superAdmin();
        $this->year = $this->schoolA->ids['session_year'];
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        SchoolMembership::where('school_id', 1)->update(['starts_on' => '2026-01-01']);
        Enrolment::where('session_year_id', $this->year)->delete();
        foreach (['asha' => 'a', 'ben' => 'a', 'cara' => 'c'] as $name => $section) {
            Enrolment::create(['learner_id' => $this->schoolA->ids[$name], 'class_section_id' => $this->schoolA->ids['section_'.$section],
                'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'legacy_dates' => false, 'reason' => 'Synthetic receipt allocation']);
        }
        foreach (['asha', 'cara'] as $learner) {
            foreach (['farid', 'mona'] as $parent) {
                GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids[$learner], 'guardian_user_id' => $this->schoolA->ids[$parent.'_user']],
                    ['status' => 'active', 'relationship' => 'guardian', 'can_see_fees' => true, 'can_approve' => true]);
            }
        }
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['ben'], 'guardian_user_id' => $this->schoolA->ids['gina_user']],
            ['status' => 'active', 'relationship' => 'guardian', 'can_see_fees' => true, 'can_approve' => true]);
        $this->account = app(FeeAccounts::class)->save($this->admin, $this->family());
    }

    public function test_every_current_payer_approves_before_billing_and_withdrawal_stops_future_charges_only(): void
    {
        $item = $this->item();
        $this->refuses(fn () => app(FeeCharges::class)->preview($this->admin, $this->chargeInput($item)), 422);
        $first = $this->accept($this->parent(), $item);
        $this->assertTrue($first->active);
        $this->assertSame(0, DB::table('ledger_entries')->count());
        $this->refuses(fn () => app(FeeCharges::class)->preview($this->admin, $this->chargeInput($item)), 422);
        $this->accept($this->parent('mona'), $item);
        $charge = $this->charge($item);
        $this->assertSame(1001, $charge->amount_cents);
        $this->assertCount(2, $charge->snapshot['eligibility']['consents']);
        $input = $this->changeInput($this->offer($this->parent(), $item), false);
        app(FeeItemConsents::class)->change($this->parent(), $this->account->id, $item->id, $input);
        $this->refuses(fn () => app(FeeCharges::class)->preview($this->admin, $this->chargeInput($item)), 422);
        $this->assertSame(1, FeeCharge::count());
        $this->assertSame(1001, (int) InvoiceLine::sum('amount_cents'));
        $this->healthy();
    }

    public function test_private_offers_expose_own_share_and_no_other_payer_or_finance_snapshot(): void
    {
        $item = $this->item();
        $response = $this->api($this->parent())->getJson($this->url())->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $response->assertJsonPath('data.items.0.price_cents', 1001)->assertJsonPath('data.items.0.share_cents', 501)->assertJsonPath('data.items.0.revision', 0)->assertJsonPath('data.items.0.learner_id', null);
        foreach (['holder_shares', 'holders', 'terms_hash', 'ledger_account', 'tax_details', $this->parent('mona')->email] as $secret) {
            $response->assertDontSee($secret);
        }
        $other = $this->api($this->parent('mona'))->getJson($this->url())->assertOk();
        $other->assertJsonPath('data.items.0.share_cents', 500);
    }

    public function test_permanent_acceptance_replays_once_and_reused_uuid_cannot_change_its_action(): void
    {
        $item = $this->item();
        $input = $this->changeInput($this->offer($this->parent(), $item));
        $first = app(FeeItemConsents::class)->change($this->parent(), $this->account->id, $item->id, $input);
        $this->assertSame($first->id, app(FeeItemConsents::class)->change($this->parent(), $this->account->id, $item->id, $input)->id);
        $this->assertSame(1, FeeItemConsent::count());
        $this->refuses(fn () => app(FeeItemConsents::class)->change($this->parent(), $this->account->id, $item->id, array_replace($input, ['active' => false, 'revision' => 1])), 409);
        $this->refuses(fn () => app(FeeItemConsents::class)->change($this->parent(), $this->account->id, $item->id, array_replace($input, ['operation_id' => (string) Str::uuid()])), 409);
    }

    public function test_changed_price_invalidates_review_and_requires_renewal_from_all_payers(): void
    {
        $item = $this->item();
        $old = $this->offer($this->parent(), $item);
        $this->accept($this->parent(), $item);
        $this->accept($this->parent('mona'), $item);
        app(FeeItems::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => $item->revision, 'name' => $item->name, 'scope' => 'family', 'amount' => '20.02', 'currency' => 'ZAR', 'tax_treatment' => 'no_vat', 'state' => 'active'], $item->id);
        $this->refuses(fn () => app(FeeItemConsents::class)->change($this->parent(), $this->account->id, $item->id, $this->changeInput($old)), 409);
        $current = $this->offer($this->parent(), $item);
        $this->assertTrue($current['selected']);
        $this->assertFalse($current['current_terms_approved']);
        $this->assertSame(1001, $current['share_cents']);
        $this->refuses(fn () => app(FeeCharges::class)->preview($this->admin, $this->chargeInput($item)), 422);
        $this->accept($this->parent(), $item);
        $this->accept($this->parent('mona'), $item);
        $this->assertSame(2002, $this->charge($item)->amount_cents);
        $this->healthy();
    }

    public function test_agreement_survives_a_new_day_without_daily_reapproval(): void
    {
        $item = $this->item();
        $this->accept($this->parent(), $item);
        $this->accept($this->parent('mona'), $item);
        $this->travelTo(now()->addDay());
        $input = $this->chargeInput($item);
        $input['charged_on'] = '2026-06-16';
        $p = app(FeeCharges::class)->preview($this->admin, $input);
        $this->assertSame(1001, $p['amount_cents']);
    }

    public function test_learner_and_family_approvals_are_distinct_and_cannot_target_another_child(): void
    {
        $family = $this->item();
        $learner = $this->item('learner');
        $page = app(FeeItemConsents::class)->page($this->parent(), $this->account->id, perPage: 50);
        $this->assertCount(3, $page['items']);
        $this->accept($this->parent(), $learner, $this->schoolA->ids['asha']);
        $this->accept($this->parent('mona'), $learner, $this->schoolA->ids['asha']);
        $this->assertSame(1001, $this->charge($learner, $this->schoolA->ids['asha'])->amount_cents);
        $this->refuses(fn () => app(FeeCharges::class)->preview($this->admin, $this->chargeInput($learner, $this->schoolA->ids['cara'])), 422);
        $offer = $this->offer($this->parent(), $learner, $this->schoolA->ids['cara']);
        $input = $this->changeInput($offer);
        $input['learner_id'] = $this->schoolA->ids['ben'];
        $this->refuses(fn () => app(FeeItemConsents::class)->change($this->parent(), $this->account->id, $learner->id, $input), 404);
        $this->refuses(fn () => app(FeeCharges::class)->preview($this->admin, $this->chargeInput($family)), 422);
    }

    public function test_cached_http_replay_checks_revoked_guardian_scope_before_returning_old_private_receipt(): void
    {
        $item = $this->item();
        $parent = $this->parent();
        $input = $this->changeInput($this->offer($parent, $item));
        $key = (string) Str::uuid();
        $this->api($parent, $key)->postJson($this->url($item), $input)->assertOk()->assertJsonPath('data.active', true);
        $this->api($parent, $key)->postJson($this->url($item), $input)->assertOk()->assertHeader('Idempotent-Replayed', 'true');
        GuardianLink::where('guardian_user_id', $parent->id)->where('learner_id', $this->schoolA->ids['cara'])->update(['can_see_fees' => false]);
        $this->api($parent, $key)->postJson($this->url($item), $input)->assertNotFound()->assertHeader('Cache-Control', 'no-store, private');
        $this->refuses(fn () => app(FeeItemConsents::class)->change($parent, $this->account->id, $item->id, $input), 404);
    }

    public function test_fee_visibility_does_not_grant_guardian_approval_authority(): void
    {
        $item = $this->item();
        $parent = $this->parent();
        GuardianLink::where('guardian_user_id', $parent->id)->update(['can_approve' => false]);
        $offer = $this->offer($parent, $item);
        $this->assertFalse($offer['can_approve']);
        $this->assertTrue($offer['available']);
        $this->api($parent)->postJson($this->url($item), $this->changeInput($offer))->assertForbidden();
        $this->assertSame(0, FeeItemConsent::count());
    }

    public function test_current_holder_and_target_ownership_are_required_even_for_historical_statement_users(): void
    {
        $item = $this->item();
        $input = $this->changeInput($this->offer($this->parent(), $item));
        $foreign = app(CurrentSchool::class)->run(2, fn () => FeeAccount::create(['reference' => 'NEF-FOREIGN-OPTIN', 'name' => 'Baobab private finance', 'currency' => 'ZAR', 'active' => true, 'revision' => 1]));
        $this->api($this->parent())->getJson('/api/v2/parent/fees/accounts/'.$foreign->id.'/optional-items')->assertNotFound()->assertDontSee('Baobab');
        $other = $this->parent('gina');
        $this->api($other)->getJson($this->url())->assertNotFound();
        $this->api($other)->postJson($this->url($item), $input)->assertNotFound();
        foreach (['tara', 'asha'] as $name) {
            $user = User::findOrFail($this->schoolA->ids[$name.'_user']);
            $this->api($user)->getJson($this->url())->assertForbidden();
            $this->api($user)->postJson($this->url($item), $input)->assertForbidden();
        }
        $this->api($this->parent())->withHeader('X-School-Id', '2')->getJson($this->url())->assertForbidden();
    }

    public function test_own_prior_approval_can_be_withdrawn_after_item_is_archived(): void
    {
        $item = $this->item();
        $this->accept($this->parent(), $item);
        app(FeeItems::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => $item->revision, 'name' => $item->name, 'scope' => 'family', 'amount' => '10.01', 'currency' => 'ZAR', 'tax_treatment' => 'no_vat', 'state' => 'archived'], $item->id);
        $offer = $this->offer($this->parent(), $item);
        $this->assertTrue($offer['selected']);
        $this->assertFalse($offer['available']);
        $this->assertNull($offer['fingerprint']);
        $event = app(FeeItemConsents::class)->change($this->parent(), $this->account->id, $item->id, $this->changeInput($offer, false));
        $this->assertFalse($event->active);
        $this->assertSame(2, $event->revision);
        $this->assertSame(0, DB::table('ledger_entries')->count());
    }

    public function test_vat_offer_own_tax_matches_the_original_posted_charge(): void
    {
        $item = $this->item();
        app(FeeTaxSettings::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'enabled' => true, 'country_code' => 'ZA', 'tax_registration' => 'PRIVATE-VAT-REG', 'seller_name' => 'Synthetic seller', 'seller_address' => '1 Synthetic Road']);
        app(FeeItems::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => $item->revision, 'name' => $item->name, 'scope' => 'family', 'amount' => '10.01', 'currency' => 'ZAR', 'tax_treatment' => 'vat_inclusive', 'vat_rate' => '15.00', 'state' => 'active'], $item->id);
        $offer = $this->offer($this->parent(), $item);
        $this->assertSame(66, $offer['vat_cents']);
        $this->accept($this->parent(), $item);
        $this->accept($this->parent('mona'), $item);
        $charge = $this->charge($item);
        $this->assertSame(131, $charge->vat_cents);
        $this->healthy();
    }

    public function test_read_only_history_denies_even_cached_acceptance_replay(): void
    {
        $item = $this->item();
        $input = $this->changeInput($this->offer($this->parent(), $item));
        $key = (string) Str::uuid();
        $this->api($this->parent(), $key)->postJson($this->url($item), $input)->assertOk();
        // Keep financial history so the disabled module retains its read-only surface.
        $this->accept($this->parent('mona'), $item);
        $this->charge($item);
        app(ModuleGate::class)->change('fees', false, $this->admin);
        $this->api($this->parent())->getJson($this->url())->assertOk()->assertJsonPath('data.writable', false);
        $this->api($this->parent(), $key)->postJson($this->url($item), $input)->assertForbidden();
        $this->assertSame(2, FeeItemConsent::count());
    }

    public function test_agreed_item_identity_mode_and_history_are_retained_in_app_and_sql(): void
    {
        $item = $this->item();
        $event = $this->accept($this->parent(), $item);
        $this->refuses(fn () => app(FeeItemRules::class)->save($this->admin, $item->id, ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'mode' => 'compulsory', 'target_scope' => 'all']), 422);
        $this->refuses(fn () => app(FeeItems::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => $item->revision, 'name' => $item->name, 'scope' => 'learner', 'amount' => '10.01', 'currency' => 'ZAR', 'tax_treatment' => 'no_vat', 'state' => 'active'], $item->id), 422);
        foreach ([fn () => DB::table('fee_item_consents')->where('id', $event->id)->delete(), fn () => DB::table('fee_items')->where('id', $item->id)->update(['scope' => 'learner']), fn () => DB::table('fee_item_eligibility_rules')->where('fee_item_id', $item->id)->update(['mode' => 'manual', 'revision' => 2])] as $write) {
            try {
                $write();
                $this->fail('Retain original agreement');
            } catch (QueryException $e) {
                $this->assertSame('45000', $e->errorInfo[0]);
            }
        }
        $m = require database_path('migrations/2026_10_27_920000_add_fee_item_consents.php');
        try {
            $m->down();
            $this->fail('Retain consent history');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Retain parent opt-in', $e->getMessage());
        }
    }

    public function test_yearly_opt_in_cannot_be_rebilled_using_a_different_occurrence(): void
    {
        $item = $this->item('family', 'once_per_year');
        $offer = $this->offer($this->parent(), $item);
        $this->assertSame('once_per_year', $offer['billing']);
        $first = $this->accept($this->parent(), $item);
        $this->assertSame('once_per_year', $first->snapshot['billing']);
        $this->accept($this->parent('mona'), $item);
        $this->charge($item);
        $this->refuses(fn () => app(FeeCharges::class)->preview($this->admin, $this->chargeInput($item)), 409);
        $this->assertSame(1, FeeCharge::count());
        $this->healthy();
    }

    public function test_billing_frequency_is_explicit_and_cannot_be_removed_from_an_opt_in_rule(): void
    {
        $item = $this->item();
        $this->refuses(fn () => app(FeeItemRules::class)->save($this->admin, $item->id, ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'mode' => 'opt_in', 'target_scope' => 'all']), 422);
        try {
            DB::table('fee_item_eligibility_rules')->where('fee_item_id', $item->id)->update(['opt_in_billing' => null, 'revision' => 2]);
            $this->fail('Choose billing frequency explicitly');
        } catch (QueryException $e) {
            $this->assertStringContainsString('fee_item_eligibility_shape', $e->getMessage());
        }
    }

    public function test_unchanged_saves_and_key_rotation_do_not_cancel_existing_financial_approval(): void
    {
        $item = $this->item();
        $this->accept($this->parent(), $item);
        $this->accept($this->parent('mona'), $item);
        app(FeeItems::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => $item->revision, 'name' => $item->name, 'scope' => 'family', 'amount' => '10.01', 'currency' => 'ZAR', 'tax_treatment' => 'no_vat', 'state' => 'active'], $item->id);
        app(FeeItemRules::class)->save($this->admin, $item->id, ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'mode' => 'opt_in', 'opt_in_billing' => 'per_occurrence', 'target_scope' => 'all']);
        app(FeeAccounts::class)->save($this->admin, $this->family(['revision' => 1, 'name' => 'Synthetic renamed family']), $this->account->id);
        $this->assertTrue($this->offer($this->parent(), $item)['current_terms_approved']);
        $old = config('app.key');
        config(['app.key' => 'base64:'.base64_encode(str_repeat('x', 32))]);
        try {
            $this->assertTrue($this->offer($this->parent(), $item)['current_terms_approved']);
            $p = app(FeeCharges::class)->preview($this->admin, $this->chargeInput($item));
            $this->assertSame(1001, $p['amount_cents']);
        } finally {
            config(['app.key' => $old]);
        }
    }

    public function test_payer_share_changes_need_renewal_without_deleting_earlier_agreements(): void
    {
        $item = $this->item();
        $this->accept($this->parent(), $item);
        $this->accept($this->parent('mona'), $item);
        app(FeeAccounts::class)->save($this->admin, $this->family(['revision' => 1, 'holders' => [['user_id' => $this->parent()->id, 'share_percent' => '75'], ['user_id' => $this->parent('mona')->id, 'share_percent' => '25']]]), $this->account->id);
        $offer = $this->offer($this->parent(), $item);
        $this->assertSame(751, $offer['share_cents']);
        $this->assertFalse($offer['current_terms_approved']);
        $this->assertSame(2, FeeItemConsent::count());
        $this->refuses(fn () => app(FeeCharges::class)->preview($this->admin, $this->chargeInput($item)), 422);
    }

    public function test_late_approval_failure_rolls_back_event_operation_and_audit_and_retry_is_safe(): void
    {
        $item = $this->item();
        $input = $this->changeInput($this->offer($this->parent(), $item));
        $counts = fn () => array_map(fn ($t) => DB::table($t)->count(), ['fee_item_consents', 'billing_operations', 'audit_log', 'ledger_entries']);
        $before = $counts();
        $original = BillingOperation::getEventDispatcher();
        $this->assertNotNull($original);
        BillingOperation::setEventDispatcher(clone $original);
        BillingOperation::creating(function (): void {
            throw new \RuntimeException('Synthetic late agreement failure');
        });
        try {
            app(FeeItemConsents::class)->change($this->parent(), $this->account->id, $item->id, $input);
            $this->fail('Expected rollback');
        } catch (\RuntimeException $e) {
            $this->assertSame('Synthetic late agreement failure', $e->getMessage());
        } finally {
            BillingOperation::setEventDispatcher($original);
        }
        $this->assertSame($before, $counts());
        $event = app(FeeItemConsents::class)->change($this->parent(), $this->account->id, $item->id, $input);
        $this->assertSame(1, $event->revision);
        $this->assertSame(1, FeeItemConsent::count());
    }

    private function item(string $scope = 'family', string $billing = 'per_occurrence'): FeeItem
    {
        $item = app(FeeItems::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic optional excursion', 'scope' => $scope, 'amount' => '10.01', 'currency' => 'ZAR', 'tax_treatment' => 'no_vat', 'state' => 'active']);
        app(FeeItemRules::class)->save($this->admin, $item->id, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'mode' => 'opt_in', 'opt_in_billing' => $billing, 'target_scope' => 'all']);

        return $item;
    }

    private function parent(string $name = 'farid'): User
    {
        return User::findOrFail($this->schoolA->ids[$name.'_user']);
    }

    private function offer(User $parent, FeeItem $item, ?int $learner = null): array
    {
        $page = app(FeeItemConsents::class)->page($parent, $this->account->id, perPage: 50);
        $row = collect($page['items'])->first(fn ($r) => $r['fee_item_id'] === $item->id && $r['learner_id'] === $learner);
        $this->assertNotNull($row);

        return $row;
    }

    private function changeInput(array $offer, bool $active = true): array
    {
        return ['operation_id' => (string) Str::uuid(), 'session_year_id' => $this->year, 'learner_id' => $offer['learner_id'], 'revision' => $offer['revision'], 'active' => $active, 'fingerprint' => $offer['fingerprint'], 'confirmed' => true];
    }

    private function accept(User $actor, FeeItem $item, ?int $learner = null): FeeItemConsent
    {
        return app(FeeItemConsents::class)->change($actor, $this->account->id, $item->id, $this->changeInput($this->offer($actor, $item, $learner)));
    }

    private function chargeInput(FeeItem $item, ?int $learner = null): array
    {
        return ['operation_id' => (string) Str::uuid(), 'fee_item_id' => $item->id, 'fee_account_id' => $this->account->id, 'session_year_id' => $this->year, 'learner_id' => $learner, 'charged_on' => '2026-06-15', 'due_on' => '2026-07-01', 'source_reference' => 'OPT-IN-'.Str::uuid()];
    }

    private function charge(FeeItem $item, ?int $learner = null): FeeCharge
    {
        $input = $this->chargeInput($item, $learner);
        $p = app(FeeCharges::class)->preview($this->admin, $input);

        return app(FeeCharges::class)->post($this->admin, $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true]);
    }

    private function url(?FeeItem $item = null): string
    {
        return '/api/v2/parent/fees/accounts/'.$this->account->id.'/optional-items'.($item === null ? '' : '/'.$item->id.'/consents');
    }

    private function api(User $actor, ?string $key = null): self
    {
        $this->app['auth']->forgetGuards();
        ParentPolicyService::acceptCurrent($actor);

        return $this->flushHeaders()->withToken($actor->createToken('synthetic-opt-in')->plainTextToken)->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $actor->id, 'Idempotency-Key' => $key ?? (string) Str::uuid()]);
    }

    private function healthy(): void
    {
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
        $this->assertTrue(FeePaymentReconciliation::healthy(app(FeePaymentReconciliation::class)->inspect()));
    }

    private function family(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic split family', 'currency' => 'ZAR', 'session_year_id' => $this->year,
            'learner_ids' => [$this->schoolA->ids['asha'], $this->schoolA->ids['cara']], 'holders' => [['user_id' => $this->schoolA->ids['farid_user'], 'share_percent' => '50'], ['user_id' => $this->schoolA->ids['mona_user'], 'share_percent' => '50']]], $changes);
    }

    private function refuses(callable $call, int $status): void
    {
        try {
            $call();
            $this->fail('Expected financial operation refusal');
        } catch (ValidationException) {
            $this->assertSame(422, $status);
        } catch (ModelNotFoundException) {
            $this->assertSame(404, $status);
        } catch (HttpExceptionInterface $e) {
            $this->assertSame($status, $e->getStatusCode());
        }
    }
}
