<?php

declare(strict_types=1);

namespace Tests\Feature\Growth;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\TeachingAllocation;
use App\Models\Growth\LearnerNote;
use App\Models\Growth\LearnerNoteAssignment;
use App\Models\Growth\LearnerNoteRevision;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Services\Growth\LearnerNotes;
use App\Services\Growth\ProgressBands;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesMasteryScenario;
use Tests\TestCase;

class ProgressNotesTest extends TestCase
{
    use UsesMasteryScenario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
    }

    private function note(string $visibility = 'staff', string $body = 'Private academic teaching observation'): array
    {
        return app(LearnerNotes::class)->create($this->actor(), $this->schoolA->ids['asha'], $this->subject, $this->year, $visibility, $body);
    }

    private function refused(callable $call, array $codes = [403, 404]): void
    {
        try {
            $call();
            $this->fail('The private operation must be refused.');
        } catch (HttpException $e) {
            $this->assertContains($e->getStatusCode(), $codes);
        } catch (ModelNotFoundException $e) {
            $this->assertContains(404, $codes);
        }
    }

    private function principal(): void
    {
        AcademicLeadership::create(['user_id' => $this->superAdmin()->id, 'kind' => 'principal', 'grade_level_id' => null,
            'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic principal']);
    }

    private function clinical(): array
    {
        $this->principal();
        $this->actor()->assignRole(SchoolRoles::COUNSELLOR);
        app(LearnerNotes::class)->assign($this->superAdmin(), $this->schoolA->ids['asha'], $this->actor()->id,
            ['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'expected_revision' => 0]);

        return $this->note('counselling', 'Restricted clinical body and needs label');
    }

    public function test_notes_encrypt_current_body_and_immutable_history_with_explicit_visibility(): void
    {
        $note = $this->note('tutor');
        $this->assertSame('Private academic teaching observation', $note['body']);
        $this->assertSame('tutor', $note['visibility']);
        $this->assertStringNotContainsString($note['body'], DB::table('learner_notes')->where('id', $note['id'])->value('body'));
        $this->assertStringNotContainsString($note['body'], DB::table('learner_note_revisions')->value('body'));
        $this->assertArrayNotHasKey('body', LearnerNote::findOrFail($note['id'])->toArray());
        $list = app(LearnerNotes::class)->listing($this->actor(), $this->schoolA->ids['asha'], $this->subject, $this->year, 'tutor');
        $this->assertArrayNotHasKey('body', $list['notes'][0]);
        app(LearnerNotes::class)->update($this->actor(), $note['id'], 1, 'Revised teaching observation');
        $history = app(LearnerNotes::class)->history($this->actor(), $note['id']);
        $this->assertSame(['Revised teaching observation', $note['body']], array_column($history, 'body'));
        $this->refused(fn () => app(LearnerNotes::class)->update($this->actor(), $note['id'], 1, 'Stale body'), [409]);
        $this->assertSame(2, LearnerNoteRevision::count());
        app(LearnerNotes::class)->update($this->actor(), $note['id'], 2, 'Revised teaching observation', true);
        $this->assertSame([], app(LearnerNotes::class)->listing($this->actor(), $this->schoolA->ids['asha'], $this->subject, $this->year, 'tutor')['notes']);
        $this->assertTrue(app(LearnerNotes::class)->read($this->actor(), $note['id'])['archived']);
    }

    public function test_maximum_multibyte_body_round_trips_without_ciphertext_truncation_and_history_is_immutable(): void
    {
        $body = str_repeat('é', 20000);
        $note = $this->note('staff', $body);
        $this->assertSame($body, app(LearnerNotes::class)->read($this->actor(), $note['id'])['body']);
        $this->assertSame($body, app(LearnerNotes::class)->history($this->actor(), $note['id'])[0]['body']);
        $this->assertGreaterThan(65535, strlen(DB::table('learner_notes')->where('id', $note['id'])->value('body')));
        $this->expectException(\LogicException::class);
        LearnerNoteRevision::query()->firstOrFail()->update(['body' => 'Attempted history rewrite']);
    }

    public function test_retained_notes_and_caseload_prevent_destructive_migration_rollback(): void
    {
        $note = $this->clinical();
        $migration = require database_path('migrations/2026_10_25_060000_create_progress_bands_and_learner_notes.php');
        try {
            $migration->down();
            $this->fail('A retained clinical record cannot be dropped.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('Retained learner notes', $error->getMessage());
        }
        $this->assertSame($note['body'], app(LearnerNotes::class)->read($this->actor(), $note['id'])['body']);
        $this->assertSame(1, LearnerNoteAssignment::count());
    }

    public function test_notes_refuse_wrong_role_school_learner_subject_and_non_author_writes(): void
    {
        $note = $this->note();
        foreach (['farid', 'asha', 'theo'] as $name) {
            $this->refused(fn () => app(LearnerNotes::class)->read($this->actor($name), $note['id']));
        }
        $this->refused(fn () => app(LearnerNotes::class)->create($this->actor(), $this->schoolA->ids['ben'], $this->subject, $this->year, 'staff', 'Other learner'));
        $this->refused(fn () => app(LearnerNotes::class)->create($this->actor(), $this->schoolA->ids['asha'], $this->schoolB->ids['maths'], $this->year, 'staff', 'Other school subject'));
        $this->refused(fn () => app(LearnerNotes::class)->create($this->actor(), $this->schoolB->ids['asha'], $this->subject, $this->year, 'staff', 'Other school learner'));
        $this->principal();
        $this->assertSame($note['body'], app(LearnerNotes::class)->read($this->superAdmin(), $note['id'])['body']);
        $this->refused(fn () => app(LearnerNotes::class)->update($this->superAdmin(), $note['id'], 1, 'Overwrite another author'), [403]);
        app(CurrentSchool::class)->set(2);
        $this->refused(fn () => app(LearnerNotes::class)->read($this->actor(), $note['id']));
    }

    public function test_authorship_does_not_survive_assignment_or_teacher_role_revocation(): void
    {
        $note = $this->note();
        TeachingAllocation::where('teaching_group_id', $this->group)->update(['starts_on' => '2026-03-01']);
        $this->refused(fn () => app(LearnerNotes::class)->read($this->actor(), $note['id']));
        TeachingAllocation::where('teaching_group_id', $this->group)->update(['starts_on' => '2026-01-01']);
        $actor = $this->actor();
        $actor->syncRoles([SchoolRoles::FACILITATOR]);
        $this->refused(fn () => app(LearnerNotes::class)->read($actor, $note['id']));
        $this->refused(fn () => app(LearnerNotes::class)->history($actor, $note['id']));
    }

    public function test_counselling_requires_both_dedicated_role_and_dated_note_only_assignment(): void
    {
        $this->refused(fn () => $this->note('counselling'));
        $this->actor()->assignRole(SchoolRoles::COUNSELLOR);
        $this->refused(fn () => $this->note('counselling'));
        $note = $this->clinical();
        $this->assertSame('Restricted clinical body and needs label', app(LearnerNotes::class)->read($this->actor(), $note['id'])['body']);
        $this->refused(fn () => app(LearnerNotes::class)->read($this->superAdmin(), $note['id']));
        $this->assertCount(1, app(LearnerNotes::class)->assignments($this->superAdmin(), $this->schoolA->ids['asha']));
        $this->assertArrayNotHasKey('body', app(LearnerNotes::class)->assignments($this->superAdmin(), $this->schoolA->ids['asha'])[0]);
        app(LearnerNotes::class)->assign($this->superAdmin(), $this->schoolA->ids['asha'], $this->actor()->id,
            ['starts_on' => '2026-01-01', 'ends_on' => '2026-02-09', 'expected_revision' => 1]);
        $this->refused(fn () => app(LearnerNotes::class)->read($this->actor(), $note['id']));
        $this->assertSame(1, LearnerNoteRevision::count());
    }

    public function test_denied_clinical_reads_never_load_body_and_audit_never_copies_text(): void
    {
        $note = $this->clinical();
        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            if (str_contains($query->sql, 'learner_notes')) {
                $queries[] = $query->sql;
            }
        });
        $this->refused(fn () => app(LearnerNotes::class)->read($this->superAdmin(), $note['id']));
        $this->assertNotEmpty($queries);
        foreach ($queries as $sql) {
            $this->assertStringNotContainsString('`body`', $sql);
            $this->assertStringNotContainsString('select *', $sql);
        }
        app(LearnerNotes::class)->read($this->actor(), $note['id']);
        app(LearnerNotes::class)->history($this->actor(), $note['id']);
        $audit = DB::table('audit_log')->where('action', 'like', 'growth.note%')->get();
        $this->assertGreaterThanOrEqual(3, $audit->count());
        $this->assertStringNotContainsString($note['body'], json_encode($audit, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('Restricted clinical', json_encode($audit, JSON_THROW_ON_ERROR));
        $this->refused(fn () => app(LearnerNotes::class)->create($this->actor(), $this->schoolA->ids['ben'], null, $this->year, 'counselling', 'Other unassigned learner'));
    }

    public function test_platform_principal_cannot_administer_counselling_caseload_metadata(): void
    {
        $this->clinical();
        PlatformStaffIdentity::create(['user_id' => $this->superAdmin()->id]);
        $this->refused(fn () => app(LearnerNotes::class)->assignments($this->superAdmin(), $this->schoolA->ids['asha']), [403]);
        $this->refused(fn () => app(LearnerNotes::class)->assign($this->superAdmin(), $this->schoolA->ids['asha'], $this->actor()->id,
            ['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'expected_revision' => 1]), [403]);
        $this->assertSame(1, LearnerNoteAssignment::count());
    }

    public function test_clinical_access_denies_platform_identity_impersonation_and_stale_membership(): void
    {
        $note = $this->clinical();
        PlatformStaffIdentity::create(['user_id' => $this->actor()->id]);
        $this->refused(fn () => app(LearnerNotes::class)->read($this->actor(), $note['id']));
        PlatformStaffIdentity::query()->update(['active' => false]);
        app(CurrentSchool::class)->impersonate(School::findOrFail(1));
        $this->refused(fn () => app(LearnerNotes::class)->read($this->actor(), $note['id']));
        app(CurrentSchool::class)->set(1);
        SchoolMembership::query()->where('school_id', 1)->where('user_id', $this->actor()->id)->where('role', SchoolRoles::COUNSELLOR)->update(['status' => 'ended']);
        $this->refused(fn () => app(LearnerNotes::class)->history($this->actor(), $note['id']));
    }

    public function test_note_scope_and_visibility_are_immutable_and_historical_writes_are_refused(): void
    {
        $note = $this->note();
        $this->assertSame('staff', app(LearnerNotes::class)->update($this->actor(), $note['id'], 1, 'Only the body changes')['visibility']);
        $past = SessionYear::create(['name' => 'Retained previous year', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31']);
        LearnerNote::whereKey($note['id'])->update(['session_year_id' => $past->id]);
        $this->refused(fn () => app(LearnerNotes::class)->update($this->actor(), $note['id'], 2, 'Historical write'), [423]);
        $this->assertSame(2, LearnerNoteRevision::count());
    }

    public function test_default_band_boundaries_and_coverage_distinguish_unknown_from_zero(): void
    {
        $bands = app(ProgressBands::class);
        foreach ([[8000, 2500, 'high'], [7999, 2500, 'secure'], [6500, 2500, 'secure'], [6499, 2500, 'support'], [0, 2500, 'support'], [10000, 2499, 'insufficient_evidence'], [null, 10000, 'insufficient_evidence']] as [$mark, $coverage, $band]) {
            $row = $bands->classify($mark, $coverage);
            $this->assertSame($band, $row['band']);
            $this->assertSame($band !== 'insufficient_evidence', $row['alerts_eligible']);
        }
        $this->refused(fn () => $bands->configure($this->actor('farid'), []));
        $settings = $bands->configure($this->superAdmin(), ['high_bp' => 8500, 'secure_bp' => 7000, 'minimum_coverage_bp' => 5000, 'expected_revision' => 0]);
        $this->assertSame(1, $settings['revision']);
        $this->assertSame('secure', $bands->classify(8000, 5000)['band']);
        $this->refused(fn () => $bands->configure($this->superAdmin(), ['high_bp' => 8500, 'secure_bp' => 7000, 'minimum_coverage_bp' => 5000, 'expected_revision' => 0]), [409]);
        app(CurrentSchool::class)->set(2);
        $this->assertSame('high', $bands->classify(8000, 2500)['band']);
    }

    public function test_family_bands_use_only_frozen_released_evidence_and_require_own_approval(): void
    {
        $guardian = $this->actor('farid');
        $guardian->forceFill(['email_verified_at' => now()])->save();
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $guardian->id], ['status' => 'active', 'can_approve' => true]);
        LearnerApproval::create(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $guardian->id, 'consent_version' => config('identity.consent_version'), 'consented_at' => now()]);
        $this->capture('8.00', '8.00');
        $bands = app(ProgressBands::class);
        $staff = $bands->staff($this->actor(), $this->group, $this->schoolA->ids['asha'], $this->term);
        $this->assertSame('high', $staff['band']);
        $this->assertSame([], $bands->family($this->actor('farid'), $this->schoolA->ids['asha'], $this->year, $this->term)['results']);
        $this->release();
        $family = $bands->family($this->actor('farid'), $this->schoolA->ids['asha'], $this->year, $this->term);
        $this->assertSame('high', $family['results'][0]['band']);
        $this->capture('0.00', '0.00', 1);
        $this->assertSame('high', $bands->family($this->actor('farid'), $this->schoolA->ids['asha'], $this->year, $this->term)['results'][0]['band']);
        $this->assertSame('support', $bands->staff($this->actor(), $this->group, $this->schoolA->ids['asha'], $this->term)['band']);
        $this->refused(fn () => $bands->family($this->actor('gina'), $this->schoolA->ids['asha'], $this->year, $this->term));
        $this->refused(fn () => $bands->family($this->actor('farid'), $this->schoolB->ids['asha'], $this->year, $this->term));
        $this->refused(fn () => $bands->staff($this->actor('theo'), $this->group, $this->schoolA->ids['asha'], $this->term));
        LearnerApproval::query()->update(['withdrawn_at' => now()]);
        $this->refused(fn () => $bands->family($this->actor('farid'), $this->schoolA->ids['asha'], $this->year, $this->term), [403]);
    }
}
