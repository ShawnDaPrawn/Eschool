<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Jobs\Academics\RecomputeMarkSnapshots;
use App\Listeners\Academics\NotifyMarksReleased;
use App\Models\Academics\AcademicExamLink;
use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\AssessmentMark;
use App\Models\Academics\CurriculumPlan;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\MarkRelease;
use App\Models\Academics\MarkSnapshot;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\ClassSchool;
use App\Models\Exam;
use App\Models\ExamMarks;
use App\Models\ExamResult;
use App\Models\ExamTimetable;
use App\Models\Notification;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\Role;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use App\Services\Academics\CurriculumPlans;
use App\Services\Academics\MarkInputs;
use App\Services\Academics\MarkModeration;
use App\Services\Academics\MarkReconciliation;
use App\Services\Academics\MarkRecording;
use App\Services\Academics\MarkReleases;
use App\Services\Academics\MarkSnapshots;
use App\Services\Academics\ReleasedMarks;
use App\Services\Academics\TeachingGroups;
use App\Services\Platform\Identity\StudentNumbers;
use App\Services\Platform\Notifications\Notifier;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class MarkEngineTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private int $group;

    private int $task;

    private int $term;

    private int $year;

    private int $grade;

    private array $questions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->year = $this->schoolA->ids['session_year'];
        $this->term = $this->schoolA->ids['term'];
        $this->grade = (int) GradeLevel::where('code', '7')->value('id');
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        Term::whereKey($this->term)->update(['start_date' => '2026-01-01', 'end_date' => '2026-03-31']);
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => $this->grade]);
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'class_section_id' => $this->schoolA->ids['section_a'], 'session_year_id' => $this->year,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic mark fixture']);
        $plan = app(CurriculumPlans::class)->save($this->superAdmin(), ['subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => $this->grade, 'session_year_id' => $this->year,
            'title' => 'Mathematics', 'annual_components' => ['sba' => 10000], 'rounding' => 'half_up', 'absent_policy' => 'incomplete', 'topics' => [],
            'assessments' => [['term_id' => $this->term, 'components' => ['sba' => 10000], 'tasks' => [['name' => 'Fractions', 'type' => 'test', 'component' => 'sba',
                'annual_component' => 'sba', 'annual_weight_bp' => 10000, 'weight_bp' => 10000, 'max_mark' => '20.00', 'due_on' => '2026-02-10', 'topic_keys' => [],
                'questions' => [['number' => '1', 'max_mark' => '7.50', 'topic_keys' => []], ['number' => '2', 'max_mark' => '12.50', 'topic_keys' => []]]]]]]]);
        $plan = app(CurriculumPlans::class)->publish($this->superAdmin(), $plan->id, $plan->assessments->sole()->id, 1);
        $this->task = $plan->assessments->sole()->tasks->sole()->id;
        $this->questions = $plan->assessments->sole()->tasks->sole()->questions->pluck('id')->all();
        $group = app(TeachingGroups::class)->save($this->superAdmin(), ['name' => 'Maths class', 'subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => $this->grade,
            'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'active' => true]);
        $this->group = $group->id;
        app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', ['teacher_id' => Teacher::where('user_id', $this->schoolA->ids['tara_user'])->value('id'),
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Allocated subject'], $this->group);
        app(TeachingGroups::class)->dated($this->superAdmin(), 'memberships', ['learner_id' => $this->schoolA->ids['asha'], 'mark_rule' => 'catch_up',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Enrolled subject'], $this->group);
    }

    private function teacher(): User
    {
        return User::findOrFail($this->schoolA->ids['tara_user']);
    }

    private function gradeHead(): User
    {
        $head = User::findOrFail($this->schoolA->ids['theo_user']);
        app(TeachingGroups::class)->dated($this->superAdmin(), 'leadership', ['user_id' => $head->id, 'session_year_id' => $this->year, 'kind' => 'grade_head', 'grade_level_id' => $this->grade,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Appointed grade head']);

        return $head;
    }

    private function input(?string $first = '7.50', ?string $second = '12.50', int $revision = 0): array
    {
        return ['rows' => [['learner_id' => $this->schoolA->ids['asha'], 'expected_revision' => $revision, 'disposition' => 'recorded', 'mark' => null,
            'question_marks' => [['question_id' => $this->questions[0], 'mark' => $first], ['question_id' => $this->questions[1], 'mark' => $second]]]]];
    }

    private function capture(array $input): array
    {
        return app(MarkRecording::class)->capture($this->teacher(), $this->group, $this->task, $input);
    }

    public function test_exact_questions_are_captured_once_and_unchanged_retries_create_no_audit_or_revision(): void
    {
        $result = $this->capture($this->input());
        $this->assertSame('20.00', $result['marks'][0]['mark']);
        $this->assertSame(2000, AssessmentMark::query()->sole()->mark_units);
        $this->assertSame([750, 1250], AssessmentMark::query()->sole()->questions->pluck('mark_units')->all());
        $result = $this->capture($this->input(revision: 1));
        $this->assertFalse($result['recompute_pending']);
        $this->assertSame(1, AssessmentMark::query()->sole()->revision);
        $this->assertSame(1, DB::table('audit_log')->where('action', 'academics.mark_captured')->count());
    }

    public function test_partial_questions_are_saved_as_incomplete_and_not_silently_zeroed(): void
    {
        $result = $this->capture($this->input('5.50', null));
        $this->assertNull($result['marks'][0]['mark']);
        app(MarkSnapshots::class)->recompute($this->group, $this->term);
        $term = MarkSnapshot::where('term_id', $this->term)->sole();
        $this->assertNull($term->percentage_bp);
        $this->assertNull($term->running_bp);
        $this->assertFalse($term->complete);
    }

    public function test_out_of_range_foreign_questions_and_binary_floats_are_atomic(): void
    {
        foreach (['7.51', 7.5, '1e2'] as $invalid) {
            $row = $this->input();
            $row['rows'][0]['question_marks'][0]['mark'] = $invalid;
            try {
                $this->capture($row);
                $this->fail('Invalid score');
            } catch (ValidationException) {
                $this->assertSame(0, AssessmentMark::count());
            }
        }
        $row = $this->input();
        $row['rows'][0]['question_marks'][0]['question_id'] = 999999;
        try {
            $this->capture($row);
            $this->fail('Foreign question');
        } catch (ValidationException) {
            $this->assertSame(0, AssessmentMark::count());
        }
    }

    public function test_stale_edits_and_locks_refuse_changes_with_conflict(): void
    {
        $this->capture($this->input());
        try {
            $this->capture($this->input('5.00', '10.00'));
            $this->fail('Stale');
        } catch (HttpException $e) {
            $this->assertSame(409, $e->getStatusCode());
        }
        Term::whereKey($this->term)->update(['marks_lock_on' => '2026-02-10']);
        try {
            $this->capture($this->input('5.00', '10.00', 1));
            $this->fail('Locked');
        } catch (HttpException $e) {
            $this->assertSame(409, $e->getStatusCode());
        }
        $this->assertSame(2000, AssessmentMark::query()->sole()->mark_units);
    }

    public function test_only_allocated_teachers_capture_and_foreign_or_nonmember_learners_are_refused(): void
    {
        foreach ([$this->superAdmin(), User::findOrFail($this->schoolA->ids['theo_user']), User::findOrFail($this->schoolA->ids['farid_user'])] as $actor) {
            try {
                app(MarkRecording::class)->capture($actor, $this->group, $this->task, $this->input());
                $this->fail('Unallocated actor');
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }
        $row = $this->input();
        $row['rows'][0]['learner_id'] = $this->schoolB->ids['asha'];
        try {
            $this->capture($row);
            $this->fail('Other school');
        } catch (ModelNotFoundException|HttpException $e) {
            $this->assertSame(0, AssessmentMark::count());
        }
        $row['rows'][0]['learner_id'] = $this->schoolA->ids['ben'];
        try {
            $this->capture($row);
            $this->fail('Other group learner');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_grade_head_moderation_and_correction_are_scoped_audited_and_require_a_reason(): void
    {
        $this->capture($this->input());
        $head = $this->gradeHead();
        app(MarkModeration::class)->moderate($head, $this->group, $this->task, ['reason' => 'Checked against script', 'rows' => [['learner_id' => $this->schoolA->ids['asha'], 'expected_revision' => 1]]]);
        $this->assertSame('moderated', AssessmentMark::query()->sole()->status);
        try {
            $this->capture($this->input('5.00', '10.00', 2));
            $this->fail('Teacher cannot alter moderation');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $input = $this->input('5.00', '10.00', 2);
        $input['rows'][0]['note'] = 'Corrected addition on the reviewed script';
        app(MarkRecording::class)->capture($head, $this->group, $this->task, $input, true);
        $this->assertSame(1500, AssessmentMark::query()->sole()->mark_units);
        $this->assertSame('captured', AssessmentMark::query()->sole()->status);
        $this->assertSame(1, DB::table('audit_log')->where('action', 'academics.mark_moderated')->count());
    }

    public function test_snapshots_are_exact_immutable_idempotent_and_jobs_never_cross_schools(): void
    {
        $this->capture($this->input('5.00', '10.00'));
        $this->assertSame(['created' => 2, 'unchanged' => 0], app(MarkSnapshots::class)->recompute($this->group, $this->term));
        $snapshot = MarkSnapshot::where('term_id', $this->term)->sole();
        $this->assertSame(7500, $snapshot->percentage_bp);
        $this->assertSame(6, $snapshot->rating_level);
        $this->assertSame(['created' => 0, 'unchanged' => 2], app(MarkSnapshots::class)->recompute($this->group, $this->term));
        try {
            $snapshot->update(['percentage_bp' => 100]);
            $this->fail('Immutable');
        } catch (\LogicException) {
            $this->assertSame(7500, $snapshot->fresh()->percentage_bp);
        }
        $job = new RecomputeMarkSnapshots(2, $this->group, $this->term);
        try {
            app()->call([$job, 'handle']);
            $this->fail('Other school job');
        } catch (ModelNotFoundException) {
            $this->assertSame(2, MarkSnapshot::count());
        }
    }

    private function releaseInput(string $mode = 'official'): array
    {
        return ['term_id' => $this->term, 'mode' => $mode, 'learner_ids' => [$this->schoolA->ids['asha']]];
    }

    private function release(array $input): array
    {
        $service = app(MarkReleases::class);
        $preview = $service->preview($this->superAdmin(), $this->group, $input);

        return $service->release($this->superAdmin(), $this->group, $input + ['fingerprint' => $preview['fingerprint']]);
    }

    public function test_release_pins_evidence_and_projects_exact_official_results_to_existing_apps(): void
    {
        $this->capture($this->input('5.01', '10.00'));
        $release = $this->release($this->releaseInput());
        $again = $this->release($this->releaseInput());
        $this->assertSame($release['id'], $again['id']);
        $this->assertSame(1, MarkRelease::count());
        $result = ExamResult::where('exact_total_units', 10000)->sole();
        $this->assertSame(75.05, $result->obtained_marks);
        $this->assertSame(75.05, (float) $result->percentage);
        $this->assertArrayNotHasKey('exact_obtained_units', $result->toArray());
        $family = app(ReleasedMarks::class);
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $view = $family->list($parent, $this->schoolA->ids['asha'], $this->year, $this->term);
        $this->assertSame(7505, $view['results'][0]['percentage_bp']);
        $this->capture($this->input('7.50', '12.50', 1));
        app(MarkSnapshots::class)->recompute($this->group, $this->term);
        $this->assertSame(7505, $family->list($parent, $this->schoolA->ids['asha'], $this->year, $this->term)['results'][0]['percentage_bp']);
        $this->assertSame(75.05, $result->fresh()->obtained_marks);
        $next = $this->release($this->releaseInput());
        $this->assertSame(2, $next['version']);
        $this->assertSame(10000, $family->list($parent, $this->schoolA->ids['asha'], $this->year, $this->term)['results'][0]['percentage_bp']);
    }

    public function test_release_requires_fresh_preview_and_incomplete_marks_cannot_be_official(): void
    {
        $service = app(MarkReleases::class);
        $input = $this->releaseInput();
        $preview = $service->preview($this->superAdmin(), $this->group, $input);
        $this->assertFalse($preview['can_release']);
        $this->capture($this->input());
        try {
            $service->release($this->superAdmin(), $this->group, $input + ['fingerprint' => $preview['fingerprint']]);
            $this->fail('Stale preview');
        } catch (HttpException $e) {
            $this->assertSame(409, $e->getStatusCode());
        }
        $this->assertSame(0, MarkRelease::count());
    }

    public function test_running_release_is_explicitly_provisional_and_does_not_publish_v1_official_results(): void
    {
        $this->capture($this->input());
        $release = $this->release($this->releaseInput('running'));
        $this->assertTrue($release['provisional']);
        $this->assertSame(0, AcademicExamLink::count());
    }

    public function test_future_calendar_definition_blocks_official_year_result_without_blocking_term(): void
    {
        Term::create(['name' => 'Next term', 'session_year_id' => $this->year, 'start_date' => '2026-04-01', 'end_date' => '2026-06-30', 'grade_from' => 0, 'grade_to' => 12]);
        $this->capture($this->input());
        app(MarkSnapshots::class)->recompute($this->group, $this->term);
        $this->assertTrue(MarkSnapshot::where('term_id', $this->term)->sole()->complete);
        $year = MarkSnapshot::whereNull('term_id')->sole();
        $this->assertFalse($year->complete);
        $this->assertSame(10000, $year->running_bp);
        $this->assertContains('annual_terms_unpublished', $year->result['warnings']);
        $this->assertTrue(app(MarkReleases::class)->preview($this->superAdmin(), $this->group, $this->releaseInput())['can_release']);
    }

    private function api(User $user): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($user->createToken('marks')->plainTextToken);
    }

    public function test_api_capture_preview_matrix_moderation_and_family_release_permissions(): void
    {
        $path = '/api/v2/academics/marks/groups/'.$this->group;
        $task = $path.'/tasks/'.$this->task;
        $this->getJson($path.'?term_id='.$this->term)->assertUnauthorized();
        $this->api($this->teacher())->getJson($path.'?term_id='.$this->term)->assertOk()->assertJsonPath('data.learners.0.result.percentage_bp', null);
        $this->postJson($task.'/preview', $this->input('5.00', '10.00'))->assertOk()->assertJsonPath('data.previews.0.result.percentage_bp', 7500);
        $this->assertSame(0, AssessmentMark::count());
        $this->postJson($task.'/capture', $this->input('5.00', '10.00'))->assertOk()->assertJsonPath('data.marks.0.revision', 1);
        $this->postJson($path.'/release-preview', $this->releaseInput())->assertForbidden();
        $this->postJson($task.'/moderate', ['reason' => 'Reviewed', 'rows' => [['learner_id' => $this->schoolA->ids['asha'], 'expected_revision' => 1]]])->assertForbidden();
        $head = $this->gradeHead();
        $this->api($head)->postJson($task.'/moderate', ['reason' => 'Reviewed', 'rows' => [['learner_id' => $this->schoolA->ids['asha'], 'expected_revision' => 1]]])->assertOk();
        $preview = $this->postJson($path.'/release-preview', $this->releaseInput())->assertOk()->json('data');
        $this->postJson($path.'/release', $this->releaseInput() + ['fingerprint' => $preview['fingerprint']])->assertOk();
        $family = '/api/v2/academics/marks/learners/'.$this->schoolA->ids['asha'].'?session_year_id='.$this->year.'&term_id='.$this->term;
        $this->api(User::findOrFail($this->schoolA->ids['farid_user']))->getJson($family)->assertOk()->assertJsonPath('data.results.0.percentage_bp', 7500);
        $this->postJson($task.'/capture', $this->input())->assertForbidden();
        $this->api($this->teacher())->getJson($family)->assertNotFound();
        $this->getJson('/api/v2/academics/marks/learners/'.$this->schoolB->ids['asha'].'?session_year_id='.$this->year)->assertNotFound();
        $this->getJson($path.'?term_id='.$this->schoolB->ids['term'])->assertNotFound();
        $this->withHeader('X-School-Id', '2')->postJson($task.'/capture', $this->input())->assertForbidden();
    }

    public function test_ended_guardian_link_revokes_even_released_result_access_and_fees_do_not_gate_it(): void
    {
        $this->capture($this->input());
        $this->release($this->releaseInput());
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $link = GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $parent->id], ['relationship' => 'father', 'status' => 'active', 'can_see_fees' => false, 'can_approve' => true]);
        $family = app(ReleasedMarks::class);
        $this->assertCount(1, $family->list($parent, $this->schoolA->ids['asha'], $this->year, $this->term)['results']);
        $link->update(['status' => 'ended']);
        try {
            $family->list($parent, $this->schoolA->ids['asha'], $this->year, $this->term);
            $this->fail('Ended link');
        } catch (HttpException $e) {
            $this->assertSame(404, $e->getStatusCode());
        }
    }

    public function test_revoked_teacher_cannot_replay_an_idempotent_capture(): void
    {
        $this->api($this->teacher())->withHeader('Idempotency-Key', 'marks-capture-one');
        $path = '/api/v2/academics/marks/groups/'.$this->group.'/tasks/'.$this->task.'/capture';
        $this->postJson($path, $this->input())->assertOk();
        TeachingAllocation::where('teaching_group_id', $this->group)->update(['ends_on' => '2026-02-09']);
        $this->postJson($path, $this->input())->assertForbidden();
        $this->assertSame(1, AssessmentMark::query()->sole()->revision);
    }

    public function test_same_year_definition_copy_reuses_scores_and_rejects_removing_recorded_roots(): void
    {
        $this->capture($this->input('5.00', '10.00'));
        $service = app(CurriculumPlans::class);
        $plan = CurriculumPlan::query()->sole();
        $copy = $service->copy($this->superAdmin(), (int) $plan->id, $this->year);
        $service->publish($this->superAdmin(), $copy->id, $copy->assessments->sole()->id, 1);
        $group = TeachingGroup::findOrFail($this->group);
        $inputs = app(MarkInputs::class)->forLearner($group, $this->schoolA->ids['asha'], $this->term);
        $this->assertSame(1, count($inputs['tasks']));
        $this->assertSame($this->task, $inputs['tasks'][0]['canonical_task_id']);
        $this->assertSame(7500, app(MarkInputs::class)->calculate($inputs)['percentage_bp']);
        $next = $service->copy($this->superAdmin(), $copy->id, $this->year);
        $data = $service->data($next);
        $data['assessments'][0]['tasks'][0]['key'] = (string) Str::uuid();
        $next = $service->save($this->superAdmin(), $data, $next->id);
        try {
            $service->publish($this->superAdmin(), $next->id, $next->assessments->sole()->id, $next->revision);
            $this->fail('Recorded task must remain in a revision');
        } catch (HttpException $e) {
            $this->assertSame(409, $e->getStatusCode());
        }
        $this->assertSame(1500, AssessmentMark::query()->sole()->mark_units);
    }

    public function test_reconciliation_reports_exact_differences_duplicates_and_other_school_isolation(): void
    {
        $this->capture($this->input('5.01', '10.00'));
        app(MarkSnapshots::class)->recompute($this->group, $this->term);
        $number = app(StudentNumbers::class)->assign(Students::findOrFail($this->schoolA->ids['asha']));
        $code = Subject::findOrFail($this->schoolA->ids['maths'])->code;
        $file = tempnam(sys_get_temp_dir(), 'marks-sheet-');
        try {
            file_put_contents($file, "student_number,subject_code,percentage\n$number,$code,75.05\n");
            $result = app(MarkReconciliation::class)->compare($this->term, $file);
            $this->assertSame(1, $result['counts']['matched']);
            file_put_contents($file, "student_number,subject_code,percentage\n$number,$code,75.04\n");
            $result = app(MarkReconciliation::class)->compare($this->term, $file);
            $this->assertSame(1, $result['counts']['different']);
            $this->assertSame(7505, $result['rows'][0]['actual_bp']);
            file_put_contents($file, "$number,$code,75.04\n", FILE_APPEND);
            try {
                app(MarkReconciliation::class)->compare($this->term, $file);
                $this->fail('Duplicate');
            } catch (ValidationException) {
                $this->assertSame(2, MarkSnapshot::count());
            }
            try {
                app(MarkReconciliation::class)->compare($this->schoolB->ids['term'], $file);
                $this->fail('Other school');
            } catch (ModelNotFoundException) {
                $this->assertSame(2, MarkSnapshot::count());
            }
        } finally {
            unlink($file);
        }
    }

    public function test_projection_rejects_older_model_edits_and_deletions_without_touching_release(): void
    {
        $this->capture($this->input());
        $this->release($this->releaseInput());
        $link = AcademicExamLink::query()->sole();
        $exam = Exam::findOrFail($link->exam_id);
        foreach ([fn () => $exam->forceFill(['name' => 'Bypass'])->save(), fn () => $exam->delete(), fn () => ExamMarks::where('exact_obtained_units', 10000)->sole()->delete()] as $attempt) {
            try {
                $attempt();
                $this->fail('Projected results are protected');
            } catch (HttpException $e) {
                $this->assertSame(409, $e->getStatusCode());
            }
        }
        $this->assertSame(1, MarkRelease::count());
        $this->assertFalse($exam->fresh()->trashed());
        $this->assertNull(ExamMarks::where('exact_obtained_units', 10000)->sole()->passing_status);
    }

    public function test_school_timezone_lock_applies_before_utc_midnight(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-02-09 22:01:00', 'UTC'));
        Term::whereKey($this->term)->update(['marks_lock_on' => '2026-02-10']);
        try {
            $this->capture($this->input());
            $this->fail('School midnight has passed');
        } catch (HttpException $e) {
            $this->assertSame(409, $e->getStatusCode());
        }
        $this->assertSame(0, AssessmentMark::count());
    }

    public function test_release_permission_revocation_is_checked_before_idempotent_replay(): void
    {
        $this->capture($this->input());
        $path = '/api/v2/academics/marks/groups/'.$this->group;
        $this->api($this->superAdmin());
        $preview = $this->postJson($path.'/release-preview', $this->releaseInput())->assertOk()->json('data');
        $this->withHeader('Idempotency-Key', 'release-for-school');
        $input = $this->releaseInput() + ['fingerprint' => $preview['fingerprint']];
        $this->postJson($path.'/release', $input)->assertOk();
        Role::where('name', 'Super Admin')->firstOrFail()->revokePermissionTo('marks-release');
        $this->postJson($path.'/release', $input)->assertForbidden();
        $this->assertSame(1, MarkRelease::count());
    }

    public function test_release_notification_retries_are_scoped_and_do_not_notify_ended_guardians(): void
    {
        $this->capture($this->input());
        $release = $this->release($this->releaseInput());
        $parentId = $this->schoolA->ids['farid_user'];
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $parentId], ['relationship' => 'father', 'status' => 'ended', 'can_approve' => false]);
        $recipients = [];
        $this->partialMock(Notifier::class, function ($mock) use (&$recipients) {
            $mock->shouldReceive('assign')->twice()->andReturnUsing(function ($notice, $ids) use (&$recipients) {
                $recipients = array_merge($recipients, $ids);
            });
        });
        $event = new OutboxEvent(['school_id' => 1, 'payload' => ['release_id' => $release['id']]]);
        $listener = app(NotifyMarksReleased::class);
        $listener->handle($event);
        $listener->handle($event);
        $this->assertNotContains($parentId, $recipients);
        $this->assertContains($this->schoolA->ids['asha_user'], $recipients);
        $this->assertSame(1, Notification::where('source_key', 'marks-release:'.$release['id'].':'.$this->schoolA->ids['asha'])->count());
        try {
            $listener->handle(new OutboxEvent(['school_id' => 2, 'payload' => ['release_id' => $release['id']]]));
            $this->fail('Foreign event must not disclose a release');
        } catch (ModelNotFoundException) {
            $this->assertSame(1, MarkRelease::count());
        }
    }

    public function test_legacy_capture_shares_exact_arithmetic_and_term_locks(): void
    {
        $exam = Exam::findOrFail($this->schoolA->ids['unit_test']);
        $timetable = ExamTimetable::where('exam_id', $exam->id)->where('subject_id', $this->schoolA->ids['science'])->firstOrFail();
        app(MarkRecording::class)->legacy($this->teacher(), [['timetable_id' => $timetable->id, 'learner_id' => $this->schoolA->ids['asha'], 'mark' => '45.25']]);
        $mark = ExamMarks::where('exam_timetable_id', $timetable->id)->where('student_id', $this->schoolA->ids['asha'])->sole();
        $this->assertSame(45.25, $mark->obtained_marks);
        $this->assertSame(4525, (int) $mark->exact_obtained_units);
        $this->assertSame(1, DB::table('audit_log')->where('action', 'academics.legacy_mark_captured')->count());
        $exam->term_id = $this->term;
        $exam->save();
        Term::whereKey($this->term)->update(['marks_lock_on' => '2026-02-10']);
        try {
            app(MarkRecording::class)->legacy($this->teacher(), [['timetable_id' => $timetable->id, 'learner_id' => $this->schoolA->ids['asha'], 'mark' => '40.00']]);
            $this->fail('Legacy writes must respect the same lock');
        } catch (HttpException $e) {
            $this->assertSame(409, $e->getStatusCode());
        }
        $this->assertSame(45.25, $mark->fresh()->obtained_marks);
    }

    public function test_dated_principal_can_release_but_cannot_correct_or_moderate_without_grade_head_appointment(): void
    {
        $principal = User::findOrFail($this->schoolA->ids['theo_user']);
        app(TeachingGroups::class)->dated($this->superAdmin(), 'leadership', ['user_id' => $principal->id, 'session_year_id' => $this->year,
            'kind' => 'principal', 'grade_level_id' => null, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Principal appointment']);
        $this->capture($this->input());
        $path = '/api/v2/academics/marks/groups/'.$this->group;
        $this->api($principal)->getJson($path.'?term_id='.$this->term)->assertOk();
        $this->postJson($path.'/release-preview', $this->releaseInput())->assertOk()->assertJsonPath('data.can_release', true);
        $this->postJson($path.'/tasks/'.$this->task.'/correct', $this->input('5.00', '10.00', 1))->assertForbidden();
        AcademicLeadership::where('user_id', $principal->id)->update(['ends_on' => '2026-02-09']);
        $this->postJson($path.'/release-preview', $this->releaseInput())->assertForbidden();
    }

    public function test_grade_head_correction_api_requires_reason_and_retains_every_question(): void
    {
        $this->capture($this->input());
        $path = '/api/v2/academics/marks/groups/'.$this->group.'/tasks/'.$this->task.'/correct';
        $this->api($this->gradeHead());
        $input = $this->input('5.00', '10.00', 1);
        $this->postJson($path, $input)->assertUnprocessable();
        $input['rows'][0]['note'] = 'Rechecked against learner script';
        $this->postJson($path, $input)->assertOk()->assertJsonPath('data.marks.0.mark', '15.00')->assertJsonPath('data.marks.0.revision', 2);
        $this->assertSame([500, 1000], AssessmentMark::query()->sole()->questions->pluck('mark_units')->all());
    }
}
