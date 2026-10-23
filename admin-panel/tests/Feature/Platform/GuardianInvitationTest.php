<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\GuardianInvitation;
use App\Models\Platform\GuardianLink;
use App\Models\User;
use App\Services\Platform\Identity\GuardianInvitations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class GuardianInvitationTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        Queue::fake();
        GuardianLink::query()->create([
            'learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->schoolA->ids['farid_user'], 'relationship' => 'father',
        ]);
    }

    public function test_invitation_is_private_encrypted_and_does_not_grant_consent(): void
    {
        $invitation = $this->issue();
        $secret = $invitation->encrypted_token;
        $this->assertSame(64, strlen($secret));
        $this->assertStringNotContainsString($secret, json_encode($invitation));
        $this->assertStringNotContainsString($secret, DB::table('guardian_invitations')->where('id', $invitation->id)->value('encrypted_token'));
        $this->assertSame(0, DB::table('learner_approvals')->count());
        $this->getJson('/api/v2/guardian/invitations/'.$secret)->assertOk()
            ->assertJsonPath('data.needs_password', true)->assertJsonPath('data.learners.0.id', $this->schoolA->ids['asha']);
    }

    public function test_acceptance_verifies_email_issues_a_parent_token_and_cannot_be_replayed(): void
    {
        $invitation = $this->issue();
        $path = '/api/v2/guardian/invitations/'.$invitation->encrypted_token.'/accept';
        $answer = $this->postJson($path, ['password' => 'SafeInvitationPassword123'])->assertOk();
        $this->assertNotEmpty($answer->json('token'));
        $this->assertNotNull(User::findOrFail($this->schoolA->ids['farid_user'])->email_verified_at);
        $this->assertSame(0, DB::table('learner_approvals')->count());
        $this->app['auth']->forgetGuards();
        $this->postJson($path, ['password' => 'SafeInvitationPassword123'])->assertNotFound();
    }

    public function test_unknown_expired_and_replaced_links_are_refused(): void
    {
        $old = $this->issue();
        $current = $this->issue();
        $this->getJson('/api/v2/guardian/invitations/'.$old->encrypted_token)->assertNotFound();
        $current->update(['expires_at' => now()->subMinute()]);
        $this->getJson('/api/v2/guardian/invitations/'.$current->encrypted_token)->assertNotFound();
        $this->getJson('/api/v2/guardian/invitations/'.str_repeat('x', 64))->assertNotFound();
    }

    public function test_new_guardian_must_choose_a_strong_password(): void
    {
        $invitation = $this->issue();
        $path = '/api/v2/guardian/invitations/'.$invitation->encrypted_token.'/accept';
        $this->postJson($path, [])->assertUnprocessable();
        $this->postJson($path, ['password' => 'weak'])->assertUnprocessable();
        $this->assertNull($invitation->fresh()->used_at);
    }

    public function test_a_removed_guardian_link_stops_acceptance(): void
    {
        $invitation = $this->issue();
        GuardianLink::query()->update(['status' => 'ended']);
        $this->postJson('/api/v2/guardian/invitations/'.$invitation->encrypted_token.'/accept', ['password' => 'SafeInvitationPassword123'])->assertNotFound();
    }

    public function test_parent_cannot_issue_invitations_or_obtain_the_secret_from_the_staff_endpoint(): void
    {
        $this->withToken($this->schoolA->tokens['farid'])->postJson('/api/v2/guardians/'.$this->schoolA->ids['farid_user'].'/invitations')->assertForbidden();
    }

    public function test_staff_cannot_invite_a_guardian_from_another_school(): void
    {
        $admin = $this->superAdmin();
        $this->withToken($admin->createToken('staff-test')->plainTextToken)->postJson('/api/v2/guardians/'.$this->schoolB->ids['farid_user'].'/invitations')->assertNotFound();
    }

    public function test_reminders_run_once_per_window_and_never_after_expiry(): void
    {
        $invitation = $this->issue();
        $invitation->update(['created_at' => now()->subDays(3)]);
        $service = app(GuardianInvitations::class);
        $this->assertSame(1, $service->remind());
        $this->assertSame(0, $service->remind());
        $invitation->update(['created_at' => now()->subDays(6)]);
        $this->assertSame(1, $service->remind());
        $this->assertSame(0, $service->remind());
        $invitation->update(['expires_at' => now()->subSecond()]);
        $this->assertSame(0, $service->remind());
    }

    private function issue(): GuardianInvitation
    {
        return app(GuardianInvitations::class)->issue(User::findOrFail($this->schoolA->ids['farid_user']), $this->superAdmin());
    }
}
