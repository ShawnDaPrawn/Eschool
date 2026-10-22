<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\User;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * App versions from before Phase 1 join the shared push topics, which carry
 * the first school's notices: they may sign in only the first school's
 * people (RequireSchoolPushTopics). Apps that join each school's own topics
 * say so with X-Push-Topics: school.
 */
class LegacyPushTopicsSignInTest extends TestCase
{
    use UsesTwoSchools;

    private const SCHOOL_TOPICS = ['X-Push-Topics' => 'school'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
    }

    public function test_an_older_app_still_signs_in_the_first_schools_people(): void
    {
        $this->postJson('/api/teacher/login', ['email' => 'tara@example.com', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonStructure(['token']);
    }

    public function test_an_older_app_cannot_sign_in_another_schools_people(): void
    {
        $teacher = User::query()->withoutGlobalScopes()->where('email', 'tara.baobab@example.com')->firstOrFail();
        $lastToken = (int) $teacher->tokens()->max('id');

        $this->postJson('/api/teacher/login', ['email' => 'tara.baobab@example.com', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonPath('error', true)
            ->assertJsonPath('message', trans('update_app_to_sign_in'))
            ->assertJsonMissingPath('token');

        $this->assertSame(0, $teacher->tokens()->where('id', '>', $lastToken)->count(), 'the token the endpoint issued is revoked');
    }

    public function test_an_app_that_joins_school_topics_signs_in_anyone(): void
    {
        $this->postJson('/api/teacher/login', ['email' => 'tara.baobab@example.com', 'password' => 'secret123'], self::SCHOOL_TOPICS)
            ->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonStructure(['token']);
        $this->postJson('/api/parent/login', ['email' => 'farid.baobab@example.com', 'password' => 'secret123'], self::SCHOOL_TOPICS)
            ->assertOk()
            ->assertJsonPath('error', false);
    }

    public function test_a_wrong_password_is_answered_the_same_either_way(): void
    {
        $older = $this->postJson('/api/teacher/login', ['email' => 'tara.baobab@example.com', 'password' => 'wrong-password-1']);
        $newer = $this->postJson('/api/teacher/login', ['email' => 'tara.baobab@example.com', 'password' => 'wrong-password-1'], self::SCHOOL_TOPICS);

        $older->assertJsonPath('error', true);
        $this->assertSame($newer->json(), $older->json());
    }
}
