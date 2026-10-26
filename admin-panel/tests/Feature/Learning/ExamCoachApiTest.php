<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Academics\TeachingAllocation;
use App\Models\Exam;
use App\Models\ExamTimetable;
use App\Models\Learning\PastPaper;
use App\Models\Learning\PastPaperVersion;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\SchoolModule;
use App\Models\Teacher;
use App\Services\Academics\TeachingGroups;
use App\Services\Learning\PastPaperLibrary;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPracticeScenario;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class ExamCoachApiTest extends TestCase
{
    use UsesPracticeScenario, ValidatesOpenApiResponses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        SchoolModule::updateOrCreate(['module' => 'exam_coach'], ['enabled' => true, 'in_package' => true]);
    }

    private function signIn(string $name = 'tara'): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->withToken($this->schoolA->tokens[$name])->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $this->actor($name)->id]);
    }

    private function input(): array
    {
        return ['operation_id' => (string) Str::uuid(), 'expected_revision' => 0, 'title' => 'Synthetic licensed paper', 'framework' => 'school', 'paper_year' => 2025,
            'paper_code' => 'P1', 'group_ids' => [$this->group], 'licence' => ['kind' => 'school_owned', 'reference' => 'Synthetic original school paper',
                'statement' => 'Synthetic school-owned rights confirmed for testing only.', 'confirmed' => true, 'expires_on' => '2026-12-31'],
            'questions' => [['number' => '1', 'prompt' => 'Explain one half.', 'memo' => 'PRIVATE-EXAM-MEMO: two equal parts.', 'max_units' => 1000,
                'topic_ids' => [$this->nodes[$this->keys[1]]]]]];
    }

    private function paper(): array
    {
        $draft = app(PastPaperLibrary::class)->draft($this->actor(), $this->group, null, $this->input());

        return app(PastPaperLibrary::class)->publish($this->actor(), $this->group, $draft['id'], $draft['version_id'],
            ['operation_id' => (string) Str::uuid(), 'expected_revision' => $draft['revision']]);
    }

    private function exam(): ExamTimetable
    {
        $exam = Exam::forceCreate(['name' => 'Synthetic upcoming exam', 'session_year_id' => $this->year, 'publish' => 1]);

        return ExamTimetable::forceCreate(['exam_id' => $exam->id, 'class_id' => $this->schoolA->ids['class_a'], 'subject_id' => $this->subject, 'total_marks' => 100,
            'passing_marks' => 40, 'date' => '2026-03-02', 'start_time' => '08:00:00', 'end_time' => '09:00:00', 'session_year_id' => $this->year]);
    }

    public function test_staff_http_draft_exact_retry_publish_and_private_learner_paper_exclude_memo(): void
    {
        $this->signIn();
        $base = '/api/v2/exam-coach';
        $staff = $base.'/staff/groups/'.$this->group;
        $this->getJson($base.'/configuration')->assertOk()->assertJsonPath('data.reader_id', $this->actor()->id)->assertJsonPath('data.staff_groups.0.id', $this->group);
        $this->getJson($staff.'/choices')->assertOk()->assertJsonFragment(['id' => $this->nodes[$this->keys[0]]])->assertJsonPath('data.frameworks.0', 'school');
        $input = $this->input();
        $paper = $this->postJson($staff.'/papers', $input)->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data');
        $this->postJson($staff.'/papers', $input)->assertOk()->assertJsonPath('data', $paper);
        $this->assertSame(1, PastPaper::count());
        $this->getJson($staff.'/papers')->assertOk()->assertJsonPath('data.papers.0.version_id', $paper['version_id']);
        $this->signIn('asha');
        $this->getJson($base.'/groups/'.$this->group.'/papers')->assertOk()->assertJsonCount(0, 'data.papers');
        $this->getJson($base.'/groups/'.$this->group.'/versions/'.$paper['version_id'])->assertNotFound()->assertDontSee('PRIVATE-EXAM-MEMO');
        $this->signIn();
        $publish = ['operation_id' => (string) Str::uuid(), 'expected_revision' => $paper['revision']];
        $path = $staff.'/papers/'.$paper['id'].'/versions/'.$paper['version_id'].'/publish';
        $receipt = $this->postJson($path, $publish)->assertOk()->json('data');
        $this->postJson($path, $publish)->assertOk()->assertJsonPath('data', $receipt);
        $this->getJson($staff.'/versions/'.$paper['version_id'])->assertOk()->assertJsonPath('data.questions.0.memo', $input['questions'][0]['memo']);
        $this->get($staff.'/versions/'.$paper['version_id'].'/memo', ['Accept' => 'application/json'])->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('Referrer-Policy', 'no-referrer');
        $this->signIn('asha');
        $this->getJson($base.'/configuration')->assertOk()->assertJsonCount(0, 'data.staff_groups')->assertJsonPath('data.learner_id', $this->schoolA->ids['asha']);
        $this->getJson($base.'/groups/'.$this->group.'/papers')->assertOk()->assertJsonPath('data.papers.0.version_id', $paper['version_id'])->assertDontSee('PRIVATE-EXAM-MEMO');
        $this->getJson($base.'/groups/'.$this->group.'/versions/'.$paper['version_id'])->assertOk()->assertJsonMissingPath('data.questions.0.memo')->assertDontSee('PRIVATE-EXAM-MEMO');
        $this->get($base.'/groups/'.$this->group.'/versions/'.$paper['version_id'].'/paper', ['Accept' => 'application/json'])->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->getJson($staff.'/versions/'.$paper['version_id'].'/memo')->assertForbidden()->assertDontSee('PRIVATE-EXAM-MEMO');
    }

    public function test_own_exam_choices_plan_replay_list_and_date_change_refusal(): void
    {
        $paper = $this->paper();
        $exam = $this->exam();
        $this->signIn('asha');
        $base = '/api/v2/exam-coach';
        $group = $base.'/groups/'.$this->group;
        $choice = $this->getJson($group.'/exams')->assertOk()->json('data.exams.0');
        $this->assertSame((int) $exam->id, $choice['id']);
        $input = ['operation_id' => (string) Str::uuid(), 'exam_timetable_id' => $exam->id, 'schedule_signature' => $choice['schedule_signature'],
            'paper_version_ids' => [$paper['version_id']], 'study_minutes' => 10];
        $plan = $this->postJson($group.'/plans', $input)->assertOk()->json('data');
        $this->postJson($group.'/plans', $input)->assertOk()->assertJsonPath('data', $plan);
        $this->getJson($base.'/plans/'.$plan['id'])->assertOk()->assertJsonPath('data.exam_on', '2026-03-02')->assertJsonPath('data.days.0.minutes', 10);
        $this->getJson($base.'/plans')->assertOk()->assertJsonPath('data.plans.0.id', $plan['id']);
        $this->signIn('farid');
        $this->getJson($base.'/plans/'.$plan['id'])->assertNotFound()->assertDontSee('Synthetic licensed paper');
        $this->signIn('asha');
        $exam->forceFill(['date' => '2026-03-03'])->save();
        $this->getJson($base.'/plans/'.$plan['id'])->assertConflict();
        $this->postJson($group.'/plans', $input)->assertConflict();
        $this->getJson($base.'/plans')->assertOk()->assertJsonCount(0, 'data.plans');
    }

    public function test_private_writes_reject_query_content_unknown_fields_and_changed_uuid_without_duplicate(): void
    {
        $this->signIn();
        $path = '/api/v2/exam-coach/staff/groups/'.$this->group.'/papers';
        $input = $this->input();
        $this->postJson($path.'?title=PRIVATE-QUERY-CANARY', $input)->assertUnprocessable()->assertHeader('Referrer-Policy', 'no-referrer');
        $this->postJson($path, $input + ['learner_id' => $this->schoolA->ids['asha']])->assertUnprocessable();
        $this->postJson($path, $input)->assertOk();
        $input['title'] = 'Changed payload';
        $this->postJson($path, $input)->assertConflict();
        $this->assertSame(1, PastPaperVersion::count());
        $this->getJson($path.'?page=0')->assertUnprocessable();
        $this->getJson($path.'?memo=1')->assertUnprocessable();
        $this->assertSame([], session()->getOldInput());
    }

    public function test_school_account_guardian_and_module_withdrawal_refuse_before_labels_or_replay(): void
    {
        $paper = $this->paper();
        $this->signIn('asha');
        $path = '/api/v2/exam-coach/groups/'.$this->group.'/versions/'.$paper['version_id'];
        $this->withHeader('X-Account-Id', (string) $this->actor('farid')->id)->getJson($path)->assertForbidden()->assertDontSee('Synthetic licensed paper');
        $this->signIn('asha');
        $this->withHeader('X-School-Id', '2')->getJson($path)->assertForbidden()->assertDontSee('Synthetic licensed paper');
        $this->signIn('asha');
        LearnerApproval::where('learner_id', $this->schoolA->ids['asha'])->update(['withdrawn_at' => now()]);
        $this->getJson($path)->assertForbidden()->assertDontSee('Synthetic licensed paper');
        $this->signIn();
        SchoolModule::where('module', 'exam_coach')->update(['enabled' => false]);
        $this->getJson('/api/v2/exam-coach/staff/groups/'.$this->group.'/papers')->assertNotFound()->assertDontSee('Synthetic licensed paper');
    }

    public function test_expired_withdrawn_or_wrong_audience_versions_disappear_from_learner_library(): void
    {
        $paper = $this->paper();
        $this->signIn('asha');
        $path = '/api/v2/exam-coach/groups/'.$this->group.'/papers';
        $version = PastPaperVersion::findOrFail($paper['version_id']);
        $version->update(['licence_expires_on' => '2026-02-09']);
        $this->getJson($path)->assertOk()->assertJsonCount(0, 'data.papers')->assertDontSee('Synthetic licensed paper');
        $version->update(['licence_expires_on' => '2026-12-31', 'group_ids' => [999999]]);
        $this->getJson($path)->assertOk()->assertJsonCount(0, 'data.papers');
        $version->update(['group_ids' => [$this->group]]);
        app(PastPaperLibrary::class)->withdraw($this->actor(), $this->group, $paper['id'], ['operation_id' => (string) Str::uuid(), 'expected_revision' => $paper['revision'], 'reason' => 'Rights withdrawn']);
        $this->getJson($path)->assertOk()->assertJsonCount(0, 'data.papers');
    }

    public function test_teacher_withdrawal_and_wrong_owner_refuse_editor_and_current_choices(): void
    {
        $this->signIn();
        $input = $this->input();
        $path = '/api/v2/exam-coach/staff/groups/'.$this->group.'/papers';
        $paper = $this->postJson($path, $input)->assertOk()->json('data');
        TeachingAllocation::where('teaching_group_id', $this->group)->update(['ends_on' => '2026-02-09']);
        $this->getJson('/api/v2/exam-coach/configuration')->assertOk()->assertJsonCount(0, 'data.staff_groups');
        $this->postJson($path, $input)->assertForbidden();
        $this->getJson('/api/v2/exam-coach/staff/groups/'.$this->group.'/versions/'.$paper['version_id'])->assertForbidden();
        $this->signIn('farid');
        $this->getJson('/api/v2/exam-coach/configuration')->assertForbidden();
        $this->getJson($path)->assertForbidden()->assertDontSee('Synthetic licensed paper');
    }

    public function test_allocated_other_teacher_can_read_published_memo_but_cannot_revise_or_see_draft_labels(): void
    {
        $paper = $this->paper();
        $reader = $this->actor('theo');
        $reader->forceFill(['password_change_required' => false])->save();
        app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', ['teacher_id' => Teacher::where('user_id', $reader->id)->value('id'),
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic additional reader'], $this->group);
        $draft = app(PastPaperLibrary::class)->draft($this->actor(), $this->group, $paper['id'], array_replace($this->input(), ['title' => 'PRIVATE-DRAFT-LABEL', 'expected_revision' => $paper['revision']]));
        $this->signIn('theo');
        $base = '/api/v2/exam-coach/staff/groups/'.$this->group;
        $this->getJson($base.'/papers')->assertOk()->assertJsonCount(1, 'data.papers')->assertJsonPath('data.papers.0.can_edit', false)->assertDontSee('PRIVATE-DRAFT-LABEL');
        $this->getJson($base.'/versions/'.$paper['version_id'])->assertOk()->assertJsonPath('data.questions.0.memo', 'PRIVATE-EXAM-MEMO: two equal parts.');
        $this->getJson($base.'/versions/'.$draft['version_id'])->assertForbidden()->assertDontSee('PRIVATE-DRAFT-LABEL');
        $this->postJson($base.'/papers/'.$paper['id'].'/versions', array_replace($this->input(), ['expected_revision' => $draft['revision']]))->assertForbidden();
        $this->assertSame(2, PastPaperVersion::count());
    }

    public function test_every_exam_endpoint_is_private_and_authentication_reachable(): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $u = '8aef50e7-2429-4d2c-a523-668ae436c4d4';
        $base = '/api/v2/exam-coach';
        $staff = $base.'/staff/groups/1';
        $paths = [['GET', $base.'/configuration'], ['GET', $staff.'/choices'], ['GET', $staff.'/papers'], ['POST', $staff.'/papers'],
            ['POST', $staff.'/papers/1/versions'], ['POST', $staff.'/papers/1/versions/'.$u.'/publish'], ['POST', $staff.'/papers/1/withdraw'],
            ['GET', $staff.'/versions/'.$u], ['GET', $staff.'/versions/'.$u.'/paper'], ['GET', $staff.'/versions/'.$u.'/memo'],
            ['GET', $base.'/groups/1/papers'], ['GET', $base.'/groups/1/versions/'.$u], ['GET', $base.'/groups/1/versions/'.$u.'/paper'],
            ['GET', $base.'/groups/1/exams'], ['POST', $base.'/groups/1/plans'], ['GET', $base.'/plans'], ['GET', $base.'/plans/'.$u]];
        foreach ($paths as [$method, $path]) {
            $this->json($method, $path)->assertUnauthorized()->assertHeader('Cache-Control', 'no-store, private')->assertHeader('Referrer-Policy', 'no-referrer')->assertHeader('X-Content-Type-Options', 'nosniff');
        }
    }
}
