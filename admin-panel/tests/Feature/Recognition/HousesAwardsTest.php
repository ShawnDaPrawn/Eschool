<?php

declare(strict_types=1);

namespace Tests\Feature\Recognition;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\HomeroomAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Activities\ActivityStaff;
use App\Models\ClassSection;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\Recognition\AwardCategory;
use App\Models\Recognition\AwardDefinition;
use App\Models\Recognition\AwardNominationVersion;
use App\Models\Recognition\AwardPresentation;
use App\Models\Recognition\House;
use App\Models\Recognition\HouseMembership;
use App\Models\Recognition\HousePoint;
use App\Models\Recognition\HousePointLimit;
use App\Models\Recognition\PointCategory;
use App\Models\Recognition\RecognitionOperation;
use App\Models\SessionYear;
use App\Models\Settings;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Recognition\AwardNominations;
use App\Services\Recognition\AwardPresentations;
use App\Services\Recognition\HousePoints;
use App\Services\Recognition\RecognitionCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class HousesAwardsTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private User $admin;

    private User $head;

    private int $year;

    private int $grade;

    private int $learner;

    private House $house;

    private PointCategory $points;

    private AwardCategory $category;

    private AwardDefinition $award;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(CarbonImmutable::parse('2026-02-10 10:00:00', 'Africa/Johannesburg'));
        config(['modules.house_points' => ['always_on' => true], 'modules.awards' => ['always_on' => true]]);
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->admin = $this->superAdmin();
        $this->head = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->year = $this->schoolA->ids['session_year'];
        $this->learner = $this->schoolA->ids['asha'];
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $this->grade = (int) GradeLevel::where('code', '5')->value('id');
        ClassSection::with('class')->findOrFail($this->schoolA->ids['section_a'])->class->forceFill(['grade_level_id' => $this->grade])->save();
        Enrolment::create(['learner_id' => $this->learner, 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => null, 'legacy_dates' => false]);
        AcademicLeadership::create(['user_id' => $this->head->id, 'session_year_id' => $this->year, 'kind' => 'grade_head', 'grade_level_id' => $this->grade, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic appointment', 'revision' => 1]);
        $c = app(RecognitionCatalog::class);
        $this->house = $c->house($this->admin, $this->op(['name' => 'Mountain', 'emblem' => 'mountain', 'primary_colour' => '#123456', 'secondary_colour' => '#ffffff', 'active' => true]));
        $this->points = $c->category($this->admin, $this->op(['name' => 'Helping others', 'default_points' => 5, 'minimum_points' => -10, 'maximum_points' => 20, 'active' => true]));
        $c->membership($this->admin, $this->op(['learner_id' => $this->learner, 'house_id' => $this->house->id, 'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'ends_on' => null, 'active' => true, 'reason' => 'Synthetic house allocation']));
        $this->category = $c->awardCategory($this->admin, $this->op(['name' => 'Grade five awards', 'parent_id' => null, 'scope_kind' => 'grade', 'grade_level_id' => $this->grade, 'subject_id' => null, 'activity_group_id' => null, 'active' => true]));
        $this->award = $c->definition($this->head, $this->op(['award_category_id' => $this->category->id, 'name' => 'Community service', 'criteria' => 'Sustained service throughout the year', 'frequency' => 'annual', 'active' => true]));
        $c->window($this->head, $this->op(['revision' => 0, 'award_category_id' => $this->category->id, 'session_year_id' => $this->year, 'opens_on' => '2026-02-01', 'closes_on' => '2026-02-28', 'status' => 'open']));
    }

    private function op(array $data = []): array
    {
        return ['operation_id' => (string) Str::uuid()] + $data;
    }

    private function input(): array
    {
        return $this->op(['learner_id' => $this->learner, 'session_year_id' => $this->year, 'point_category_id' => $this->points->id, 'points' => 5, 'reason' => 'Thoughtful assistance', 'public' => false]);
    }

    private function nomination(): array
    {
        return $this->op(['session_year_id' => $this->year, 'grade_level_id' => $this->grade, 'learner_id' => $this->learner, 'award_definition_id' => $this->award->id, 'reason' => 'Consistent synthetic service evidence']);
    }

    private function presentation(?int $nomination = null): array
    {
        return $this->op(['learner_id' => $this->learner, 'session_year_id' => $this->year, 'award_definition_id' => $this->award->id, 'award_nomination_id' => $nomination, 'presented_on' => '2026-02-10', 'event' => 'Assembly']);
    }

    private function refuse(int $status, callable $work): void
    {
        try {
            $work();
            $this->fail('Expected refusal '.$status);
        } catch (HttpException|ModelNotFoundException $e) {
            $this->assertSame($status, $e instanceof HttpException ? $e->getStatusCode() : 404);
        }
    }

    public function test_points_are_idempotent_and_counter_event_contains_only_invalidation_metadata(): void
    {
        $d = $this->input();
        $p = app(HousePoints::class)->issue($this->head, $d);
        $this->assertSame($p->id, app(HousePoints::class)->issue($this->head, $d)->id);
        $this->assertSame(1, HousePoint::count());
        $this->assertSame(5, (int) HousePointLimit::sole()->spent);
        $counter = app(HousePoints::class)->counters($this->head, $this->year);
        $this->assertSame(5, $counter['items'][0]['points']);
        $event = OutboxEvent::where('type', 'house_points.changed')->sole();
        $this->assertSame(['school_id' => 1, 'session_year_id' => $this->year, 'house_id' => (int) $this->house->id, 'revision' => (int) $p->id], $event->payload);
        $this->assertNotContains('Thoughtful assistance', array_values(RecognitionOperation::where('kind', 'point.issue')->sole()->toArray()));
        $this->assertNotSame('Thoughtful assistance', DB::table('house_points')->value('reason'));
        $changed = $d;
        $changed['points'] = 6;
        $this->refuse(409, fn () => app(HousePoints::class)->issue($this->head, $changed));
    }

    public function test_reversal_is_append_only_freezes_house_and_never_restores_daily_allowance(): void
    {
        $d = $this->input();
        $d['points'] = 20;
        $p = app(HousePoints::class)->issue($this->head, $d);
        $op = $this->op(['reason' => 'Wrong learner selected']);
        $r = app(HousePoints::class)->reverse($this->head, (int) $p->id, $op);
        $this->assertSame(-20, $r->points);
        $this->assertSame($p->id, (int) $r->reverses_id);
        $this->assertSame(0, app(HousePoints::class)->counters($this->head, $this->year)['items'][0]['points']);
        $this->assertSame($r->id, app(HousePoints::class)->reverse($this->head, (int) $p->id, $op)->id);
        $this->assertSame(20, (int) HousePointLimit::sole()->spent);
        $this->refuse(409, fn () => app(HousePoints::class)->reverse($this->head, (int) $p->id, $this->op(['reason' => 'Again'])));
        $this->refuse(409, fn () => app(HousePoints::class)->reverse($this->head, (int) $r->id, $this->op(['reason' => 'Reverse reversal'])));
        $this->refuse(409, fn () => $p->update(['points' => 100]));
        $this->refuse(409, fn () => $p->delete());
        $this->assertSame('Mountain', $r->house_name);
    }

    public function test_daily_cap_includes_negative_points_and_rolls_back_failed_write(): void
    {
        $d = $this->input();
        $d['points'] = 20;
        app(HousePoints::class)->issue($this->head, $d);
        $d['operation_id'] = (string) Str::uuid();
        app(HousePoints::class)->issue($this->head, $d);
        $d['operation_id'] = (string) Str::uuid();
        $d['points'] = -10;
        app(HousePoints::class)->issue($this->head, $d);
        $this->refuse(422, fn () => app(HousePoints::class)->issue($this->head, $this->input()));
        $this->assertSame(3, HousePoint::count());
        $this->assertSame(50, (int) HousePointLimit::sole()->spent);
        $this->travelTo(CarbonImmutable::parse('2026-02-11 00:01:00', 'Africa/Johannesburg'));
        app(HousePoints::class)->issue($this->head, $this->input());
        $this->assertSame(2, HousePointLimit::count());
    }

    public function test_school_local_day_controls_daily_cap(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-02-10 22:30:00', 'UTC'));
        $p = app(HousePoints::class)->issue($this->head, $this->input());
        $this->assertSame('2026-02-11', $p->on_date->toDateString());
    }

    public function test_revoked_scope_wrong_role_and_wrong_school_cannot_write_or_replay(): void
    {
        $d = $this->input();
        app(HousePoints::class)->issue($this->head, $d);
        AcademicLeadership::where('user_id', $this->head->id)->update(['ends_on' => '2026-02-09']);
        $this->refuse(404, fn () => app(HousePoints::class)->issue($this->head, $d));
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->refuse(403, fn () => app(HousePoints::class)->issue($parent, $this->input()));
        $sourceId = (int) HousePoint::value('id');
        app(CurrentSchool::class)->run(2, function () use ($sourceId): void {
            $other = User::create(['first_name' => 'Other', 'last_name' => 'Admin', 'email' => 'recognition-other@example.test', 'password' => bcrypt('Synthetic2026'), 'status' => 1]);
            $other->assignRole('Super Admin');
            $this->refuse(404, fn () => app(HousePoints::class)->reverse($other, $sourceId, $this->op(['reason' => 'Other school'])));
        });
        $this->assertSame(1, HousePoint::count());
    }

    public function test_house_membership_overlap_and_missing_membership_are_refused(): void
    {
        $d = $this->op(['learner_id' => $this->learner, 'house_id' => $this->house->id, 'session_year_id' => $this->year, 'starts_on' => '2026-02-01', 'ends_on' => null, 'active' => true, 'reason' => 'Overlap']);
        $this->refuse(409, fn () => app(RecognitionCatalog::class)->membership($this->admin, $d));
        HouseMembership::query()->update(['ends_on' => '2026-02-09']);
        $this->refuse(409, fn () => app(HousePoints::class)->issue($this->head, $this->input()));
        $this->assertSame(0, HousePoint::count());
    }

    public function test_module_disable_pending_password_and_platform_operator_block_recognition(): void
    {
        $this->head->forceFill(['password_change_required' => true])->save();
        $this->refuse(403, fn () => app(HousePoints::class)->issue($this->head, $this->input()));
        $this->head->forceFill(['password_change_required' => false])->save();
        PlatformStaffIdentity::create(['user_id' => $this->head->id, 'role' => 'support', 'active' => true]);
        $this->refuse(403, fn () => app(HousePoints::class)->issue($this->head, $this->input()));
        config(['modules.house_points' => ['default_on' => false, 'dependencies' => []]]);
        $this->refuse(403, fn () => app(HousePoints::class)->issue($this->admin, $this->input()));
    }

    public function test_public_flag_requires_explicit_school_setting_and_configuration_uses_revision(): void
    {
        $d = $this->input();
        $d['public'] = true;
        $this->refuse(422, fn () => app(HousePoints::class)->issue($this->head, $d));
        $catalog = app(RecognitionCatalog::class);
        $s = $catalog->settings();
        $op = $this->op(['revision' => $s->revision, 'daily_staff_cap' => 75, 'public_points_allowed' => true, 'principal_approval_required' => false]);
        $updated = $catalog->configure($this->admin, 'house_points', $op);
        $this->assertSame(2, $updated->revision);
        $this->assertTrue(app(HousePoints::class)->issue($this->head, $d)->public);
        $this->refuse(409, fn () => $catalog->configure($this->admin, 'house_points', $this->op(array_diff_key($op, ['operation_id' => true]))));
    }

    public function test_nomination_contract_enforces_named_grade_head_placement_window_and_version(): void
    {
        $n = app(AwardNominations::class);
        $this->assertSame('Grade five awards', $n->catalogue($this->head, $this->year, $this->grade)['items'][0]['name']);
        $op = $this->nomination();
        $row = $n->nominate($this->head, $op);
        $this->assertSame($row->id, $n->nominate($this->head, $op)->id);
        $this->assertSame(1, AwardNominationVersion::count());
        $this->assertSame('Community service', $row->definition_name);
        $this->assertNotSame($op['reason'], DB::table('award_nominations')->value('reason'));
        $this->assertSame($op['reason'], AwardNominationVersion::sole()->snapshot['reason']);
        $this->refuse(409, fn () => $n->nominate($this->head, $this->nomination()));
        AcademicLeadership::where('user_id', $this->head->id)->update(['ends_on' => '2026-02-09']);
        $this->refuse(403, fn () => $n->nominate($this->head, $op));
    }

    public function test_closed_window_and_changed_placement_refuse_nomination_even_for_retry(): void
    {
        $op = $this->nomination();
        app(AwardNominations::class)->nominate($this->head, $op);
        $this->travelTo(CarbonImmutable::parse('2026-03-01 10:00:00', 'Africa/Johannesburg'));
        $this->refuse(404, fn () => app(AwardNominations::class)->nominate($this->head, $op));
        $this->travelBack();
        Enrolment::where('learner_id', $this->learner)->update(['ends_on' => '2026-02-09']);
        $this->refuse(404, fn () => app(AwardNominations::class)->nominate($this->head, $this->nomination()));
    }

    public function test_review_approval_presentation_and_annual_duplicate_are_transactional(): void
    {
        $n = app(AwardNominations::class);
        $row = $n->nominate($this->head, $this->nomination());
        $row = $n->review($this->head, (int) $row->id, $this->op(['revision' => 1, 'decision' => 'approved', 'reason' => 'Criteria checked']));
        $this->assertSame('approved', $row->state);
        $this->assertSame(2, AwardNominationVersion::count());
        $d = $this->presentation((int) $row->id);
        $p = app(AwardPresentations::class)->present($this->head, $d);
        $this->assertSame($p->id, app(AwardPresentations::class)->present($this->head, $d)->id);
        $this->assertSame('Community service', $p->title);
        $this->refuse(409, fn () => app(AwardPresentations::class)->present($this->head, $this->presentation()));
        $this->assertSame(1, AwardPresentation::count());
    }

    public function test_changed_definition_requires_new_nomination_review_and_never_rewrites_presentation(): void
    {
        $n = app(AwardNominations::class);
        $row = $n->nominate($this->head, $this->nomination());
        $this->award->update(['name' => 'Revised service award', 'revision' => 2]);
        $this->refuse(409, fn () => $n->review($this->head, (int) $row->id, $this->op(['revision' => 1, 'decision' => 'approved', 'reason' => 'Review'])));
        $p = app(AwardPresentations::class)->present($this->head, $this->presentation());
        $this->award->update(['name' => 'A later name', 'revision' => 3]);
        $this->assertSame('Revised service award', $p->fresh()->title);
        $this->refuse(409, fn () => $p->update(['title' => 'Rewritten']));
        $this->refuse(409, fn () => $p->delete());
    }

    public function test_withdrawal_retains_history_and_allows_fresh_nomination(): void
    {
        $n = app(AwardNominations::class);
        $row = $n->nominate($this->head, $this->nomination());
        $op = $this->op(['revision' => 1, 'reason' => 'Evidence needs correction']);
        $withdrawn = $n->withdraw($this->head, (int) $row->id, $op);
        $this->assertSame('withdrawn', $withdrawn->state);
        $this->assertSame($withdrawn->id, $n->withdraw($this->head, (int) $row->id, $op)->id);
        $new = $n->nominate($this->head, $this->nomination());
        $this->assertNotSame($row->id, $new->id);
        $this->assertSame(3, AwardNominationVersion::count());
    }

    public function test_permanent_profile_contains_awards_and_outside_awards_never_house_points(): void
    {
        app(HousePoints::class)->issue($this->head, $this->input());
        app(AwardPresentations::class)->present($this->head, $this->presentation());
        app(AwardPresentations::class)->outside($this->admin, $this->op(['learner_id' => $this->learner, 'session_year_id' => $this->year, 'title' => 'Regional music', 'criteria' => 'External achievement', 'outside_organisation' => 'Synthetic Music Society', 'presented_on' => '2026-02-09', 'event' => null]));
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        GuardianLink::updateOrCreate(['learner_id' => $this->learner, 'guardian_user_id' => $parent->id], ['status' => 'active', 'can_approve' => true, 'relationship' => 'father']);
        $profile = app(AwardPresentations::class)->profile($parent, $this->learner, true);
        $this->assertCount(2, $profile['items']);
        $this->assertStringNotContainsString('Thoughtful assistance', json_encode($profile));
        $this->assertArrayNotHasKey('points', $profile['items'][0]);
        $other = User::findOrFail($this->schoolA->ids['gina_user']);
        $this->refuse(404, fn () => app(AwardPresentations::class)->profile($other, $this->learner, true));
        GuardianLink::query()->update(['status' => 'withdrawn']);
        $this->refuse(404, fn () => app(AwardPresentations::class)->profile($parent, $this->learner, true));
    }

    public function test_void_is_retained_reasoned_and_releases_annual_deduplication(): void
    {
        $service = app(AwardPresentations::class);
        $p = $service->present($this->head, $this->presentation());
        $op = $this->op(['revision' => 1, 'reason' => 'Recorded against wrong certificate']);
        $v = $service->void($this->head, (int) $p->id, $op);
        $this->assertSame('void', $v->state);
        $this->assertSame($v->id, $service->void($this->head, (int) $p->id, $op)->id);
        $this->assertNull($v->dedupe_key);
        $this->assertCount(0, $service->profile($this->head, $this->learner)['items']);
        $service->present($this->head, $this->presentation());
        $this->assertSame(2, AwardPresentation::count());
    }

    public function test_category_admin_can_make_scoped_subcategories_and_archived_categories_block_new_awards(): void
    {
        $c = app(RecognitionCatalog::class);
        $d = $this->op(['name' => 'Subject subcategory', 'parent_id' => $this->category->id, 'scope_kind' => 'grade', 'grade_level_id' => $this->grade, 'subject_id' => $this->schoolA->ids['maths'], 'activity_group_id' => null, 'active' => true]);
        $child = $c->awardCategory($this->head, $d);
        $this->assertSame($this->category->id, (int) $child->parent_id);
        $d['operation_id'] = (string) Str::uuid();
        $d['scope_kind'] = 'school';
        $d['grade_level_id'] = null;
        $d['subject_id'] = null;
        $this->refuse(422, fn () => $c->awardCategory($this->head, $d));
        $d = $this->op(['revision' => $this->category->revision, 'name' => $this->category->name, 'parent_id' => null, 'scope_kind' => 'grade', 'grade_level_id' => $this->grade, 'subject_id' => null, 'activity_group_id' => null, 'active' => false]);
        $c->awardCategory($this->head, $d, (int) $this->category->id);
        $this->refuse(409, fn () => app(AwardPresentations::class)->present($this->head, $this->presentation()));
    }

    public function test_retained_records_prevent_destructive_migration_rollback(): void
    {
        $migration = require database_path('migrations/2026_10_25_140000_create_houses_and_awards.php');
        try {
            $migration->down();
            $this->fail('Recognition records must prevent rollback');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Retained', $e->getMessage());
        }$this->assertSame(1, House::count());
        $this->assertSame(1, AwardDefinition::count());
    }

    public function test_optional_principal_approval_is_enforced_only_when_configured(): void
    {
        $catalog = app(RecognitionCatalog::class);
        $catalog->configure($this->admin, 'awards', $this->op(['revision' => $catalog->settings()->revision, 'daily_staff_cap' => 50, 'public_points_allowed' => false, 'principal_approval_required' => true]));
        $row = app(AwardNominations::class)->nominate($this->head, $this->nomination());
        $this->refuse(403, fn () => app(AwardNominations::class)->review($this->head, (int) $row->id, $this->op(['revision' => 1, 'decision' => 'approved', 'reason' => 'Head review'])));
        $this->refuse(403, fn () => app(AwardPresentations::class)->present($this->admin, $this->presentation()));
        AcademicLeadership::create(['user_id' => $this->admin->id, 'session_year_id' => $this->year, 'kind' => 'principal', 'grade_level_id' => null, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic principal', 'revision' => 1]);
        app(AwardNominations::class)->review($this->admin, (int) $row->id, $this->op(['revision' => 1, 'decision' => 'approved', 'reason' => 'Principal checked criteria']));
        $p = app(AwardPresentations::class)->present($this->head, $this->presentation((int) $row->id));
        $this->assertSame('Community service', $p->title);
    }

    public function test_rollback_removes_ledger_daily_spend_receipt_and_outbox_together(): void
    {
        $before = RecognitionOperation::count();
        try {
            DB::transaction(function (): void {
                app(HousePoints::class)->issue($this->head, $this->input());
                throw new \RuntimeException('Synthetic enclosing rollback');
            });
        } catch (\RuntimeException $e) {
            $this->assertSame('Synthetic enclosing rollback', $e->getMessage());
        }
        $this->assertSame(0, HousePoint::count());
        $this->assertSame(0, HousePointLimit::count());
        $this->assertSame(0, OutboxEvent::where('type', 'house_points.changed')->count());
        $this->assertSame($before, RecognitionOperation::count());
    }

    public function test_definition_can_be_reused_in_a_later_year_without_changing_prior_awards(): void
    {
        app(AwardPresentations::class)->present($this->head, $this->presentation());
        $new = SessionYear::create(['name' => '2027', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31', 'default' => 0]);
        Settings::updateOrCreate(['type' => 'session_year'], ['message' => (string) $new->id]);
        $this->travelTo(CarbonImmutable::parse('2027-02-10 10:00:00', 'Africa/Johannesburg'));
        SchoolMembership::query()->update(['ends_on' => null]);
        AcademicLeadership::where('user_id', $this->head->id)->update(['ends_on' => '2026-12-31']);
        AcademicLeadership::create(['user_id' => $this->head->id, 'session_year_id' => $new->id, 'kind' => 'grade_head', 'grade_level_id' => $this->grade, 'starts_on' => '2027-01-01', 'ends_on' => '2027-12-31', 'reason' => 'Renewed head appointment', 'revision' => 1]);
        Enrolment::where('learner_id', $this->learner)->update(['ends_on' => '2026-12-31']);
        Enrolment::create(['learner_id' => $this->learner, 'session_year_id' => $new->id, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2027-01-01', 'ends_on' => null, 'legacy_dates' => false]);
        $d = $this->presentation();
        $d['session_year_id'] = (int) $new->id;
        $d['presented_on'] = '2027-02-10';
        app(AwardPresentations::class)->present($this->head, $d);
        $this->assertSame(1, AwardDefinition::count());
        $this->assertSame(2, AwardPresentation::count());
        $this->assertCount(2, app(AwardPresentations::class)->profile($this->head, $this->learner)['items']);
    }

    public function test_dated_homeroom_allocation_and_subject_eligibility_are_authoritative(): void
    {
        $teacher = User::findOrFail($this->schoolA->ids['theo_user']);
        $allocation = HomeroomAllocation::create(['teacher_id' => $this->schoolA->ids['theo_teacher'], 'class_section_id' => $this->schoolA->ids['section_a'], 'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic register teacher', 'revision' => 1]);
        $d = $this->input();
        $p = app(HousePoints::class)->issue($teacher, $d);
        $this->assertSame('homeroom', $p->scope_kind);
        $allocation->update(['ends_on' => '2026-02-09']);
        $this->refuse(404, fn () => app(HousePoints::class)->issue($teacher, $d));
        $c = app(RecognitionCatalog::class);
        $cat = $c->awardCategory($this->admin, $this->op(['name' => 'Subject merit', 'parent_id' => $this->category->id, 'scope_kind' => 'grade', 'grade_level_id' => $this->grade, 'subject_id' => $this->schoolA->ids['maths'], 'activity_group_id' => null, 'active' => true]));
        $definition = $c->definition($this->head, $this->op(['award_category_id' => $cat->id, 'name' => 'Mathematical service', 'criteria' => 'Subject evidence', 'frequency' => 'annual', 'active' => true]));
        $d = $this->presentation();
        $d['award_definition_id'] = (int) $definition->id;
        $this->refuse(404, fn () => app(AwardPresentations::class)->present($this->head, $d));
        $group = TeachingGroup::create(['name' => 'Maths', 'subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => $this->grade, 'session_year_id' => $this->year, 'class_section_id' => null, 'active' => true]);
        $member = TeachingGroupMembership::create(['teaching_group_id' => $group->id, 'learner_id' => $this->learner, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic maths', 'mark_rule' => 'catch_up', 'active' => true]);
        app(AwardPresentations::class)->present($this->head, $d);
        $this->assertSame(1, AwardPresentation::count());
        $member->update(['ends_on' => '2026-02-09']);
        $new = $this->presentation();
        $new['award_definition_id'] = (int) $definition->id;
        $this->refuse(404, fn () => app(AwardPresentations::class)->present($this->head, $new));
    }

    public function test_group_head_category_authority_requires_current_group_membership_and_active_ancestry(): void
    {
        config(['modules.groups' => ['always_on' => true]]);
        $g = ActivityGroup::create(['session_year_id' => $this->year, 'parent_id' => null, 'name' => 'Music', 'discipline' => 'Culture', 'season' => '2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'gender' => 'any', 'active' => true, 'revision' => 1]);
        $teacher = User::findOrFail($this->schoolA->ids['theo_user']);
        ActivityStaff::create(['activity_group_id' => $g->id, 'user_id' => $teacher->id, 'role' => 'admin', 'cascade' => true, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic music head', 'revision' => 1]);
        $c = app(RecognitionCatalog::class);
        $cat = $c->awardCategory($this->admin, $this->op(['name' => 'Music honours', 'parent_id' => null, 'scope_kind' => 'group', 'grade_level_id' => null, 'subject_id' => null, 'activity_group_id' => $g->id, 'active' => true]));
        $definition = $c->definition($teacher, $this->op(['award_category_id' => $cat->id, 'name' => 'Music merit', 'criteria' => 'Music achievement', 'frequency' => 'annual', 'active' => true]));
        $d = $this->presentation();
        $d['award_definition_id'] = (int) $definition->id;
        $this->refuse(404, fn () => app(AwardPresentations::class)->present($teacher, $d));
        $m = ActivityMembership::create(['activity_group_id' => $g->id, 'learner_id' => $this->learner, 'role' => 'member', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic membership', 'revision' => 1]);
        app(AwardPresentations::class)->present($teacher, $d);
        $this->assertSame(1, AwardPresentation::count());
        $m->update(['ends_on' => '2026-02-09']);
        $new = $this->presentation();
        $new['award_definition_id'] = (int) $definition->id;
        $this->refuse(404, fn () => app(AwardPresentations::class)->present($teacher, $new));
        $g->update(['active' => false]);
        $this->refuse(403, fn () => $c->definition($teacher, $this->op(['award_category_id' => $cat->id, 'name' => 'Other', 'criteria' => 'Other', 'frequency' => 'annual', 'active' => true])));
    }

    private function api(User $actor): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($actor->createToken('recognition-synthetic')->plainTextToken)->withHeader('X-School-Id', '1')->withHeader('X-Account-Id', (string) $actor->id);
    }

    public function test_api_projects_configuration_choices_counters_and_authorized_point_history(): void
    {
        $base = '/api/v2/recognition/house_points';
        $this->api($this->head);
        $this->getJson("$base/access")->assertOk()->assertJsonPath('data.manager', false)->assertHeader('Cache-Control', 'no-store, private');
        $this->getJson("$base/configuration")->assertOk()->assertJsonPath('data.houses.0.name', 'Mountain');
        $this->getJson("$base/learners")->assertOk()->assertJsonPath('data.items.0.id', $this->learner);
        $d = $this->input();
        $id = $this->postJson("$base/points", $d)->assertOk()->json('data.id');
        $this->postJson("$base/points", $d)->assertOk()->assertJsonPath('data.id', $id);
        $this->getJson("$base/counters")->assertOk()->assertJsonPath('data.items.0.points', 5);
        $this->getJson("$base/learners/{$this->learner}/points")->assertOk()->assertJsonPath('data.items.0.reason', 'Thoughtful assistance');
        $this->postJson("$base/points/$id/reverse", $this->op(['reason' => 'Synthetic correction']))->assertOk();
        $this->getJson("$base/counters")->assertOk()->assertJsonPath('data.items.0.points', 0);
    }

    public function test_api_award_nomination_review_and_permanent_profile_round_trip(): void
    {
        $base = '/api/v2/recognition/awards';
        $this->api($this->head);
        $this->getJson("$base/configuration")->assertOk()->assertJsonPath('data.award_definitions.0.name', 'Community service');
        $this->getJson("$base/nomination-catalogue?grade_level_id={$this->grade}")->assertOk()->assertJsonPath('data.items.0.category_id', (int) $this->category->id);
        $id = $this->postJson("$base/nominations", $this->nomination())->assertOk()->json('data.id');
        $this->getJson("$base/nominations")->assertOk()->assertJsonPath('data.items.0.learner_id', $this->learner);
        $this->postJson("$base/nominations/$id/review", $this->op(['revision' => 1, 'decision' => 'approved', 'reason' => 'Reviewed evidence']))->assertOk();
        $presentation = $this->postJson("$base/presentations", $this->presentation($id))->assertOk()->json('data.id');
        $this->getJson("$base/learners/{$this->learner}/awards")->assertOk()->assertJsonPath('data.items.0.title', 'Community service');
        $this->postJson("$base/presentations/$presentation/void", $this->op(['revision' => 1, 'reason' => 'Correcting certificate']))->assertOk();
        $this->getJson("$base/learners/{$this->learner}/awards")->assertOk()->assertJsonCount(0, 'data.items');
    }

    public function test_api_family_reads_require_own_child_and_never_receive_pending_nominations(): void
    {
        app(HousePoints::class)->issue($this->head, $this->input());
        app(AwardPresentations::class)->present($this->head, $this->presentation());
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->api($parent);
        $this->getJson("/api/v2/recognition/awards/learners/{$this->learner}/awards")->assertOk();
        $this->getJson("/api/v2/recognition/house_points/learners/{$this->learner}/points")->assertOk();
        $this->getJson('/api/v2/recognition/awards/nominations')->assertForbidden();
        $this->getJson('/api/v2/recognition/awards/configuration')->assertForbidden();
        $this->postJson('/api/v2/recognition/awards/presentations', $this->presentation())->assertForbidden();
        $this->api(User::findOrFail($this->schoolA->ids['gina_user']))->getJson("/api/v2/recognition/awards/learners/{$this->learner}/awards")->assertNotFound();
    }

    public function test_api_refuses_other_school_account_query_mutations_and_revoked_uuid_replay(): void
    {
        $base = '/api/v2/recognition/house_points';
        $this->getJson("$base/access")->assertUnauthorized();
        $this->api($this->head);
        $d = $this->input();
        $this->postJson("$base/points", $d)->assertOk();
        $this->withHeader('X-Account-Id', (string) $this->admin->id)->getJson("$base/access")->assertForbidden();
        $this->api($this->head)->withHeader('X-School-Id', '2')->getJson("$base/access")->assertForbidden();
        $this->api($this->head)->postJson("$base/points?reason=DoNotPersistQuery", $this->input())->assertUnprocessable();
        AcademicLeadership::where('user_id', $this->head->id)->update(['ends_on' => '2026-02-09']);
        $this->postJson("$base/points", $d)->assertNotFound();
        $this->assertSame(1, HousePoint::count());
    }

    public function test_api_catalogue_changes_use_shared_rules_and_refuse_non_managers(): void
    {
        $base = '/api/v2/recognition/house_points';
        $d = $this->op(['name' => 'River', 'emblem' => 'river', 'primary_colour' => '#123456', 'secondary_colour' => '#ffffff', 'active' => true]);
        $this->api($this->head)->postJson("$base/catalogue/houses", $d)->assertForbidden();
        $this->api($this->admin)->postJson("$base/catalogue/houses", $d)->assertOk();
        $this->postJson("$base/catalogue/houses", $this->op(['name' => 'Missing properties']))->assertUnprocessable();
        $this->postJson("$base/settings", $this->op(['revision' => 1, 'daily_staff_cap' => 65, 'public_points_allowed' => false, 'principal_approval_required' => false]))->assertOk()->assertJsonPath('data.revision', 2);
        $this->getJson("$base/configuration")->assertOk()->assertJsonCount(2, 'data.houses');
    }

    public function test_api_read_only_module_preserves_reads_and_refuses_writes(): void
    {
        config(['modules.awards' => ['default' => true, 'dependencies' => []]]);
        SchoolModule::updateOrCreate(['module' => 'awards'], ['in_package' => true, 'enabled' => true, 'read_only' => true]);
        $this->api($this->head)->getJson('/api/v2/recognition/awards/access')->assertOk()->assertJsonPath('data.can_write', false);
        $this->getJson("/api/v2/recognition/awards/nomination-catalogue?grade_level_id={$this->grade}")->assertOk();
        $this->postJson('/api/v2/recognition/awards/nominations', $this->nomination())->assertForbidden();
    }

    public function test_managers_can_read_retained_awards_and_point_history_after_physical_departure(): void
    {
        app(HousePoints::class)->issue($this->head, $this->input());
        app(AwardPresentations::class)->present($this->head, $this->presentation());
        Enrolment::where('learner_id', $this->learner)->update(['ends_on' => '2026-02-09']);
        $this->assertCount(1, app(AwardPresentations::class)->profile($this->admin, $this->learner)['items']);
        $this->assertCount(1, app(HousePoints::class)->history($this->admin, $this->learner, $this->year)['items']);
        $this->refuse(404, fn () => app(AwardPresentations::class)->profile($this->head, $this->learner));
    }

    public function test_presented_nominations_retain_a_version_and_do_not_block_future_nomination_after_void(): void
    {
        $n = app(AwardNominations::class);
        $row = $n->nominate($this->head, $this->nomination());
        $n->review($this->head, (int) $row->id, $this->op(['revision' => 1, 'decision' => 'approved', 'reason' => 'Checked']));
        $p = app(AwardPresentations::class)->present($this->head, $this->presentation((int) $row->id));
        $this->assertSame('presented', $row->fresh()->state);
        $this->assertSame(3, AwardNominationVersion::count());
        app(AwardPresentations::class)->void($this->head, (int) $p->id, $this->op(['revision' => 1, 'reason' => 'Correcting certificate']));
        $replacement = $n->nominate($this->head, $this->nomination());
        $this->assertNotSame($row->id,$replacement->id);
    }
}
