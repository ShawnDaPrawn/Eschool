<?php

declare(strict_types=1);

namespace Tests\Feature\Contracts;

use App\Models\User;
use cebe\openapi\Reader;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\Concerns\UsesDemoSchool;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\Support\ApiRoute;
use Tests\Support\OpenApiSpec;
use Tests\TestCase;

/**
 * openapi.yaml is the API v2 contract (Bible 3.3 and 5.5). This suite fails
 * when a v2 route has no operation in the spec (or the spec describes a route
 * that does not exist), when a v2 route has no scenario here, and when a v2
 * response does not match the spec: every request below goes through
 * ValidatesOpenApiResponses.
 *
 * Adding a v2 endpoint: describe it in openapi.yaml, add its scenarios to
 * scenarios() (each profile that may call it, and the refusals), and
 * regenerate packages/eschool_api (tool/generate.sh).
 */
class OpenApiContractTest extends TestCase
{
    use UsesDemoSchool;
    use ValidatesOpenApiResponses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDemoSchool();
    }

    /**
     * Calls whose responses are checked against the spec: name => [who,
     * method, path, body, expected status]. "who" is a DemoSchool person, or
     * superadmin, staff (a role the school created), norole, or null for no
     * token.
     *
     * @return array<string, array{0: ?string, 1: string, 2: string, 3: array<string, mixed>, 4: int}>
     */
    public static function scenarios(): array
    {
        return [
            'enrolment list wrong role' => ['farid', 'GET', 'api/v2/staff/learners/1/enrolments', [], 403],
            'enrolment write wrong role' => ['farid', 'POST', 'api/v2/staff/learners/1/enrolments', [], 403],
            'unknown invitation' => [null, 'GET', 'api/v2/guardian/invitations/1', [], 404],
            'unknown invitation accept' => [null, 'POST', 'api/v2/guardian/invitations/1/accept', [], 404],
            'invitation issue wrong role' => ['farid', 'POST', 'api/v2/guardians/1/invitations', [], 403],
            'guardian approvals list' => ['farid', 'GET', 'api/v2/guardian/approvals', [], 200],
            'guardian decision wrong role' => ['tara', 'POST', 'api/v2/guardian/approvals/1', ['decision' => 'approve', 'consent_version' => '2026-10-23'], 403],
            'context as a learner' => ['asha', 'GET', 'api/v2/me/context', [], 200],
            'context as a parent of two' => ['farid', 'GET', 'api/v2/me/context', [], 200],
            'context as a guardian' => ['gina', 'GET', 'api/v2/me/context', [], 200],
            'context as a teacher' => ['tara', 'GET', 'api/v2/me/context', [], 200],
            'context as the super admin' => ['superadmin', 'GET', 'api/v2/me/context', [], 200],
            'context as school-created staff' => ['staff', 'GET', 'api/v2/me/context', [], 200],
            'context without a token' => [null, 'GET', 'api/v2/me/context', [], 401],
            'context for an account with no role' => ['norole', 'GET', 'api/v2/me/context', [], 403],
        ];
    }

    #[DataProvider('scenarios')]
    public function test_v2_responses_match_the_spec(?string $who, string $method, string $path, array $body, int $status): void
    {
        if ($who !== null) {
            $this->withToken($this->tokenFor($who));
        }

        // ValidatesOpenApiResponses checks the response against openapi.yaml.
        $this->json($method, '/'.$path, $body)->assertStatus($status);
    }

    public function test_a_rate_limited_answer_matches_the_spec(): void
    {
        $token = $this->tokenFor('tara');
        for ($i = 0; $i < 60; $i++) {
            $this->app['auth']->forgetGuards();
            $this->withToken($token)->getJson('/api/v2/me/context')->assertOk();
        }
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/v2/me/context')->assertStatus(429)->assertHeader('Retry-After');
    }

    public function test_the_spec_is_valid_openapi_3_1(): void
    {
        $spec = Reader::readFromYamlFile(OpenApiSpec::file());

        $this->assertTrue($spec->validate(), implode("\n", $spec->getErrors()));
        $this->assertStringStartsWith('3.1', $spec->openapi);
        $this->assertSame('/'.OpenApiSpec::BASE_PATH, $spec->servers[0]->url);
    }

    public function test_every_v2_route_is_in_the_spec_and_every_operation_is_a_route(): void
    {
        $routes = array_filter(ApiRoute::all($this->app['router']), fn (ApiRoute $route) => $route->isV2());
        $this->assertNotEmpty($routes);

        $undescribed = array_values(array_map(
            fn (ApiRoute $route) => $route->label(),
            array_filter($routes, fn (ApiRoute $route) => ! OpenApiSpec::describes($route->method, $route->uri)),
        ));
        $this->assertSame([], $undescribed, 'These v2 routes have no operation in openapi.yaml; describe them there');

        $served = array_map(fn (ApiRoute $route) => $route->method.' '.OpenApiSpec::pathFor($route->uri), $routes);
        $missing = [];
        foreach (OpenApiSpec::operations() as $path => $methods) {
            foreach ($methods as $method) {
                if (! in_array("$method $path", $served, true)) {
                    $missing[] = "$method $path";
                }
            }
        }
        $this->assertSame([], $missing, 'openapi.yaml describes operations no route serves');
    }

    public function test_every_v2_route_has_a_scenario_here(): void
    {
        $covered = array_map(fn (array $scenario) => $scenario[1].' '.OpenApiSpec::pathFor($scenario[2]), self::scenarios());
        $uncovered = [];
        foreach (ApiRoute::all($this->app['router']) as $route) {
            if ($route->isV2() && ! in_array($route->method.' '.OpenApiSpec::pathFor($route->uri), $covered, true)) {
                $uncovered[] = $route->label();
            }
        }

        $this->assertSame([], $uncovered, 'These v2 routes have no scenario in OpenApiContractTest::scenarios()');
    }

    public function test_the_dart_clients_sample_answer_matches_the_spec(): void
    {
        // packages/eschool_api's tests decode this file; both sides read one shape.
        $fixture = json_decode((string) file_get_contents(base_path('../packages/eschool_api/test/fixtures/me_context_parent.json')), true);

        OpenApiSpec::assertResponseMatches('GET', 'api/v2/me/context', new JsonResponse($fixture));
    }

    public function test_a_response_that_breaks_the_spec_fails(): void
    {
        $context = $this->withToken($this->tokenFor('asha'))->getJson('/api/v2/me/context')->json();

        foreach ([
            'an undocumented field' => fn (array $body) => $body + ['surprise' => 1],
            'a missing field' => function (array $body) {
                unset($body['data']['current_school']['timezone']);

                return $body;
            },
            'a wrong type' => fn (array $body) => array_replace_recursive($body, ['data' => ['user' => ['id' => '12']]]),
        ] as $break => $change) {
            try {
                OpenApiSpec::assertResponseMatches('GET', 'api/v2/me/context', new JsonResponse($change($context)));
            } catch (AssertionFailedError) {
                continue;
            }
            $this->fail("A response with $break passed the contract");
        }

        $this->expectException(AssertionFailedError::class);
        OpenApiSpec::assertResponseMatches('GET', 'api/v2/me/context', new JsonResponse($context, 418));
    }

    private function tokenFor(string $who): string
    {
        $user = match ($who) {
            'superadmin' => $this->superAdmin(),
            'staff' => $this->newUser(Role::firstOrCreate(['name' => 'Office staff', 'guard_name' => 'web'])->name),
            'norole' => $this->newUser(null),
            default => null,
        };

        return $user?->createToken('test')->plainTextToken ?? $this->school->tokens[$who];
    }

    private function newUser(?string $role): User
    {
        $user = User::forceCreate([
            'first_name' => 'Lee', 'last_name' => 'Office', 'email' => 'lee'.uniqid().'@example.com',
            'password' => Hash::make('secret123'), 'gender' => 'Female', 'mobile' => '0123456789',
        ]);
        if ($role !== null) {
            $user->assignRole($role);
        }

        return $user;
    }
}
