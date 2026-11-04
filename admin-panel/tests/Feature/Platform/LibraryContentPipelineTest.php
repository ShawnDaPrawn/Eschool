<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Ai\AiUsage;
use App\Models\Ai\PlatformAiUsage;
use App\Models\Library\LibraryAiDraft;
use App\Models\Library\LibraryItem;
use App\Models\Library\LibraryItemVersion;
use App\Models\Library\LibraryReview;
use App\Models\Library\LibrarySource;
use App\Models\Library\LibraryTopic;
use App\Models\Platform\AuditLog;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\InferenceModerator;
use App\Services\Ai\Inference\InferenceProvider;
use App\Services\Ai\Inference\ModerationContext;
use App\Services\Ai\Inference\ProviderRequest;
use App\Services\Ai\Inference\ProviderResponse;
use App\Services\Learning\Library\LibraryDrafting;
use App\Services\Learning\Library\LibraryItems;
use App\Services\Learning\Library\LibraryPages;
use App\Services\Learning\Library\LibraryQuality;
use App\Services\Learning\Library\LibraryReviewers;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolOwnedTables;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\PassesPlatformTwoFactor;
use Tests\Concerns\UsesContentLibrary;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * The content production pipeline (Action Plan 3.4, SA.16): HQ asks for AI
 * drafts of library items through the gateway's HQ path; they land as drafts
 * labelled as AI drafts and are published only after a subject reviewer who
 * did not ask for them approves; learners' answers across schools flag weak
 * items as counts and rates, never a school or a learner. The model provider
 * is always a local probe: no test calls a provider.
 * engineering/specs/learning/content-pipeline.md.
 */
class LibraryContentPipelineTest extends TestCase
{
    use PassesPlatformTwoFactor;
    use UsesContentLibrary, UsesTwoSchools;

    private LibraryTopic $fractions;

    private LibrarySource $source;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->forget();
        $this->standard('MATH-SP');
        $this->fractions = $this->libraryTopic('Fractions');
        $this->source = $this->librarySource();
        LibraryProbeProvider::reset();
        LibraryProbeModerator::reset();
        // Belt and braces: no request may leave the test, whatever the config says.
        Http::swap(new Factory);
        Http::preventStrayRequests();
    }

    /** Inference on, a qualified provider, and the probe in place of the real adapters. */
    private function switchOn(array $changes = []): void
    {
        $feature = 'ai.platform.features.library_drafting.';
        config($changes + ['ai.inference.enabled' => true, 'ai.providers.openai.qualified' => true, $feature.'enabled' => true, $feature.'model' => 'library-protocol-test',
            $feature.'cents_per_million_tokens' => 1000, $feature.'provider_class' => LibraryProbeProvider::class, $feature.'moderator' => LibraryProbeModerator::class]);
    }

    private function ask(array $changes = []): array
    {
        return $changes + ['operation_id' => (string) Str::uuid(), 'library_topic_id' => $this->fractions->id, 'library_source_id' => $this->source->id,
            'kind' => 'practice_question', 'count' => 2, 'brief' => 'Equivalent fractions with halves and quarters, one choice and one number question.'];
    }

    private function refuses(callable $call, int $status): void
    {
        try {
            $call();
            $this->fail('Expected a refusal.');
        } catch (HttpException $error) {
            $this->assertSame($status, $error->getStatusCode());
        }
    }

    /** @return array<string, int> */
    private function counts(): array
    {
        return $this->libraryCounts() + ['platform_ai_usage' => DB::table('platform_ai_usage')->count(), 'ai_usage' => DB::table('ai_usage')->count()];
    }

    public function test_content_staff_get_ai_drafts_that_are_published_only_after_a_subject_reviewer_approves(): void
    {
        $this->switchOn();
        $this->assignReviewer('MATH-SP');
        $content = $this->libraryStaff('content');
        $this->signInAs($content);
        $this->get('/saas/library/frameworks/'.$this->fractions->library_framework_id)->assertOk()->assertSee('Ask AI for drafts')->assertSee('Ask for drafts');
        $request = $this->ask();
        $this->post('/saas/library/drafts', $request)->assertRedirect()->assertSessionHasNoErrors();

        // Two items, each version 1 a draft labelled AI draft, linked to the topic, the source and the request.
        $draft = LibraryAiDraft::query()->sole();
        $this->assertSame('completed', $draft->state);
        $items = LibraryItem::query()->orderBy('id')->get();
        $this->assertCount(2, $items);
        $this->assertSame($items->pluck('id')->map(fn ($id) => (int) $id)->all(), $draft->item_ids);
        foreach ($items as $item) {
            $this->assertSame($this->fractions->id, $item->library_topic_id);
            $this->assertSame($this->source->id, $item->library_source_id);
            $this->assertNull($item->published_version_id, 'nothing AI-written is published');
            $version = LibraryItemVersion::query()->where('library_item_id', $item->id)->sole();
            $this->assertSame(['draft', 'ai_draft', $draft->id, (int) $content->id], [$version->state, $version->origin, $version->library_ai_draft_id, (int) $version->created_by]);
            $this->assertSame([(int) $content->id], $version->editors(), 'the person who asked is the first editor');
        }
        $this->assertSame('choice', LibraryItemVersion::query()->where('library_item_id', $items[0]->id)->sole()->content['definition']['kind']);
        // Metered in HQ's own ledger, never a school's; flag codes and hashes only.
        $usage = PlatformAiUsage::query()->sole();
        $this->assertSame(['library_drafting', 'completed', 160, (int) $content->id], [$usage->feature, $usage->state, $usage->used_units, $usage->user_id]);
        $this->assertGreaterThan(0, $usage->cost_cents);
        $this->assertSame($usage->id, $draft->platform_ai_usage_id);
        $this->assertSame(0, AiUsage::acrossSchools()->count());
        $this->assertSame([1, 2], [LibraryProbeProvider::$calls, LibraryProbeModerator::$calls], 'input and output moderated');
        // The model saw library data and the brief only.
        $sent = json_decode(LibraryProbeProvider::$inputs[0], true);
        $this->assertSame(['task', 'kind', 'count', 'framework', 'subject_code', 'grade', 'topic', 'prerequisites', 'source', 'brief'], array_keys($sent));
        $this->assertSame(['CAPS', 'MATH-SP', 'Fractions'], [$sent['framework'], $sent['subject_code'], $sent['topic']]);
        foreach (School::query()->pluck('name') as $school) {
            $this->assertStringNotContainsString($school, LibraryProbeProvider::$inputs[0]);
        }

        // The same form sent again is the same request: no second call, no new items. Someone else reusing it is refused.
        $this->post('/saas/library/drafts', $request)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame([1, 2], [LibraryProbeProvider::$calls, LibraryItem::query()->count()]);
        $this->refuses(fn () => app(LibraryDrafting::class)->request($this->libraryStaff('owner'), $request), 409);

        // Not publishable until reviewed.
        $item = $items[0];
        $version = LibraryItemVersion::query()->where('library_item_id', $item->id)->sole();
        $this->get('/saas/library/items/'.$item->id)->assertOk()->assertSee('AI draft')->assertSee('not yet approved by a reviewer')->assertSee('Equivalent fractions with halves');
        $this->post('/saas/library/items/'.$item->id.'/publish', ['version_id' => $version->id, 'revision' => $item->fresh()->revision])->assertStatus(409);
        $this->post('/saas/library/items/'.$item->id.'/submit', ['revision' => $item->fresh()->revision])->assertRedirect()->assertSessionHasNoErrors();
        $this->post('/saas/library/items/'.$item->id.'/publish', ['version_id' => $version->id, 'revision' => $item->fresh()->revision])->assertStatus(409);
        $this->assertNull($item->fresh()->published_version_id);

        // The reviewer filters the queue to AI drafts and approves; then Content publishes.
        $this->signInAs($this->libraryStaff('reviewer'));
        $this->get('/saas/library/reviews?filter=ai_draft')->assertOk()->assertSee($item->title)->assertSee('AI draft: check every fact, answer and explanation');
        $this->post('/saas/library/versions/'.$version->id.'/review', ['decision' => 'approved'])->assertRedirect()->assertSessionHasNoErrors();
        $this->signInAs($content);
        $this->post('/saas/library/items/'.$item->id.'/publish', ['version_id' => $version->id, 'revision' => $item->fresh()->revision])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($version->id, $item->fresh()->published_version_id);
        $this->get('/saas/library/items/'.$item->id)->assertOk()->assertSee('reviewed by Reviewer Librarian');

        // The audit carries the origin and the reviewer's name; nothing is filed against a school.
        $reviewed = AuditLog::query()->where('action', 'library_reviewed')->sole();
        $this->assertSame(['ai_draft', 'Reviewer Librarian'], [$reviewed->after['origin'], $reviewed->after['reviewed_by']]);
        $published = AuditLog::query()->where('action', 'library_published')->sole();
        $this->assertSame(['ai_draft', 'Reviewer Librarian'], [$published->after['origin'], $published->after['reviewed_by']]);
        $this->assertEquals(['library_ai_draft_requested' => 1, 'library_item_created' => 2, 'library_ai_drafted' => 1],
            AuditLog::query()->whereIn('action', ['library_ai_draft_requested', 'library_item_created', 'library_ai_drafted'])->pluck('action')->countBy()->all());
        $this->assertSame(0, AuditLog::query()->where('action', 'like', 'library_%')->whereNotNull('school_id')->count());
        $this->assertSame(0, AuditLog::query()->where('action', 'library_ai_draft_requested')->where('after', 'like', '%Equivalent fractions%')->count(), 'the brief is not audited');
    }

    public function test_the_person_who_asked_for_an_ai_draft_can_never_approve_it_and_its_label_stays(): void
    {
        $this->switchOn();
        $owner = $this->libraryStaff('owner');
        $content = $this->libraryStaff('content');
        $this->assignReviewer('MATH-SP', 'owner');
        $this->assignReviewer('MATH-SP');
        $items = app(LibraryItems::class);
        // The Owner, a Maths reviewer, asks; Content edits and sends it for review.
        $draft = app(LibraryDrafting::class)->request($owner, $this->ask(['count' => 1]));
        $item = LibraryItem::query()->findOrFail($draft->item_ids[0]);
        $items->saveDraft($content, $item->id, $this->question(['title' => 'Equivalent fractions', 'revision' => $item->revision]));
        $version = $items->submit($content, $item->id, ['revision' => $item->fresh()->revision]);
        $this->assertSame('ai_draft', $version->fresh()->origin, 'staff edits do not turn an AI draft into staff writing');
        $this->assertSame('Someone else must review a version you wrote, changed or submitted.', app(LibraryReviewers::class)->refusal($owner, $version->fresh()));
        $this->refuses(fn () => $items->review($owner, $version->id, ['decision' => 'approved']), 403);
        $this->signInAs($owner)->post('/saas/library/versions/'.$version->id.'/review', ['decision' => 'approved'])->assertForbidden();
        $this->assertSame(0, LibraryReview::query()->count());
        $this->assertSame('in_review', $version->fresh()->state);
        // The origin can never be changed, not even on a draft.
        $this->refuses(fn () => LibraryItemVersion::query()->findOrFail($version->id)->update(['origin' => 'staff']), 409);

        // A reviewer who had no hand in it approves; the next draft keeps the label and the request.
        $items->review($this->libraryStaff('reviewer'), $version->id, ['decision' => 'approved']);
        $items->publish($content, $item->id, ['version_id' => $version->id, 'revision' => $item->fresh()->revision]);
        $next = $items->saveDraft($content, $item->id, $this->question(['answers' => '.5', 'revision' => $item->fresh()->revision]));
        $this->assertSame(['ai_draft', $draft->id], [$next->origin, $next->library_ai_draft_id]);
        $this->assertContains((int) $owner->id, $next->editors());
        // A version written by staff is labelled with its source.
        $staff = $this->draftItem($this->fractions, $this->source, ['title' => 'Staff question']);
        $page = app(LibraryPages::class)->item($content, $staff->id);
        $this->assertSame('staff', $page['versions'][0]['origin']);
        $this->assertSame('Written by staff from Navigate Ed maths bank original (All rights reserved, Navigate Ed)', $page['versions'][0]['provenance']);
    }

    public function test_while_inference_is_off_drafting_is_refused_with_a_clear_message_and_nothing_is_written(): void
    {
        $before = $this->counts();
        $content = $this->libraryStaff('content');
        $this->signInAs($content);
        $this->get('/saas/library/frameworks/'.$this->fractions->library_framework_id)->assertOk()
            ->assertSee('AI drafting is switched off')->assertDontSee('Ask for drafts');
        $this->get('/saas/library')->assertOk()->assertSee('AI drafting is switched off');
        $this->post('/saas/library/drafts', $this->ask())->assertRedirect()->assertSessionHasErrors(['drafting' => LibraryDrafting::MESSAGES['inference_disabled']]);

        // The feature on but the platform switch off, or a provider not qualified: still refused.
        $this->switchOn(['ai.inference.enabled' => false]);
        $this->post('/saas/library/drafts', $this->ask())->assertSessionHasErrors(['drafting' => LibraryDrafting::MESSAGES['inference_disabled']]);
        $this->switchOn(['ai.providers.openai.qualified' => false]);
        $this->post('/saas/library/drafts', $this->ask())->assertSessionHasErrors(['drafting' => LibraryDrafting::MESSAGES['provider_not_qualified']]);
        // The shipped config: off everywhere, no provider qualified.
        $shipped = require config_path('ai.php');
        $this->assertFalse($shipped['inference']['enabled']);
        $this->assertFalse($shipped['platform']['features']['library_drafting']['enabled']);
        foreach ($shipped['providers'] as $provider) {
            $this->assertFalse($provider['qualified']);
        }

        $this->assertSame($before, $this->counts());
        $this->assertSame([0, 0], [LibraryProbeProvider::$calls, LibraryProbeModerator::$calls]);
        Http::assertNothingSent();
    }

    public function test_moderation_a_bad_answer_and_hq_caps_add_nothing_to_the_library(): void
    {
        $this->switchOn();
        $content = $this->libraryStaff('content');
        $drafting = app(LibraryDrafting::class);

        // Output flagged by moderation: held, the flag code kept, the text never.
        LibraryProbeModerator::$flagOutput = true;
        $held = $drafting->request($content, $this->ask());
        $this->assertSame(['held', 'inference_quarantined'], [$held->state, $held->failure_code]);
        $usage = PlatformAiUsage::query()->sole();
        $this->assertSame(['quarantined', ['provider_content_flag']], [$usage->state, $usage->flags]);
        $this->assertSame(0, LibraryItem::query()->count());
        foreach ((array) DB::table('platform_ai_usage')->first() as $value) {
            $this->assertStringNotContainsString('fraction', strtolower((string) $value));
        }
        LibraryProbeModerator::$flagOutput = false;

        // An answer that is not library content (a link in it, or the wrong number of items) is refused whole.
        LibraryProbeProvider::$change = fn (array $output) => ['kind' => $output['kind'], 'items' => [$output['items'][0]]];
        $this->assertSame('library_output_invalid', $drafting->request($content, $this->ask())->failure_code);
        LibraryProbeProvider::$change = function (array $output) {
            $output['items'][1]['prompt'] = 'See https://example.org for the answer.';

            return $output;
        };
        $bad = $drafting->request($content, $this->ask());
        $this->assertSame(['failed', 'library_output_invalid'], [$bad->state, $bad->failure_code]);
        $this->assertSame(0, LibraryItem::query()->count());
        $this->signInAs($content)->post('/saas/library/drafts', $this->ask())->assertRedirect()->assertSessionHasErrors(['drafting' => LibraryDrafting::MESSAGES['library_output_invalid']]);
        LibraryProbeProvider::$change = null;

        // HQ's caps: the whole platform's and each person's, checked before the provider is called.
        $calls = LibraryProbeProvider::$calls;
        $this->switchOn(['ai.platform.features.library_drafting.platform_daily_cents' => 1]);
        $this->assertSame('platform_daily_cap', $drafting->request($content, $this->ask())->failure_code);
        $this->switchOn(['ai.platform.features.library_drafting.platform_daily_cents' => 2000, 'ai.platform.features.library_drafting.actor_daily_cents' => 1]);
        $this->assertSame('actor_daily_cap', $drafting->request($content, $this->ask())->failure_code);
        $this->assertSame($calls, LibraryProbeProvider::$calls);
        $this->assertSame(0, LibraryItem::query()->count());
        // A lesson is one at a time; a past-paper question is never drafted by AI.
        foreach ([['kind' => 'lesson', 'count' => 2], ['kind' => 'past_paper_question']] as $wrong) {
            try {
                $drafting->request($content, $this->ask($wrong));
                $this->fail('Expected a validation error.');
            } catch (ValidationException $error) {
                $this->assertArrayHasKey(array_key_last($wrong), $error->errors());
            }
        }
    }

    public function test_wrong_hq_roles_and_school_accounts_are_refused_and_nothing_is_written(): void
    {
        $this->switchOn();
        $item = $this->publishedItem($this->fractions);
        $before = $this->counts();
        $attempt = function () use ($item) {
            $this->post('/saas/library/drafts', $this->ask())->assertForbidden();
            $this->get('/saas/library/attention')->assertForbidden();
            $this->post('/saas/library/items/'.$item->id.'/redraft', ['revision' => $item->fresh()->revision])->assertForbidden();
        };
        foreach (['finance', 'sales', 'support', 'operations', 'reviewer'] as $role) {
            $this->signInAs($this->libraryStaff($role));
            $attempt();
            $this->refuses(fn () => app(LibraryDrafting::class)->request($this->libraryStaff($role), $this->ask()), 403);
        }
        foreach ([$this->superAdmin(), User::query()->findOrFail($this->schoolA->ids['tara_user']), User::query()->findOrFail($this->schoolB->ids['tara_user'])] as $person) {
            $this->signInAs($person);
            $attempt();
        }
        // Platform staff acting inside a school are refused too: the library and its signals are read and written only from HQ.
        foreach ([1, 2] as $school) {
            app(CurrentSchool::class)->run($school, function () {
                $this->refuses(fn () => app(LibraryDrafting::class)->request($this->libraryStaff('content'), $this->ask()), 403);
                $this->refuses(fn () => app(LibraryQuality::class)->signals(), 403);
            });
        }
        $this->assertSame($before, $this->counts());
        $this->assertSame(0, LibraryProbeProvider::$calls);
    }

    public function test_weak_items_are_flagged_only_for_large_groups_and_never_show_a_school_or_a_learner(): void
    {
        School::query()->forceCreate(['id' => 3, 'name' => 'Cycad Primary', 'slug' => 'cycad-primary', 'timezone' => 'Africa/Johannesburg']);
        $hard = $this->publishedItem($this->fractions, ['title' => 'Hard fractions']);
        $easy = $this->publishedItem($this->fractions, ['title' => 'Easy fractions']);
        $fine = $this->publishedItem($this->fractions, ['title' => 'Fine fractions']);
        $twoSchools = $this->publishedItem($this->fractions, ['title' => 'Two schools only']);
        $fewLearners = $this->publishedItem($this->fractions, ['title' => 'Nineteen learners']);
        // Learners' answers in three schools (synthetic rows, no person behind them).
        $this->answers($hard, [1 => [10, 2], 2 => [8, 2], 3 => [7, 1]]);          // 25 learners, 20% right
        $this->answers($easy, [1 => [10, 10], 2 => [10, 10], 3 => [10, 10]]);     // 30 learners, all right
        $this->answers($fine, [1 => [10, 6], 2 => [10, 6], 3 => [10, 6]]);        // 60%: no signal
        $this->answers($twoSchools, [1 => [20, 1], 2 => [20, 1]]);                // 40 learners but 2 schools: suppressed
        $this->answers($fewLearners, [1 => [7, 0], 2 => [6, 0], 3 => [6, 0]]);    // 19 learners: suppressed
        // A school's own question answered by many: never counted (not library content).
        $this->answers(null, [1 => [30, 0], 2 => [30, 0], 3 => [30, 0]]);
        $before = $this->libraryCounts() + ['reviews' => DB::table('practice_reviews')->count(), 'entries' => DB::table('practice_set_entries')->count()];

        $signals = app(LibraryQuality::class)->signals();
        $this->assertEqualsCanonicalizing([$hard->id, $easy->id, $fine->id], array_keys($signals));
        $this->assertSame([2000, 'hard', 25], [$signals[$hard->id]['facility_bp'], $signals[$hard->id]['signal'], $signals[$hard->id]['learners']]);
        $this->assertSame(['easy', null], [$signals[$easy->id]['signal'], $signals[$fine->id]['signal']]);
        foreach ($signals as $row) {
            $this->assertSame(['version_id', 'answers', 'learners', 'facility_bp', 'signal'], array_keys($row), 'counts and rates only');
        }

        $this->signInAs($this->libraryStaff('content'));
        $page = $this->get('/saas/library/attention')->assertOk()->assertSee('Hard fractions')->assertSee('Easy fractions')->assertSee('20%')->assertSee('20 to 49')
            ->assertDontSee('Fine fractions')->assertDontSee('Two schools only')->assertDontSee('Nineteen learners');
        foreach (School::query()->pluck('name')->push('Jacaranda Demo School') as $school) {
            $page->assertDontSee($school);
        }
        $page->assertViewHas('rows', fn (array $rows) => array_keys($rows[0]) === ['id', 'title', 'revision', 'framework', 'subject_code', 'grade', 'topic', 'facility', 'learners', 'signal', 'signal_label', 'origin', 'open']);
        $this->get('/saas/library')->assertOk()->assertSee('2 items need attention from learners');
        $this->get('/saas/library/items/'.$hard->id)->assertOk()->assertSee('20% right, from 20 to 49 learners');
        // Reading the signals copies nothing into the library and changes no school row.
        $this->assertSame($before, $this->libraryCounts() + ['reviews' => DB::table('practice_reviews')->count(), 'entries' => DB::table('practice_set_entries')->count()]);

        // From the list, HQ opens a new draft of the flagged item; the published version stays what learners see.
        $published = $hard->fresh()->published_version_id;
        $this->post('/saas/library/items/'.$hard->id.'/redraft', ['revision' => $hard->fresh()->revision])->assertRedirect()->assertSessionHasNoErrors();
        $draft = LibraryItemVersion::query()->where('library_item_id', $hard->id)->orderByDesc('number')->first();
        $this->assertSame([2, 'draft'], [$draft->number, $draft->state]);
        $this->assertSame(LibraryItemVersion::query()->findOrFail($published)->content, $draft->content);
        $this->assertSame($published, $hard->fresh()->published_version_id);
        $this->get('/saas/library/attention')->assertOk()->assertSee('Draft');
        $this->post('/saas/library/items/'.$hard->id.'/redraft', ['revision' => $hard->fresh()->revision])->assertStatus(409);
        // Sent for review, it shows under the queue's "Needs attention" filter, and only there with the AI filter off.
        app(LibraryItems::class)->submit($this->libraryStaff('content'), $hard->id, ['revision' => $hard->fresh()->revision]);
        $queue = app(LibraryPages::class)->reviews($this->libraryStaff('reviewer'), 1, 'attention');
        $this->assertSame(['Hard fractions'], array_column($queue['waiting'], 'title'));
        $this->assertTrue($queue['waiting'][0]['attention']);
        $this->assertSame(0, app(LibraryPages::class)->reviews($this->libraryStaff('reviewer'), 1, 'ai_draft')['total']);
        $this->signInAs($this->libraryStaff('reviewer'))->get('/saas/library/reviews?filter=attention')->assertOk()->assertSee('Hard fractions')
            ->assertSee('Needs attention: learners');
    }

    public function test_new_tables_belong_to_no_school(): void
    {
        foreach (['library_ai_drafts', 'platform_ai_usage'] as $table) {
            $this->assertNotContains('school_id', Schema::getColumnListing($table), $table);
            $this->assertArrayHasKey($table, SchoolOwnedTables::GLOBAL, $table);
        }
        // HQ metering is never written from inside a school.
        app(CurrentSchool::class)->run(1, fn () => $this->refuses(fn () => PlatformAiUsage::query()->create(['user_id' => 1, 'feature' => 'library_drafting', 'provider' => 'openai',
            'model' => 'x', 'operation_id' => (string) Str::uuid(), 'request_hash' => str_repeat('a', 64), 'usage_date' => '2026-11-04', 'state' => 'reserved',
            'billing_currency' => 'USD', 'prompt_key' => 'library-drafting', 'prompt_version' => '1.0.0', 'prompt_sha256' => str_repeat('b', 64)]), 403));
        $this->assertSame(0, PlatformAiUsage::query()->count());
    }

    /**
     * Synthetic answers to an item's published version (or to a school's own
     * question when $item is null): per school, [learners, of whom right].
     */
    private function answers(?LibraryItem $item, array $schools): void
    {
        static $learner = 900000;
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach ($schools as $school => [$learners, $right]) {
                for ($i = 0; $i < $learners; $i++) {
                    $learner++;
                    $set = DB::table('practice_daily_sets')->insertGetId(['school_id' => $school, 'learner_id' => $learner, 'teaching_group_id' => 1, 'practice_on' => '2026-11-03',
                        'offline_until' => '2026-11-04 00:00:00', 'created_at' => now(), 'updated_at' => now()]);
                    $entry = DB::table('practice_set_entries')->insertGetId(['school_id' => $school, 'practice_daily_set_id' => $set, 'position' => 1,
                        'practice_item_version_id' => $item === null ? 999999 : null, 'library_item_version_id' => $item?->published_version_id, 'created_at' => now(), 'updated_at' => now()]);
                    DB::table('practice_reviews')->insert(['school_id' => $school, 'learner_id' => $learner, 'practice_set_entry_id' => $entry, 'answer' => 'synthetic',
                        'correct' => $i < $right, 'schedule' => '{}', 'reviewed_on' => '2026-11-03', 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}

/** A local stand-in for a provider: never a network call. */
class LibraryProbeProvider implements InferenceProvider
{
    public static int $calls = 0;

    /** @var list<string> */
    public static array $inputs = [];

    /** @var (callable(array): array)|null */
    public static $change = null;

    public static function reset(): void
    {
        self::$calls = 0;
        self::$inputs = [];
        self::$change = null;
    }

    public function preflight(ProviderRequest $request): void {}

    public function infer(ProviderRequest $request): ProviderResponse
    {
        self::$calls++;
        self::$inputs[] = (string) $request->input;
        $input = json_decode((string) $request->input, true, 16, JSON_THROW_ON_ERROR);
        $items = [];
        for ($i = 0; $i < $input['count']; $i++) {
            $items[] = $input['kind'] === 'lesson'
                ? ['title' => 'Equivalent fractions', 'body' => 'Two fractions are equivalent when they name the same part of a whole. Worked example: 1/2 = 2/4.']
                : ($i === 0
                    ? ['title' => 'Which equals one half?', 'question_kind' => 'choice', 'prompt' => 'Which fraction equals one half?', 'explanation' => 'Two quarters make one half.',
                        'options' => [['id' => 'A', 'text' => '2/4'], ['id' => 'B', 'text' => '1/3']], 'answers' => ['A'], 'estimated_seconds' => 90]
                    : ['title' => 'Quarters in a half', 'question_kind' => 'number', 'prompt' => 'How many quarters make one half?', 'explanation' => 'One half is two quarters.',
                        'options' => [], 'answers' => ['2'], 'estimated_seconds' => 60]);
        }
        $output = ['kind' => $input['kind'], 'items' => $items];
        if (self::$change !== null) {
            $output = (self::$change)($output);
        }

        return new ProviderResponse(json_encode($output, JSON_THROW_ON_ERROR), 160);
    }
}

/** A local stand-in for moderation: counts calls, flags output on request. */
class LibraryProbeModerator implements InferenceModerator
{
    public static int $calls = 0;

    public static bool $flagOutput = false;

    public static function reset(): void
    {
        self::$calls = 0;
        self::$flagOutput = false;
    }

    public function preflight(Capability $capability, string|array $input, ModerationContext $context): void {}

    public function flags(Capability $capability, string $direction, string|array $content, ModerationContext $context): array
    {
        self::$calls++;

        return $direction === 'output' && self::$flagOutput ? ['provider_content_flag'] : [];
    }
}
