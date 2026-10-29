<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\FeesPaid;
use App\Models\Platform\GuardianLink;
use App\Models\SessionYear;
use App\Models\Settings;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Services\Platform\Identity\GuardianService;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class GuardianChildResponseTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        foreach (['asha', 'cara', 'ben'] as $child) {
            app(GuardianService::class)->syncLegacy(Students::findOrFail($this->schoolA->ids[$child]));
        }
    }

    private function profile(): array
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($this->schoolA->tokens['farid'])->getJson('/api/parent/get-profile-data')->assertOk()->json('data.children');
    }

    private function signIn(): array
    {
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
        $this->defaultHeaders = [];

        return $this->postJson('/api/parent/login', ['email' => 'farid@example.com', 'password' => 'secret123'])->assertOk()->assertJsonPath('error', false)->json('data.children');
    }

    private function endAshaLink(): void
    {
        GuardianLink::query()->where('learner_id', $this->schoolA->ids['asha'])->where('guardian_user_id', $this->schoolA->ids['farid_user'])->update(['status' => 'ended']);
    }

    public function test_profile_excludes_an_ended_link_while_another_child_keeps_the_parent_active(): void
    {
        $this->endAshaLink();
        $ids = array_column($this->profile(), 'id');
        $this->assertContains($this->schoolA->ids['cara'], $ids);
        $this->assertNotContains($this->schoolA->ids['asha'], $ids);
    }

    public function test_sign_in_excludes_an_ended_link_while_another_child_remains_active(): void
    {
        $this->endAshaLink();
        $ids = array_column($this->signIn(), 'id');
        $this->assertContains($this->schoolA->ids['cara'], $ids);
        $this->assertNotContains($this->schoolA->ids['asha'], $ids);
    }

    public function test_explicit_active_link_appears_without_a_legacy_parent_column(): void
    {
        GuardianLink::query()->create(['learner_id' => $this->schoolA->ids['ben'], 'guardian_user_id' => $this->schoolA->ids['farid_user'], 'relationship' => 'guardian']);
        $this->assertContains($this->schoolA->ids['ben'], array_column($this->profile(), 'id'));
        $this->assertContains($this->schoolA->ids['ben'], array_column($this->signIn(), 'id'));
    }

    public function test_current_year_filter_applies_to_fathers_as_well_as_other_guardians(): void
    {
        StudentSessions::query()->where('student_id', $this->schoolA->ids['cara'])->where('session_year_id', $this->schoolA->ids['session_year'])->delete();
        $this->assertNotContains($this->schoolA->ids['cara'], array_column($this->profile(), 'id'));
        $this->assertNotContains($this->schoolA->ids['cara'], array_column($this->signIn(), 'id'));
    }

    public function test_guardian_without_fee_rights_does_not_receive_debt_flags_or_an_academic_paywall(): void
    {
        Settings::query()->updateOrCreate(['type' => 'compulsory_fee_payment_mode'], ['message' => '1']);
        SessionYear::query()->findOrFail($this->schoolA->ids['session_year'])->forceFill([
            'include_fee_installments' => 0, 'fee_due_date' => now()->subDays(2)->toDateString(), 'free_app_use_date' => now()->subDays(3)->toDateString(),
        ])->save();
        FeesPaid::query()->where('student_id', $this->schoolA->ids['asha'])->delete();
        $before = collect($this->profile())->firstWhere('id', $this->schoolA->ids['asha']);
        $this->assertSame(0, $before['is_fee_payment_due'], 'Legacy debt never creates an academic paywall, including for guardians with fee rights.');
        GuardianLink::query()->where('learner_id', $this->schoolA->ids['asha'])->where('guardian_user_id', $this->schoolA->ids['farid_user'])->update(['can_see_fees' => false]);
        $after = collect($this->profile())->firstWhere('id', $this->schoolA->ids['asha']);
        $this->assertSame(0, $after['is_fee_payment_due']);
        $login = collect($this->signIn())->firstWhere('id', $this->schoolA->ids['asha']);
        $this->assertSame(0, $login['is_fee_payment_due']);
    }

    public function test_wrong_school_selection_is_refused_without_any_child_data(): void
    {
        $this->withToken($this->schoolA->tokens['farid'])->withHeader('X-School-Id', '2')->getJson('/api/parent/get-profile-data')->assertForbidden();
    }

    public function test_teacher_token_cannot_read_the_parent_profile(): void
    {
        $this->withToken($this->schoolA->tokens['tara'])->getJson('/api/parent/get-profile-data')->assertForbidden();
    }
}
