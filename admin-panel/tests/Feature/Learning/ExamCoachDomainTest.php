<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Academics\TeachingAllocation;
use App\Models\Exam;
use App\Models\ExamTimetable;
use App\Models\Learning\ExamCoachOperation;
use App\Models\Learning\ExamStudyPlan;
use App\Models\Learning\PastPaper;
use App\Models\Learning\PastPaperVersion;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\SchoolModule;
use App\Models\Students;
use App\Models\Teacher;
use App\Services\Academics\TeachingGroups;
use App\Services\Learning\ExamCoachDates;
use App\Services\Learning\ExamStudyPlans;
use App\Services\Learning\PastPaperLibrary;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\UsesPracticeScenario;
use Tests\TestCase;

class ExamCoachDomainTest extends TestCase
{
    use UsesPracticeScenario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        SchoolModule::updateOrCreate(['module' => 'exam_coach'], ['enabled' => true, 'in_package' => true]);
    }

    private function input(): array
    {
        return ['operation_id' => (string) Str::uuid(), 'expected_revision' => 0, 'title' => 'Synthetic licensed fractions paper',
            'framework' => 'school', 'paper_year' => 2025, 'paper_code' => 'P1', 'group_ids' => [$this->group],
            'licence' => ['kind' => 'school_owned', 'reference' => 'Synthetic original school paper',
                'statement' => 'Synthetic school-owned rights confirmed for testing only.', 'confirmed' => true, 'expires_on' => '2026-12-31'],
            'questions' => [['number' => '1', 'prompt' => 'Explain one half.', 'memo' => 'PRIVATE-MEMO-CANARY: two equal parts.',
                'max_units' => 1000, 'topic_ids' => [$this->nodes[$this->keys[1]]]]]];
    }

    private function paper(): array
    {
        $draft = app(PastPaperLibrary::class)->draft($this->actor(), $this->group, null, $this->input());

        return app(PastPaperLibrary::class)->publish($this->actor(), $this->group, $draft['id'], $draft['version_id'],
            ['operation_id' => (string) Str::uuid(), 'expected_revision' => $draft['revision']]);
    }

    private function exam(): ExamTimetable
    {
        $exam = Exam::forceCreate(['name' => 'Synthetic actual upcoming exam',
            'session_year_id' => $this->year, 'publish' => 1]);

        return ExamTimetable::forceCreate(['exam_id' => $exam->id, 'class_id' => $this->schoolA->ids['class_a'],
            'subject_id' => $this->subject, 'total_marks' => 100, 'passing_marks' => 40, 'date' => '2026-03-02',
            'start_time' => '08:00:00', 'end_time' => '09:00:00', 'session_year_id' => $this->year]);
    }

    public function test_reviewed_paper_is_encrypted_and_learner_never_receives_memo(): void
    {
        $input = $this->input();
        $draft = app(PastPaperLibrary::class)->draft($this->actor(), $this->group, null, $input);
        $this->assertSame($draft, app(PastPaperLibrary::class)->draft($this->actor(), $this->group, null, $input));
        $this->assertSame(1, PastPaper::count());
        $this->refuses(fn () => app(PastPaperLibrary::class)->data($this->actor('asha'), $this->group, $draft['version_id']), 404);
        $version = PastPaperVersion::findOrFail($draft['version_id']);
        $this->assertStringNotContainsString('PRIVATE-MEMO-CANARY', $version->getRawOriginal('definition'));
        $receipt = app(PastPaperLibrary::class)->publish($this->actor(), $this->group, $draft['id'], $draft['version_id'],
            ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1]);
        $learner = app(PastPaperLibrary::class)->data($this->actor('asha'), $this->group, $receipt['version_id']);
        $this->assertSame('Explain one half.', $learner['questions'][0]['prompt']);
        $this->assertArrayNotHasKey('memo', $learner['questions'][0]);
        $this->assertStringNotContainsString('PRIVATE-MEMO-CANARY', json_encode($learner, JSON_THROW_ON_ERROR));
        $staff = app(PastPaperLibrary::class)->data($this->actor(), $this->group, $receipt['version_id'], true);
        $this->assertStringContainsString('PRIVATE-MEMO-CANARY', $staff['questions'][0]['memo']);
    }

    public function test_wrong_role_school_and_guardian_withdrawal_cannot_read_paper(): void
    {
        $paper = $this->paper();
        $this->refuses(fn () => app(PastPaperLibrary::class)->data($this->actor('farid'), $this->group, $paper['version_id']), 404);
        $this->refuses(fn () => app(CurrentSchool::class)->run(2, fn () => app(PastPaperLibrary::class)->data($this->actor('asha'), $this->group, $paper['version_id'])), 404);
        LearnerApproval::where('learner_id', $this->schoolA->ids['asha'])->update(['withdrawn_at' => now()]);
        $this->refuses(fn () => app(PastPaperLibrary::class)->data($this->actor('asha'), $this->group, $paper['version_id']), 403);
    }

    public function test_expired_or_withdrawn_rights_and_stale_revisions_are_refused(): void
    {
        $paper = $this->paper();
        $this->refuses(fn () => app(PastPaperLibrary::class)->withdraw($this->actor(), $this->group, $paper['id'],
            ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1, 'reason' => 'Synthetic withdrawal']), 409);
        PastPaperVersion::findOrFail($paper['version_id'])->update(['licence_expires_on' => '2026-02-09']);
        $this->refuses(fn () => app(PastPaperLibrary::class)->data($this->actor('asha'), $this->group, $paper['version_id']), 409);
        PastPaperVersion::findOrFail($paper['version_id'])->update(['licence_expires_on' => '2026-12-31']);
        app(PastPaperLibrary::class)->withdraw($this->actor(), $this->group, $paper['id'], ['operation_id' => (string) Str::uuid(),
            'expected_revision' => $paper['revision'], 'reason' => 'Synthetic rights withdrawal']);
        $this->refuses(fn () => app(PastPaperLibrary::class)->data($this->actor('asha'), $this->group, $paper['version_id']), 404);
        $this->assertSame(1, PastPaperVersion::count());
        $record = PastPaper::findOrFail($paper['id']);
        $this->assertSame('Synthetic rights withdrawal', $record->withdrawal_reason);
        $this->assertSame((int) $this->actor()->id, (int) $record->withdrawn_by);
        $this->assertStringNotContainsString('Synthetic rights withdrawal', $record->getRawOriginal('withdrawal_reason'));
    }

    public function test_replay_rechecks_current_teacher_allocation_and_exact_content(): void
    {
        $input = $this->input();
        app(PastPaperLibrary::class)->draft($this->actor(), $this->group, null, $input);
        $this->refuses(fn () => app(PastPaperLibrary::class)->draft($this->actor(), $this->group, null, array_replace($input, ['title' => 'Changed intent'])), 409);
        TeachingAllocation::where('teaching_group_id', $this->group)->update(['ends_on' => '2026-02-09']);
        $this->refuses(fn () => app(PastPaperLibrary::class)->draft($this->actor(), $this->group, null, $input), 403);
        $this->assertSame(1, ExamCoachOperation::count());
    }

    public function test_licence_confirmation_and_current_topic_references_are_required(): void
    {
        $input = $this->input();
        $input['licence']['confirmed'] = false;
        try {
            app(PastPaperLibrary::class)->draft($this->actor(), $this->group, null, $input);
            $this->fail('An unchecked licence cannot create a reviewed paper.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('licence.confirmed', $error->errors());
        }
        $input = $this->input();
        $input['questions'][0]['topic_ids'] = [999999];
        $this->refuses(fn () => app(PastPaperLibrary::class)->draft($this->actor(), $this->group, null, $input), 422);
        $this->assertSame(0, PastPaper::count());
    }

    public function test_study_plan_counts_back_from_own_exam_and_revalidates_schedule(): void
    {
        $paper = $this->paper();
        $exam = $this->exam();
        $choices = app(ExamCoachDates::class)->choices($this->actor('asha'), $this->group);
        $choice = collect($choices)->firstWhere('id', $exam->id);
        $this->assertNotNull($choice);
        $input = ['operation_id' => (string) Str::uuid(), 'exam_timetable_id' => $exam->id, 'schedule_signature' => $choice['schedule_signature'],
            'paper_version_ids' => [$paper['version_id']], 'study_minutes' => 20];
        $receipt = app(ExamStudyPlans::class)->create($this->actor('asha'), $this->group, $input);
        $this->assertSame($receipt, app(ExamStudyPlans::class)->create($this->actor('asha'), $this->group, $input));
        $plan = app(ExamStudyPlans::class)->read($this->actor('asha'), $receipt['id']);
        $this->assertCount(14, $plan['days']);
        $this->assertSame('2026-02-27', $plan['days'][13]['date']);
        $this->assertSame('Fractions', $plan['days'][0]['topic_name']);
        $this->assertSame(1, ExamStudyPlan::count());
        $this->refuses(fn () => app(ExamStudyPlans::class)->read($this->actor('farid'), $receipt['id']), 404);
        $exam->forceFill(['date' => '2026-03-03'])->save();
        $this->refuses(fn () => app(ExamStudyPlans::class)->read($this->actor('asha'), $receipt['id']), 409);
        $this->refuses(fn () => app(ExamStudyPlans::class)->create($this->actor('asha'), $this->group, $input), 409);
    }

    public function test_integer_weighted_schedule_preserves_paper_share_and_is_bounded(): void
    {
        $rows = app(ExamStudyPlans::class)->schedule('2026-02-02', '2026-03-02', [1 => 3, 2 => 1], 20);
        $this->assertCount(20, $rows);
        $this->assertSame([1 => 15, 2 => 5], array_count_values(array_column($rows, 'topic_id')));
        $this->assertSame($rows, app(ExamStudyPlans::class)->schedule('2026-02-02', '2026-03-02', [1 => 3, 2 => 1], 20));
        $this->refuses(fn () => app(ExamStudyPlans::class)->schedule('2026-02-02', '2028-03-02', [1 => 3], 20), 422);
    }

    public function test_paper_and_staff_memo_render_real_private_pdfs(): void
    {
        $paper = $this->paper();
        $bytes = app(PastPaperLibrary::class)->document($this->actor('asha'), $this->group, $paper['version_id']);
        $this->assertStringStartsWith('%PDF-', $bytes);
        $memo = app(PastPaperLibrary::class)->document($this->actor(), $this->group, $paper['version_id'], true);
        $this->assertStringStartsWith('%PDF-', $memo);
        $this->refuses(fn () => app(PastPaperLibrary::class)->document($this->actor('asha'), $this->group, $paper['version_id'], true), 403);
    }

    public function test_reused_topics_across_questions_are_valid_but_duplicate_question_numbers_are_not(): void
    {
        $input = $this->input();
        $input['questions'][] = array_replace($input['questions'][0], ['number' => '2']);
        $draft = app(PastPaperLibrary::class)->draft($this->actor(), $this->group, null, $input);
        $this->assertCount(2, PastPaperVersion::findOrFail($draft['version_id'])->definition['questions']);
        $input['operation_id'] = (string) Str::uuid();
        $input['questions'][1]['number'] = '1';
        try {
            app(PastPaperLibrary::class)->draft($this->actor(), $this->group, null, $input);
            $this->fail('Question keys must be unambiguous.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('questions.0.number', $error->errors());
        }
    }

    public function test_mastery_visibility_withdrawal_invalidates_derived_plan_and_disabled_mastery_still_allows_basic_plan(): void
    {
        $paper = $this->paper();
        $exam = $this->exam();
        $input = ['operation_id' => (string) Str::uuid(), 'exam_timetable_id' => $exam->id,
            'schedule_signature' => app(ExamCoachDates::class)->signature($exam), 'paper_version_ids' => [$paper['version_id']], 'study_minutes' => 20];
        $receipt = app(ExamStudyPlans::class)->create($this->actor('asha'), $this->group, $input);
        SchoolModule::where('module', 'mastery')->update(['enabled' => false]);
        $this->refuses(fn () => app(ExamStudyPlans::class)->read($this->actor('asha'), $receipt['id']), 409);
        $input['operation_id'] = (string) Str::uuid();
        $basic = app(ExamStudyPlans::class)->create($this->actor('asha'), $this->group, $input);
        $this->assertCount(14, app(ExamStudyPlans::class)->read($this->actor('asha'), $basic['id'])['days']);
    }

    public function test_open_ended_enrolment_can_read_its_own_exam_choices(): void
    {
        Enrolment::where('learner_id', $this->schoolA->ids['asha'])->where('session_year_id', $this->year)->update(['ends_on' => null]);
        $exam = $this->exam();
        $choices = app(ExamCoachDates::class)->choices($this->actor('asha'), $this->group);
        $this->assertContains((int) $exam->id, array_column($choices, 'id'));
    }

    public function test_a_current_allocated_reader_cannot_edit_another_teachers_paper(): void
    {
        $paper = $this->paper();
        $reader = $this->actor('theo');
        $reader->forceFill(['password_change_required' => false])->save();
        app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', ['teacher_id' => Teacher::where('user_id', $reader->id)->value('id'),
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic additional reader'], $this->group);
        $this->assertSame('Synthetic licensed fractions paper', app(PastPaperLibrary::class)->data($reader, $this->group, $paper['version_id'], true)['title']);
        $this->refuses(fn () => app(PastPaperLibrary::class)->draft($reader, $this->group, $paper['id'], array_replace($this->input(),
            ['expected_revision' => $paper['revision']])), 403);
        $this->assertSame(1, PastPaperVersion::count());
    }

    public function test_another_approved_student_cannot_read_the_owners_study_plan(): void
    {
        $paper = $this->paper();
        $exam = $this->exam();
        $receipt = app(ExamStudyPlans::class)->create($this->actor('asha'), $this->group, ['operation_id' => (string) Str::uuid(),
            'exam_timetable_id' => $exam->id, 'schedule_signature' => app(ExamCoachDates::class)->signature($exam),
            'paper_version_ids' => [$paper['version_id']], 'study_minutes' => 20]);
        $reader = $this->actor('ben');
        $reader->forceFill(['password_change_required' => false, 'status' => 1])->save();
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['ben'], 'guardian_user_id' => $this->actor('farid')->id],
            ['relationship' => 'father', 'status' => 'active', 'can_approve' => true]);
        LearnerApproval::updateOrCreate(['learner_id' => $this->schoolA->ids['ben'], 'guardian_user_id' => $this->actor('farid')->id],
            ['consent_version' => config('identity.consent_version'), 'consented_at' => now(), 'withdrawn_at' => null]);
        $this->assertTrue(app(ApprovalService::class)->approved(Students::findOrFail($this->schoolA->ids['ben'])));
        $this->refuses(fn () => app(ExamStudyPlans::class)->read($reader, $receipt['id']), 404);
    }
}
