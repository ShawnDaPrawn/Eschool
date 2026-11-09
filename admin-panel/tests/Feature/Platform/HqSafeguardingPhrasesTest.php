<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\AuditLog;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\User;
use App\Services\Learning\SafeguardingPhraseReviews;
use App\Services\Learning\SafeguardingPhrases;
use App\Services\Learning\TutorLanguages;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\PassesPlatformTwoFactor;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * HQ's view of the safeguarding phrase lists (Action Plan 3.2): staff holding
 * safeguarding.phrases see each language's review state and its phrases,
 * read-only, and record a professional review, audited without any phrase.
 * engineering/specs/ai/safeguarding-phrases.md.
 */
class HqSafeguardingPhrasesTest extends TestCase
{
    use PassesPlatformTwoFactor;
    use UsesTwoSchools;

    private const REVIEW = ['outcome' => 'reviewed', 'reviewer' => 'Synthetic Reviewer, registered social worker (synthetic)', 'reviewed_on' => '2026-11-02',
        'note' => 'Synthetic note: checked with a fluent speaker; no changes needed.'];

    protected function setUp(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-08 10:00', 'Africa/Johannesburg'));
        parent::setUp();
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->forget();
        config(['ai.tutor.home_languages' => ['af', 'zu']]);
    }

    private function staff(string $role): User
    {
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => ucfirst($role), 'last_name' => 'Operator', 'email' => $role.'@platform.example', 'mobile' => '', 'password' => 'Synthetic-phrases-password-731!', 'status' => 1]));
        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => $role, 'expires_at' => now()->addHour()]);

        return $user;
    }

    /** The review form as the page sends it: with the fingerprint of the list it displayed. */
    private function form(string $language): array
    {
        return self::REVIEW + ['list_sha256' => app(SafeguardingPhrases::class)->sha256($language)];
    }

    /** @return list<string> every phrase of every list, without its wildcards */
    private function phrases(): array
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

    public function test_content_staff_see_each_state_and_record_an_audited_review_that_offers_the_language(): void
    {
        $content = $this->staff('content');
        $this->actingAsHq($content);
        $page = $this->get('/saas/safeguarding-phrases')->assertOk()->assertSee('Safeguarding phrase lists')
            ->assertSee('Not offered in the tutor until its list is reviewed')->assertSee('No review recorded yet')
            // The phrases are shown read-only, for the reviewers.
            ->assertSee('selfmoord pleeg')->assertSee('*zibulala*')->assertSee('kill myself');
        $this->assertSame(['draft', 'draft', 'draft'], array_column($page->viewData('languages'), 'state'));
        $this->get('/saas/home')->assertOk()->assertSee('Safeguarding phrases');
        $this->assertSame([], TutorLanguages::enabled());

        $this->post('/saas/safeguarding-phrases/af/reviews', $this->form('af'))->assertRedirect('/saas/safeguarding-phrases')->assertSessionHasNoErrors();
        $row = DB::table('safeguarding_phrase_reviews')->sole();
        $this->assertSame(['af', 'reviewed', app(SafeguardingPhrases::class)->sha256('af'), self::REVIEW['reviewer'], '2026-11-02', (int) $content->id],
            [$row->language, $row->outcome, $row->list_sha256, $row->reviewer, substr((string) $row->reviewed_on, 0, 10), (int) $row->recorded_by]);
        $this->assertSame(['af' => 'Afrikaans'], app(CurrentSchool::class)->run(1, fn () => TutorLanguages::enabled()));
        // The decision is the platform's: the same for every school.
        $this->assertSame(['af' => 'Afrikaans'], app(CurrentSchool::class)->run(2, fn () => TutorLanguages::enabled()));

        $audit = AuditLog::query()->where('action', 'platform_safeguarding_phrase_review_recorded')->sole();
        $this->assertNull($audit->school_id);
        $this->assertSame(['language' => 'af', 'state' => 'draft'], $audit->before);
        $this->assertSame(['af', 'reviewed', self::REVIEW['reviewer'], '2026-11-02'], [$audit->after['language'], $audit->after['state'], $audit->after['reviewer'], $audit->after['reviewed_on']]);
        $stored = mb_strtolower(json_encode([$audit->before, $audit->after], JSON_UNESCAPED_UNICODE).json_encode(DB::table('safeguarding_phrase_reviews')->get(), JSON_UNESCAPED_UNICODE));
        foreach ($this->phrases() as $phrase) {
            $this->assertStringNotContainsString(mb_strtolower($phrase), $stored, $phrase);
        }

        $page = $this->get('/saas/safeguarding-phrases')->assertOk()->assertSee('Offered in the tutor as a home language.')->assertSee(self::REVIEW['note']);
        $this->assertSame(['draft', 'reviewed', 'draft'], array_column($page->viewData('languages'), 'state'));
        $this->get('/saas/audit')->assertForbidden();

        // A later review asking for changes puts it back to draft; the history keeps both.
        $this->post('/saas/safeguarding-phrases/af/reviews', ['outcome' => 'changes_needed'] + $this->form('af'))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame([], TutorLanguages::enabled());
        $page = $this->get('/saas/safeguarding-phrases')->assertOk();
        $this->assertSame(['draft', 'draft', 'draft'], array_column($page->viewData('languages'), 'state'));
        $this->assertCount(2, $page->viewData('languages')[1]['history']);
        $latest = AuditLog::query()->where('action', 'platform_safeguarding_phrase_review_recorded')->orderByDesc('id')->firstOrFail();
        $this->assertSame([['language' => 'af', 'state' => 'reviewed'], 'draft', 'changes_needed'], [$latest->before, $latest->after['state'], $latest->after['outcome']]);
    }

    public function test_an_owner_records_reviews_and_english_review_never_switches_the_tutor(): void
    {
        $this->actingAsHq($this->staff('owner'));
        $this->post('/saas/safeguarding-phrases/en/reviews', $this->form('en'))->assertRedirect()->assertSessionHasNoErrors();
        $this->post('/saas/safeguarding-phrases/zu/reviews', $this->form('zu'))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(['en', 'zu'], app(SafeguardingPhraseReviews::class)->reviewedLanguages());
        $this->assertSame(['zu' => 'isiZulu'], TutorLanguages::enabled());
        $this->get('/saas/safeguarding-phrases')->assertOk()->assertSee('English is the tutor')->assertSee('Audit log');
        $this->get('/saas/audit')->assertOk()->assertSee('Safeguarding phrase list review recorded');
    }

    public function test_wrong_roles_and_school_accounts_cannot_see_or_record_a_review(): void
    {
        $staff = [];
        foreach (['operations', 'support', 'finance', 'reviewer', 'sales'] as $role) {
            $this->actingAsHq($staff[$role] = $this->staff($role));
            $this->get('/saas/safeguarding-phrases')->assertForbidden();
            $this->post('/saas/safeguarding-phrases/af/reviews', $this->form('af'))->assertForbidden();
            $this->get('/saas/home')->assertOk()->assertDontSee('Safeguarding phrases');
        }
        // The service refuses on its own too.
        try {
            app(SafeguardingPhraseReviews::class)->record($staff['reviewer'], 'af', $this->form('af'));
            $this->fail('Expected a refusal.');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
        }
        // School accounts, from either school, never reach HQ.
        $this->newBrowserSession();
        $this->actingAs($this->superAdmin());
        $this->get('/saas/safeguarding-phrases')->assertForbidden();
        $this->post('/saas/safeguarding-phrases/af/reviews', $this->form('af'))->assertForbidden();
        $this->actingAs(app(CurrentSchool::class)->run(2, fn () => User::query()->findOrFail($this->schoolB->ids['tara_user'])));
        $this->withHeader('X-School-Id', '2')->post('/saas/safeguarding-phrases/zu/reviews', $this->form('zu'))->assertForbidden();
        $this->assertSame(0, DB::table('safeguarding_phrase_reviews')->count());
        $this->assertSame(0, AuditLog::query()->where('action', 'platform_safeguarding_phrase_review_recorded')->count());
        $this->assertSame([], TutorLanguages::enabled());
    }

    public function test_a_review_needs_who_when_and_a_note_and_the_phrases_cannot_be_changed_in_hq(): void
    {
        $this->actingAsHq($this->staff('content'));
        foreach ([['outcome' => 'approved'], ['reviewer' => ''], ['reviewed_on' => '2026-11-20'], ['reviewed_on' => '02/11/2026'], ['note' => 'short']] as $bad) {
            $this->from('/saas/safeguarding-phrases')->post('/saas/safeguarding-phrases/af/reviews', $bad + $this->form('af'))
                ->assertRedirect('/saas/safeguarding-phrases')->assertSessionHasErrors(array_keys($bad)[0]);
        }
        // Only the platform languages have lists.
        $this->post('/saas/safeguarding-phrases/fr/reviews', $this->form('af'))->assertNotFound();
        $this->assertSame(0, DB::table('safeguarding_phrase_reviews')->count());
        // No HQ route writes a phrase: only the page and the review form exist.
        $routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($route) => str_contains($route->uri(), 'safeguarding-phrases'));
        $this->assertSame(['GET|HEAD saas/safeguarding-phrases', 'POST saas/safeguarding-phrases/{language}/reviews'],
            $routes->map(fn ($route) => implode('|', $route->methods()).' '.$route->uri())->sort()->values()->all());
        $this->put('/saas/safeguarding-phrases', ['categories' => ['self_harm' => ['anything']]])->assertStatus(405);
        $before = app(SafeguardingPhrases::class)->list('af');
        $this->post('/saas/safeguarding-phrases/af/reviews', $this->form('af') + ['categories' => ['self_harm' => ['anything']]])->assertRedirect();
        $this->assertSame($before, app(SafeguardingPhrases::class)->list('af'));
    }

    public function test_a_review_covers_only_the_list_the_reviewer_was_shown(): void
    {
        $content = $this->staff('content');
        $this->actingAsHq($content);
        $current = app(SafeguardingPhrases::class)->sha256('af');
        // The form carries the fingerprint of the list it displays.
        $this->get('/saas/safeguarding-phrases')->assertOk()->assertSee('name="list_sha256" value="'.$current.'"', false);

        // A list deployed while the page was open: the old fingerprint is refused and nothing is recorded or audited.
        $stale = str_repeat('a', 64);
        $this->from('/saas/safeguarding-phrases')->post('/saas/safeguarding-phrases/af/reviews', ['list_sha256' => $stale] + self::REVIEW)
            ->assertRedirect('/saas/safeguarding-phrases')->assertSessionHasErrors(['list_sha256' => 'This list has changed since the page was opened. Open the page again, read the current list and review it again.']);
        // Without a fingerprint, or a malformed one, it is refused too.
        $this->from('/saas/safeguarding-phrases')->post('/saas/safeguarding-phrases/af/reviews', self::REVIEW)->assertSessionHasErrors('list_sha256');
        $this->from('/saas/safeguarding-phrases')->post('/saas/safeguarding-phrases/af/reviews', ['list_sha256' => strtoupper($current)] + self::REVIEW)->assertSessionHasErrors('list_sha256');
        try {
            app(SafeguardingPhraseReviews::class)->record($content, 'af', ['list_sha256' => app(SafeguardingPhrases::class)->sha256('zu')] + self::REVIEW);
            $this->fail('Expected a validation error.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('list_sha256', $error->errors());
        }
        $this->assertSame(0, DB::table('safeguarding_phrase_reviews')->count());
        $this->assertSame(0, AuditLog::query()->where('action', 'platform_safeguarding_phrase_review_recorded')->count());
        $this->assertSame([], TutorLanguages::enabled());

        // The fingerprint of the list as it is now is recorded as before.
        $this->post('/saas/safeguarding-phrases/af/reviews', ['list_sha256' => $current] + self::REVIEW)->assertRedirect('/saas/safeguarding-phrases')->assertSessionHasNoErrors();
        $this->assertSame([$current, 'reviewed'], [DB::table('safeguarding_phrase_reviews')->sole()->list_sha256, DB::table('safeguarding_phrase_reviews')->sole()->outcome]);
        $this->assertSame($current, AuditLog::query()->where('action', 'platform_safeguarding_phrase_review_recorded')->sole()->after['list_sha256']);
        $this->assertSame(['af' => 'Afrikaans'], TutorLanguages::enabled());
    }
}
