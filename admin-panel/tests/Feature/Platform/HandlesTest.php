<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\GuardianLink;
use App\Models\Platform\HandleProposal;
use App\Models\Students;
use App\Models\Term;
use App\Models\User;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Identity\Handles;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class HandlesTest extends TestCase
{
    use UsesTwoSchools;
    use ValidatesOpenApiResponses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        User::findOrFail($this->schoolA->ids['asha_user'])->forceFill(['dob' => '2012-06-13'])->save();
        app(GuardianService::class)->syncLegacy(Students::findOrFail($this->schoolA->ids['asha']));
    }

    public function test_under_eighteen_proposes_and_verified_guardian_activates_once(): void
    {
        $answer = $this->withToken($this->schoolA->tokens['asha'])->putJson('/api/v2/me/handle', ['handle' => 'orbit'])->assertOk();
        $id = $answer->json('data.proposal.id');
        $this->assertNull($answer->json('data.handle'));
        $this->assertNull(User::findOrFail($this->schoolA->ids['asha_user'])->handle);
        $this->verifiedGuardian('farid_user');
        $this->app['auth']->forgetGuards();
        $this->withToken($this->schoolA->tokens['farid'])->getJson('/api/v2/guardian/handle-proposals')->assertOk()->assertJsonPath('data.0.handle', 'orbit');
        $this->postJson('/api/v2/guardian/handle-proposals/'.$id, ['decision' => 'approve'])->assertOk()->assertJsonPath('data.handle', 'orbit');
        $this->postJson('/api/v2/guardian/handle-proposals/'.$id, ['decision' => 'approve'])->assertConflict();
        $this->assertSame(1, DB::table('handle_changes')->where('user_id', $this->schoolA->ids['asha_user'])->count());
        $this->assertSame(0, DB::table('learner_approvals')->where('learner_id', $this->schoolA->ids['asha'])->count(), 'A handle decision must not grant account-access consent.');
        $this->assertDatabaseHas('audit_log', ['action' => 'identity.handle_changed', 'subject_id' => $this->schoolA->ids['asha_user']]);
    }

    public function test_adult_sets_handle_directly_and_v1_output_keeps_its_shape(): void
    {
        $user = User::findOrFail($this->schoolA->ids['asha_user']);
        $user->forceFill(['dob' => '2000-01-01'])->save();
        $answer = $this->withToken($this->schoolA->tokens['asha'])->putJson('/api/v2/me/handle', ['handle' => 'orbit'])->assertOk();
        $this->assertSame('orbit', $answer->json('data.handle'));
        $this->assertNull($answer->json('data.proposal'));
        $this->assertArrayNotHasKey('handle', $user->fresh()->toArray());
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v2/me/context')->assertOk()->assertJsonPath('data.user.handle', 'orbit');
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
        $this->postJson('/api/student/login', ['gr_number' => 'orbit', 'password' => 'secret123'])->assertOk()->assertJsonPath('error', false);
    }

    public function test_parent_and_staff_can_choose_their_own_handle_without_guardian_approval(): void
    {
        foreach (['farid' => 'guardian.orbit', 'tara' => 'teacher.orbit'] as $person => $handle) {
            $this->app['auth']->forgetGuards();
            $this->withToken($this->schoolA->tokens[$person])->putJson('/api/v2/me/handle', ['handle' => $handle])->assertOk()->assertJsonPath('data.handle', $handle);
            $this->app['auth']->forgetGuards();
            $this->defaultHeaders = [];
            $this->app['auth']->shouldUse('web');
            $this->postJson('/api/'.($person === 'farid' ? 'parent' : 'teacher').'/login', ['email' => $handle, 'password' => 'secret123'])
                ->assertOk()->assertJsonPath('error', false);
            $this->app['auth']->guard('web')->logoutCurrentDevice();
        }
    }

    public function test_an_unverified_or_unrelated_guardian_cannot_approve(): void
    {
        $proposal = $this->propose();
        $this->withToken($this->schoolA->tokens['farid'])->postJson('/api/v2/guardian/handle-proposals/'.$proposal->id, ['decision' => 'approve'])->assertForbidden();
        $this->verifiedGuardian('gina_user');
        $this->app['auth']->forgetGuards();
        $this->withToken($this->schoolA->tokens['gina'])->postJson('/api/v2/guardian/handle-proposals/'.$proposal->id, ['decision' => 'approve'])->assertNotFound();
        $this->assertNull(User::findOrFail($proposal->user_id)->handle);
    }

    public function test_teacher_wrong_school_and_removed_links_cannot_decide(): void
    {
        $proposal = $this->propose();
        $this->withToken($this->schoolA->tokens['tara'])->postJson('/api/v2/guardian/handle-proposals/'.$proposal->id, ['decision' => 'approve'])->assertForbidden();
        $this->app['auth']->forgetGuards();
        app(CurrentSchool::class)->run(2, fn () => User::findOrFail($this->schoolB->ids['farid_user'])->forceFill(['email_verified_at' => now()])->save());
        $this->withToken($this->schoolB->tokens['farid'])->withHeader('X-School-Id', '2')
            ->postJson('/api/v2/guardian/handle-proposals/'.$proposal->id, ['decision' => 'approve'])->assertNotFound();
        $this->defaultHeaders = [];
        $this->verifiedGuardian('farid_user');
        GuardianLink::query()->where('learner_id', $proposal->learner_id)->where('guardian_user_id', $this->schoolA->ids['farid_user'])->update(['status' => 'ended']);
        $this->app['auth']->forgetGuards();
        $this->withToken($this->schoolA->tokens['farid'])->postJson('/api/v2/guardian/handle-proposals/'.$proposal->id, ['decision' => 'approve'])->assertNotFound();
    }

    public function test_expired_and_superseded_requests_cannot_activate(): void
    {
        $first = $this->propose();
        app(Handles::class)->request(User::findOrFail($first->user_id), 'new.orbit');
        $this->verifiedGuardian('farid_user');
        $this->withToken($this->schoolA->tokens['farid'])->postJson('/api/v2/guardian/handle-proposals/'.$first->id, ['decision' => 'approve'])->assertConflict();
        $second = HandleProposal::query()->where('status', 'pending')->firstOrFail();
        $this->travel(8)->days();
        $this->postJson('/api/v2/guardian/handle-proposals/'.$second->id, ['decision' => 'approve'])->assertGone();
    }

    public function test_rejection_releases_a_pending_name_without_changing_the_current_handle(): void
    {
        $proposal = $this->propose();
        $this->verifiedGuardian('farid_user');
        $this->withToken($this->schoolA->tokens['farid'])->postJson('/api/v2/guardian/handle-proposals/'.$proposal->id, ['decision' => 'reject'])->assertOk()->assertJsonPath('data.handle', null);
        $other = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->assertSame('orbit', app(Handles::class)->request($other, 'orbit')['handle']);
    }

    public function test_change_limit_and_old_name_reservation_are_global(): void
    {
        $user = User::findOrFail($this->schoolA->ids['tara_user']);
        app(Handles::class)->request($user, 'first.orbit');
        try {
            app(Handles::class)->request($user, 'second.orbit');
            $this->fail('Changed twice in one term');
        } catch (ValidationException) {
            $this->assertSame('first.orbit', $user->fresh()->handle);
        }
        $this->travel(70)->days();
        app(Handles::class)->request($user, 'second.orbit');
        $other = app(CurrentSchool::class)->run(2, fn () => User::findOrFail($this->schoolB->ids['tara_user']));
        try {
            app(CurrentSchool::class)->run(2, fn () => app(Handles::class)->request($other, 'first.orbit'));
            $this->fail('An old name was reused within ninety days');
        } catch (ValidationException) {
            $this->assertNull($other->fresh()->handle);
        }
        $this->travel(91)->days();
        $answer = app(CurrentSchool::class)->run(2, fn () => app(Handles::class)->request($other, 'first.orbit'));
        $this->assertSame('first.orbit', $answer['handle']);
    }

    public function test_pending_name_is_reserved_across_schools(): void
    {
        $this->propose();
        $this->expectException(ValidationException::class);
        app(CurrentSchool::class)->run(2, fn () => app(Handles::class)->request(User::findOrFail($this->schoolB->ids['tara_user']), 'orbit'));
    }

    public function test_joining_another_school_does_not_bypass_an_overlapping_term(): void
    {
        $user = User::findOrFail($this->schoolA->ids['tara_user']);
        app(Handles::class)->request($user, 'first.orbit');
        app(CurrentSchool::class)->run(2, function () use ($user) {
            $user->assignRole('Teacher');
            Term::query()->update(['end_date' => now()->addDays(120)->toDateString()]);
        });
        $this->travel(70)->days();
        $this->expectException(ValidationException::class);
        app(Handles::class)->request($user, 'second.orbit');
    }

    public function test_rejecting_a_proposal_for_an_old_name_keeps_its_original_ninety_day_hold(): void
    {
        $service = app(Handles::class);
        $first = $this->propose();
        $this->verifiedGuardian('farid_user');
        $guardian = User::findOrFail($this->schoolA->ids['farid_user']);
        $user = User::findOrFail($first->user_id);
        $service->decide($guardian, $first->id, 'approve');
        $this->travel(70)->days();
        Term::query()->forceCreate(['name' => 'Short term', 'session_year_id' => $this->schoolA->ids['session_year'], 'start_date' => now()->toDateString(), 'end_date' => now()->addDays(10)->toDateString()]);
        $second = $service->request($user, 'second.orbit');
        $service->decide($guardian, $second['proposal']['id'], 'approve');
        $expected = DB::table('handle_claims')->where('handle', 'orbit')->value('reserved_until');
        $this->travel(11)->days();
        $third = $service->request($user, 'orbit');
        $service->decide($guardian, $third['proposal']['id'], 'reject');
        $this->assertSame($expected, DB::table('handle_claims')->where('handle', 'orbit')->value('reserved_until'));
    }

    public function test_user_id_in_a_request_cannot_change_another_person(): void
    {
        $this->withToken($this->schoolA->tokens['tara'])->putJson('/api/v2/me/handle', ['handle' => 'own.orbit', 'user_id' => $this->schoolA->ids['asha_user']])->assertOk();
        $this->assertNull(User::findOrFail($this->schoolA->ids['asha_user'])->handle);
        $this->assertSame('own.orbit', User::findOrFail($this->schoolA->ids['tara_user'])->handle);
    }

    public function test_handle_history_blocks_permanent_cleanup_before_any_academic_data_is_removed(): void
    {
        $proposal = $this->propose();
        $this->actingAs($this->superAdmin())->deleteJson('/permanent-delete/'.$proposal->learner_id)
            ->assertJsonPath('error', true)->assertJsonPath('message', 'This learner has handle request history. Archive the learner instead; retained records require the retention process.');
        $this->assertNotNull(Students::find($proposal->learner_id));
        $this->assertNotNull($proposal->fresh());
    }

    #[DataProvider('invalidHandles')]
    public function test_private_identifiers_and_reserved_or_invalid_names_are_refused(string $handle): void
    {
        $this->withToken($this->schoolA->tokens['asha'])->putJson('/api/v2/me/handle', ['handle' => $handle])->assertUnprocessable();
        $this->assertSame(0, HandleProposal::query()->count());
    }

    public static function invalidHandles(): array
    {
        return array_map(fn ($value) => [$value], ['admin', 'a.d.m.i.n', 'support', 'super_admin', 'baobab_high', 'baobab-high', 'demo.star', 'astro20120613', 'astro13062012', 'ab', 'a..b', 'a__b', 'a._b', '3stars', 'star.', 'star-name']);
    }

    private function propose(): HandleProposal
    {
        app(Handles::class)->request(User::findOrFail($this->schoolA->ids['asha_user']), 'orbit');

        return HandleProposal::query()->where('user_id', $this->schoolA->ids['asha_user'])->where('status', 'pending')->firstOrFail();
    }

    private function verifiedGuardian(string $id): void
    {
        User::findOrFail($this->schoolA->ids[$id])->forceFill(['email_verified_at' => now()])->save();
    }
}
