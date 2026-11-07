<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Growth\MasteryEvidence;
use App\Models\Growth\MasteryRecord;
use App\Models\Growth\MasteryTopic;
use App\Models\Learning\DiagnosticAttempt;
use App\Models\Learning\DiagnosticAttemptItem;
use App\Models\Library\LibraryItem;
use App\Models\Library\LibraryTopic;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\SchoolModule;
use App\Models\Platform\SchoolStage;
use App\Models\User;
use App\Services\Academics\TeachingGroups;
use App\Services\Growth\MasteryRecompute;
use App\Services\Learning\DiagnosticService;
use App\Services\Learning\Library\LibraryCatalogue;
use App\Services\Learning\Library\LibraryItems;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Stages\SchoolStages;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\UsesContentLibrary;
use Tests\Concerns\UsesPracticeScenario;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

/**
 * The diagnostic start (Action Plan 3.7): a learner takes the library's
 * starting check, marked on the server, and every answer becomes mastery
 * evidence of its own provenance. engineering/specs/learning/diagnostic-start.md.
 *
 * School A teaches CAPS MATH-SP Grade 7. The library has Whole numbers
 * (Grade 6) ← Fractions ← Decimals, with three, three and two published
 * questions, and a Grade 7 diagnostic that lists all eight.
 */
class DiagnosticStartTest extends TestCase
{
    use UsesContentLibrary, UsesPracticeScenario, ValidatesOpenApiResponses;

    /** @var array<string, LibraryTopic> */
    private array $topics = [];

    /** @var array<string, LibraryItem> */
    private array $items = [];

    private LibraryItem $diagnostic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        $standard = $this->standard('MATH-SP', 6, 9);
        DB::table('curriculum_plans')->where('school_id', 1)->update(['standard_id' => $standard->id,
            'standard_snapshot' => json_encode(['id' => $standard->id, 'framework' => 'CAPS', 'code' => 'MATH-SP', 'name' => $standard->name])]);
        $this->topics['whole'] = $this->libraryTopic('Whole numbers', 'MATH-SP', '6');
        $this->topics['fractions'] = $this->libraryTopic('Fractions');
        $this->topics['decimals'] = $this->libraryTopic('Decimals');
        $this->asHq(function () {
            $catalogue = app(LibraryCatalogue::class);
            $catalogue->setPrerequisites($this->libraryStaff('content'), $this->topics['fractions']->id, ['prerequisite_ids' => [$this->topics['whole']->id]]);
            $catalogue->setPrerequisites($this->libraryStaff('content'), $this->topics['decimals']->id, ['prerequisite_ids' => [$this->topics['fractions']->id]]);
        });
        // Listed Decimals first: the prerequisite graph, not the list, puts Whole numbers first.
        foreach (['d1' => ['decimals', '0.1'], 'd2' => ['decimals', '0.2'], 'f1' => ['fractions', '0.5'], 'f2' => ['fractions', '0.25'], 'f3' => ['fractions', '0.75'],
            'w1' => ['whole', '7'], 'w2' => ['whole', '12'], 'w3' => ['whole', '30']] as $key => [$topic, $answer]) {
            $this->items[$key] = $this->publishedItem($this->topics[$topic], ['title' => 'Question '.$key, 'prompt' => 'Synthetic question '.$key.'?',
                'explanation' => 'SECRET-EXPLANATION-'.$key, 'answers' => $answer]);
        }
        $this->diagnostic = $this->publishedDiagnostic(array_map(fn ($i) => $i->id, array_values($this->items)));
    }

    private function publishedDiagnostic(array $ids): LibraryItem
    {
        return $this->asHq(function () use ($ids) {
            $items = app(LibraryItems::class);
            $content = $this->libraryStaff('content');
            $item = $items->create($content, ['library_topic_id' => $this->topics['fractions']->id, 'library_source_id' => $this->librarySource()->id, 'kind' => 'diagnostic',
                'title' => 'Grade 7 maths starting check', 'instructions' => 'Answer what you can. It is fine not to know.', 'item_ids' => $ids]);
            $version = $items->submit($content, $item->id, ['revision' => $item->fresh()->revision]);
            $items->review($this->libraryStaff('reviewer'), $version->id, ['decision' => 'approved']);
            $items->publish($content, $item->id, ['version_id' => $version->id, 'revision' => $item->fresh()->revision]);

            return $item->fresh();
        });
    }

    private function service(): DiagnosticService
    {
        return app(DiagnosticService::class);
    }

    private function start(string $who = 'asha', ?int $learner = null): array
    {
        return $this->service()->start($this->actor($who), $learner ?? $this->schoolA->ids['asha'], $this->group);
    }

    private function answer(array $attempt, string $answer, string $who = 'asha'): array
    {
        return $this->service()->answer($this->actor($who), $this->schoolA->ids[$who], $attempt['id'], $attempt['question']['id'], ['answer' => $answer]);
    }

    /** Ben joins the group as an approved learner of his own. */
    private function enrolBen(): void
    {
        $ben = $this->schoolA->ids['ben'];
        $this->actor('ben')->forceFill(['password_change_required' => false])->save();
        $this->actor('gina')->forceFill(['email_verified_at' => now(), 'status' => 1])->save();
        GuardianLink::updateOrCreate(['learner_id' => $ben, 'guardian_user_id' => $this->actor('gina')->id], ['relationship' => 'mother', 'status' => 'active', 'can_approve' => true]);
        LearnerApproval::create(['learner_id' => $ben, 'guardian_user_id' => $this->actor('gina')->id, 'consent_version' => config('identity.consent_version'), 'consented_at' => now()]);
        Enrolment::create(['learner_id' => $ben, 'class_section_id' => $this->schoolA->ids['section_a'], 'session_year_id' => $this->year,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic diagnostic fixture']);
        app(TeachingGroups::class)->dated($this->superAdmin(), 'memberships', ['learner_id' => $ben, 'mark_rule' => 'catch_up',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Enrolled subject'], $this->group);
    }

    /** Whole numbers right, right (third skipped); Fractions right, wrong, wrong; Decimals wrong, wrong. */
    private function finish(array $attempt): array
    {
        foreach (['7', '12', '0.5', '0.3', '0.3', '0.9', '0.9'] as $answer) {
            $attempt = $this->answer($attempt, $answer);
        }

        return $attempt;
    }

    public function test_starting_check_skips_question_kinds_its_answer_contract_cannot_mark(): void
    {
        $written = $this->publishedItem($this->topics['whole'], ['question_kind' => 'short_text', 'answers' => 'seven', 'prompt' => 'Write seven in words.']);
        $this->asHq(function () use ($written): void {
            $items = app(LibraryItems::class);
            $content = $this->libraryStaff('content');
            $items->saveDraft($content, $this->diagnostic->id, ['revision' => $this->diagnostic->fresh()->revision, 'title' => 'Mixed starting check',
                'instructions' => 'Answer what you can.', 'item_ids' => [$written->id, $this->items['w1']->id]]);
            $version = $items->submit($content, $this->diagnostic->id, ['revision' => $this->diagnostic->fresh()->revision]);
            $items->review($this->libraryStaff('reviewer'), $version->id, ['decision' => 'approved']);
            $items->publish($content, $this->diagnostic->id, ['version_id' => $version->id, 'revision' => $this->diagnostic->fresh()->revision]);
        });
        $this->switchLibrary(1, true);
        $attempt = $this->start();
        $this->assertSame('number', $attempt['question']['kind']);
        $this->assertSame('Synthetic question w1?', $attempt['question']['prompt']);
        $this->assertSame(1, $attempt['remaining']);
        $this->assertSame('completed', $this->answer($attempt, '7')['status']);
    }

    public function test_a_learner_takes_the_starting_check_and_fills_the_mastery_map(): void
    {
        $this->switchLibrary(1, true);
        $this->enrolBen();
        $before = $this->libraryCounts();
        $attempt = $this->start();
        $this->assertSame('in_progress', $attempt['status']);
        $this->assertSame(8, $attempt['remaining']);
        $this->assertSame('Answer what you can. It is fine not to know.', $attempt['instructions']);
        $this->assertSame('Synthetic question w1?', $attempt['question']['prompt'], 'prerequisites first');
        // Starting again resumes the same attempt.
        $this->assertSame($attempt['id'], $this->start()['id']);

        $attempt = $this->answer($attempt, '7');
        $attempt = $this->answer($attempt, '12');
        // Two agreeing answers settle Whole numbers: its third question is skipped.
        $this->assertSame('Synthetic question f1?', $attempt['question']['prompt']);
        $this->assertSame(5, $attempt['remaining']);
        $attempt = $this->answer($attempt, '0.5');
        $attempt = $this->answer($attempt, '0.3');
        // Split answers ask the topic's third question.
        $this->assertSame('Synthetic question f3?', $attempt['question']['prompt']);
        $attempt = $this->answer($attempt, '0.3');
        $attempt = $this->answer($attempt, '0.9');
        $done = $this->answer($attempt, '0.9');
        $this->assertSame('completed', $done['status']);
        $this->assertNull($done['question']);
        $this->assertSame(7, $done['answered']);
        $this->assertSame(['Fractions', 'Decimals'], $done['focus_topics']);

        // Evidence: one row per answer, its own provenance, on the school's mirror nodes, in the practice audience.
        $evidence = MasteryEvidence::query()->orderBy('id')->get();
        $this->assertCount(7, $evidence);
        $this->assertSame(['diagnostic'], $evidence->pluck('source_type')->unique()->values()->all());
        $this->assertSame(['practice'], $evidence->pluck('audience')->unique()->values()->all());
        $this->assertSame(['library'], $evidence->pluck('content_source')->unique()->values()->all());
        $this->assertSame('library_item:'.$this->items['w1']->id, $evidence[0]->item_key);
        $this->assertSame($this->items['w1']->published_version_id, (int) $evidence[0]->library_item_version_id);
        $mirrors = MasteryTopic::query()->whereNotNull('library_topic_id')->pluck('id', 'library_topic_id');
        $this->assertSame((int) $mirrors[$this->topics['whole']->id], (int) $evidence[0]->topic_id);
        $this->assertSame((int) MasteryTopic::query()->find($mirrors[$this->topics['whole']->id])->grade_level_id, $this->grade('6'), 'an earlier grade is mirrored too');
        // The map is filled: every topic of the check has a record in every audience.
        foreach ($this->topics as $topic) {
            foreach (['staff', 'family', 'practice'] as $audience) {
                $record = MasteryRecord::query()->where('topic_id', $mirrors[$topic->id])->where('audience', $audience)->where('policy_version', 'recent20-v1')->sole();
                $this->assertGreaterThan(0, $record->evidence_count);
            }
        }
        $this->assertSame(4, MasteryRecord::query()->where('topic_id', $mirrors[$this->topics['whole']->id])->where('audience', 'staff')->value('level'));
        $this->assertSame(0, MasteryRecord::query()->where('topic_id', $mirrors[$this->topics['decimals']->id])->where('audience', 'staff')->value('level'));

        // The teacher sees who has finished and the starting map, by name, never ranked.
        $view = $this->service()->group($this->actor('tara'), $this->group);
        $this->assertTrue($view['available']);
        $this->assertSame(['Whole numbers', 'Fractions', 'Decimals'], array_column($view['topics'], 'name'));
        $this->assertSame([1, 0, 0, 0], [$view['topics'][0]['secure'], $view['topics'][0]['developing'], $view['topics'][0]['gap'], $view['topics'][0]['not_reached']]);
        $this->assertSame(1, $view['topics'][1]['developing']);
        $this->assertSame(1, $view['topics'][2]['gap']);
        $this->assertSame(['Asha Demo', 'Ben Demo'], array_column($view['learners'], 'name'));
        $this->assertSame(['completed', 'not_started'], array_column($view['learners'], 'status'));
        $this->assertSame(['secure', 'developing', 'gap'], array_column($view['learners'][0]['topics'], 'band'));
        $this->assertArrayNotHasKey('score', $view['learners'][0]);

        // The learner and the family see a plain starting point, never a level or a score.
        $own = $this->service()->summary($this->actor('asha'), $this->schoolA->ids['asha'])['diagnostics'][0];
        $this->assertSame(['completed', $done['id'], false, ['Fractions', 'Decimals']], [$own['status'], $own['attempt_id'], $own['can_start'], $own['focus_topics']]);
        $family = $this->service()->summary($this->actor('farid'), $this->schoolA->ids['asha'])['diagnostics'][0];
        $this->assertNull($family['attempt_id']);
        $this->assertFalse($family['can_start']);
        $this->assertStringContainsString('It is not a mark.', $family['message']);
        $this->assertSame(['group_id', 'subject_name', 'status', 'attempt_id', 'can_start', 'message', 'focus_topics'], array_keys($family));

        // Ben has not started: his summary offers it.
        $ben = $this->service()->summary($this->actor('ben'), $this->schoolA->ids['ben'])['diagnostics'][0];
        $this->assertSame(['not_started', true], [$ben['status'], $ben['can_start']]);

        // The school's answers wrote only school rows: the library is exactly as it was.
        $this->assertSame($before, $this->libraryCounts());
    }

    public function test_a_prerequisite_chain_through_a_topic_with_no_question_still_orders_the_check(): void
    {
        // A ← B ← C, where the check lists C first and has no question for B.
        $a = $this->libraryTopic('Chain A');
        $b = $this->libraryTopic('Chain B');
        $c = $this->libraryTopic('Chain C');
        $this->asHq(function () use ($a, $b, $c) {
            $catalogue = app(LibraryCatalogue::class);
            $catalogue->setPrerequisites($this->libraryStaff('content'), $b->id, ['prerequisite_ids' => [$a->id]]);
            $catalogue->setPrerequisites($this->libraryStaff('content'), $c->id, ['prerequisite_ids' => [$b->id]]);
        });
        $c1 = $this->publishedItem($c, ['title' => 'Chain C question', 'prompt' => 'Chain question C?', 'answers' => '3']);
        $a1 = $this->publishedItem($a, ['title' => 'Chain A question', 'prompt' => 'Chain question A?', 'answers' => '1']);
        $earlier = $this->diagnostic->id;
        $this->diagnostic = $this->publishedDiagnostic([$c1->id, $a1->id]);
        // The group takes the earliest published check: unpublish setUp's (a fixture shortcut; HQ has no unpublish yet).
        DB::table('library_items')->where('id', $earlier)->update(['published_version_id' => null]);
        $this->switchLibrary(1, true);
        $attempt = $this->start();
        $this->assertSame('Chain question A?', $attempt['question']['prompt'], 'A is asked before C');
        $this->assertSame([$a->id, $c->id], DiagnosticAttemptItem::query()->orderBy('position')->pluck('library_topic_id')->all());
    }

    public function test_the_device_never_receives_answers_or_explanations_and_is_not_told_right_or_wrong(): void
    {
        $this->switchLibrary(1, true);
        $attempt = $this->start();
        $payloads = [$attempt];
        foreach (['999', '999', '999'] as $answer) {
            $attempt = $this->answer($attempt, $answer);
            $payloads[] = $attempt;
        }
        $payloads[] = $this->service()->read($this->actor('asha'), $this->schoolA->ids['asha'], $attempt['id']);
        foreach ($payloads as $payload) {
            $json = json_encode($payload, JSON_THROW_ON_ERROR);
            $this->assertStringNotContainsString('SECRET-EXPLANATION', $json);
            $this->assertStringNotContainsString('"answers"', $json);
            $this->assertStringNotContainsString('"correct"', $json);
            $this->assertStringNotContainsString('"explanation"', $json);
            $this->assertSame(['id', 'position', 'kind', 'prompt', 'options'], array_keys($payload['question']));
        }
        // The answer is stored encrypted, never in plain text.
        $this->assertStringNotContainsString('999', (string) DB::table('diagnostic_attempt_items')->whereNotNull('answer')->value('answer'));
    }

    public function test_submissions_are_idempotent_and_a_retake_never_double_counts(): void
    {
        $this->switchLibrary(1, true);
        $first = $this->start();
        $after = $this->answer($first, '7');
        // The same answer again (a retry) changes nothing; a different one is refused.
        $this->assertSame($after, $this->answer($first, '7.0'));
        $this->refuses(fn () => $this->answer($first, '8'), 409);
        $this->assertSame(1, MasteryEvidence::query()->count());
        // Out of order, and a malformed answer.
        $later = DiagnosticAttemptItem::query()->where('position', 5)->sole();
        $this->refuses(fn () => $this->service()->answer($this->actor('asha'), $this->schoolA->ids['asha'], $first['id'], $later->id, ['answer' => '0.5']), 409);
        $this->expectValidation(fn () => $this->answer($after, 'seven'));
        // The rest answered "999": Whole numbers splits (three asked), Fractions and Decimals stop after two.
        $attempt = $this->start();
        while ($attempt['question'] !== null) {
            $attempt = $this->answer($attempt, '999');
        }
        $this->assertSame(7, MasteryEvidence::query()->count());
        // Starting again in the same year resumes the finished attempt: nothing new is asked or written.
        $again = $this->start();
        $this->assertSame([$first['id'], 'completed', null], [$again['id'], $again['status'], $again['question']]);
        $this->assertSame(1, DiagnosticAttempt::query()->count());
        $this->assertSame(7, MasteryEvidence::query()->count());
        // A skipped question cannot be answered.
        $skipped = DiagnosticAttemptItem::query()->where('state', 'skipped')->sole();
        $this->refuses(fn () => $this->service()->answer($this->actor('asha'), $this->schoolA->ids['asha'], $first['id'], $skipped->id, ['answer' => '30']), 409);
    }

    public function test_diagnostic_evidence_never_overwrites_or_masquerades_as_assessment_marks(): void
    {
        $this->switchLibrary(1, true);
        $this->capture('4.00', '9.00');
        app(MasteryRecompute::class)->group($this->group);
        $marks = MasteryEvidence::query()->where('source_type', 'question_mark')->orderBy('id')->get(['id', 'achieved_units', 'source_key', 'topic_id'])->toArray();
        $this->assertNotSame([], $marks);
        $official = DB::table('question_marks')->count();
        $this->finish($this->start());
        $this->assertSame($official, DB::table('question_marks')->count(), 'no assessment mark is written');
        $this->assertSame($marks, MasteryEvidence::query()->where('source_type', 'question_mark')->orderBy('id')->get(['id', 'achieved_units', 'source_key', 'topic_id'])->toArray());
        // Rebuilding from marks replaces mark evidence only: the diagnostic stays, and stays apart.
        app(MasteryRecompute::class)->group($this->group);
        $this->assertSame(7, MasteryEvidence::query()->where('source_type', 'diagnostic')->count());
        $this->assertSame(0, MasteryEvidence::query()->where('source_type', 'diagnostic')->where('audience', '!=', 'practice')->count());
        $this->assertSame(0, MasteryEvidence::query()->where('source_type', 'diagnostic')->whereIn('topic_id', array_column($marks, 'topic_id'))->count());
    }

    public function test_wrong_roles_and_other_learners_are_refused(): void
    {
        $this->switchLibrary(1, true);
        $this->enrolBen();
        $attempt = $this->start();
        $asha = $this->schoolA->ids['asha'];
        // The teacher, a guardian and another learner cannot start, read or answer Asha's check.
        $this->refuses(fn () => $this->start('tara'), 404);
        $this->refuses(fn () => $this->start('farid'), 404);
        $this->refuses(fn () => $this->start('ben'), 404);
        $this->refuses(fn () => $this->service()->read($this->actor('farid'), $asha, $attempt['id']), 404);
        $this->refuses(fn () => $this->service()->read($this->actor('ben'), $asha, $attempt['id']), 404);
        $this->refuses(fn () => $this->service()->read($this->actor('ben'), $this->schoolA->ids['ben'], $attempt['id']), 404);
        $this->refuses(fn () => $this->service()->answer($this->actor('ben'), $this->schoolA->ids['ben'], $attempt['id'], $attempt['question']['id'], ['answer' => '7']), 404);
        $this->refuses(fn () => $this->service()->answer($this->actor('ben'), $asha, $attempt['id'], $attempt['question']['id'], ['answer' => '7']), 404);
        // Another learner's summary, and a guardian of someone else.
        $this->refuses(fn () => $this->service()->summary($this->actor('ben'), $asha), 404);
        $this->refuses(fn () => $this->service()->summary($this->actor('gina'), $asha), 404);
        // Only the group's teacher sees the starting map.
        $this->refuses(fn () => $this->service()->group($this->actor('asha'), $this->group), 403);
        $this->refuses(fn () => $this->service()->group($this->actor('farid'), $this->group), 403);
        $this->refuses(fn () => $this->service()->group($this->actor('theo'), $this->group), 403);
        $this->assertSame(1, DiagnosticAttempt::query()->count());
        $this->assertSame(0, DiagnosticAttemptItem::query()->where('state', 'answered')->count());
    }

    public function test_school_b_cannot_see_assign_or_answer_school_a_diagnostics_or_evidence(): void
    {
        // School B's switch does nothing for School A.
        $this->switchLibrary(2, true);
        $this->refuses(fn () => $this->start(), 404);
        $this->switchLibrary(1, true);
        $attempt = $this->finish($this->start());
        app(CurrentSchool::class)->run(2, function () use ($attempt) {
            $asha = $this->schoolA->ids['asha'];
            $this->assertSame(0, DiagnosticAttempt::query()->count());
            $this->assertSame(0, DiagnosticAttemptItem::query()->count());
            $this->assertSame(0, MasteryEvidence::query()->count());
            $this->refuses(fn () => $this->service()->read($this->actor('asha'), $asha, $attempt['id']), 404);
            $this->refuses(fn () => $this->service()->start($this->actor('asha'), $asha, $this->group), 404);
            $this->refuses(fn () => $this->service()->summary($this->actor('farid'), $asha), 404);
            $baobab = User::findOrFail($this->schoolB->ids['tara_user']);
            $this->refuses(fn () => $this->service()->group($baobab, $this->group), 403);
        });
        $this->assertSame(0, DB::table('diagnostic_attempts')->where('school_id', 2)->count());
        $this->assertSame(0, DB::table('mastery_evidence')->where('school_id', 2)->count());
        $this->assertSame(0, DB::table('mastery_topics')->where('school_id', 2)->whereNotNull('library_topic_id')->count(), 'nothing mirrored into School B');
    }

    public function test_switching_the_library_off_withdraws_the_check(): void
    {
        $this->refuses(fn () => $this->start(), 404);
        $this->assertSame([], $this->service()->summary($this->actor('asha'), $this->schoolA->ids['asha'])['diagnostics']);
        $this->assertFalse($this->service()->group($this->actor('tara'), $this->group)['available']);
        $this->switchLibrary(1, true);
        $attempt = $this->start();
        $this->switchLibrary(1, false);
        $this->refuses(fn () => $this->service()->read($this->actor('asha'), $this->schoolA->ids['asha'], $attempt['id']), 404);
        $this->refuses(fn () => $this->answer($attempt, '7'), 404);
        $this->assertSame([], $this->service()->summary($this->actor('farid'), $this->schoolA->ids['asha'])['diagnostics']);
        $this->assertFalse($this->service()->group($this->actor('tara'), $this->group)['available']);
        $this->assertSame(0, MasteryEvidence::query()->count());
    }

    public function test_the_mastery_module_and_early_years_stage_are_respected(): void
    {
        $this->switchLibrary(1, true);
        SchoolModule::query()->where('module', 'mastery')->update(['enabled' => false]);
        app(ModuleGate::class)->forget();
        $this->refuses(fn () => $this->start(), 403);
        $this->refuses(fn () => $this->service()->summary($this->actor('farid'), $this->schoolA->ids['asha']), 403);
        $this->refuses(fn () => $this->service()->group($this->actor('tara'), $this->group), 403);
        SchoolModule::query()->where('module', 'mastery')->update(['enabled' => true]);
        app(ModuleGate::class)->forget();

        // An early-years learner does not use practice: nothing is offered, and a start is refused.
        SchoolStage::query()->updateOrCreate(['grade_level_id' => $this->grade('7')], ['stage' => SchoolStages::EARLY_YEARS]);
        app(SchoolStages::class)->forget();
        app(ModuleGate::class)->forget();
        $this->refuses(fn () => $this->start(), 403);
        $this->assertSame([], $this->service()->summary($this->actor('farid'), $this->schoolA->ids['asha'])['diagnostics']);
        $this->assertSame(0, DiagnosticAttempt::query()->count());
    }

    public function test_the_api_and_panel_page_follow_the_contract(): void
    {
        $this->switchLibrary(1, true);
        $base = '/api/v2/learning/diagnostics';
        $asha = $this->schoolA->ids['asha'];
        $this->signIn('asha');
        $this->getJson("$base/learners/$asha")->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('data.diagnostics.0.can_start', true);
        $attempt = $this->postJson("$base/learners/$asha/groups/{$this->group}/attempts")->assertOk()->json('data');
        $this->assertStringNotContainsString('SECRET-EXPLANATION', json_encode($attempt, JSON_THROW_ON_ERROR));
        $this->postJson("$base/learners/$asha/groups/{$this->group}/attempts?learner=2")->assertUnprocessable();
        $path = "$base/learners/$asha/attempts/{$attempt['id']}";
        $this->getJson($path)->assertOk()->assertJsonPath('data.question.id', $attempt['question']['id']);
        $next = $this->postJson("$path/items/{$attempt['question']['id']}/answer", ['answer' => '7'])->assertOk()->json('data');
        $this->postJson("$path/items/{$attempt['question']['id']}/answer", ['answer' => '7'])->assertOk()->assertJsonPath('data', $next);
        $this->postJson("$path/items/{$attempt['question']['id']}/answer", ['answer' => '8'])->assertConflict();
        $this->postJson("$path/items/{$next['question']['id']}/answer", ['answer' => ''])->assertUnprocessable();
        $this->getJson("$base/groups/{$this->group}")->assertForbidden();

        $this->signIn('farid');
        $this->getJson("$base/learners/$asha")->assertOk()->assertJsonPath('data.diagnostics.0.attempt_id', null);
        $this->getJson($path)->assertNotFound();
        $this->postJson("$base/learners/$asha/groups/{$this->group}/attempts")->assertNotFound();

        $this->signIn('tara');
        $this->getJson("$base/groups/{$this->group}")->assertOk()->assertJsonPath('data.learners.0.status', 'in_progress');
    }

    public function test_the_teacher_page_shows_the_starting_map_only_to_the_groups_teacher(): void
    {
        $this->switchLibrary(1, true);
        $this->finish($this->start());
        $this->actingAs($this->actor('tara'))->get(route('starting-checks.index'))->assertOk()->assertSee('Grade 7 maths starting check')->assertSee('Asha Demo')
            ->assertSee('Whole numbers')->assertSee('Start here')->assertDontSee('SECRET-EXPLANATION')->assertDontSee('Synthetic question');
        $this->actingAs($this->actor('theo'))->get(route('starting-checks.index', ['group' => $this->group]))->assertForbidden();
        $this->actingAs($this->actor('farid'))->get(route('starting-checks.index'))->assertDontSee('Asha Demo');
    }

    private function signIn(string $name): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->withToken($this->schoolA->tokens[$name])->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $this->actor($name)->id]);
    }

    private function expectValidation(callable $call): void
    {
        try {
            $call();
            $this->fail('Expected a validation error.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('answer', $error->errors());
        }
    }
}
