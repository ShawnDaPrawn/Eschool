<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Academics\TeachingAllocation;
use App\Models\Learning\PracticeReview;
use App\Models\Platform\LearnerApproval;
use App\Services\Facilitators\FacilitatorAccess;
use App\Services\Growth\MasteryRecompute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPracticeScenario;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class PracticeApiTest extends TestCase
{
    use UsesPracticeScenario, ValidatesOpenApiResponses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
    }

    private function signIn(string $name): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->withToken($this->schoolA->tokens[$name])->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $this->actor($name)->id]);
    }

    private function question(array $changes = []): array
    {
        return $changes + ['operation_id' => (string) Str::uuid(), 'expected_revision' => 0, 'topic_id' => $this->nodes[$this->keys[1]],
            'content_version_id' => $this->source, 'estimated_seconds' => 120, 'active' => true,
            'definition' => ['kind' => 'number', 'prompt' => 'One half as a decimal?', 'explanation' => 'Divide one by two.', 'options' => [], 'answers' => ['.5']]];
    }

    public function test_teacher_approves_and_revises_immutable_bank_with_all_typed_choices(): void
    {
        $this->signIn('tara');
        $this->assertTrue(app(FacilitatorAccess::class)->profile($this->actor())['features']['daily_practice']);
        $base = '/api/v2/learning/practice';
        $this->getJson("$base/access")->assertOk()->assertJsonPath('data.staff_groups.0.id', $this->group);
        $this->getJson("$base/groups/{$this->group}/choices")->assertOk()->assertJsonPath('data.sources.0.id', $this->source);
        $input = $this->question();
        $created = $this->postJson("$base/groups/{$this->group}/bank", $input)->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data');
        $this->postJson("$base/groups/{$this->group}/bank", $input)->assertOk()->assertJsonPath('data', $created);
        $this->getJson("$base/groups/{$this->group}/bank")->assertOk()->assertJsonPath('data.items.0.definition.answers.0', '0.5');
        $changed = $this->question(['expected_revision' => 1]);
        $this->postJson("$base/groups/{$this->group}/bank/{$created['id']}", $changed)->assertOk()->assertJsonPath('data.revision', 2);
        $this->postJson("$base/groups/{$this->group}/bank/{$created['id']}", $this->question(['expected_revision' => 1]))->assertConflict();
    }

    public function test_own_approved_learner_issues_resumes_and_reviews_same_set_without_official_marks(): void
    {
        $this->approve();
        $this->signIn('asha');
        $base = '/api/v2/learning/practice';
        $learner = $this->schoolA->ids['asha'];
        $this->getJson("$base/access")->assertOk()->assertJsonPath('data.learner_id', $learner)->assertJsonPath('data.staff_groups', []);
        $input = ['operation_id' => (string) Str::uuid(), 'practice_on' => '2026-02-10'];
        $set = $this->postJson("$base/learners/$learner/groups/{$this->group}/daily-sets", $input)->assertOk()->json('data');
        $this->postJson("$base/learners/$learner/groups/{$this->group}/daily-sets", $input)->assertOk()->assertJsonPath('data.id', $set['id']);
        $this->getJson("$base/learners/$learner/daily-sets/{$set['id']}")->assertOk()->assertJsonPath('data.items.0.definition.answers.0', '0.5');
        $entry = $set['items'][0]['id'];
        $path = "$base/learners/$learner/daily-sets/{$set['id']}/entries/$entry/review";
        $review = ['operation_id' => (string) Str::uuid(), 'answer' => '.50', 'rating' => 3];
        $official = DB::table('question_marks')->count();
        $first = $this->postJson($path, $review)->assertOk()->assertJsonPath('data.correct', true)->json('data');
        $this->postJson($path, $review)->assertOk()->assertJsonPath('data', $first);
        $this->postJson($path, array_replace($review, ['answer' => '.25']))->assertConflict();
        $this->assertSame($official, DB::table('question_marks')->count());
        $this->assertSame(1, PracticeReview::count());
    }

    public function test_unapproved_owner_wrong_role_other_learner_and_replacement_account_are_refused(): void
    {
        $this->approve();
        $set = $this->issue();
        $base = '/api/v2/learning/practice';
        $learner = $this->schoolA->ids['asha'];
        $this->signIn('farid');
        $this->getJson("$base/groups/{$this->group}/bank")->assertForbidden();
        $this->getJson("$base/learners/$learner/daily-sets/{$set['id']}")->assertNotFound();
        $this->signIn('asha');
        $this->getJson("$base/learners/{$this->schoolA->ids['ben']}/daily-sets/{$set['id']}")->assertNotFound();
        $this->withHeader('X-Account-Id', (string) $this->actor('farid')->id)->getJson("$base/access")->assertForbidden();
        $this->withHeader('X-Account-Id', (string) $this->actor('asha')->id);
        LearnerApproval::query()->update(['withdrawn_at' => now()]);
        $this->getJson("$base/learners/$learner/daily-sets/{$set['id']}")->assertForbidden();
    }

    public function test_source_authority_precedes_bank_body_load_and_current_teacher_allocation(): void
    {
        $this->approve();
        $this->signIn('tara');
        $base = '/api/v2/learning/practice';
        $this->content->update(['author_id' => $this->actor('theo')->id]);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->postJson("$base/groups/{$this->group}/bank", $this->question())->assertNotFound();
        foreach (DB::getQueryLog() as $query) {
            if (str_contains($query['query'], 'from `content_versions`')) {
                $this->assertStringStartsWith('select `id` from `content_versions`', $query['query']);
                $this->assertStringNotContainsString('`markdown`', $query['query']);
            }
        }
        DB::disableQueryLog();
        TeachingAllocation::where('teaching_group_id', $this->group)->update(['ends_on' => '2026-02-09']);
        $this->getJson("$base/groups/{$this->group}/choices")->assertForbidden();
    }

    public function test_private_input_errors_and_unauthed_replies_are_typed_no_store_even_for_html_accept(): void
    {
        $base = '/api/v2/learning/practice';
        $this->signIn('tara');
        $this->get("$base/groups/{$this->group}/bank?page=0", ['Accept' => 'text/html'])->assertUnprocessable()->assertJsonPath('code', 'validation_failed')->assertSessionMissing('_old_input');
        $bad = $this->question(['definition' => ['kind' => 'number', 'prompt' => 'PRIVATE bounded draft', 'explanation' => 'Worked explanation', 'answers' => ['1e3'], 'options' => []]]);
        $this->postJson("$base/groups/{$this->group}/bank", $bad)->assertUnprocessable()->assertHeader('Cache-Control', 'no-store, private')->assertDontSee('PRIVATE bounded draft');
        $this->postJson("$base/groups/{$this->group}/bank?owner=forbidden", $this->question())->assertUnprocessable();
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->getJson("$base/access")->assertUnauthorized()->assertHeader('Cache-Control', 'no-store, private');
        $this->postJson("$base/groups/{$this->group}/bank", [])->assertUnauthorized()->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_practice_only_projection_never_reintroduces_released_marks_when_family_visibility_is_off(): void
    {
        $this->capture('0.00', '0.00');
        $this->graph();
        $this->release();
        app(MasteryRecompute::class)->group($this->group);
        $this->graph(false, 1);
        $this->approve();
        $set = $this->issue();
        $this->review($set);
        $view = $this->masteryView('asha', staff: false);
        $topics = collect($view['topics'])->keyBy('id');
        $this->assertFalse($view['marks_visible']);
        $this->assertSame(4, $topics[$this->nodes[$this->keys[1]]]['level']);
        $this->assertNull($topics[$this->nodes[$this->keys[2]]]['level']);
        $this->assertSame(1, $topics[$this->nodes[$this->keys[1]]]['evidence_count']);
    }
}
