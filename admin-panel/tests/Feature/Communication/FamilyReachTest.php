<?php

declare(strict_types=1);

namespace Tests\Feature\Communication;

use App\Models\Platform\GuardianLink;
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
