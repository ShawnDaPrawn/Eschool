<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * One login, children at two schools (Action Plan 1.1, server side): the
 * parent's app reads each child with X-School-Id set to that child's school.
 * A child is only ever read in their own school; the other school's child is
 * never found, whatever header is sent. engineering/specs/platform/tenancy.md,
 * "A guardian at two schools".
 */
class ParentAcrossSchoolsTest extends TestCase
{
    use UsesTwoSchools;

    /** Farid is Asha's and Cara's father at School A; here he is also a guardian of Cara at School B. */
    private User $farid;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->farid = User::findOrFail($this->schoolA->ids['farid_user']);
        app(CurrentSchool::class)->run(self::SCHOOL_B_ID, function (): void {
            $this->farid->assignRole('Parent');
            SchoolMembership::query()->firstOrCreate(['school_id' => self::SCHOOL_B_ID, 'user_id' => $this->farid->id, 'role' => 'Parent'], ['status' => 'active']);
            GuardianLink::query()->create(['learner_id' => $this->schoolB->ids['cara'], 'guardian_user_id' => $this->farid->id, 'relationship' => 'other', 'can_approve' => true]);
        });
        app(CurrentSchool::class)->forget();
    }

    /** @return list<string> every v2 family read keyed only by the learner, e.g. api/v2/academics/marks/learners/{learner} */
    private function familyReads(): array
    {
        $uris = [];
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (str_starts_with($uri, 'api/v2/') && in_array('GET', $route->methods(), true)
                && str_contains($uri, '{learner}') && preg_match_all('/\{[^}]+\}/', $uri) === 1 && ! str_contains($uri, '/staff/')) {
                $uris[] = $uri;
            }
        }
        sort($uris);

        return $uris;
    }

    /** One request as Farid's app sends it: his token and only the headers given (test headers otherwise persist between calls). */
    private function read(string $uri, int $learner, array $headers = []): TestResponse
    {
        $this->flushHeaders();
        $this->app['auth']->forgetGuards();

        return $this->withToken($this->schoolA->tokens['farid'])->withHeaders($headers)->getJson('/'.str_replace('{learner}', (string) $learner, $uri));
    }

    public function test_the_context_lists_both_schools_and_each_child_with_their_own_school(): void
    {
        $answer = $this->withToken($this->schoolA->tokens['farid'])->getJson('/api/v2/me/context')->assertOk();
        $this->assertEqualsCanonicalizing([1, 2], array_column(array_column($answer->json('data.schools'), 'school'), 'id'));
        $schools = array_column(array_map(fn ($child) => ['id' => $child['id'], 'school' => $child['school']['id']], $answer->json('data.children')), 'school', 'id');
        $this->assertSame(1, $schools[$this->schoolA->ids['asha']]);
        $this->assertSame(2, $schools[$this->schoolB->ids['cara']]);
    }

    public function test_each_child_is_read_in_their_own_school(): void
    {
        $token = $this->schoolA->tokens['farid'];
        $this->withToken($token)->getJson('/api/v2/academics/timetable/week?learner_id='.$this->schoolA->ids['asha'])->assertOk()
            ->assertJsonPath('data.session_year_id', $this->schoolA->ids['session_year'])->assertDontSee(self::SCHOOL_B_MARKER);
        $this->flushHeaders();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->withHeader('X-School-Id', '2')->getJson('/api/v2/academics/timetable/week?learner_id='.$this->schoolB->ids['cara'])
            ->assertOk()->assertJsonPath('data.session_year_id', $this->schoolB->ids['session_year']);
    }

    public function test_a_child_is_never_found_through_the_other_schools_context(): void
    {
        $this->withoutMiddleware([ThrottleRequests::class]);
        $reads = $this->familyReads();
        $this->assertGreaterThan(10, count($reads), 'the family read sweep found too few routes');
        $leaks = [];
        $cases = [
            // School B's Cara, asked for in School A (the default school) and with School A named.
            ['learner' => $this->schoolB->ids['cara'], 'headers' => [], 'marker' => self::SCHOOL_B_MARKER],
            ['learner' => $this->schoolB->ids['cara'], 'headers' => ['X-School-Id' => '1'], 'marker' => self::SCHOOL_B_MARKER],
            // School A's Asha, asked for in School B.
            ['learner' => $this->schoolA->ids['asha'], 'headers' => ['X-School-Id' => '2'], 'marker' => 'GR-ASHA'],
        ];
        foreach ($cases as $case) {
            foreach ($reads as $uri) {
                $response = $this->read($uri, $case['learner'], $case['headers']);
                $status = $response->getStatusCode();
                // 429 would hide an answer, so it counts as a failure too.
                if ($status < 400 || $status === 429 || $status >= 500 || str_contains((string) $response->getContent(), $case['marker'])) {
                    $leaks[] = $status.' '.$uri.' '.json_encode($case['headers']);
                }
            }
        }
        $this->assertSame([], $leaks);

    }

    /**
     * Same-school controls: each of these family reads answers 200 for each
     * child in that child's own school, so its refusal above is the school
     * check, not a read that refuses every parent. The other reads in the
     * sweep need module data the demo schools don't have (or are staff-only);
     * for them the sweep proves only that they never answer across schools.
     */
    #[DataProvider('answeredReads')]
    public function test_a_family_read_answers_for_each_child_in_their_own_school(string $uri): void
    {
        $this->withoutMiddleware([ThrottleRequests::class]);
        $this->assertContains($uri, $this->familyReads(), 'the control route is no longer a family read');
        $this->read($uri, $this->schoolA->ids['asha'])->assertOk()->assertDontSee(self::SCHOOL_B_MARKER);
        $this->read($uri, $this->schoolB->ids['cara'], ['X-School-Id' => '2'])->assertOk()->assertDontSee('GR-ASHA');
        // And each is refused for the other school's child in the same contexts.
        $this->assertGreaterThanOrEqual(400, $this->read($uri, $this->schoolB->ids['cara'])->getStatusCode());
        $this->assertGreaterThanOrEqual(400, $this->read($uri, $this->schoolA->ids['asha'], ['X-School-Id' => '2'])->getStatusCode());
    }

    /** @return array<string, array{string}> */
    public static function answeredReads(): array
    {
        $uris = ['api/v2/academics/content/learners/{learner}', 'api/v2/academics/marks/learners/{learner}/periods',
            'api/v2/academics/registers/learners/{learner}/qr', 'api/v2/academics/registers/learners/{learner}/today',
            'api/v2/academics/reports/learners/{learner}'];

        return array_combine($uris, array_map(fn ($uri) => [$uri], $uris));
    }

    public function test_a_school_the_parent_does_not_belong_to_is_refused(): void
    {
        $this->withToken($this->schoolA->tokens['farid'])->withHeader('X-School-Id', '3')->getJson('/api/v2/me/context')->assertForbidden();
        $this->withToken($this->schoolA->tokens['farid'])->withHeader('X-School-Id', '3')
            ->getJson('/api/v2/academics/timetable/week?learner_id='.$this->schoolB->ids['cara'])->assertForbidden();
    }

    public function test_another_parent_at_school_a_never_reaches_school_bs_child(): void
    {
        // Mona is Asha's mother at School A only.
        $token = $this->schoolA->tokens['mona'];
        $this->withToken($token)->withHeader('X-School-Id', '2')->getJson('/api/v2/me/context')->assertForbidden();
        $this->flushHeaders();
        $this->app['auth']->forgetGuards();
        $response = $this->withToken($token)->getJson('/api/v2/academics/timetable/week?learner_id='.$this->schoolB->ids['cara'])->assertDontSee(self::SCHOOL_B_MARKER);
        $this->assertTrue($response->getStatusCode() >= 400 && $response->getStatusCode() < 500, 'status '.$response->getStatusCode());
    }
}
