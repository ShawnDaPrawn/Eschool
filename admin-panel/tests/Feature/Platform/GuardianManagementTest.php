<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Parents;
use App\Models\Platform\AuditLog;
use App\Models\Platform\GuardianInvitation;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Identity\GuardianLinks;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * The school office manages a learner's guardians (Action Plan 1.2).
 * Specification: engineering/specs/platform/guardian-management.md.
 */
class GuardianManagementTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        Queue::fake();
    }

    private function links(): GuardianLinks
    {
        return app(GuardianLinks::class);
    }

    private function grandmother(array $changes = []): array
    {
        return $changes + ['email' => 'Nomvula.Gran@Example.org', 'first_name' => 'Nomvula', 'last_name' => 'Gran', 'mobile' => '082 555 0101',
            'relationship' => 'grandparent', 'can_approve' => true, 'can_see_fees' => false];
    }

    private function link(int $learner, string $person): GuardianLink
    {
        return GuardianLink::query()->where('learner_id', $learner)->where('guardian_user_id', $this->schoolA->ids[$person.'_user'])->sole();
    }

    public function test_the_office_adds_a_third_guardian_who_gets_an_account_an_invitation_and_the_child(): void
    {
        $asha = $this->schoolA->ids['asha'];
        $this->assertSame(['Farid Demo', 'Mona Demo'], array_column($this->links()->list($this->superAdmin(), $asha), 'name'), 'legacy parents are linked first');

        $link = $this->links()->add($this->superAdmin(), $asha, $this->grandmother());

        $gran = User::query()->where('email', 'nomvula.gran@example.org')->sole();
        $this->assertTrue((bool) $gran->password_change_required);
        $this->assertSame(['grandparent', true, false, 'managed', 'active'], [$link->relationship, $link->can_approve, $link->can_see_fees, $link->source, $link->status]);
        $this->assertTrue(SchoolMembership::query()->active()->where('school_id', 1)->where('user_id', $gran->id)->where('role', 'Parent')->exists());
        $this->assertNotNull(Parents::query()->where('user_id', $gran->id)->first(), 'the installed parent app needs a parent row');
        $this->assertSame(1, GuardianInvitation::query()->where('guardian_user_id', $gran->id)->count(), 'an unverified guardian is invited');
        $this->assertContains($gran->id, app(GuardianService::class)->guardianUserIds(Students::findOrFail($asha))->all());
        $this->assertSame(1, AuditLog::query()->where('action', 'guardian_link_added')->count());
        $this->assertCount(3, $this->links()->list($this->superAdmin(), $asha));

        try {
            $this->links()->add($this->superAdmin(), $asha, $this->grandmother());
            $this->fail('the same guardian was linked twice');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('email', $error->errors());
        }
    }

    public function test_the_last_approving_guardian_cannot_be_removed_or_lose_the_right(): void
    {
        $ben = $this->schoolA->ids['ben'];
        $this->links()->list($this->superAdmin(), $ben);
        $gina = $this->link($ben, 'gina');
        foreach ([fn () => $this->links()->end($this->superAdmin(), $gina->id, ['reason' => 'Moved away']),
            fn () => $this->links()->update($this->superAdmin(), $gina->id, ['relationship' => 'guardian', 'can_approve' => false, 'can_see_fees' => true])] as $change) {
            try {
                $change();
                $this->fail('the last approving guardian was weakened');
            } catch (ValidationException $error) {
                $this->assertArrayHasKey('guardian', $error->errors());
            }
        }
        $this->assertSame(['active', true], [$gina->fresh()->status, $gina->fresh()->can_approve]);

        // With a second approver, the first can go.
        $this->links()->add($this->superAdmin(), $ben, $this->grandmother());
        $this->links()->end($this->superAdmin(), $gina->id, ['reason' => 'Guardianship transferred']);
        $this->assertSame(['ended', 'Guardianship transferred'], [$gina->fresh()->status, $gina->fresh()->ended_reason]);
        $this->assertNotNull($gina->fresh()->ended_at);
    }

    public function test_removing_a_guardian_ends_their_access_at_once_when_no_child_is_left(): void
    {
        $ben = $this->schoolA->ids['ben'];
        $this->links()->add($this->superAdmin(), $ben, $this->grandmother());
        $gina = User::findOrFail($this->schoolA->ids['gina_user']);
        $gina->createToken(AppTokens::PARENT);

        $this->links()->end($this->superAdmin(), $this->link($ben, 'gina')->id, ['reason' => 'No longer the guardian']);

        $this->assertNotContains($gina->id, app(GuardianService::class)->guardianUserIds(Students::findOrFail($ben))->all());
        $this->assertFalse(SchoolMembership::query()->active()->where('school_id', 1)->where('user_id', $gina->id)->where('role', 'Parent')->exists());
        $this->assertSame(0, $gina->tokens()->where('name', AppTokens::PARENT)->count(), 'parent-app sign-ins end');

        // A parent with another child at the school keeps their access.
        $asha = $this->schoolA->ids['asha'];
        $this->links()->list($this->superAdmin(), $asha);
        $this->links()->end($this->superAdmin(), $this->link($asha, 'farid')->id, ['reason' => 'Court order']);
        $this->assertTrue(SchoolMembership::query()->active()->where('school_id', 1)->where('user_id', $this->schoolA->ids['farid_user'])->where('role', 'Parent')->exists(), 'Farid still has Cara');
    }

    public function test_a_legacy_parent_edit_never_ends_a_guardian_the_office_added(): void
    {
        $asha = $this->schoolA->ids['asha'];
        $link = $this->links()->add($this->superAdmin(), $asha, $this->grandmother(['relationship' => 'guardian']));
        $learner = Students::findOrFail($asha);
        $learner->forceFill(['mother_id' => null])->save();

        $this->assertSame('active', $link->fresh()->status);
        $this->assertSame('ended', $this->link($asha, 'mona')->status, 'the legacy field still governs its own link');
    }

    public function test_only_the_office_manages_guardians_and_only_for_learners_and_links_of_its_school(): void
    {
        $asha = $this->schoolA->ids['asha'];
        foreach (['tara_user', 'farid_user', 'asha_user'] as $person) {
            try {
                $this->links()->add(User::findOrFail($this->schoolA->ids[$person]), $asha, $this->grandmother());
                $this->fail($person.' added a guardian');
            } catch (HttpException $error) {
                $this->assertSame(403, $error->getStatusCode());
            }
            $this->actingAs(User::findOrFail($this->schoolA->ids[$person]), 'web')->get(route('learner-guardians.index', $asha))->assertForbidden();
        }
        $this->actingAs($this->superAdmin(), 'web');
        $this->get(route('learner-guardians.index', $this->schoolB->ids['asha']))->assertNotFound();
        $this->post(route('learner-guardians.store', $this->schoolB->ids['asha']), $this->grandmother())->assertNotFound();
        $foreign = app(CurrentSchool::class)->run(2, function () {
            app(GuardianService::class)->syncLegacy(Students::findOrFail($this->schoolB->ids['asha']));

            return (int) GuardianLink::query()->where('learner_id', $this->schoolB->ids['asha'])->firstOrFail()->id;
        });
        $this->post(route('learner-guardians.end', [$asha, $foreign]), ['reason' => 'Cross-school attempt'])->assertNotFound();
        $this->links()->list($this->superAdmin(), $this->schoolA->ids['ben']);
        $otherLearnersLink = $this->link($this->schoolA->ids['ben'], 'gina')->id;
        $this->put(route('learner-guardians.update', [$asha, $otherLearnersLink]), ['relationship' => 'other', 'can_approve' => '1', 'can_see_fees' => '1'])->assertNotFound();
        $this->assertSame(0, GuardianLink::query()->where('source', 'managed')->count());
        $this->assertSame('active', app(CurrentSchool::class)->run(2, fn () => GuardianLink::query()->findOrFail($foreign)->status));
    }

    public function test_the_panel_page_adds_changes_and_removes_guardians(): void
    {
        $asha = $this->schoolA->ids['asha'];
        $this->actingAs($this->superAdmin(), 'web')->get(route('people-profiles.index', $this->schoolA->ids['asha_user']))->assertOk()->assertSee('Manage guardians');
        $this->get(route('learner-guardians.index', $asha))->assertOk()->assertSee('Farid Demo')->assertSee('Add a guardian');
        $this->post(route('learner-guardians.store', $asha), $this->grandmother(['can_approve' => '1']))->assertRedirect(route('learner-guardians.index', $asha));
        $gran = GuardianLink::query()->where('source', 'managed')->sole();
        $this->put(route('learner-guardians.update', [$asha, $gran->id]), ['relationship' => 'caregiver'])->assertRedirect();
        $this->assertSame(['caregiver', false, false], [$gran->fresh()->relationship, $gran->fresh()->can_approve, $gran->fresh()->can_see_fees], 'unticked boxes switch the rights off');
        $this->post(route('learner-guardians.end', [$asha, $gran->id]), ['reason' => 'x'])->assertSessionHasErrors('reason');
        $this->post(route('learner-guardians.end', [$asha, $gran->id]), ['reason' => 'Arrangement ended'])->assertRedirect();
        $this->get(route('learner-guardians.index', $asha))->assertOk()->assertSee('Past guardians')->assertSee('Nomvula Gran');
    }
}
