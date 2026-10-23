<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\GuardianLink;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class GuardianApprovalTest extends TestCase
{
    use UsesTwoSchools;
    use ValidatesOpenApiResponses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        config(['identity.approval_enforced' => true]);
        $this->link('farid', 'asha');
        $this->link('mona', 'asha');
        DB::table('users')->where('id', $this->schoolA->ids['gina_user'])->update(['email_verified_at' => now()]);
    }

    public function test_verified_guardian_can_approve_once_and_learner_can_then_use_the_api(): void
    {
        $this->withToken($this->schoolA->tokens['asha'])->getJson('/api/student/dashboard')->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->decision('farid', 'approve')->assertOk()->assertJsonPath('data.status', 'approved');
        $this->decision('farid', 'approve')->assertOk()->assertJsonPath('data.approved_by_count', 1);
        $this->app['auth']->forgetGuards();
        $this->withToken($this->schoolA->tokens['asha'])->getJson('/api/student/dashboard')->assertOk();
    }

    public function test_unverified_guardian_cannot_approve(): void
    {
        DB::table('users')->where('id', $this->schoolA->ids['farid_user'])->update(['email_verified_at' => null]);
        $this->decision('farid', 'approve')->assertForbidden();
    }

    public function test_wrong_guardian_and_wrong_school_cannot_approve(): void
    {
        $this->decision('gina', 'approve')->assertNotFound();
        $this->app['auth']->forgetGuards();
        $this->withToken($this->schoolA->tokens['farid'])->postJson('/api/v2/guardian/approvals/'.$this->schoolB->ids['asha'], $this->body('approve'))->assertNotFound();
    }

    public function test_teacher_cannot_act_as_a_guardian(): void
    {
        $this->decision('tara', 'approve')->assertForbidden();
    }

    public function test_any_approving_guardian_can_withdraw_all_approvals_and_existing_tokens(): void
    {
        $this->decision('farid', 'approve')->assertOk();
        $this->decision('mona', 'approve')->assertOk()->assertJsonPath('data.approved_by_count', 2);
        $this->decision('mona', 'withdraw')->assertOk()->assertJsonPath('data.status', 'withdrawn');
        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $this->schoolA->ids['asha_user'])->count());
        $this->assertFalse(app(ApprovalService::class)->approved(Students::findOrFail($this->schoolA->ids['asha'])));
    }

    public function test_removed_link_or_deactivated_guardian_is_not_valid_consent(): void
    {
        $this->decision('farid', 'approve')->assertOk();
        GuardianLink::query()->where('guardian_user_id', $this->schoolA->ids['farid_user'])->update(['status' => 'ended']);
        $this->assertFalse(app(ApprovalService::class)->approved(Students::findOrFail($this->schoolA->ids['asha'])));
    }

    public function test_current_consent_text_must_be_accepted(): void
    {
        $this->withToken($this->schoolA->tokens['farid'])->postJson('/api/v2/guardian/approvals/'.$this->schoolA->ids['asha'], [
            'decision' => 'approve', 'consent_version' => 'old-version',
        ])->assertUnprocessable();
    }

    public function test_list_contains_only_the_guardians_own_children(): void
    {
        $this->withToken($this->schoolA->tokens['farid'])->getJson('/api/v2/guardian/approvals')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.learner.id', $this->schoolA->ids['asha']);
    }

    public function test_unapproved_learner_cannot_obtain_a_token_or_context_when_enforcement_is_enabled(): void
    {
        $user = User::findOrFail($this->schoolA->ids['asha_user']);
        $this->expectException(HttpException::class);
        AppTokens::issue($user, 'student-app');
    }

    public function test_a_refused_portal_sign_in_does_not_keep_a_session(): void
    {
        $this->postJson('/api/student/login', ['gr_number' => 'GR-ASHA', 'password' => 'secret123'], ['Origin' => 'http://localhost'])
            ->assertForbidden();
        $this->assertGuest('web');
    }

    public function test_an_approval_in_another_school_does_not_approve_this_school(): void
    {
        $this->decision('farid', 'approve')->assertOk();
        app(CurrentSchool::class)->run(2, function () {
            $this->assertFalse(app(ApprovalService::class)->approved(Students::findOrFail($this->schoolB->ids['asha'])));
        });
    }

    public function test_an_ended_link_removes_legacy_child_access_and_is_not_reactivated_by_an_unrelated_edit(): void
    {
        GuardianLink::query()->where('guardian_user_id', $this->schoolA->ids['farid_user'])->update(['status' => 'ended']);
        $learner = Students::findOrFail($this->schoolA->ids['asha']);
        $learner->update(['height' => '130']);
        $this->assertSame('ended', GuardianLink::query()->where('guardian_user_id', $this->schoolA->ids['farid_user'])->value('status'));
        $this->withToken($this->schoolA->tokens['farid'])->getJson('/api/parent/subjects?child_id='.$learner->id)->assertJsonPath('error', true);
    }

    public function test_a_guardians_fee_right_does_not_remove_their_academic_access(): void
    {
        GuardianLink::query()->where('guardian_user_id', $this->schoolA->ids['farid_user'])->update(['can_see_fees' => false]);
        $this->withToken($this->schoolA->tokens['farid'])->getJson('/api/parent/fees-paid-list?child_id='.$this->schoolA->ids['asha'])->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->withToken($this->schoolA->tokens['farid'])->getJson('/api/parent/subjects?child_id='.$this->schoolA->ids['asha'])->assertOk()->assertJsonPath('error', false);
    }

    private function link(string $guardian, string $learner): void
    {
        DB::table('users')->where('id', $this->schoolA->ids[$guardian.'_user'])->update(['email_verified_at' => now()]);
        GuardianLink::query()->create([
            'learner_id' => $this->schoolA->ids[$learner], 'guardian_user_id' => $this->schoolA->ids[$guardian.'_user'], 'relationship' => 'guardian',
        ]);
    }

    private function decision(string $who, string $decision): TestResponse
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($this->schoolA->tokens[$who])->postJson('/api/v2/guardian/approvals/'.$this->schoolA->ids['asha'], $this->body($decision));
    }

    private function body(string $decision): array
    {
        return ['decision' => $decision, 'consent_version' => config('identity.consent_version')];
    }
}
