<?php

declare(strict_types=1);

namespace Tests\Feature\Pastoral;

use App\Models\Pastoral\SupportAssignment;
use App\Models\Pastoral\SupportPlan;
use App\Models\Pastoral\SupportPlanVersion;
use App\Models\Pastoral\SupportReview;
use App\Models\People\Enrolment;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Academics\TeachingGroups;
use App\Services\Pastoral\SupportPlans;
use App\Services\Pastoral\SupportQueries;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class SupportPlansTest extends TestCase
{
    use UsesTwoSchools;

    private User $professional;

    private int $learner;

    private SupportAssignment $appointment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(CarbonImmutable::parse('2026-02-10 10:00:00', 'Africa/Johannesburg'));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        config(['modules.support_plans' => ['always_on' => true]]);
        $this->learner = $this->schoolA->ids['asha'];
        Enrolment::create(['learner_id' => $this->learner, 'class_section_id' => $this->schoolA->ids['section_a'], 'session_year_id' => $this->schoolA->ids['session_year'], 'starts_on' => '2026-01-01', 'ends_on' => null, 'legacy_dates' => false]);
        $this->professional = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->professional->syncRoles(['Counsellor']);
        $year = $this->schoolA->ids['session_year'];
        SessionYear::whereKey($year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        app(TeachingGroups::class)->dated($this->superAdmin(), 'leadership', ['user_id' => $this->superAdmin()->id, 'session_year_id' => $year, 'kind' => 'principal', 'grade_level_id' => null, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic support principal']);
        $this->appointment = app(SupportPlans::class)->assign($this->superAdmin(), ['operation_id' => (string) Str::uuid(), 'learner_id' => $this->learner, 'user_id' => $this->professional->id, 'starts_on' => '2026-02-10', 'ends_on' => '2026-12-31', 'reason' => 'Private appointment rationale']);
    }

    private function body(): array
    {
        return ['operation_id' => (string) Str::uuid(), 'learner_id' => $this->learner, 'started_on' => '2026-02-01', 'review_due_on' => '2026-03-01', 'sias_stage' => 'SNA1', 'needs' => 'Private assessed support needs', 'accommodations' => 'Private full accommodation plan'];
    }

    private function plan(): SupportPlan
    {
        return app(SupportPlans::class)->save($this->professional, $this->body());
    }

    private function refused(callable $fn, int $status = 403): void
    {
        try {
            $fn();
            $this->fail('Expected a support refusal.');
        } catch (HttpException $e) {
            $this->assertSame($status, $e->getStatusCode());
        }
    }

    public function test_assigned_professional_saves_encrypted_plan_and_versions_with_exact_replay(): void
    {
        $body = $this->body();
        $s = app(SupportPlans::class);
        $plan = $s->save($this->professional, $body);
        $this->assertSame($plan->id, $s->save($this->professional, $body)->id);
        $this->assertSame(1, SupportPlan::count());
        $this->assertSame(1, SupportPlanVersion::count());
        $this->assertStringNotContainsString('Private assessed', DB::table('support_plans')->value('contents'));
        $this->assertStringNotContainsString('Private assessed', DB::table('support_plan_versions')->value('snapshot'));
        $this->assertStringNotContainsString('Private appointment', DB::table('support_assignments')->value('reason'));
        $this->assertStringNotContainsString('Private assessed', json_encode($plan));
        $this->assertSame('Private assessed support needs', app(SupportQueries::class)->plan($this->professional, $plan->id)['needs']);
        $next = $this->body();
        $next['revision'] = 1;
        $next['needs'] = 'Changed private needs';
        $s->save($this->professional, $next, $plan->id);
        $this->assertSame(2, SupportPlanVersion::count());
        $this->assertSame('Private assessed support needs', SupportPlanVersion::orderBy('revision')->firstOrFail()->snapshot['contents']['needs']);
        $this->refused(fn () => $s->save($this->professional, array_replace($body, ['needs' => 'Different']), null), 409);
        $this->assertStringNotContainsString('Private assessed', json_encode(DB::table('audit_log')->get()));
    }

    public function test_principal_only_manages_metadata_and_generic_admin_cannot_read_body(): void
    {
        $plan = $this->plan();
        $q = app(SupportQueries::class);
        $meta = $q->assignments($this->superAdmin());
        $this->assertSame($this->learner, $meta['items'][0]['learner_id']);
        $this->assertStringNotContainsString('Private', json_encode($meta));
        $this->refused(fn () => $q->plan($this->superAdmin(), $plan->id));
        $this->refused(fn () => app(SupportPlans::class)->save($this->superAdmin(), $this->body()));
        $teacher = User::findOrFail($this->schoolA->ids['theo_user']);
        $this->refused(fn () => $q->plan($teacher, $plan->id));
        $this->refused(fn () => $q->cases(User::findOrFail($this->schoolA->ids['farid_user'])));
    }

    public function test_unassigned_professional_and_other_school_cannot_discover_or_read_case(): void
    {
        $plan = $this->plan();
        $other = User::findOrFail($this->schoolA->ids['theo_user']);
        $other->syncRoles(['Safeguarding']);
        $this->assertSame([], app(SupportQueries::class)->cases($other)['items']);
        try {
            app(SupportQueries::class)->plan($other, $plan->id);
            $this->fail('An unassigned professional saw a plan.');
        } catch (ModelNotFoundException) {
            $this->assertTrue(true);
        }
        app(CurrentSchool::class)->run(2, function () use ($plan) {
            $actor = User::findOrFail($this->schoolB->ids['tara_user']);
            $actor->syncRoles(['Counsellor']);
            try {
                app(SupportQueries::class)->plan($actor, $plan->id);
                $this->fail('A foreign case was visible.');
            } catch (ModelNotFoundException) {
                $this->assertTrue(true);
            }
        });
    }

    public function test_teacher_receives_only_explicit_frozen_accommodations_and_changes_withdraw_it(): void
    {
        $plan = $this->plan();
        $teacher = User::findOrFail($this->schoolA->ids['theo_user']);
        $s = app(SupportPlans::class);
        $row = $s->disclose($this->professional, $plan->id, ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'user_id' => $teacher->id, 'expires_on' => '2026-04-01', 'accommodations' => 'Approved quiet seating', 'confirmation' => true, 'reason' => 'Private disclosure rationale']);
        $view = app(SupportQueries::class)->disclosure($teacher, $row->id);
        $this->assertSame('Approved quiet seating', $view['accommodations']);
        $this->assertArrayNotHasKey('needs', $view);
        $this->assertArrayNotHasKey('sias_stage', $view);
        $this->assertStringNotContainsString('Private', json_encode($view));
        $this->assertStringNotContainsString('Approved quiet', DB::table('support_disclosures')->value('accommodations'));
        $next = $this->body();
        $next['revision'] = 1;
        $next['accommodations'] = 'Changed private accommodation plan';
        $s->save($this->professional, $next, $plan->id);
        $this->assertFalse($row->fresh()->active);
        $this->assertSame([], app(SupportQueries::class)->teacher($teacher)['items']);
        $this->assertSame(2, $plan->fresh()->accommodation_revision);
    }

    public function test_reviews_are_append_only_and_close_revokes_disclosures_without_erasing_history(): void
    {
        $s = app(SupportPlans::class);
        $plan = $this->plan();
        $data = ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'reviewed_on' => '2026-02-10', 'next_review_on' => '2026-03-10', 'outcome' => 'Private review outcome'];
        $disclosed = $s->disclose($this->professional, $plan->id, ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'user_id' => $this->schoolA->ids['theo_user'], 'expires_on' => '2026-04-01', 'accommodations' => 'Approved instructions', 'confirmation' => true, 'reason' => 'Limited classroom support']);
        $review = $s->review($this->professional, $plan->id, $data);
        $this->assertSame($review->id, $s->review($this->professional, $plan->id, $data)->id);
        $this->assertSame(1, SupportReview::count());
        $this->assertSame(2, SupportPlanVersion::count());
        $s->close($this->professional, $plan->id, ['operation_id' => (string) Str::uuid(), 'revision' => 2, 'reason' => 'Private closure rationale']);
        $this->assertSame('closed', $plan->fresh()->state);
        $this->assertFalse($disclosed->fresh()->active);
        $this->assertSame(2, SupportReview::count());
        $this->assertSame(3, SupportPlanVersion::count());
        $this->assertStringNotContainsString('Private review', DB::table('support_reviews')->orderBy('id')->value('outcome'));
        $this->refused(fn () => $s->review($this->professional, $plan->id, array_replace($data, ['operation_id' => (string) Str::uuid(), 'revision' => 3])), 409);
    }

    public function test_revoked_caseload_module_role_and_operator_status_block_completed_replays(): void
    {
        $body = $this->body();
        $s = app(SupportPlans::class);
        $s->save($this->professional, $body);
        config(['modules.support_plans' => ['default' => false]]);
        $this->refused(fn () => $s->save($this->professional, $body));
        config(['modules.support_plans' => ['always_on' => true]]);
        $this->professional->syncRoles(['Teacher']);
        $this->refused(fn () => $s->save($this->professional, $body));
        $this->professional->syncRoles(['Counsellor']);
        PlatformStaffIdentity::create(['user_id' => $this->professional->id]);
        $this->refused(fn () => $s->save($this->professional, $body));
        PlatformStaffIdentity::where('user_id', $this->professional->id)->delete();
        app(CurrentSchool::class)->impersonate(School::findOrFail(1));
        $this->refused(fn () => $s->save($this->professional, $body));
        app(CurrentSchool::class)->set(1);
        $s->revoke($this->superAdmin(), 'assignment', $this->appointment->id, ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'reason' => 'Private appointment withdrawal']);
        $this->refused(fn () => $s->save($this->professional, $body));
        $this->assertSame(1, SupportPlan::count());
    }

    public function test_context_switch_rolls_back_and_restores_the_original_school(): void
    {
        SupportPlan::created(function () {
            app(CurrentSchool::class)->set(2);
        });
        try {
            $this->refused(fn () => $this->plan());
        } finally {
            SupportPlan::flushEventListeners();
        }
        $this->assertSame(1, app(CurrentSchool::class)->id());
        $this->assertSame(0, SupportPlan::count());
        $this->assertSame(0, DB::table('support_plan_versions')->count());
    }

    public function test_named_configuration_scopes_unplanned_learners_and_staff_before_case_creation(): void
    {
        $q = app(SupportQueries::class);
        $mine = $q->configuration($this->professional, 'professional');
        $this->assertSame([$this->learner], array_column($mine['learners'], 'id'));
        $this->assertNotEmpty($mine['learners'][0]['name']);
        $this->assertContains($this->schoolA->ids['theo_user'], array_column($mine['staff'], 'id'));
        $this->assertNotContains($this->professional->id, array_column($mine['staff'], 'id'));
        $principal = $q->configuration($this->superAdmin(), 'principal');
        $this->assertContains($this->professional->id, array_column($principal['staff'], 'id'));
        $this->assertSame([], $q->configuration($this->professional, 'professional', 'NoSuchSyntheticName')['learners']);
        $this->appointment->update(['active' => false]);
        $this->assertSame([], $q->configuration($this->professional, 'professional')['learners']);
    }

    public function test_disclosure_expiry_owner_and_enrolment_withdrawal_remove_the_projection(): void
    {
        $plan = $this->plan();
        $teacher = User::findOrFail($this->schoolA->ids['theo_user']);
        $row = app(SupportPlans::class)->disclose($this->professional, $plan->id, ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'user_id' => $teacher->id, 'expires_on' => '2026-02-11', 'accommodations' => 'Approved instructions', 'confirmation' => true, 'reason' => 'Limited support']);
        $other = User::findOrFail($this->schoolA->ids['farid_user']);
        $other->syncRoles(['Teacher']);
        $this->getDisclosureMissing($other, $row->id);
        Enrolment::where('learner_id', $this->learner)->update(['ends_on' => '2026-02-09']);
        $this->getDisclosureMissing($teacher, $row->id);
        Enrolment::where('learner_id', $this->learner)->update(['ends_on' => null]);
        $this->assertSame('Approved instructions', app(SupportQueries::class)->disclosure($teacher, $row->id)['accommodations']);
        $this->travelTo(CarbonImmutable::parse('2026-02-12 10:00:00', 'Africa/Johannesburg'));
        $this->getDisclosureMissing($teacher, $row->id);
    }

    private function getDisclosureMissing(User $teacher, int $id): void
    {
        try {
            app(SupportQueries::class)->disclosure($teacher, $id);
            $this->fail('An unavailable projection was exposed.');
        } catch (ModelNotFoundException) {
            $this->assertTrue(true);
        } catch (HttpException $e) {
            $this->assertSame(404, $e->getStatusCode());
        }
    }

    public function test_retained_support_history_refuses_destructive_rollback(): void
    {
        $this->plan();
        $migration = require database_path('migrations/2026_10_25_120000_create_support_plans.php');
        $this->expectException(\RuntimeException::class);
        $migration->down();
    }
}
