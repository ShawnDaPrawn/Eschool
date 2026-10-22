<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Http\Middleware\CheckChild;
use App\Http\Middleware\CheckStudent;
use App\Models\Parents;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\UsesTwoSchools;
use Tests\Support\ApiRoute;
use Tests\TestCase;

/**
 * The tenancy suite (the Build Bible, section 5.2 and section 7): one case
 * per API route, v1 and v2, read from the router, so a new route is covered
 * the moment it exists.
 *
 * Two complete schools (UsesTwoSchools). For every route a School A user of
 * the route's profile asks for School B's records: every id the API reads
 * (class_section_id, child_id, student_id, exam_id and so on) is School B's,
 * and for parent and teacher routes once more with the user's own child or
 * learner and School B's other records. Whatever the route answers, nothing
 * of School B is in it (every School B name, title and sign-in carries the
 * word "Baobab"), and it never fails with a server error. A v2 route asked
 * to work in School B (X-School-Id) by someone with no membership there is
 * refused with 403. v1 refuses the way it always has for a record it cannot
 * find (an error envelope, 4xx or an empty list), so installed apps see no
 * new error codes.
 *
 * Each case also checks the route is guarded: a token (401 without), a role
 * group, and school.context after auth (or school.context:public on the
 * routes anyone may call).
 */
class ApiRouteTenancyTest extends TestCase
{
    use UsesTwoSchools;

    /**
     * Routes anyone may call without signing in, each with its reason. Keep
     * it short: everything else needs a token.
     */
    public const PUBLIC = [
        'POST api/student/login' => 'sign-in',
        'POST api/student/forgot-password' => 'password reset before sign-in',
        'POST api/parent/login' => 'sign-in',
        'POST api/teacher/login' => 'sign-in',
        'POST api/forgot-password' => 'password reset before sign-in',
        'GET api/settings' => 'app settings and versions the apps read before sign-in (no secrets, see StudentApiSecurityTest)',
        'GET api/holidays' => 'public school calendar',
        'GET api/sliders' => 'public home-screen images',
        'GET api/current-session-year' => 'public school year',
        'GET api/get-session-year' => 'public school years',
        'GET api/get-semesters' => 'public terms',
        'GET api/get-events-list' => 'public school events',
        'GET api/get-events-details' => 'public school events',
        'POST api/get-events-details' => 'public school events (older clients)',
        'GET api/get-gallery-albums' => 'public gallery',
        'GET api/get-gallery-images' => 'public gallery',
        'GET api/get-gallery-videos' => 'public gallery',
    ];

    /**
     * Signed-in routes with no role group because every profile uses them.
     * v1 only: v2 routes for every profile sit in the role.any group.
     */
    public const ANY_PROFILE = [
        'POST api/logout' => 'every app signs out (v1)',
        'POST api/change-password' => 'every app changes its own password (v1)',
    ];

    /** Who asks, by the route's role group: a School A person (UsesTwoSchools / DemoSchool). */
    private const ASKER = [
        'role:Student' => 'asha',
        'role:Parent' => 'farid',
        'role:Teacher' => 'tara',
        'role.any' => 'tara',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        // No push, mail or job leaves the test.
        Queue::fake();
        Mail::fake();
    }

    /** @return iterable<string, array{0: string, 1: string}> */
    public static function apiRoutes(): iterable
    {
        foreach (ApiRoute::fromFreshApplication() as $route) {
            yield strtolower($route->label()) => [$route->method, $route->uri];
        }
    }

    #[DataProvider('apiRoutes')]
    public function test_route_is_guarded(string $method, string $uri): void
    {
        $route = $this->route($method, $uri);
        $label = $route->label();

        if (array_key_exists($label, self::PUBLIC)) {
            $this->assertFalse($route->requiresAuthentication(), "$label is on the public list but needs a token: take it off the list");
            $this->assertTrue($this->hasMiddleware($route, ['school.context:public', 'school.context:sign-in']), "$label is public: it needs school.context:public (or :sign-in)");
            $this->assertIsolated($route);

            return;
        }

        $this->assertTrue($route->requiresAuthentication(), "$label needs auth:sanctum, or a place on the public list with a reason");
        $this->json($method, $route->path())->assertStatus(401);

        if (array_key_exists($label, self::ANY_PROFILE)) {
            $this->assertFalse($route->hasRoleGroup(), "$label is on the any-profile list but has a role group: take it off the list");
        } else {
            $this->assertTrue($route->hasRoleGroup(), "$label is in no role group: put it in role:<Role>, staff or role.any");
        }
        $this->assertTrue($this->hasMiddleware($route, ['school.context', 'school.context:optional']), "$label needs school.context after auth:sanctum");
        $this->assertGreaterThan(
            array_search('auth:sanctum', $route->middleware, true),
            $this->firstIndex($route, ['school.context', 'school.context:optional']),
            "$label: school.context must come after auth:sanctum"
        );

        $this->assertIsolated($route);
    }

    public function test_the_lists_only_name_routes_that_exist(): void
    {
        $labels = array_map(fn (ApiRoute $route) => $route->label(), ApiRoute::all($this->app['router']));

        $this->assertSame([], array_values(array_diff(array_keys(self::PUBLIC + self::ANY_PROFILE), $labels)), 'These listed routes no longer exist');
        $this->assertGreaterThan(150, count($labels), 'The router should list every v1 and v2 route');
    }

    /**
     * The check is only as good as the marker: School B's own people see it.
     */
    public function test_school_bs_own_people_see_school_bs_records(): void
    {
        $this->asB('asha')->getJson('/api/student/subjects')->assertOk()->assertSee(self::SCHOOL_B_MARKER);
        $this->asB('tara')->getJson('/api/teacher/classes')->assertOk()->assertSee(self::SCHOOL_B_MARKER);
        $this->asB('farid')->getJson('/api/parent/subjects?child_id='.$this->schoolB->ids['asha'])->assertOk()->assertSee(self::SCHOOL_B_MARKER);
        $this->asB('tara')->getJson('/api/v2/me/context')->assertOk()->assertJsonPath('data.current_school.id', self::SCHOOL_B_ID)
            ->assertSee(self::SCHOOL_B_MARKER);
        // And School A's people see School A's.
        $this->asA('tara')->getJson('/api/teacher/classes')->assertOk()->assertDontSee(self::SCHOOL_B_MARKER);
    }

    /**
     * The installed (v1) apps send no school: School B's people sign in to
     * their own school, from the account the sign-in names.
     */
    public function test_school_bs_people_sign_in_to_their_own_school_on_v1(): void
    {
        $this->flushHeaders();
        $this->postJson('/api/student/login', ['gr_number' => 'GR-BAOBAB-ASHA', 'password' => 'secret123'])
            ->assertOk()->assertJsonPath('error', false)->assertSee(self::SCHOOL_B_MARKER);
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/teacher/login', ['email' => 'tara.baobab@example.com', 'password' => 'secret123'])
            ->assertOk()->assertJsonPath('error', false)->assertSee(self::SCHOOL_B_MARKER);
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/student/login', ['gr_number' => 'GR-ASHA', 'password' => 'secret123'])
            ->assertOk()->assertJsonPath('error', false)->assertDontSee(self::SCHOOL_B_MARKER);
    }

    /**
     * A parent with a child at each school reaches each child only while
     * working in that child's school.
     */
    public function test_a_parent_with_a_child_at_each_school_reaches_each_only_in_its_school(): void
    {
        $farid = User::query()->findOrFail($this->schoolA->ids['farid_user']);
        $childB = $this->schoolB->ids['cara'];
        app(CurrentSchool::class)->run(self::SCHOOL_B_ID, function () use ($farid, $childB) {
            $farid->assignRole('Parent');
            $parent = Parents::query()->forceCreate(['user_id' => $farid->id, 'first_name' => 'Farid', 'last_name' => 'Parent', 'gender' => 'Male', 'email' => $farid->email, 'mobile' => '0123456789']);
            Students::query()->whereKey($childB)->update(['father_id' => $parent->id]);
        });
        // Working in School A, the School B child is not theirs to see.
        $this->assertRefusedWithoutSchoolB($this->asA('farid')->getJson("/api/parent/subjects?child_id=$childB"));
        $this->asA('farid')->getJson('/api/parent/subjects?child_id='.$this->schoolA->ids['asha'])->assertOk()->assertJsonPath('error', false);

        // Working in School B, it is; School A's child is not.
        $this->asA('farid', self::SCHOOL_B_ID)->getJson("/api/parent/subjects?child_id=$childB")
            ->assertOk()->assertJsonPath('error', false)->assertSee(self::SCHOOL_B_MARKER);
        $this->asA('farid', self::SCHOOL_B_ID)->getJson('/api/parent/subjects?child_id='.$this->schoolA->ids['asha'])
            ->assertJsonPath('error', true);
        $this->asA('farid', self::SCHOOL_B_ID)->getJson('/api/v2/me/context')
            ->assertOk()->assertJsonPath('data.current_school.id', self::SCHOOL_B_ID)->assertJsonCount(2, 'data.schools');
    }

    /**
     * A School A user asks the route for School B's records, in every way the
     * route can be asked.
     */
    private function assertIsolated(ApiRoute $route): void
    {
        $label = $route->label();
        $asker = $this->asker($route);
        $own = array_intersect_key($this->schoolA->ids, array_flip(['asha', 'section_a', 'class_a']));

        $variants = ['every id School B\'s' => $this->schoolBParams()];
        if ($this->hasMiddleware($route, [CheckChild::class])) {
            $variants["own child, School B's records"] = ['child_id' => $own['asha']] + $this->schoolBParams();
        }
        if ($this->hasMiddleware($route, [CheckStudent::class])) {
            $variants["own learner, School B's records"] = ['student_id' => $own['asha']] + $this->schoolBParams();
        }

        foreach ($variants as $variant => $params) {
            $response = $this->ask($route, $asker, $params);
            $this->assertLessThan(500, $response->getStatusCode(), "$label ($variant) failed with a server error: ".substr((string) $response->getContent(), 0, 300));
            $this->assertStringNotContainsStringIgnoringCase(self::SCHOOL_B_MARKER, (string) $response->getContent(), "$label ($variant) handed a School A user School B's records");
        }

        if ($route->isV2() && $asker !== null) {
            $this->ask($route, $asker, [], ['X-School-Id' => (string) self::SCHOOL_B_ID])->assertForbidden();
        }
    }

    /** @param array<string, string> $headers */
    private function ask(ApiRoute $route, ?string $asker, array $params, array $headers = []): TestResponse
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $request = $asker === null ? $this : $this->withToken($asker);
        $request = $request->withHeaders($headers);
        $path = $route->path();

        return $route->method === 'GET'
            ? $request->getJson($path.'?'.http_build_query($params))
            : $request->json($route->method, $path, $params);
    }

    /** The School A token of the route's profile; null for the public routes, asked as a guest. */
    private function asker(ApiRoute $route): ?string
    {
        if (array_key_exists($route->label(), self::PUBLIC)) {
            return null;
        }
        $group = $route->roleGroup();
        if ($group === 'staff') {
            return $this->superAdmin()->createToken('tenancy')->plainTextToken;
        }

        return $this->schoolA->tokens[self::ASKER[$group] ?? 'tara'];
    }

    /**
     * Every id the API reads, set to a School B record.
     *
     * @return array<string, int|string>
     */
    private function schoolBParams(): array
    {
        $b = $this->schoolB->ids;

        return [
            'id' => $b['lesson'],
            'child_id' => $b['asha'],
            'student_id' => $b['asha'],
            'user_id' => $b['tara_user'],
            'receiver_id' => $b['tara_user'],
            'class_section_id' => $b['section_a'],
            'class_id' => $b['class_a'],
            'subject_id' => $b['maths'],
            'session_year_id' => $b['session_year'],
            'semester_id' => $b['semester'],
            'lesson_id' => $b['lesson'],
            'topic_id' => $b['topic'],
            'file_id' => $b['lesson_file'],
            'assignment_id' => $b['assignment'],
            'assignment_submission_id' => $b['submission'],
            'exam_id' => $b['exam'],
            'online_exam_id' => $b['online_exam'],
            'timetable_id' => $b['timetable_maths'],
            'leave_id' => $b['leave_to_decide'],
            'announcement_id' => $b['notice'],
            'event_id' => $b['event'],
            'album_id' => $b['album'],
            'fees_paid_id' => $b['fees_paid'],
            'payment_transaction_id' => $b['payment'],
            'transaction_id' => $b['payment'],
        ];
    }

    /** A fresh request as a School B person, working in School B. */
    private function asB(string $person): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($this->schoolB->tokens[$person])->withHeader('X-School-Id', (string) self::SCHOOL_B_ID);
    }

    /** A fresh request as a School A person, working in the school given (else their first). */
    private function asA(string $person, ?int $school = null): self
    {
        $this->app['auth']->forgetGuards();
        $request = $this->flushHeaders()->withToken($this->schoolA->tokens[$person]);

        return $school === null ? $request : $request->withHeader('X-School-Id', (string) $school);
    }

    private function assertRefusedWithoutSchoolB(TestResponse $response): void
    {
        $this->assertTrue($response->getStatusCode() >= 400 || $response->json('error') === true, 'Expected a refusal: '.substr((string) $response->getContent(), 0, 200));
        $this->assertStringNotContainsStringIgnoringCase(self::SCHOOL_B_MARKER, (string) $response->getContent());
    }

    /** @param list<string> $names */
    private function hasMiddleware(ApiRoute $route, array $names): bool
    {
        return $this->firstIndex($route, $names) !== null;
    }

    /** @param list<string> $names */
    private function firstIndex(ApiRoute $route, array $names): ?int
    {
        foreach ($route->middleware as $index => $middleware) {
            if (in_array($middleware, $names, true)) {
                return $index;
            }
        }

        return null;
    }

    private function route(string $method, string $uri): ApiRoute
    {
        foreach (ApiRoute::all($this->app['router']) as $route) {
            if ($route->method === $method && $route->uri === $uri) {
                return $route;
            }
        }

        $this->fail("$method $uri is not a route");
    }
}
