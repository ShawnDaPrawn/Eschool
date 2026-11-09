<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Ai\AiQuarantineSnapshot;
use App\Models\Ai\AiUsage;
use App\Models\Learning\TutorMessage;
use App\Models\Platform\SchoolModule;
use App\Services\Learning\SafeguardingPhraseReviews;
use App\Services\Learning\SafeguardingPhrases;
use App\Services\Learning\TutorLanguages;
use App\Services\Learning\TutorModeration;
use App\Services\Learning\TutorQueries;
use App\Services\Learning\TutorSessions;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Client\Factory;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\UsesPracticeScenario;
use Tests\TestCase;

/**
 * Action Plan 3.2: the local safeguarding phrase guard in every platform
 * language. Every list, reviewed or draft, is checked before any AI call; a
 * home language is offered in the tutor only once its list is reviewed.
 * engineering/specs/ai/safeguarding-phrases.md.
 */
class TutorSafeguardingLanguagesTest extends TestCase
{
    use UsesPracticeScenario;

    /** Realistic disclosures per language, written the way learners write. */
    private const DISCLOSURES = [
        'en' => [
            'self_harm' => ['I want to KILL MYSELF', 'i just want to end my life', 'sometimes I hurt my self', 'k1ll myself', 'I want to killl myselfff'],
            'abuse' => ['I am being abused at home'],
            'sexual_violence' => ['I was sexually-assaulted'],
        ],
        'af' => [
            'self_harm' => ['Ek wil myself doodmaak', 'ek wil my lewe beëindig', 'Ek wil my lewe beeindig', "ek wil 'n einde aan my lewe maak", 'Ek wil nie meer lewe nie', 'ek wil myself SEER MAAK',
                'Ek dink aan selfmoord', 'ek wil my dood maak'],
            'abuse' => ['Ek word mishandel by die huis', 'Hy misbruik my'],
            'sexual_violence' => ['Ek is verkrag', 'hy het my seksueel aangerand', 'Hy betas my'],
        ],
        'zu' => [
            'self_harm' => ['Ngifuna ukuzibulala', 'ngizozibulala namhlanje', 'uku-zibulala', 'Sengifuna ukufa', 'angisafuni ukuphila', 'ngifuna ukuqeda impilo yami', 'ngizozilimaza'],
            'abuse' => ['Ngiyahlukunyezwa ekhaya', 'uyangihlukumeza'],
            'sexual_violence' => ['wangidlwengula', 'Ngadlwengulwa', 'ukuhlukunyezwa ngokocansi'],
        ],
    ];

    /** Mixed English/Afrikaans and English/isiZulu messages, including a switch inside the phrase. */
    private const MIXED = [
        'Ek sukkel met breuke and honestly I want to kill myself',
        'Help me with fractions asseblief, ek wil myself doodmaak',
        'ek wil myself kill',
        'I need help with maths ngoba ngifuna ukuzibulala',
        'Ngicela usizo nge-algebra, I am being abused',
        'ngifuna uku-kill myself',
        'Ek word abused deur my oom',
    ];

    /** Ordinary schoolwork in each language that must never be caught. */
    private const BENIGN = [
        'How do I add fractions with different denominators?', 'Skill myself up in decimals, please', 'I will spend my life studying science',
        'Hoe deel ek breuke?', 'Die diere word in die storie mishandel', 'Ek wil my huiswerk klaarmaak', 'Wat is die einde van die storie?',
        'Ngicela ungichazele izibalo', 'Izibulali-zinambuzane zisebenza kanjani?', 'Izilimazo emdlalweni wezemidlalo', 'Ngifuna ukufunda ngezibalo',
    ];

    /** @var list<string> */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        SchoolModule::updateOrCreate(['module' => 'tutor'], ['enabled' => true, 'in_package' => true]);
        config(['ai.inference.enabled' => true, 'ai.inference.features.tutor.enabled' => true, 'ai.inference.features.tutor.model' => 'tutor-protocol-test',
            'ai.inference.features.tutor.cents_per_million_tokens' => 1000, 'ai.inference.features.tutor.prompt' => 'tutor-v2',
            'ai.providers.openai.qualified' => true, 'ai.providers.openai.key' => 'synthetic-test-only', 'ai.tutor.home_languages' => ['af', 'zu']]);
        Http::swap(new Factory);
        Http::preventStrayRequests();
        // The local fake provider: no real provider is called.
        Http::fake(function ($request) {
            $this->sent[] = $request->body();
            if (str_ends_with($request->url(), '/moderations')) {
                return Http::response(['results' => [['flagged' => false]]]);
            }
            $content = $request['messages'][1]['content'];
            $input = json_decode(is_string($content) ? $content : $content[0]['text'], true, 16, JSON_THROW_ON_ERROR);
            $output = ['subject_token' => $input['subject_token'], 'kind' => 'hint', 'text' => 'Kom ons begin met gelyke dele.', 'question' => 'Wat is die noemer?'];

            return Http::response(['model' => 'tutor-protocol-test', 'choices' => [['finish_reason' => 'stop', 'message' => ['role' => 'assistant', 'content' => json_encode($output)]]],
                'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 40, 'total_tokens' => 160]]);
        });
    }

    private function open(): array
    {
        return app(TutorSessions::class)->create($this->actor('asha'), $this->group, [$this->source], (string) Str::uuid());
    }

    private function turn(array $session, string $text, ?string $language = null): array
    {
        return app(TutorSessions::class)->send($this->actor('asha'), $session['id'], (string) Str::uuid(), $text, '', [], 'ask', $language);
    }

    private function review(string $language, string $outcome = 'reviewed', ?string $sha256 = null): void
    {
        DB::table('safeguarding_phrase_reviews')->insert(['language' => $language, 'outcome' => $outcome, 'list_sha256' => $sha256 ?? app(SafeguardingPhrases::class)->sha256($language),
            'reviewer' => 'Synthetic reviewer, synthetic organisation', 'reviewed_on' => '2026-02-01', 'note' => 'Synthetic review for this test only.', 'created_at' => now()]);
    }

    /** Every phrase as written in its list, made into a word a learner could type. */
    private function written(string $phrase): string
    {
        return implode(' ', array_map(fn (string $word) => (str_starts_with($word, '*') ? 'uku' : '').trim($word, '*').(strlen($word) > 1 && str_ends_with($word, '*') ? 'a' : ''),
            explode(' ', $phrase)));
    }

    /** @return list<string> every phrase of every list, without its wildcards */
    private function allPhrases(): array
    {
        $all = [];
        foreach (array_keys(SafeguardingPhrases::LANGUAGES) as $language) {
            foreach (app(SafeguardingPhrases::class)->list($language)['categories'] as $phrases) {
                foreach ($phrases as $phrase) {
                    $all[] = str_replace('*', '', $phrase);
                }
            }
        }

        return $all;
    }

    public function test_each_languages_list_covers_every_category_and_each_phrase_is_caught(): void
    {
        $guard = app(SafeguardingPhrases::class);
        $this->assertSame(['en', 'af', 'zu'], array_keys(SafeguardingPhrases::LANGUAGES));
        // Every home language the tutor can offer has its own list.
        $this->assertSame([], array_diff(array_keys(TutorLanguages::OFFERED), array_keys(SafeguardingPhrases::LANGUAGES)));
        foreach (array_keys(SafeguardingPhrases::LANGUAGES) as $language) {
            $list = $guard->list($language);
            $this->assertSame(SafeguardingPhrases::CATEGORIES, array_keys($list['categories']), $language);
            foreach ($list['categories'] as $category => $phrases) {
                $this->assertNotEmpty($phrases);
                foreach ($phrases as $phrase) {
                    $this->assertContains($category, $guard->categories('Can you help with my homework? '.$this->written($phrase).'.'), $language.': '.$phrase);
                }
            }
            foreach (self::DISCLOSURES[$language] as $category => $messages) {
                foreach ($messages as $message) {
                    $this->assertSame([$category], $guard->categories($message), $language.': '.$message);
                    $this->assertSame(['safeguarding_disclosure'], app(TutorModeration::class)->localFlags($message), $message);
                }
            }
        }
        // English keeps the five original phrases exactly.
        $this->assertSame(['self_harm' => ['kill myself', 'end my life', 'hurt myself'], 'abuse' => ['being abused'], 'sexual_violence' => ['sexually assaulted']],
            $guard->list('en')['categories']);
    }

    public function test_mixed_language_messages_are_caught_and_ordinary_schoolwork_is_not(): void
    {
        $guard = app(SafeguardingPhrases::class);
        foreach (self::MIXED as $message) {
            $this->assertTrue($guard->caught($message), $message);
        }
        // Broken bytes never switch the guard off.
        $this->assertTrue($guard->caught("Ek wil myself\xff doodmaak"));
        foreach (self::BENIGN as $message) {
            $this->assertSame([], $guard->categories($message), $message);
        }
        // Every label the apps and panel show, in each platform language, passes.
        foreach (array_keys(SafeguardingPhrases::LANGUAGES) as $language) {
            $labels = json_decode((string) file_get_contents(resource_path('lang/'.$language.'.json')), true, 4, JSON_THROW_ON_ERROR);
            foreach ($labels as $key => $label) {
                $this->assertFalse($guard->caught($key.' '.$label), $language.': '.$key);
            }
        }
    }

    public function test_draft_lists_are_still_checked_and_nothing_reaches_the_provider(): void
    {
        // Nothing is reviewed: every non-English list is a draft.
        $this->assertSame([], app(SafeguardingPhraseReviews::class)->reviewedLanguages());
        $this->assertSame([], TutorLanguages::enabled());
        $session = $this->open();
        $messages = [self::DISCLOSURES['af']['self_harm'][0], self::DISCLOSURES['zu']['abuse'][0], self::MIXED[1], self::MIXED[3], self::DISCLOSURES['zu']['sexual_violence'][0]];
        foreach ($messages as $message) {
            $turn = $this->turn($session, $message);
            $this->assertSame(['quarantined', null, null, TutorSessions::SUPPORT_MESSAGE], [$turn['state'], $turn['question'], $turn['response'], $turn['support_message']], $message);
        }
        // Not one request left the server: no moderation, no embedding, no completion.
        $this->assertSame([], $this->sent);
        $usage = AiUsage::where('feature', 'tutor')->get();
        $this->assertCount(count($messages), $usage);
        $this->assertSame(['local-safeguarding-guard'], $usage->pluck('provider')->unique()->values()->all());
        $this->assertSame(['quarantined'], $usage->pluck('state')->unique()->values()->all());
        $this->assertSame(count($messages), AiQuarantineSnapshot::count());

        // Over the API too, for a draft language's disclosure.
        $this->app['auth']->forgetGuards();
        $this->withToken($this->schoolA->tokens['asha'])->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $this->actor('asha')->id]);
        $this->postJson('/api/v2/tutor/sessions/'.$session['id'].'/messages', ['operation_id' => (string) Str::uuid(), 'question' => 'Ngiyahlukunyezwa, ngicela usizo'])
            ->assertOk()->assertJsonPath('data.state', 'quarantined')->assertJsonPath('data.question', null)->assertDontSee('Ngiyahlukunyezwa');
        $this->assertSame([], $this->sent);

        // School B sees none of School A's quarantined turns.
        $this->assertSame([0, 0], app(CurrentSchool::class)->run(2, fn () => [AiUsage::count(), TutorMessage::count()]));
    }

    public function test_a_draft_language_cannot_be_chosen_for_tutoring_until_reviewed(): void
    {
        $session = $this->open();
        // Listed by the deployment but draft: not offered and refused.
        $this->assertSame([], app(TutorQueries::class)->configuration($this->actor('asha'))['home_languages']);
        $this->assertThrows(fn () => $this->turn($session, 'Hoe tel ek breuke bymekaar?', 'af'), ValidationException::class);
        $this->assertThrows(fn () => $this->turn($session, 'Ngicela ungisize ngezibalo', 'zu'), ValidationException::class);
        $this->assertSame(0, TutorMessage::count());

        // A review of an earlier version of the list does not count.
        $this->review('af', 'reviewed', str_repeat('0', 64));
        $this->assertSame([], TutorLanguages::enabled());

        // Reviewed now: Afrikaans is offered; isiZulu stays draft.
        $this->review('af');
        $this->assertSame([['code' => 'af', 'name' => 'Afrikaans']], app(TutorQueries::class)->configuration($this->actor('asha'))['home_languages']);
        $turn = $this->turn($session, 'Hoe tel ek breuke bymekaar?', 'af');
        $this->assertSame(['completed', 'af'], [$turn['state'], $turn['explain_language']]);
        $this->assertThrows(fn () => $this->turn($session, 'Ngicela ungisize ngezibalo', 'zu'), ValidationException::class);
        // A reviewed language's disclosure is still caught before any call.
        $sent = count($this->sent);
        $this->assertSame('quarantined', $this->turn($session, 'Ek wil myself doodmaak', 'af')['state']);
        $this->assertCount($sent, $this->sent);

        // The API offers and refuses the same languages.
        $this->app['auth']->forgetGuards();
        $this->withToken($this->schoolA->tokens['asha'])->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $this->actor('asha')->id]);
        $this->getJson('/api/v2/tutor/configuration')->assertOk()->assertJsonPath('data.home_languages', [['code' => 'af', 'name' => 'Afrikaans']]);
        $path = '/api/v2/tutor/sessions/'.$session['id'].'/messages';
        $this->postJson($path, ['operation_id' => (string) Str::uuid(), 'question' => 'Ngicela usizo', 'language' => 'zu'])->assertUnprocessable();

        // A later review asking for changes makes Afrikaans draft again, at once.
        $this->review('af', 'changes_needed');
        $this->getJson('/api/v2/tutor/configuration')->assertOk()->assertJsonPath('data.home_languages', []);
        $this->postJson($path, ['operation_id' => (string) Str::uuid(), 'question' => 'Hoe deel ek breuke?', 'language' => 'af'])->assertUnprocessable();

        // A deployment that does not list a reviewed language still does not offer it.
        $this->review('zu');
        config(['ai.tutor.home_languages' => ['af']]);
        $this->assertSame([], TutorLanguages::enabled());
    }

    public function test_the_phrases_never_reach_the_provider_or_the_logs(): void
    {
        $logged = [];
        Event::listen(MessageLogged::class, function (MessageLogged $event) use (&$logged) {
            $logged[] = $event->message.' '.json_encode($event->context);
        });
        $this->review('af');
        $this->review('zu');
        $session = $this->open();
        $this->assertSame('completed', $this->turn($session, 'Hoe tel ek breuke bymekaar?', 'af')['state']);
        $this->assertSame('completed', $this->turn($session, 'Ngicela ungisize ngezibalo', 'zu')['state']);
        $this->assertSame('quarantined', $this->turn($session, self::MIXED[0])['state']);
        $this->assertNotEmpty($this->sent);
        $haystack = mb_strtolower(implode("\n", $this->sent)."\n".implode("\n", $logged));
        foreach ($this->allPhrases() as $phrase) {
            $this->assertStringNotContainsString(mb_strtolower($phrase), $haystack, $phrase);
        }
        // Nor do the system prompts mention the lists.
        $this->assertStringNotContainsString('safeguarding/', $haystack);
    }
}
