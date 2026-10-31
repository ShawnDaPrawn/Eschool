<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\ClassSchool;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolAccountEvent;
use App\Models\Platform\SchoolContract;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\StaffInvitation;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Saas\PlatformOnboarding;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\PassesPlatformTwoFactor;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * HQ onboarding checklist (SA.6): live counts per school, never a person,
 * and training sessions on the school's timeline.
 * engineering/specs/platform/hq-onboarding.md.
 */
class HqOnboardingTest extends TestCase
{
    use PassesPlatformTwoFactor;
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->forget();
    }

    private function staff(string $role): User
    {
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => ucfirst($role), 'last_name' => 'Operator', 'email' => $role.'@platform.example', 'mobile' => '', 'password' => 'Synthetic-onboarding-password-582!', 'status' => 1]));
        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => $role, 'expires_at' => now()->addHour()]);

        return $user;
    }

    /** @return array<string, array<string, mixed>> */
    private function steps(int $school): array
    {
        return collect(app(PlatformOnboarding::class)->checklist($school)['steps'])->keyBy('key')->all();
    }

    public function test_the_checklist_reads_each_step_from_the_schools_live_data(): void
    {
        $steps = $this->steps(1);

        $this->assertSame('todo', $steps['contract']['status']);
        $this->assertSame('later', $steps['sending_domain']['status']);
        // The demo school's classes have no grades yet; three learners, none with a LURITS number.
        $this->assertSame(['started', '2 homerooms, 0 with a grade.'], [$steps['homerooms']['status'], $steps['homerooms']['detail']]);
        $this->assertSame(['started', '3 learners, 0 with a LURITS number.'], [$steps['learners']['status'], $steps['learners']['detail']]);
        $this->assertSame('todo', $steps['training']['status']);

        app(CurrentSchool::class)->run(1, function () {
            SchoolContract::query()->create(['starts_on' => now()->subMonth()->toDateString(), 'contracted_learners' => 300, 'price_per_learner_cents' => 1500,
                'signatory' => 'Principal', 'operator_agreement_version' => '2026-10', 'operator_agreement_signed_on' => now()->subMonth()->toDateString()]);
            ClassSchool::query()->update(['grade_level_id' => DB::table('grade_levels')->where('code', '5')->value('id')]);
            StaffInvitation::query()->create(['user_id' => $this->superAdmin()->id, 'purpose' => StaffInvitation::SCHOOL_ADMIN, 'token_hash' => str_repeat('a', 64),
                'encrypted_token' => str_repeat('b', 64), 'email_hash' => str_repeat('c', 64), 'expires_at' => now()->addDay(), 'accepted_at' => now()]);
            // Every learner has an approving guardian with a verified email who approved.
            foreach (Students::query()->get() as $learner) {
                $guardian = User::query()->findOrFail($this->schoolA->ids['farid_user']);
                GuardianLink::query()->updateOrCreate(['learner_id' => $learner->id, 'guardian_user_id' => $guardian->id], ['relationship' => 'father', 'status' => 'active', 'can_approve' => true]);
                LearnerApproval::query()->create(['learner_id' => $learner->id, 'guardian_user_id' => $guardian->id, 'consent_version' => '2026-10-23', 'consented_at' => now()]);
            }
            User::query()->whereKey($this->schoolA->ids['farid_user'])->update(['email_verified_at' => now()]);
        });
        $steps = $this->steps(1);

        $this->assertSame('done', $steps['contract']['status']);
        $this->assertSame('done', $steps['administrator']['status']);
        $this->assertSame('done', $steps['homerooms']['status']);
        $this->assertSame('done', $steps['guardians']['status'], $steps['guardians']['detail']);
        $this->assertStringContainsString('3 (100%) are approved', $steps['guardians']['detail']);
        // School B's data is its own.
        $this->assertSame('todo', $this->steps(2)['contract']['status']);
    }

    public function test_the_page_shows_counts_and_no_person(): void
    {
        $this->actingAsHq($this->staff('support'));

        $page = $this->get('/saas/schools/1/onboarding')->assertOk()->assertSee('Onboarding:')->assertSee('Learners imported with LURITS numbers');
        foreach (['Asha', 'Farid', 'Tara', 'GR-ASHA', self::SCHOOL_B_MARKER] as $person) {
            $page->assertDontSee($person);
        }
        // Support can read the checklist but not record training.
        $page->assertDontSee('Record a training session');
        $this->post('/saas/schools/1/training', ['held_on' => now()->toDateString(), 'topic' => 'Marks capture'])->assertForbidden();
    }

    public function test_operations_records_a_training_session_on_the_timeline(): void
    {
        $this->actingAsHq($this->staff('operations'));

        $this->post('/saas/schools/1/training', ['held_on' => now()->subDay()->toDateString(), 'topic' => 'Registers and marks', 'attendees' => 12])
            ->assertRedirect('/saas/schools/1/onboarding');
        $this->post('/saas/schools/1/training', ['held_on' => now()->addDay()->toDateString(), 'topic' => 'Future'])->assertSessionHasErrors('held_on');

        $event = app(CurrentSchool::class)->run(1, fn () => SchoolAccountEvent::query()->where('kind', 'training')->sole());
        $this->assertStringContainsString('Registers and marks (12 people)', $event->body);
        $this->assertSame(0, app(CurrentSchool::class)->run(2, fn () => SchoolAccountEvent::query()->where('kind', 'training')->count()));
        $this->assertSame('done', $this->steps(1)['training']['status']);
        $this->get('/saas/schools/999/onboarding')->assertNotFound();
    }

    public function test_training_accepts_an_omitted_attendee_count(): void
    {
        $this->actingAsHq($this->staff('operations'));
        $this->post('/saas/schools/1/training', ['held_on' => now()->toDateString(), 'topic' => 'Safe onboarding'])
            ->assertRedirect('/saas/schools/1/onboarding');
        $event = app(CurrentSchool::class)->run(1, fn () => SchoolAccountEvent::query()->where('kind', 'training')->sole());
        $this->assertStringContainsString('Safe onboarding', $event->body);
        $this->assertSame(0, app(CurrentSchool::class)->run(2, fn () => SchoolAccountEvent::query()->where('kind', 'training')->count()));
    }

    public function test_departed_staff_acceptances_do_not_complete_current_staff_onboarding(): void
    {
        app(CurrentSchool::class)->run(1, function () {
            $former = User::query()->findOrFail($this->schoolA->ids['tara_user']);
            $current = User::query()->findOrFail($this->schoolA->ids['theo_user']);
            SchoolMembership::query()->where('school_id', 1)->update(['status' => SchoolMembership::ENDED]);
            SchoolMembership::query()->updateOrCreate(['school_id' => 1, 'user_id' => $current->id, 'role' => 'Teacher'], ['status' => SchoolMembership::ACTIVE, 'starts_on' => null, 'ends_on' => null]);
            $admin = $this->superAdmin();
            SchoolMembership::query()->updateOrCreate(['school_id' => 1, 'user_id' => $admin->id, 'role' => 'Super Admin'], ['status' => SchoolMembership::ACTIVE, 'starts_on' => null, 'ends_on' => null]);
            foreach ([$admin, $former] as $index => $user) {
                StaffInvitation::query()->create(['user_id' => $user->id, 'purpose' => StaffInvitation::STAFF,
                    'token_hash' => hash('sha256', 'onboarding-'.$index), 'encrypted_token' => 'synthetic',
                    'email_hash' => hash('sha256', $user->email), 'expires_at' => now()->addDay(), 'accepted_at' => now()]);
            }
        });
        $step = $this->steps(1)['staff'];
        $this->assertSame('started', $step['status']);
        $this->assertStringContainsString('2 staff', $step['detail']);
        $this->assertStringContainsString('1 signed in', $step['detail']);
    }

    public function test_school_staff_cannot_open_it(): void
    {
        $this->actingAs($this->superAdmin());

        $this->get('/saas/schools/1/onboarding')->assertForbidden();
    }
}
