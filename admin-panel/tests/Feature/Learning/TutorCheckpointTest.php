<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Academics\AssessmentPlan;
use App\Models\Academics\QuestionMark;
use App\Models\Growth\MasteryEvidence;
use App\Models\Learning\PracticeItem;
use App\Models\Learning\TutorCheckpoint;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\SchoolModule;
use App\Services\Learning\TutorAccess;
use App\Services\Learning\TutorCheckpoints;
use App\Services\Learning\TutorContext;
use App\Services\Learning\TutorQueries;
use App\Services\Learning\TutorSessions;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPracticeScenario;
use Tests\TestCase;

class TutorCheckpointTest extends TestCase
{
    use UsesPracticeScenario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        SchoolModule::updateOrCreate(['module' => 'tutor'], ['enabled' => true, 'in_package' => true]);
        app(ModuleGate::class)->forget();
        Http::preventStrayRequests();
        Http::fake();
    }

    private function tutorSession(bool $topic = true): array
    {
        return app(TutorSessions::class)->create($this->actor('asha'), $this->group, [$this->source], (string) Str::uuid(), $topic ? $this->nodes[$this->keys[1]] : null);
    }

    private function checkpoint(array $session, ?string $operation = null): array
    {
        return app(TutorCheckpoints::class)->issue($this->actor('asha'), $session['id'], $operation ?? (string) Str::uuid());
    }

    public function test_published_topic_selection_is_optional_but_binds_current_source_context_and_replay(): void
    {
        $actor = $this->actor('asha');
        $config = app(TutorQueries::class)->configuration($actor);
        $this->assertContains($this->nodes[$this->keys[1]], array_column($config['groups'][0]['topics'], 'id'));
        $this->assertTrue($config['checkpoints_enabled']);
        $operation = (string) Str::uuid();
        $session = app(TutorSessions::class)->create($actor, $this->group, [$this->source], $operation, $this->nodes[$this->keys[1]]);
        $this->assertSame($session, app(TutorSessions::class)->create($actor, $this->group, [$this->source], $operation, $this->nodes[$this->keys[1]]));
        $this->refuses(fn () => app(TutorSessions::class)->create($actor, $this->group, [$this->source], $operation), 409);
        $row = app(TutorAccess::class)->session($actor, $session['id']);
        $context = app(TutorContext::class)->capture($actor, $row);
        $this->assertSame($session['topic_id'], $context['manifest']['topic']['id']);
        $this->assertSame('Fractions', json_decode($context['text'], true)['selected_topic']);
        $this->assertNull($this->tutorSession(false)['topic_id']);
        Http::assertNothingSent();
    }

    public function test_issued_checkpoint_has_no_answer_or_explanation_and_exact_issue_replay_is_one_row(): void
    {
        $approved = $this->approve();
        $session = $this->tutorSession();
        $operation = (string) Str::uuid();
        $first = $this->checkpoint($session, $operation);
        $this->assertSame($first, $this->checkpoint($session, $operation));
        $this->assertSame('ready', $first['state']);
        $this->assertSame('One half as a decimal?', $first['question']['prompt']);
        $this->assertArrayNotHasKey('answers', $first['question']);
        $this->assertArrayNotHasKey('answer', $first);
        $this->assertNull($first['explanation']);
        $this->assertNull($first['correct']);
        $this->assertNull($first['verified_by']);
        $this->assertSame($approved['version_id'], (int) TutorCheckpoint::sole()->practice_item_version_id);
        $this->assertSame(1, TutorCheckpoint::count());
        Http::assertNothingSent();
    }

    public function test_deterministic_first_answer_creates_only_practice_provenance_and_replay_does_not_double_evidence(): void
    {
        $this->approve();
        $session = $this->tutorSession();
        $checkpoint = $this->checkpoint($session);
        $marks = QuestionMark::count();
        $operation = (string) Str::uuid();
        $service = app(TutorCheckpoints::class);
        $first = $service->answer($this->actor('asha'), $session['id'], $checkpoint['id'], $operation, '.500');
        $this->assertSame($first, $service->answer($this->actor('asha'), $session['id'], $checkpoint['id'], $operation, '.500'));
        $this->assertTrue($first['correct']);
        $this->assertSame(now('UTC')->toDateTimeString(), DB::table('tutor_checkpoints')->value('answered_at'));
        $this->assertSame('approved_answer_definition', $first['verified_by']);
        $this->assertSame('Divide one by two to get 0.5.', $first['explanation']);
        $evidence = MasteryEvidence::where('source_type', 'tutor_checkpoint')->sole();
        $this->assertSame('practice', $evidence->audience);
        $this->assertSame(1, $evidence->achieved_units);
        $this->assertSame($marks, QuestionMark::count());
        $this->assertSame(1, TutorCheckpoint::count());
        $this->assertNotSame('.500', DB::table('tutor_checkpoints')->value('answer'));
        $this->assertCount(1, app(TutorSessions::class)->read($this->actor('asha'), $session['id'])['checkpoints']);
        Http::assertNothingSent();
    }

    public function test_wrong_first_answer_is_immutable_and_new_sessions_or_uuids_cannot_farm_the_same_version(): void
    {
        $this->approve();
        $session = $this->tutorSession();
        $checkpoint = $this->checkpoint($session);
        $operation = (string) Str::uuid();
        $service = app(TutorCheckpoints::class);
        $first = $service->answer($this->actor('asha'), $session['id'], $checkpoint['id'], $operation, '0.25');
        $this->assertFalse($first['correct']);
        $this->refuses(fn () => $service->answer($this->actor('asha'), $session['id'], $checkpoint['id'], $operation, '0.5'), 409);
        $this->refuses(fn () => $service->answer($this->actor('asha'), $session['id'], $checkpoint['id'], (string) Str::uuid(), '0.5'), 409);
        $this->refuses(fn () => $this->checkpoint($session), 422);
        $other = $this->tutorSession();
        $this->refuses(fn () => $this->checkpoint($other), 422);
        $this->assertSame(0, MasteryEvidence::where('source_type', 'tutor_checkpoint')->sole()->achieved_units);
        Http::assertNothingSent();
    }

    public function test_teacher_revision_or_bank_withdrawal_revokes_the_old_question_before_answers_or_explanation_load(): void
    {
        $approved = $this->approve();
        $session = $this->tutorSession();
        $checkpoint = $this->checkpoint($session);
        $this->approve(['expected_revision' => 1], $approved['id']);
        $service = app(TutorCheckpoints::class);
        $this->refuses(fn () => $service->read($this->actor('asha'), $session['id'], $checkpoint['id']), 404);
        $new = $this->checkpoint($session);
        $this->assertNotSame($new['id'], $checkpoint['id']);
        PracticeItem::whereKey($approved['id'])->update(['active' => false]);
        $this->refuses(fn () => $service->answer($this->actor('asha'), $session['id'], $new['id'], (string) Str::uuid(), '0.5'), 404);
        $this->assertSame(0, MasteryEvidence::where('source_type', 'tutor_checkpoint')->count());
    }

    public function test_practice_module_off_refuses_checkpoints_but_subject_chat_and_existing_sessions_stay_available(): void
    {
        $this->approve();
        $session = $this->tutorSession();
        SchoolModule::where('module', 'daily_practice')->update(['enabled' => false]);
        app(ModuleGate::class)->forget();
        $this->refuses(fn () => $this->checkpoint($session), 403);
        $this->assertFalse(app(TutorQueries::class)->configuration($this->actor('asha'))['checkpoints_enabled']);
        $this->assertSame([], app(TutorSessions::class)->read($this->actor('asha'), $session['id'])['checkpoints']);
        $this->assertNotEmpty($this->tutorSession()['id']);
        Http::assertNothingSent();
    }

    public function test_parent_teacher_wrong_school_and_another_approved_student_cannot_read_or_answer_own_checkpoints(): void
    {
        $this->approve();
        $session = $this->tutorSession();
        $checkpoint = $this->checkpoint($session);
        $service = app(TutorCheckpoints::class);
        foreach (['farid', 'tara'] as $name) {
            $this->refuses(fn () => $service->read($this->actor($name), $session['id'], $checkpoint['id']), 404);
        }
        $ben = $this->actor('ben');
        $ben->forceFill(['password_change_required' => false, 'status' => 1])->save();
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['ben'], 'guardian_user_id' => $this->actor('farid')->id], ['status' => 'active', 'can_approve' => true]);
        LearnerApproval::create(['learner_id' => $this->schoolA->ids['ben'], 'guardian_user_id' => $this->actor('farid')->id,
            'consent_version' => config('identity.consent_version'), 'consented_at' => now()]);
        $this->refuses(fn () => $service->read($ben, $session['id'], $checkpoint['id']), 404);
        $actor = $this->actor('asha');
        app(CurrentSchool::class)->run(self::SCHOOL_B_ID, fn () => $this->refuses(fn () => $service->read($actor, $session['id'], $checkpoint['id']), 404));
        $this->assertSame(0, MasteryEvidence::where('source_type', 'tutor_checkpoint')->count());
        Http::assertNothingSent();
    }

    public function test_guardian_or_published_term_withdrawal_denies_source_replay_and_keeps_audit_rows(): void
    {
        $this->approve();
        $session = $this->tutorSession();
        $checkpoint = $this->checkpoint($session);
        AssessmentPlan::where('term_id', $this->term)->update(['state' => 'draft']);
        $this->refuses(fn () => app(TutorCheckpoints::class)->read($this->actor('asha'), $session['id'], $checkpoint['id']), 404);
        $this->refuses(fn () => app(TutorSessions::class)->read($this->actor('asha'), $session['id']), 404);
        $this->assertSame(1, TutorCheckpoint::count());
        Http::assertNothingSent();
    }

    public function test_withdrawn_guardian_approval_refuses_checkpoint_answer_before_evidence(): void
    {
        $this->approve();
        $session = $this->tutorSession();
        $checkpoint = $this->checkpoint($session);
        LearnerApproval::where('learner_id', $this->schoolA->ids['asha'])->update(['withdrawn_at' => now()]);
        $this->refuses(fn () => app(TutorCheckpoints::class)->answer($this->actor('asha'), $session['id'], $checkpoint['id'], (string) Str::uuid(), '0.5'), 403);
        $this->assertSame(0, MasteryEvidence::where('source_type', 'tutor_checkpoint')->count());
        $this->assertNull(TutorCheckpoint::sole()->answered_at);
        Http::assertNothingSent();
    }
}
