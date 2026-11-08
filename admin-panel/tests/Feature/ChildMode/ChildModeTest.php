<?php

declare(strict_types=1);

namespace Tests\Feature\ChildMode;

use App\Models\Academics\GradeLevel;
use App\Models\ChildMode\ChildModePin;
use App\Models\ChildMode\ChildModeSession;
use App\Models\ClassSchool;
use App\Models\Platform\GuardianLink;
use App\Models\Students;
use App\Models\User;
use App\Services\ChildMode\ChildModeSessions;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Identity\GuardianLinks;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Stages\SchoolStages;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

/**
 * Child mode in the parent app (Action Plan 4.2): the PIN, the child token,
 * what a child may open by grade and stage, revocation, and tenancy.
 * engineering/specs/platform/child-mode.md.
 */
class ChildModeTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private const PIN = '2580';

    private const NEW_PIN = '7193';

    protected function setUp(): void
    {
        parent::setUp();
        // A fixed school-day morning, before the fixtures, so memberships are current.
        $this->travelTo(Carbon::parse('2027-05-10 09:00:00', 'UTC'));
        $this->setUpTwoSchools();
        DB::table('users')->whereIn('id', [$this->schoolA->ids['farid_user'], $this->schoolA->ids['mona_user'], $this->schoolA->ids['gina_user']])->update(['email_verified_at' => now()]);
        foreach (['asha', 'ben', 'cara'] as $learner) {
            app(GuardianService::class)->syncLegacy(Students::query()->findOrFail($this->schoolA->ids[$learner]));
        }
        $this->grade('class_a', '5');
        $this->grade('class_c', '6');
        $this->approve('farid', 'asha');
        $this->approve('gina', 'ben');
    }

    private function grade(string $class, ?string $code): void
    {
        $grade = $code === null ? null : (int) GradeLevel::query()->where('code', $code)->value('id');
        ClassSchool::query()->whereKey($this->schoolA->ids[$class])->update(['grade_level_id' => $grade]);
    }

    private function user(string $who): User
    {
        return User::query()->findOrFail($this->schoolA->ids[$who.'_user']);
    }

    private function learner(string $who): Students
    {
        return Students::query()->findOrFail($this->schoolA->ids[$who]);
    }

    private function approve(string $guardian, string $learner): void
    {
        app(ApprovalService::class)->decide($this->user($guardian), $this->learner($learner), 'approve', (string) config('identity.consent_version'));
    }

    private function modules(string ...$keys): void
    {
        foreach ($keys as $key) {
            app(ModuleGate::class)->change($key, true, $this->superAdmin());
        }
    }

    /** A parent-app sign-in, as the parent app gets one. */
    private function parentToken(string $who): string
    {
        return AppTokens::issue($this->user($who), AppTokens::PARENT);
    }

    private function api(?string $token, array $headers = ['X-School-Id' => '1']): self
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        if ($token !== null) {
            $this->withToken($token);
        }

        return $this->withHeaders($headers);
    }

    private function base(string $learner): string
    {
        return '/api/v2/child-mode/children/'.$this->schoolA->ids[$learner];
    }

    private function setPin(string $token, string $learner = 'asha', string $pin = self::PIN): TestResponse
    {
        return $this->api($token)->putJson($this->base($learner).'/pin', ['pin' => $pin, 'pin_confirmation' => $pin]);
    }

    private function start(string $token, string $learner = 'asha', string $pin = self::PIN): TestResponse
    {
        return $this->api($token)->postJson($this->base($learner).'/sessions', ['pin' => $pin]);
    }

    /** @return array{0: string, 1: string} a parent-app token and a child token for Asha */
    private function childSession(string $guardian = 'farid'): array
    {
        $parent = $this->parentToken($guardian);
        if (! ChildModePin::query()->where('learner_id', $this->schoolA->ids['asha'])->exists()) {
            $this->setPin($parent)->assertOk();
        }
        $child = $this->start($parent)->assertOk()->json('data.token');
        $this->assertIsString($child);

        return [$parent, $child];
    }

    private function childToken(string $plain): ?PersonalAccessToken
    {
        return PersonalAccessToken::findToken($plain);
    }

    public function test_an_approving_guardian_sets_a_pin_and_starts_a_short_child_session_that_opens_only_the_childs_things(): void
    {
        $this->modules('daily_practice', 'tutor', 'reading');
        $parent = $this->parentToken('farid');
        $this->api($parent)->getJson($this->base('asha'))->assertOk()
            ->assertJsonPath('data.available', true)->assertJsonPath('data.pin_set', false)
            ->assertJsonPath('data.opens', ['my_day', 'practice', 'tutor', 'library'])->assertJsonPath('data.session_minutes', 30);

        $this->setPin($parent)->assertOk()->assertJsonPath('message', 'Child mode PIN set.')->assertJsonPath('data.pin_set', true);
        $pin = ChildModePin::query()->where('learner_id', $this->schoolA->ids['asha'])->sole();
        $this->assertNotSame(self::PIN, $pin->pin_hash);
        $this->assertTrue(Hash::check(self::PIN, $pin->pin_hash));
        $this->assertSame(1, $pin->version);

        $response = $this->start($parent)->assertOk()->assertJsonPath('data.session.first_name', 'Asha')
            ->assertJsonPath('data.session.school.id', 1)->assertJsonPath('data.session.opens', ['my_day', 'practice', 'tutor', 'library'])
            ->assertJsonPath('data.session.expires_at', now()->addMinutes(30)->toIso8601String());
        $child = $this->childToken($response->json('data.token'));
        $this->assertSame(AppTokens::CHILD, $child->name);
        $this->assertSame((int) $this->schoolA->ids['farid_user'], (int) $child->tokenable_id);
        $this->assertSame(['child-mode:school:1', 'child-mode:learner:'.$this->schoolA->ids['asha']], $child->abilities);
        $this->assertTrue($child->expires_at->equalTo(now()->addMinutes(30)));

        $this->api($response->json('data.token'), [])->getJson('/api/v2/child-mode/session')->assertOk()
            ->assertJsonPath('data.learner_id', $this->schoolA->ids['asha'])->assertJsonPath('data.opens', ['my_day', 'practice', 'tutor', 'library'])
            ->assertDontSee(self::SCHOOL_B_MARKER)->assertHeader('Cache-Control', 'no-store, private');

        // Leaving child mode needs the PIN; the token is gone at once.
        $this->api($response->json('data.token'), [])->postJson('/api/v2/child-mode/session/end', ['pin' => self::NEW_PIN])->assertUnprocessable();
        $this->api($response->json('data.token'), [])->postJson('/api/v2/child-mode/session/end', ['pin' => self::PIN])->assertOk();
        $this->assertNull(PersonalAccessToken::query()->find($child->id));
        $this->api($response->json('data.token'), [])->getJson('/api/v2/child-mode/session')->assertUnauthorized();
        $session = ChildModeSession::query()->sole();
        $this->assertSame(ChildModeSessions::ENDED_WITH_PIN, $session->end_reason);
        $this->assertNotNull($session->ended_at);
        // The parent-app sign-in is untouched.
        $this->api($parent)->getJson($this->base('asha'))->assertOk();

        // The audit trail holds ids and times, never the PIN, its hash or the token.
        $trail = DB::table('audit_log')->where('action', 'like', 'child_mode.%')->orderBy('id')->get();
        $this->assertSame(['child_mode.pin_set', 'child_mode.session_started', 'child_mode.session_ended'], $trail->pluck('action')->values()->all());
        foreach ($trail as $row) {
            $text = (string) $row->before.(string) $row->after;
            $this->assertStringNotContainsString(self::PIN, $text);
            $this->assertStringNotContainsString($pin->pin_hash, $text);
            $this->assertStringNotContainsString('token', strtolower($text));
        }
        $this->assertSame(['id', 'school_id', 'learner_id', 'guardian_user_id', 'token_id', 'issued_from_token_id', 'pin_version', 'started_at', 'expires_at', 'ended_at', 'end_reason', 'ended_by', 'created_at', 'updated_at'],
            array_keys((array) DB::table('child_mode_sessions')->first()));
    }

    public function test_tutor_opens_from_grade_four_practice_from_grade_three_and_switched_off_modules_stay_shut(): void
    {
        $this->modules('daily_practice', 'tutor', 'reading');
        $parent = $this->parentToken('farid');
        $opens = fn () => $this->api($parent)->getJson($this->base('asha'))->assertOk()->json('data.opens');

        $this->grade('class_a', '3');
        $this->assertSame(['my_day', 'practice', 'library'], $opens());
        $this->grade('class_a', '4');
        $this->assertSame(['my_day', 'practice', 'tutor', 'library'], $opens());
        $this->grade('class_a', '7');
        $this->assertSame(['my_day', 'practice', 'tutor', 'library'], $opens());

        app(ModuleGate::class)->change('tutor', false, $this->superAdmin());
        app(ModuleGate::class)->change('reading', false, $this->superAdmin());
        $this->assertSame(['my_day', 'practice'], $opens());

        // A school that moves Grade 2 into the junior stage gets child mode there, without practice or the tutor.
        $this->grade('class_a', '2');
        $this->assertSame('early_years', $this->api($parent)->getJson($this->base('asha'))->json('data.reason'));
        DB::table('school_stages')->updateOrInsert(['school_id' => 1, 'grade_level_id' => GradeLevel::query()->where('code', '2')->value('id')], ['stage' => SchoolStages::JUNIOR, 'created_at' => now(), 'updated_at' => now()]);
        $this->assertSame(['my_day'], $opens());
    }

    public function test_early_years_high_school_and_an_ungraded_homeroom_have_no_child_mode(): void
    {
        [, $child] = $this->childSession();
        $parent = $this->parentToken('farid');
        foreach (['R' => 'early_years', '2' => 'early_years', '8' => 'high_school', null => 'grade_unknown'] as $code => $reason) {
            $this->grade('class_a', $code === '' ? null : (string) $code);
            $this->api($parent)->getJson($this->base('asha'))->assertOk()->assertJsonPath('data.available', false)
                ->assertJsonPath('data.reason', $reason)->assertJsonPath('data.opens', []);
            $this->setPin($parent, pin: self::NEW_PIN)->assertForbidden();
            $this->start($parent)->assertForbidden();
        }
        $this->grade('class_a', 'R');
        $this->api($parent)->getJson($this->base('asha'))->assertJsonPath('data.message', 'Child mode is not used in the early years. You can follow your child\'s learning here in the parents\' app.');
        // A session already running ends on its next request once the child's stage no longer allows it.
        $this->api($child, [])->getJson('/api/v2/child-mode/session')->assertUnauthorized();
        $this->assertNull($this->childToken($child));
        $this->assertSame('no_longer_allowed:early_years', ChildModeSession::query()->sole()->end_reason);
    }

    public function test_wrong_pins_are_counted_per_child_and_lock_child_mode_until_a_guardian_sets_a_new_pin(): void
    {
        $this->setPin($this->parentToken('farid'))->assertOk();
        $farid = $this->parentToken('farid');
        // Mona is Asha's other approving guardian: her tries count towards the same lock.
        $this->approve('mona', 'asha');
        DB::table('users')->where('id', $this->schoolA->ids['mona_user'])->update(['email_verified_at' => now()]);
        $mona = $this->parentToken('mona');
        foreach ([[$farid, 4], [$mona, 3], [$farid, 2], [$mona, 1]] as [$token, $left]) {
            $this->start($token, pin: '1357')->assertUnprocessable()->assertJsonPath('data.errors.pin.0', "That PIN is not right. You have $left more tries.");
        }
        $this->start($farid, pin: '1357')->assertStatus(429)->assertHeader('Retry-After', '900');
        // Locked: even the right PIN waits.
        $this->start($mona)->assertStatus(429);
        $this->api($farid)->getJson($this->base('asha'))->assertJsonPath('data.pin_locked_until', now()->addMinutes(15)->toIso8601String());
        $this->assertTrue(DB::table('audit_log')->where('action', 'child_mode.pin_locked')->exists());
        $this->assertSame(0, ChildModeSession::query()->count());

        $this->travel(16)->minutes();
        $this->start($farid)->assertOk();

        // A new PIN clears a lock straight away.
        foreach (range(1, 5) as $try) {
            $this->start($farid, pin: '1357');
        }
        $this->start($farid)->assertStatus(429);
        $this->setPin($mona, pin: self::NEW_PIN)->assertOk()->assertJsonPath('message', 'Child mode PIN changed. Child mode has ended on every device.');
        $this->start($farid, pin: self::NEW_PIN)->assertOk();
    }

    public function test_weak_or_mistyped_pins_are_refused_when_set(): void
    {
        $parent = $this->parentToken('farid');
        foreach (['1111', '1234', '987654', '123', '1234567', 'abcd'] as $pin) {
            $this->setPin($parent, pin: $pin)->assertUnprocessable()->assertJsonValidationErrors('pin', 'data.errors');
        }
        $this->api($parent)->putJson($this->base('asha').'/pin', ['pin' => self::PIN, 'pin_confirmation' => self::NEW_PIN])->assertUnprocessable()
            ->assertJsonPath('data.errors.pin.0', 'The two PINs do not match.');
        $this->start($parent)->assertUnprocessable()->assertJsonPath('data.errors.pin.0', 'Set a child mode PIN first.');
        $this->assertSame(0, ChildModePin::query()->count());
    }

    public function test_a_child_token_expires_after_its_short_lifetime(): void
    {
        [, $child] = $this->childSession();
        $this->travel(29)->minutes();
        $this->api($child, [])->getJson('/api/v2/child-mode/session')->assertOk();
        $this->travel(2)->minutes();
        $this->api($child, [])->getJson('/api/v2/child-mode/session')->assertUnauthorized();
        // When anything later ends it, it is recorded as expired at its expiry.
        ChildModeSessions::revokeForLearner($this->schoolA->ids['asha'], ChildModeSessions::PIN_CHANGED);
        $session = ChildModeSession::query()->sole();
        $this->assertSame(ChildModeSessions::EXPIRED, $session->end_reason);
        $this->assertTrue($session->ended_at->equalTo($session->expires_at));
    }

    public function test_one_child_session_per_parent_sign_in_and_the_guardian_can_end_theirs_anywhere(): void
    {
        [$parent, $first] = $this->childSession();
        $second = $this->start($parent)->assertOk()->json('data.token');
        $this->api($first, [])->getJson('/api/v2/child-mode/session')->assertUnauthorized();
        $this->assertSame(ChildModeSessions::REPLACED, ChildModeSession::query()->orderBy('id')->first()->end_reason);

        // From another device, the guardian ends child mode without the PIN.
        $other = $this->parentToken('farid');
        $this->api($other)->postJson($this->base('asha').'/sessions/end')->assertOk()->assertJsonPath('data.ended', 1);
        $this->api($second, [])->getJson('/api/v2/child-mode/session')->assertUnauthorized();
        $this->assertSame(ChildModeSessions::ENDED_BY_GUARDIAN, ChildModeSession::query()->orderByDesc('id')->first()->end_reason);
    }

    /** @return array<string, array{0: string}> */
    public static function revocations(): array
    {
        return [
            'the guardian is removed' => [ChildModeSessions::GUARDIAN_REMOVED],
            'the guardian loses the approval right' => [ChildModeSessions::APPROVAL_RIGHT_REMOVED],
            'the child\'s approval is withdrawn' => [ChildModeSessions::APPROVAL_WITHDRAWN],
            'another guardian changes the PIN' => [ChildModeSessions::PIN_CHANGED],
            'the guardian signs out of that device' => [ChildModeSessions::PARENT_SIGNED_OUT],
            'the guardian signs out everywhere' => [ChildModeSessions::SIGNED_OUT_EVERYWHERE],
        ];
    }

    #[DataProvider('revocations')]
    public function test_child_tokens_are_revoked_immediately(string $reason): void
    {
        $this->approve('mona', 'asha');
        DB::table('users')->where('id', $this->schoolA->ids['mona_user'])->update(['email_verified_at' => now()]);
        [$parent, $child] = $this->childSession();
        $token = $this->childToken($child);
        $this->api($child, [])->getJson('/api/v2/child-mode/session')->assertOk();
        $link = GuardianLink::query()->where('learner_id', $this->schoolA->ids['asha'])->where('guardian_user_id', $this->schoolA->ids['farid_user'])->sole();
        $admin = $this->superAdmin();

        match ($reason) {
            ChildModeSessions::GUARDIAN_REMOVED => app(GuardianLinks::class)->end($admin, (int) $link->id, ['reason' => 'Synthetic removal']),
            ChildModeSessions::APPROVAL_RIGHT_REMOVED => app(GuardianLinks::class)->update($admin, (int) $link->id, ['relationship' => 'father', 'can_approve' => false, 'can_see_fees' => true]),
            ChildModeSessions::APPROVAL_WITHDRAWN => app(ApprovalService::class)->decide($this->user('mona'), $this->learner('asha'), 'withdraw', (string) config('identity.consent_version')),
            ChildModeSessions::PIN_CHANGED => $this->setPin($this->parentToken('mona'), pin: self::NEW_PIN)->assertOk(),
            ChildModeSessions::PARENT_SIGNED_OUT => $this->api($parent)->postJson('/api/logout')->assertOk(),
            ChildModeSessions::SIGNED_OUT_EVERYWHERE => $this->api($this->parentToken('farid'))->postJson('/api/change-password', ['current_password' => 'secret123', 'new_password' => 'Changed-Pass-2027!', 'new_confirm_password' => 'Changed-Pass-2027!'])->assertOk(),
        };

        // The token is deleted straight away, not left to expire.
        $this->assertNull(PersonalAccessToken::query()->find($token->id), $reason);
        $session = ChildModeSession::query()->sole();
        $this->assertSame($reason, $session->end_reason);
        $this->assertNotNull($session->ended_at);
        $this->api($child, [])->getJson('/api/v2/child-mode/session')->assertUnauthorized();
        $this->assertTrue(DB::table('audit_log')->where('action', 'child_mode.session_ended')->where('subject_id', $session->id)->exists());
    }

    public function test_a_session_whose_parent_sign_in_was_pruned_ends_on_its_next_request(): void
    {
        [$parent, $child] = $this->childSession();
        // The parent app signs this device out by deleting its token (for example a sixth device).
        PersonalAccessToken::findToken($parent)?->delete();
        $this->api($child, [])->getJson('/api/v2/child-mode/session')->assertUnauthorized();
        $this->assertSame(ChildModeSessions::PARENT_SIGNED_OUT, ChildModeSession::query()->sole()->end_reason);
    }

    public function test_wrong_guardian_another_family_and_a_non_approving_guardian_are_refused(): void
    {
        $this->setPin($this->parentToken('farid'))->assertOk();
        // Gina is Ben's guardian, not Asha's: nothing about Asha is revealed.
        $gina = $this->parentToken('gina');
        $this->api($gina)->getJson($this->base('asha'))->assertNotFound();
        $this->setPin($gina, pin: self::NEW_PIN)->assertNotFound();
        $this->start($gina)->assertNotFound();
        $this->api($gina)->postJson($this->base('asha').'/sessions/end')->assertNotFound();
        $this->assertSame(1, ChildModePin::query()->sole()->version);

        // Mona is Asha's guardian without the approval right.
        GuardianLink::query()->where('learner_id', $this->schoolA->ids['asha'])->where('guardian_user_id', $this->schoolA->ids['mona_user'])->update(['can_approve' => false]);
        DB::table('users')->where('id', $this->schoolA->ids['mona_user'])->update(['email_verified_at' => now()]);
        $mona = $this->parentToken('mona');
        $this->api($mona)->getJson($this->base('asha'))->assertOk()->assertJsonPath('data.available', false)->assertJsonPath('data.reason', 'not_approving')->assertJsonPath('data.opens', []);
        $this->setPin($mona, pin: self::NEW_PIN)->assertForbidden();
        $this->start($mona)->assertForbidden();

        // A guardian who has not confirmed their email, and a child without a current approval.
        DB::table('users')->where('id', $this->schoolA->ids['farid_user'])->update(['email_verified_at' => null]);
        $this->start($this->parentToken('farid'))->assertForbidden();
        DB::table('users')->where('id', $this->schoolA->ids['farid_user'])->update(['email_verified_at' => now()]);
        DB::table('learner_approvals')->where('learner_id', $this->schoolA->ids['asha'])->update(['withdrawn_at' => now()]);
        $this->api($this->parentToken('farid'))->getJson($this->base('asha'))->assertJsonPath('data.reason', 'approval_needed');
        $this->start($this->parentToken('farid'))->assertForbidden();
        $this->assertSame(0, ChildModeSession::query()->count());
    }

    public function test_another_school_never_reaches_this_schools_children_or_child_sessions(): void
    {
        [, $child] = $this->childSession();
        // School B's own parent, asking for a School A child by id.
        $parentB = app(CurrentSchool::class)->run(self::SCHOOL_B_ID, fn () => AppTokens::issue(User::query()->findOrFail($this->schoolB->ids['farid_user']), AppTokens::PARENT));
        $b = fn () => $this->api($parentB, ['X-School-Id' => (string) self::SCHOOL_B_ID]);
        $b()->getJson($this->base('asha'))->assertNotFound();
        $b()->putJson($this->base('asha').'/pin', ['pin' => self::NEW_PIN, 'pin_confirmation' => self::NEW_PIN])->assertNotFound();
        $b()->postJson($this->base('asha').'/sessions', ['pin' => self::PIN])->assertNotFound();
        $b()->postJson($this->base('asha').'/sessions/end')->assertNotFound();
        // School A's parent cannot work in School B, where they have no membership.
        $this->api($this->parentToken('farid'), ['X-School-Id' => (string) self::SCHOOL_B_ID])->getJson($this->base('asha'))->assertForbidden();
        // A child token is bound to its school: another school by header is refused.
        $this->api($child, ['X-School-Id' => (string) self::SCHOOL_B_ID])->getJson('/api/v2/child-mode/session')->assertForbidden();
        $this->api($child, ['X-School-Id' => '1'])->getJson('/api/v2/child-mode/session')->assertOk()->assertDontSee(self::SCHOOL_B_MARKER);
        // School B's own child sessions table holds nothing of School A.
        $this->assertSame(0, app(CurrentSchool::class)->run(self::SCHOOL_B_ID, fn () => ChildModeSession::query()->count()));
        $this->assertSame(1, ChildModeSession::query()->where('school_id', 1)->count());
    }

    public function test_wrong_role_and_wrong_token_are_refused(): void
    {
        $this->setPin($this->parentToken('farid'))->assertOk();
        foreach ([$this->schoolA->tokens['tara'], $this->schoolA->tokens['asha']] as $token) {
            $this->api($token)->getJson($this->base('asha'))->assertForbidden();
            $this->api($token)->putJson($this->base('asha').'/pin', ['pin' => self::NEW_PIN, 'pin_confirmation' => self::NEW_PIN])->assertForbidden();
            $this->api($token)->postJson($this->base('asha').'/sessions', ['pin' => self::PIN])->assertForbidden();
            $this->api($token)->getJson('/api/v2/child-mode/session')->assertForbidden();
        }
        $this->api(null)->getJson($this->base('asha'))->assertUnauthorized();
        $this->api(null)->getJson('/api/v2/child-mode/session')->assertUnauthorized();
        // A parent's own sign-in is not a child token.
        $this->api($this->parentToken('farid'))->getJson('/api/v2/child-mode/session')->assertForbidden();
        // Child mode starts only from a parent-app sign-in, not any other token of the account.
        $this->start($this->user('farid')->createToken(AppTokens::TEACHER)->plainTextToken)->assertForbidden();
        $this->assertSame(0, ChildModeSession::query()->count());
    }

    public function test_a_child_token_is_not_a_guardian_sign_in_on_the_guardian_routes(): void
    {
        [, $child] = $this->childSession();
        $this->api($child)->getJson($this->base('asha'))->assertUnauthorized();
        $this->api($child)->putJson($this->base('asha').'/pin', ['pin' => self::NEW_PIN, 'pin_confirmation' => self::NEW_PIN])->assertUnauthorized();
        $this->api($child)->postJson($this->base('asha').'/sessions', ['pin' => self::PIN])->assertUnauthorized();
        $this->api($child)->getJson('/api/parent/subjects?child_id='.$this->schoolA->ids['asha'])->assertUnauthorized();
        $this->api($child)->getJson('/api/v2/me/context')->assertUnauthorized();
        $this->api($child)->postJson('/api/logout')->assertUnauthorized();
        $this->assertSame(1, ChildModePin::query()->sole()->version);
    }
}
