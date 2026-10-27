<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeeCharge;
use App\Models\Money\FeeItem;
use App\Models\Money\FeeItemBatch;
use App\Models\Money\FeeItemEligibilityRule;
use App\Models\Money\InvoiceLine;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Money\FeeAccounts;
use App\Services\Money\FeeCharges;
use App\Services\Money\FeeItemBatches;
use App\Services\Money\FeeItemRules;
use App\Services\Money\FeeItems;
use App\Services\Money\FeePaymentReconciliation;
use App\Services\Money\FeeTaxSettings;
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
use Tests\TestCase;

class FeeItemEligibilityTest extends TestCase
{
    use UsesTwoSchools;

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

    public function test_compulsory_family_batch_previews_exceptions_posts_once_and_keeps_occurrence_identity(): void
    {
        $item = $this->item();
        $this->rule($item);
        $input = $this->input($item);
        $p = app(FeeItemBatches::class)->preview($this->admin, $input);
        $this->assertCount(1, $p['charges']);
        $this->assertCount(1, $p['exceptions']);
        $this->assertSame(1001, $p['amount_cents']);
        $this->assertSame(0, DB::table('ledger_entries')->count());
        $post = $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true];
        $this->refuses(fn () => app(FeeItemBatches::class)->post($this->admin, $post), 422);
        $post['exclude_exceptions'] = true;
        $batch = app(FeeItemBatches::class)->post($this->admin, $post);
        $this->assertSame($batch->id, app(FeeItemBatches::class)->post($this->admin, $post)->id);
        $this->assertSame(1, FeeCharge::count());
        $this->assertSame(2, InvoiceLine::count());
        $p2 = app(FeeItemBatches::class)->preview($this->admin, $this->input($item));
        $this->assertSame([], $p2['charges']);
        $this->assertCount(1, $p2['skipped']);
        $this->assertStringNotContainsString('PRIVATE-OCCURRENCE', DB::table('fee_item_batches')->value('snapshot'));
        $this->healthy();
    }

    public function test_phase_rules_apply_to_batches_and_manual_charge_path_without_crossing_other_learners(): void
    {
        foreach (['a' => '1', 'c' => '7'] as $section => $grade) {
            $class = DB::table('class_sections')->where('id', $this->schoolA->ids['section_'.$section])->value('class_id');
            DB::table('classes')->where('id', $class)->where('school_id', 1)->update(['grade_level_id' => DB::table('grade_levels')->where('code', $grade)->value('id')]);
        }
        $item = $this->item('learner');
        $this->rule($item, ['applies_to' => [['field' => 'phase', 'values' => ['Foundation']]]]);
        $p = app(FeeItemBatches::class)->preview($this->admin, $this->input($item));
        $this->assertCount(1, $p['charges']);
        $this->assertSame($this->schoolA->ids['asha'], $p['charges'][0]['input']['learner_id']);
        $this->assertSame(1, $p['excluded_by_rule']);
        $one = $this->oneOff($item, $this->schoolA->ids['cara']);
        $this->refuses(fn () => app(FeeCharges::class)->preview($this->admin, $one), 422);
        $this->assertSame(0, FeeCharge::count());
    }

    public function test_dated_activity_memberships_deduplicate_family_and_changes_invalidate_review(): void
    {
        [$group,$a] = $this->activity();
        $b = $this->activityMember($group, $this->schoolA->ids['cara']);
        $item = $this->item();
        $this->rule($item, ['mode' => 'membership', 'activity_group_id' => $group->id]);
        $input = $this->input($item);
        $p = app(FeeItemBatches::class)->preview($this->admin, $input);
        $this->assertCount(1, $p['charges']);
        $this->assertSame(2, count($p['eligibility']['matched_learner_ids']));
        $b->update(['active' => false, 'revision' => 2]);
        $this->refuses(fn () => app(FeeItemBatches::class)->post($this->admin, $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true]), 409);
        $this->assertSame(0, FeeCharge::count());
        $p = app(FeeItemBatches::class)->preview($this->admin, $input);
        $batch = app(FeeItemBatches::class)->post($this->admin, $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true]);
        $this->assertCount(1, $batch->charge_ids);
        $a->update(['active' => false, 'revision' => 2]);
        $this->assertSame([], app(FeeItemBatches::class)->preview($this->admin, $this->input($item, ['source_reference' => 'NEXT-OCCURRENCE']))['charges']);
        $this->healthy();
    }

    public function test_group_and_membership_dates_and_inactive_group_never_create_undated_charges(): void
    {
        [$group,$member] = $this->activity();
        $member->update(['starts_on' => '2026-07-01']);
        $item = $this->item('learner');
        $this->rule($item, ['mode' => 'membership', 'activity_group_id' => $group->id]);
        $this->assertSame([], app(FeeItemBatches::class)->preview($this->admin, $this->input($item))['charges']);
        $member->update(['starts_on' => '2026-01-01', 'ends_on' => '2026-06-14']);
        $this->assertSame([], app(FeeItemBatches::class)->preview($this->admin, $this->input($item))['charges']);
        $member->update(['ends_on' => '2026-12-31']);
        $group->update(['active' => false]);
        $this->assertSame([], app(FeeItemBatches::class)->preview($this->admin, $this->input($item))['charges']);
        $this->refuses(fn () => app(FeeCharges::class)->preview($this->admin, $this->oneOff($item, $this->schoolA->ids['asha'])), 422);
    }

    public function test_teaching_group_and_subject_rules_use_real_dated_memberships(): void
    {
        $grade = (int) DB::table('classes')->where('id', $this->schoolA->ids['class_a'])->value('grade_level_id');
        if ($grade === 0) {
            $grade = (int) DB::table('grade_levels')->value('id');
        }
        $group = TeachingGroup::create(['subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => $grade, 'session_year_id' => $this->year, 'name' => 'Synthetic billable subject', 'active' => true]);
        $member = TeachingGroupMembership::create(['teaching_group_id' => $group->id, 'learner_id' => $this->schoolA->ids['asha'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-06-15', 'reason' => 'Synthetic dated subject membership']);
        foreach (['teaching_group_id' => $group->id, 'subject_id' => $this->schoolA->ids['maths']] as $column => $id) {
            $item = $this->item('learner');
            $this->rule($item, ['mode' => 'membership', $column => $id]);
            $p = app(FeeItemBatches::class)->preview($this->admin, $this->input($item));
            $this->assertCount(1, $p['charges']);
            $this->assertSame($this->schoolA->ids['asha'], $p['charges'][0]['input']['learner_id']);
        }
        $member->update(['ends_on' => '2026-06-14']);
        $this->assertSame([], app(FeeItemBatches::class)->preview($this->admin, $this->input($item))['charges']);
    }

    public function test_changed_price_and_rule_revision_require_a_new_review(): void
    {
        $item = $this->item();
        $rule = $this->rule($item);
        $input = $this->input($item);
        $p = app(FeeItemBatches::class)->preview($this->admin, $input);
        $this->rule($item, ['revision' => $rule->revision]);
        $this->refuses(fn () => app(FeeItemBatches::class)->post($this->admin, $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true, 'exclude_exceptions' => true]), 409);
        $p = app(FeeItemBatches::class)->preview($this->admin, $input);
        app(FeeItems::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => $item->revision, 'name' => $item->name, 'scope' => 'family', 'amount' => '20.02', 'currency' => 'ZAR', 'tax_treatment' => 'no_vat', 'state' => 'active'], $item->id);
        $this->refuses(fn () => app(FeeItemBatches::class)->post($this->admin, $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true, 'exclude_exceptions' => true]), 409);
        $this->assertSame(0, FeeCharge::count());
    }

    public function test_late_batch_failure_rolls_back_every_invoice_and_permanent_receipt(): void
    {
        $item = $this->item('learner');
        $this->rule($item);
        $input = $this->input($item);
        $p = app(FeeItemBatches::class)->preview($this->admin, $input);
        $this->assertCount(2, $p['charges']);
        $post = $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true, 'exclude_exceptions' => true];
        $counts = fn () => array_map(fn ($t) => DB::table($t)->count(), ['ledger_entries', 'ledger_postings', 'invoices', 'invoice_lines', 'fee_charges', 'billing_operations', 'audit_log', 'domain_events', 'fee_item_batches']);
        $before = $counts();
        $original = FeeCharge::getEventDispatcher();
        $this->assertNotNull($original);
        FeeCharge::setEventDispatcher(clone $original);
        $writes = 0;
        FeeCharge::created(function () use (&$writes): void {
            if (++$writes === 2) {
                throw new \RuntimeException('Synthetic later batch failure');
            }
        });
        try {
            app(FeeItemBatches::class)->post($this->admin, $post);
            $this->fail('Expected rollback');
        } catch (\RuntimeException $e) {
            $this->assertSame('Synthetic later batch failure', $e->getMessage());
        } finally {
            FeeCharge::setEventDispatcher($original);
        }
        $this->assertSame($before, $counts());
        $batch = app(FeeItemBatches::class)->post($this->admin, $post);
        $this->assertCount(2, $batch->charge_ids);
        $this->healthy();
    }

    public function test_wrong_role_real_foreign_sources_and_rule_bindings_are_refused(): void
    {
        $item = $this->item();
        $ruleInput = $this->ruleInput();
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->refuses(fn () => app(FeeItemRules::class)->save($teacher, $item->id, $ruleInput), 403);
        $this->refuses(fn () => app(FeeItemBatches::class)->preview($teacher, $this->input($item)), 403);
        $foreign = app(CurrentSchool::class)->run(2, fn () => ActivityGroup::create(['session_year_id' => $this->schoolB->ids['session_year'], 'name' => 'Baobab private billable route', 'discipline' => 'Transport', 'season' => '2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'revision' => 1]));
        $this->refuses(fn () => app(FeeItemRules::class)->save($this->admin, $item->id, array_replace($ruleInput, ['mode' => 'membership', 'activity_group_id' => $foreign->id])), 404);
        $bind = ['expected_school_id' => 1, 'expected_account_id' => $this->admin->id];
        $this->actingAs($this->admin)->post('/fee-items/'.$item->id.'/rules', $ruleInput + array_replace($bind, ['expected_school_id' => 2]))->assertForbidden();
        $this->post('/fee-items/'.$item->id.'/rules', $ruleInput + array_replace($bind, ['expected_account_id' => 999999]))->assertForbidden();
        $this->get('/fee-items/'.$item->id.'/rules')->assertOk()->assertDontSee('Baobab private billable route');
        $this->actingAs($teacher)->get('/fee-item-batches')->assertForbidden();
        $this->get('/fee-items/'.$item->id.'/rules')->assertForbidden();
    }

    public function test_original_batch_is_immutable_and_disabled_fees_retain_history(): void
    {
        $item = $this->item();
        $this->rule($item);
        $input = $this->input($item);
        $p = app(FeeItemBatches::class)->preview($this->admin, $input);
        $post = $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true, 'exclude_exceptions' => true];
        $batch = app(FeeItemBatches::class)->post($this->admin, $post);
        try {
            DB::table('fee_item_batches')->where('id', $batch->id)->delete();
            $this->fail('Retain immutable batch');
        } catch (QueryException $e) {
            $this->assertSame('45000', $e->errorInfo[0]);
        }
        app(ModuleGate::class)->change('fees', false, $this->admin);
        $this->actingAs($this->admin)->get('/fee-item-batches/'.$batch->id)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->post('/fee-item-batches/new', $post + ['expected_school_id' => 1, 'expected_account_id' => $this->admin->id])->assertForbidden();
        $this->get('/fee-items/'.$item->id.'/rules')->assertOk()->assertSee('read-only');
    }

    public function test_private_panel_rule_and_batch_review_post_replay(): void
    {
        $item = $this->item();
        $bind = ['expected_school_id' => 1, 'expected_account_id' => $this->admin->id];
        $rule = $this->ruleInput();
        $this->actingAs($this->admin)->post('/fee-items/'.$item->id.'/rules', $rule + $bind)->assertStatus(303);
        $this->post('/fee-items/'.$item->id.'/rules', $rule + $bind)->assertStatus(303);
        $this->get('/fee-item-batches/new?fee_item_id='.$item->id)->assertOk();
        $input = $this->input($item);
        $this->post('/fee-item-batches/review', $input + $bind)->assertOk()->assertSee('Exceptions requiring attention')->assertSee('ZAR 10.01')->assertHeader('Cache-Control', 'no-store, private');
        $p = app(FeeItemBatches::class)->preview($this->admin, $input);
        $post = $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true, 'exclude_exceptions' => true] + $bind;
        $this->post('/fee-item-batches/new', $post)->assertStatus(303);
        $this->post('/fee-item-batches/new', $post)->assertStatus(303);
        $this->assertSame(1, FeeItemBatch::count());
        $batch = FeeItemBatch::sole();
        $this->get('/fee-item-batches/'.$batch->id)->assertOk()->assertSee('Posted item invoices');
        $this->post('/fee-items/'.$item->id.'/rules', $this->ruleInput(['revision' => 1, 'rule_rows' => [['field' => '', 'values' => 'unexpected']]]) + $bind)->assertStatus(422)->assertSessionMissing('_old_input');
        $this->post('/fee-items/'.$item->id.'/rules', $this->ruleInput(['revision' => 1, 'applies_to' => [['field' => 'PRIVATE-INVALID-RULE', 'values' => ['PRIVATE-VALUE']]]]) + $bind)->assertStatus(422)->assertSessionMissing('_old_input');
        $this->post('/fee-item-batches/review', array_replace($input, ['source_reference' => str_repeat('ß', 60)]) + $bind)->assertStatus(422)->assertSessionMissing('_old_input');
    }

    public function test_actual_batch_and_item_ids_are_hidden_from_another_authorised_school(): void
    {
        $item = $this->item();
        $this->rule($item);
        $input = $this->input($item);
        $p = app(FeeItemBatches::class)->preview($this->admin, $input);
        $batch = app(FeeItemBatches::class)->post($this->admin, $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true, 'exclude_exceptions' => true]);
        SchoolMembership::create(['school_id' => 2, 'user_id' => $this->admin->id, 'role' => 'Super Admin', 'status' => 'active', 'starts_on' => '2026-01-01']);
        app(CurrentSchool::class)->run(2, function (): void {
            $this->admin->fresh()->assignRole('Super Admin');
        });
        $this->actingAs($this->admin)->withSession([config('tenancy.session_key') => 2])->get('/fee-item-batches/'.$batch->id)->assertNotFound()->assertDontSee('Synthetic automatic item');
        $this->withSession([config('tenancy.session_key') => 2])->get('/fee-items/'.$item->id.'/rules')->assertNotFound();
    }

    public function test_changed_guardian_rights_and_unconfirmed_dates_are_explicit_exceptions(): void
    {
        $item = $this->item();
        $this->rule($item);
        $input = $this->input($item);
        $p = app(FeeItemBatches::class)->preview($this->admin, $input);
        GuardianLink::where('guardian_user_id', $this->schoolA->ids['farid_user'])->where('learner_id', $this->schoolA->ids['cara'])->update(['can_see_fees' => false]);
        $this->refuses(fn () => app(FeeItemBatches::class)->post($this->admin, $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true, 'exclude_exceptions' => true]), 409);
        $this->assertSame(0, FeeCharge::count());
        Enrolment::where('learner_id', $this->schoolA->ids['ben'])->update(['legacy_dates' => true]);
        $review = app(FeeItemBatches::class)->preview($this->admin, $input);
        $issue = collect($review['exceptions'])->firstWhere('learner_id', $this->schoolA->ids['ben']);
        $this->assertNotNull($issue);
        $this->assertStringContainsString('dated enrolment', $issue['message']);
    }

    public function test_taxable_automatic_items_keep_the_same_original_vat_ledger_and_clearing_stays_zero(): void
    {
        $item = $this->item();
        $this->rule($item);
        app(FeeTaxSettings::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'enabled' => true, 'country_code' => 'ZA', 'tax_registration' => 'SYNTHETIC-VAT', 'seller_name' => 'Synthetic seller', 'seller_address' => '1 Synthetic Road']);
        app(FeeItems::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => $item->revision, 'name' => $item->name, 'scope' => 'family', 'amount' => '10.01', 'currency' => 'ZAR', 'tax_treatment' => 'vat_inclusive', 'vat_rate' => '15.00', 'state' => 'active'], $item->id);
        $input = $this->input($item);
        $p = app(FeeItemBatches::class)->preview($this->admin, $input);
        $this->assertSame(131, $p['charges'][0]['preview']['vat_cents']);
        app(FeeItemBatches::class)->post($this->admin, $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true, 'exclude_exceptions' => true]);
        $this->assertSame(870, (int) DB::table('ledger_accounts')->where('school_id', 1)->where('type', 'fee_income')->sum('balance_cents'));
        $this->assertSame(131, (int) DB::table('ledger_accounts')->where('school_id', 1)->where('type', 'fee_vat')->sum('balance_cents'));
        $this->assertSame(0, (int) DB::table('ledger_accounts')->where('school_id', 1)->whereIn('type', ['clearing', 'due_to_school'])->sum('balance_cents'));
        $this->healthy();
    }

    private function item(string $scope = 'family'): FeeItem
    {
        return app(FeeItems::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic automatic item', 'scope' => $scope, 'amount' => '10.01', 'currency' => 'ZAR', 'tax_treatment' => 'no_vat', 'state' => 'active']);
    }

    private function ruleInput(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'mode' => 'compulsory', 'target_scope' => 'all', 'applies_to' => []], $changes);
    }

    private function rule(FeeItem $item, array $changes = []): FeeItemEligibilityRule
    {
        return app(FeeItemRules::class)->save($this->admin, $item->id, $this->ruleInput($changes));
    }

    private function input(FeeItem $item, array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'fee_item_id' => $item->id, 'session_year_id' => $this->year, 'charged_on' => '2026-06-15', 'due_on' => '2026-07-01', 'source_reference' => 'PRIVATE-OCCURRENCE'], $changes);
    }

    private function oneOff(FeeItem $item, int $learner): array
    {
        return $this->input($item) + ['fee_account_id' => $this->account->id, 'learner_id' => $learner];
    }

    private function activity(): array
    {
        $group = ActivityGroup::create(['session_year_id' => $this->year, 'name' => 'Synthetic transport route', 'discipline' => 'Transport', 'season' => '2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'revision' => 1]);

        return [$group, $this->activityMember($group, $this->schoolA->ids['asha'])];
    }

    private function activityMember(ActivityGroup $group, int $learner): ActivityMembership
    {
        return ActivityMembership::create(['activity_group_id' => $group->id, 'learner_id' => $learner, 'role' => 'member', 'active' => true, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'revision' => 1, 'reason' => 'Synthetic dated billable membership']);
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
