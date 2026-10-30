<?php

declare(strict_types=1);

namespace Tests\Feature\Communication;

use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Services\Communication\FamilyReach;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Identity\GuardianService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * Family reach (Action Plan 2.6).
 * Specification: engineering/specs/communication/family-reach.md.
 */
class FamilyReachTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
    }

    private function report(): array
    {
        return app(FamilyReach::class)->report($this->superAdmin());
    }

    private function reason(array $report, string $learner): ?string
    {
        return collect($report['unreached'])->firstWhere('learner_id', $this->schoolA->ids[$learner])['reason'] ?? null;
    }

    public function test_reach_counts_the_parent_app_and_verified_email_and_nothing_else(): void
    {
        User::whereIn('id', [$this->schoolA->ids['farid_user'], $this->schoolA->ids['mona_user'], $this->schoolA->ids['gina_user']])->update(['email_verified_at' => null]);
        $before = $this->report();
        $this->assertSame(3, $before['total'], 'the three current learners of this school only');
        $this->assertSame(3, $before['counts']['unreachable']);

        User::whereKey($this->schoolA->ids['farid_user'])->update(['email_verified_at' => now()]);
        User::findOrFail($this->schoolA->ids['gina_user'])->createToken(AppTokens::PARENT);
        $after = $this->report();
        $this->assertSame(['app' => 1, 'email' => 2, 'unreachable' => 0, 'no_guardian' => 0], $after['counts'], 'Ben by the app; Asha and Cara by Farid\'s email');
        $this->assertSame([], $after['unreached']);

        // An expired sign-in is not current: past Sanctum's expiration, or its own expires_at.
        $gina = User::findOrFail($this->schoolA->ids['gina_user']);
        $gina->tokens()->update(['created_at' => now()->subMinutes((int) config('sanctum.expiration') + 1)]);
        $this->assertSame('unreachable', $this->reason($this->report(), 'ben'), 'a token older than the configured expiration');
        config(['sanctum.expiration' => null]);
        $this->assertNull($this->reason($this->report(), 'ben'), 'without a configured expiration the token is current');
        $gina->tokens()->update(['expires_at' => now()->subMinute()]);
        $this->assertSame('unreachable', $this->reason($this->report(), 'ben'), 'a token past its own expires_at');
        $gina->tokens()->update(['expires_at' => now()->addDay()]);
        $this->assertNull($this->reason($this->report(), 'ben'));

        User::whereKey($this->schoolA->ids['gina_user'])->update(['status' => 0]);
        $this->assertSame('unreachable', $this->reason($this->report(), 'ben'), 'a switched-off account reaches nobody');
    }

    public function test_guardians_are_counted_as_the_school_counts_them(): void
    {
        $asha = Students::findOrFail($this->schoolA->ids['asha']);
        foreach (['farid_user', 'mona_user'] as $parent) {
            GuardianLink::query()->create(['learner_id' => $asha->id, 'guardian_user_id' => $this->schoolA->ids[$parent], 'relationship' => 'father', 'status' => 'ended']);
        }
        $this->assertSame([], app(GuardianService::class)->guardianUserIds($asha)->all());

        $this->assertSame('no_guardian', $this->reason($this->report(), 'asha'), 'ended links override the legacy parent fields');
    }

    public function test_reach_requires_current_membership_and_a_current_parent_role_for_the_app(): void
    {
        $gina = User::findOrFail($this->schoolA->ids['gina_user']);
        $gina->forceFill(['email_verified_at' => now()])->save();
        $gina->createToken(AppTokens::PARENT);
        $this->assertSame(1, $this->report()['counts']['app']);

        SchoolMembership::query()->where('school_id', 1)->where('user_id', $gina->id)->where('role', 'Parent')->update(['status' => SchoolMembership::SUSPENDED]);
        $this->assertSame('unreachable', $this->reason($this->report(), 'ben'), 'The delivery guard also refuses a suspended school membership');

        // Another current role allows email delivery, but cannot make an old
        // parent-app sign-in usable after the local Parent role has ended.
        $gina->assignRole('Teacher');
        SchoolMembership::query()->updateOrCreate(['school_id' => 1, 'user_id' => $gina->id, 'role' => 'Teacher'],
            ['status' => SchoolMembership::ACTIVE, 'starts_on' => now()->toDateString()]);
        SchoolMembership::query()->where('school_id', 1)->where('user_id', $gina->id)->where('role', 'Parent')->update(['status' => SchoolMembership::ENDED]);
        $this->assertSame(0, $this->report()['counts']['app']);
        $this->assertNull($this->reason($this->report(), 'ben'), 'An active school member with verified email remains reachable by email');
        $gina->forceFill(['email_verified_at' => null])->save();
        $this->assertSame('unreachable', $this->reason($this->report(), 'ben'), 'The still-existing token cannot substitute for a current Parent role');
    }

    public function test_only_the_office_sees_it(): void
    {
        foreach (['tara_user', 'farid_user', 'asha_user'] as $person) {
            try {
                app(FamilyReach::class)->report(User::findOrFail($this->schoolA->ids[$person]));
                $this->fail($person.' saw the reach report');
            } catch (HttpException $error) {
                $this->assertSame(403, $error->getStatusCode());
            }
        }
        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']), 'web')->get(route('family-reach.index'))->assertForbidden();
        $this->actingAs($this->superAdmin(), 'web')->get(route('family-reach.index'))->assertOk()->assertSee('Family reach')->assertSee('Reach by homeroom')->assertDontSee('Baobab');
    }
}
