<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\User;
use App\Services\Platform\Identity\PasswordPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Tests\Support\SignInFixtures;
use Tests\TestCase;

/**
 * Every endpoint where a person chooses a password refuses a weak one and one
 * found in a breach (Have I Been Pwned, faked in TestCase), and accepts a good one.
 */
class PasswordPolicyTest extends TestCase
{
    use RefreshDatabase;
    use SignInFixtures;

    private const BREACHED = 'Breached-pass-2024';

    private const GOOD = 'Harbour-lights-77';

    /** Too short, letters only, numbers only. */
    private const WEAK = ['short1', 'onlyletters-here', '12345678901234'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->breachedPasswords = [self::BREACHED];
        $this->seedLikeInstaller();
        $this->buildSignInSchool();
    }

    public function test_the_rule_is_the_default_password_rule(): void
    {
        $this->assertSame(10, PasswordPolicy::MIN_LENGTH);
        $this->assertEquals(PasswordPolicy::rule(), \Illuminate\Validation\Rules\Password::defaults());
    }

    public function test_the_panel_profile_page(): void
    {
        $teacher = $this->person('teacher');
        $change = fn (string $password) => $this->actingAs($teacher)->postJson('/changePassword', [
            'old_password' => 'secret123', 'new_password' => $password, 'confirm_password' => $password,
        ]);

        foreach (self::WEAK as $password) {
            $change($password)->assertUnprocessable()->assertJsonValidationErrors('new_password');
        }
        $change(self::BREACHED)->assertUnprocessable()->assertJsonValidationErrors(['new_password' => 'data leak']);
        $this->assertTrue(Hash::check('secret123', $teacher->fresh()->password));

        $change(self::GOOD)->assertOk()->assertJson(['error' => false]);
        $this->assertTrue(Hash::check(self::GOOD, $teacher->fresh()->password));
    }

    public function test_the_panel_reset_page(): void
    {
        $teacher = $this->person('teacher');
        $token = Password::broker()->createToken($teacher);
        $reset = fn (string $password) => $this->from('/password/reset/'.$token)->post('/password/reset', [
            'token' => $token, 'email' => $teacher->email, 'password' => $password, 'password_confirmation' => $password,
        ]);

        foreach ([...self::WEAK, self::BREACHED] as $password) {
            $reset($password)->assertSessionHasErrors('password');
            $this->assertTrue(Hash::check('secret123', $teacher->fresh()->password), $password);
        }

        $reset(self::GOOD)->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check(self::GOOD, $teacher->fresh()->password));
    }

    public function test_the_app_change_password_call_for_learners_parents_and_teachers(): void
    {
        foreach (['learner', 'parent', 'teacher'] as $person) {
            $user = $this->person($person);
            $token = $user->createToken('app')->plainTextToken;
            $change = function (string $password) use ($token) {
                $this->app['auth']->forgetGuards();

                return $this->withToken($token)->postJson('/api/change-password', [
                    'current_password' => 'secret123', 'new_password' => $password, 'new_confirm_password' => $password,
                ]);
            };

            foreach ([...self::WEAK, self::BREACHED] as $password) {
                $change($password)->assertOk()->assertJson(['error' => true]);
            }
            $this->assertTrue(Hash::check('secret123', User::find($user->id)->password), $person);

            $change(self::GOOD)->assertOk()->assertJson(['error' => false]);
            $this->assertTrue(Hash::check(self::GOOD, User::find($user->id)->password), $person);
        }
    }

    public function test_the_breach_check_sends_only_a_hash_prefix(): void
    {
        $token = $this->person('teacher')->createToken('app')->plainTextToken;
        $this->withToken($token)->postJson('/api/change-password', [
            'current_password' => 'secret123', 'new_password' => self::GOOD, 'new_confirm_password' => self::GOOD,
        ])->assertJson(['error' => false]);

        $prefix = substr(strtoupper(sha1(self::GOOD)), 0, 5);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.pwnedpasswords.com/range/'.$prefix);
        Http::assertNotSent(fn ($request) => str_contains($request->url().$request->body(), self::GOOD));
    }
}
