<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Learning\LearningPath;
use App\Models\Platform\SchoolModule;
use App\Models\User;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPracticeScenario;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

/** The guided learning path API (Action Plan 3.9); every response is checked against openapi.yaml. */
class LearningPathApiTest extends TestCase
{
    use UsesPracticeScenario, ValidatesOpenApiResponses;

    private const BASE = '/api/v2/learning/paths';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        for ($i = 1; $i <= 4; $i++) {
            $this->approve(['definition' => ['kind' => 'choice', 'prompt' => "Which is one half? ($i)", 'explanation' => 'One half is 0.5.',
                'options' => [['id' => 'a', 'text' => '0.5'], ['id' => 'b', 'text' => '0.2']], 'answers' => ['a']]]);
        }
    }

    private function signIn(string $name, string $school = 'A'): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $demo = $school === 'A' ? $this->schoolA : $this->schoolB;
        $this->withToken($demo->tokens[$name])->withHeaders(['X-School-Id' => $school === 'A' ? '1' : '2', 'X-Account-Id' => (string) $demo->ids[$name.'_user']]);
    }

    private function own(): string
    {
        return self::BASE.'/learners/'.$this->schoolA->ids['asha'].'/groups/'.$this->group;
    }

    private function step(string $action, int $revision, ?string $operation = null): array
    {
        return $this->postJson($this->own().'/actions', ['operation_id' => $operation ?? (string) Str::uuid(), 'expected_revision' => $revision, 'action' => $action])
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data');
    }

    private function answerAll(array $state, string $answer = 'a'): array
    {
        foreach ($state['items'] as $item) {
            $state = $this->postJson($this->own().'/items/'.$item['id'].'/answer', ['operation_id' => (string) Str::uuid(), 'answer' => $answer])->assertOk()->json('data');
        }

        return $state;
    }

    public function test_own_learner_walks_the_whole_path_over_http_and_a_double_submit_advances_once(): void
    {
        $this->signIn('asha');
        $this->getJson($this->own())->assertOk()->assertJsonPath('data.step', 'gap')->assertJsonPath('data.path_id', null);
        $operation = (string) Str::uuid();
        $teach = $this->step('next', 0, $operation);
        $this->assertSame($teach, $this->step('next', 0, $operation));
        $this->postJson($this->own().'/actions', ['operation_id' => (string) Str::uuid(), 'expected_revision' => 0, 'action' => 'next'])->assertConflict();
        $this->assertSame(['teach', 'Fractions', 1], [$teach['step'], $teach['topic']['name'], LearningPath::sole()->revision]);
        $practise = $this->step('practise', 1);
        $this->assertSame([['id' => 'a', 'text' => '0.5'], ['id' => 'b', 'text' => '0.2']], $practise['items'][0]['question']['options']);
        $this->assertStringNotContainsString('answers', json_encode($practise, JSON_THROW_ON_ERROR));
        $state = $this->answerAll($practise);
        $check = $this->step('check', $state['revision']);
        $done = $this->answerAll($check);
        $this->assertSame(['review', true], [$done['step'], $done['check']['passed']]);
        $this->postJson($this->own().'/actions?action=next', ['operation_id' => (string) Str::uuid(), 'expected_revision' => $done['revision'], 'action' => 'next'])->assertUnprocessable();
        $this->postJson($this->own().'/actions', ['operation_id' => 'not-a-uuid', 'expected_revision' => $done['revision'], 'action' => 'jump'])->assertUnprocessable();
    }

    public function test_teacher_and_guardian_reads_and_wrong_roles_are_refused(): void
    {
        $this->signIn('asha');
        $this->step('next', 0);
        $learner = $this->schoolA->ids['asha'];

        $this->signIn('tara');
        $this->getJson(self::BASE."/groups/{$this->group}/learners")->assertOk()->assertJsonPath('data.learners.0.step', 'teach')->assertJsonPath('data.learners.0.topic.name', 'Fractions');
        $this->getJson($this->own())->assertNotFound();
        $this->postJson($this->own().'/actions', ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1, 'action' => 'practise'])->assertNotFound();
        $this->getJson(self::BASE."/learners/$learner/summary")->assertNotFound();

        $this->signIn('farid');
        $this->getJson(self::BASE."/learners/$learner/summary")->assertOk()->assertJsonPath('data.subjects.0.step_label', 'Learning something new');
        $this->getJson($this->own())->assertNotFound();
        $this->getJson(self::BASE."/groups/{$this->group}/learners")->assertForbidden();

        $this->signIn('gina');
        $this->getJson(self::BASE."/learners/$learner/summary")->assertNotFound();

        $this->signIn('asha');
        $this->getJson(self::BASE."/groups/{$this->group}/learners")->assertForbidden();
        $this->getJson(self::BASE."/learners/$learner/summary")->assertNotFound();
        $this->getJson(self::BASE.'/learners/'.$this->schoolA->ids['ben'].'/groups/'.$this->group)->assertNotFound();
        $this->assertSame('teach', LearningPath::sole()->step);
    }

    public function test_module_off_refuses_and_school_b_never_reaches_school_a_paths(): void
    {
        $this->signIn('asha');
        $this->step('next', 0);
        SchoolModule::where('module', 'mastery')->update(['enabled' => false]);
        app(ModuleGate::class)->forget();
        // A switched-off module is hidden by the module middleware (404), as for every module.
        $this->getJson($this->own())->assertNotFound();
        $this->getJson(self::BASE.'/learners/'.$this->schoolA->ids['asha'].'/summary')->assertNotFound();
        SchoolModule::where('module', 'mastery')->update(['enabled' => true]);
        app(ModuleGate::class)->forget();

        app(CurrentSchool::class)->run(self::SCHOOL_B_ID, function () {
            foreach (['mastery', 'daily_practice'] as $module) {
                SchoolModule::updateOrCreate(['module' => $module], ['in_package' => true, 'enabled' => true]);
            }
            User::whereKey($this->schoolB->ids['asha_user'])->update(['password_change_required' => false]);
        });
        app(ModuleGate::class)->forget();
        $before = LearningPath::sole()->only(['step', 'revision']);
        foreach (['asha', 'tara', 'farid'] as $name) {
            $this->signIn($name, 'B');
            $this->assertContains($this->getJson($this->own())->status(), [403, 404]);
            $this->assertContains($this->postJson($this->own().'/actions', ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1, 'action' => 'practise'])->status(), [403, 404]);
            $this->assertContains($this->getJson(self::BASE."/groups/{$this->group}/learners")->status(), [403, 404]);
            $this->assertContains($this->getJson(self::BASE.'/learners/'.$this->schoolA->ids['asha'].'/summary')->status(), [403, 404]);
        }
        $this->assertSame($before, LearningPath::sole()->only(['step', 'revision']));
    }
}
