<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Jobs\Platform\SendMail;
use App\Mail\Platform\PlatformStaffInvitationMail;
use App\Models\Platform\AuditLog;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\PlatformStaffInvitation;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Platform\Saas\PlatformStaff;
use App\Services\Platform\Saas\PlatformStaffAccess;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\PassesPlatformTwoFactor;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * Platform staff roles (SA.1): each HQ page and action checks the role on the
 * staff member's grant; Owners invite staff by email, who choose their own
 * password. engineering/specs/platform/platform-roles.md.
 */
class PlatformRolesTest extends TestCase
{
    use PassesPlatformTwoFactor;
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->forget();
    }

    private function staff(string $role, string $email = ''): User
    {
        $email = $email !== '' ? $email : $role.'@platform.example';
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => ucfirst($role), 'last_name' => 'Operator', 'email' => $email, 'mobile' => '', 'password' => 'Synthetic-roles-password-582!', 'status' => 1]));
        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => $role, 'expires_at' => now()->addHour()]);

        return $user;
    }

    /** @return array{user: User, url: string} */
    private function invite(User $owner, array $changes = []): array
    {
        return app(PlatformStaff::class)->invite($owner, $changes + ['first_name' => 'Sipho', 'last_name' => 'Ndlovu', 'email' => 'Sipho@Platform.Example', 'role' => 'support', 'days' => 30, 'reason' => 'Joins the support desk']);
    }

    public function test_each_role_reaches_only_its_pages_and_actions(): void
    {
        $owner = $this->staff('owner');
        $sales = $this->staff('sales');
        $finance = $this->staff('finance');
        $school = self::SCHOOL_B_ID;

        $this->actingAsHq($sales)->get('/saas')->assertOk()->assertDontSee('Set up a school');
        $this->get('/saas/schools/'.$school)->assertOk()->assertDontSee('Change package')->assertDontSee('Change status')->assertSee('Your role cannot change modules')
            // Links and forms another role's pages need are not offered either.
            ->assertDontSee('School record, contacts and contracts')->assertDontSee('Audit log for this school')->assertDontSee('Send the invitation again');
        $this->get('/saas/schools/create')->assertForbidden();
        $this->put('/saas/schools/'.$school.'/package', ['package_key' => 'standard'])->assertForbidden();
        $this->put('/saas/schools/'.$school.'/status', ['status' => 'suspended', 'reason' => 'Should never be allowed'])->assertForbidden();
        $this->get('/saas/staff')->assertForbidden();

        $this->actingAsHq($finance)->get('/saas/schools/'.$school)->assertOk()->assertSee('Change package')->assertDontSee('Change status')
            ->assertSee('School record, contacts and contracts')->assertSee('Audit log for this school');
        $this->get('/saas/audit')->assertOk()->assertSee('Export CSV');
        $this->actingAsHq($this->staff('support'))->get('/saas/audit')->assertOk()->assertDontSee('Export CSV');
        $this->actingAsHq($finance);
        $this->put('/saas/schools/'.$school.'/package', ['package_key' => 'standard'])->assertRedirect();
        $this->put('/saas/schools/'.$school.'/status', ['status' => 'suspended', 'reason' => 'Should never be allowed'])->assertForbidden();

        $this->actingAsHq($owner)->get('/saas')->assertOk()->assertSee('Set up a school')->assertSee('Platform staff');
        $this->get('/saas/staff')->assertOk()->assertSee($sales->email);

        // A role removed from the config grants nothing.
        PlatformAdminGrant::query()->where('user_id', $sales->id)->update(['role' => 'retired']);
        $this->assertFalse(app(PlatformStaffAccess::class)->can($sales->fresh(), 'schools.view'));
        $this->actingAsHq($sales)->get('/saas')->assertForbidden();
    }

    public function test_an_owner_invites_staff_who_choose_their_own_password(): void
    {
        Queue::fake();
        $owner = $this->staff('owner');
        $invited = $this->invite($owner);
        $user = $invited['user'];

        $this->assertSame('sipho@platform.example', $user->email);
        $this->assertSame(0, SchoolMembership::query()->where('user_id', $user->id)->count());
        $this->assertSame('support', PlatformAdminGrant::query()->where('user_id', $user->id)->value('role'));
        $this->assertStringNotContainsString(basename($invited['url']), (string) json_encode(PlatformStaffInvitation::query()->get()->makeVisible('token_hash')->toArray()));

        // The email goes out once the invitation is stored (after commit), and re-checks before sending.
        app(PlatformStaff::class)->deliver((int) PlatformStaffInvitation::query()->where('user_id', $user->id)->value('id'), $user->email, $invited['url'], $user->id);
        $job = Queue::pushed(SendMail::class)->first(fn ($job) => $job->mailable instanceof PlatformStaffInvitationMail);
        $this->assertNotNull($job);
        $this->assertSame($invited['url'], $job->mailable->invitationUrl);

        $this->get($invited['url'])->assertOk()->assertSee('sipho@platform.example')->assertHeader('Cache-Control', 'no-store, private');
        $this->post($invited['url'], ['password' => 'Sipho-chose-this-password-731!', 'password_confirmation' => 'Sipho-chose-this-password-731!'])->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('Sipho-chose-this-password-731!', $user->fresh()->password));
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->get($invited['url'])->assertNotFound();

        $this->keepSession($this->post('/login', ['email' => 'sipho@platform.example', 'password' => 'Sipho-chose-this-password-731!']))->assertRedirect('/saas');
        // Choosing the password leads into setting up two-factor sign-in (SA.2) before any HQ page.
        $this->get('/saas')->assertRedirect(route('saas.two-factor.enrol'));
        $this->get('/saas/staff')->assertRedirect(route('saas.two-factor.enrol'));
        $this->get('/saas/two-factor')->assertOk()->assertSee('Set up two-factor sign-in');
        $this->passPlatformTwoFactor($user);
        $this->get('/saas')->assertOk();
        $this->get('/saas/staff')->assertForbidden();

        // A queued email for a used invitation is never sent.
        Mail::fake();
        $job->handle();
        Mail::assertNothingSent();
    }

    public function test_invitations_refuse_school_accounts_and_stop_when_access_ends(): void
    {
        $owner = $this->staff('owner');
        $this->expectValidation(fn () => $this->invite($owner, ['email' => $this->superAdmin()->email]), 'email');

        $invited = $this->invite($owner);
        app(PlatformStaff::class)->revoke($owner, $invited['user']->id, 'Changed plans before starting');
        $this->get($invited['url'])->assertNotFound();
        $this->assertTrue(AuditLog::query()->where('action', 'platform_staff_revoked')->exists());
        $this->assertFalse(app(PlatformStaff::class)->canDeliver((int) PlatformStaffInvitation::query()->where('user_id', $invited['user']->id)->value('id'), $invited['user']->email));
    }

    public function test_the_install_always_keeps_an_active_owner(): void
    {
        $owner = $this->staff('owner');
        $other = $this->staff('operations');

        $this->expectValidation(fn () => app(PlatformStaff::class)->change($owner, $owner->id, ['role' => 'support', 'days' => 30, 'reason' => 'Stepping back from ownership']), 'role');
        $this->expectValidation(fn () => app(PlatformStaff::class)->revoke($other, $owner->id, 'Trying to remove the last owner'), 'role');

        app(PlatformStaff::class)->change($owner, $other->id, ['role' => 'owner', 'days' => 30, 'reason' => 'Shares ownership now']);
        app(PlatformStaff::class)->change($owner, $owner->id, ['role' => 'support', 'days' => 30, 'reason' => 'Stepping back from ownership']);
        $this->assertSame('support', PlatformAdminGrant::query()->where('user_id', $owner->id)->value('role'));
        $grant = AuditLog::query()->where('action', 'platform_staff_role_granted')->latest('id')->firstOrFail();
        $this->assertSame('Stepping back from ownership', $grant->after['reason']);
        $this->assertSame('owner', $grant->before['role']);
    }

    public function test_a_disabled_or_deleted_owner_does_not_count_as_the_remaining_owner(): void
    {
        $owner = $this->staff('owner');
        $disabled = $this->staff('owner', 'disabled-owner@platform.example');
        $deleted = $this->staff('owner', 'deleted-owner@platform.example');
        app(CurrentSchool::class)->acrossSchools(function () use ($disabled, $deleted): void {
            User::query()->whereKey($disabled->id)->update(['status' => 0]);
            User::query()->findOrFail($deleted->id)->delete();
        });

        $this->expectValidation(fn () => app(PlatformStaff::class)->change($owner, $owner->id, ['role' => 'support', 'days' => 30, 'reason' => 'Stepping back from ownership']), 'role');
        $this->expectValidation(fn () => app(PlatformStaff::class)->revoke($owner, $owner->id, 'Trying to remove the last owner'), 'role');
        $this->assertSame('owner', PlatformAdminGrant::query()->where('user_id', $owner->id)->value('role'));

        // The usable Owner may still demote the accounts that can no longer sign in.
        app(PlatformStaff::class)->revoke($owner, $disabled->id, 'Account disabled after leaving');
        $this->assertFalse((bool) PlatformAdminGrant::query()->where('user_id', $disabled->id)->value('active'));
    }

    public function test_the_staff_page_forms_validate_and_save(): void
    {
        Queue::fake();
        $owner = $this->staff('owner');
        $this->actingAsHq($owner)->post('/saas/staff', ['first_name' => 'Lindiwe', 'last_name' => 'Khumalo', 'email' => 'lindiwe@platform.example', 'role' => 'content', 'days' => 400, 'reason' => 'Writes help content'])
            ->assertSessionHasErrors('days');
        $this->post('/saas/staff', ['first_name' => 'Lindiwe', 'last_name' => 'Khumalo', 'email' => 'lindiwe@platform.example', 'role' => 'content', 'days' => 60, 'reason' => 'Writes help content'])
            ->assertRedirect('/saas/staff')->assertSessionHas('success');
        $lindiwe = User::query()->where('email', 'lindiwe@platform.example')->sole();
        $this->put('/saas/staff/'.$lindiwe->id, ['role' => 'sales', 'days' => 10, 'reason' => 'Moves to the sales team'])->assertRedirect('/saas/staff');
        $this->assertSame('sales', PlatformAdminGrant::query()->where('user_id', $lindiwe->id)->value('role'));
        $this->post('/saas/staff/'.$lindiwe->id.'/invitation')->assertRedirect('/saas/staff');
        $this->assertSame(2, PlatformStaffInvitation::query()->where('user_id', $lindiwe->id)->count());
        $this->assertSame(1, PlatformStaffInvitation::query()->where('user_id', $lindiwe->id)->whereNull('used_at')->count(), 'a new link replaces the old one');
        $this->post('/saas/staff/'.$lindiwe->id.'/revoke', ['reason' => 'Left the company'])->assertRedirect('/saas/staff');
        $this->assertFalse((bool) PlatformAdminGrant::query()->where('user_id', $lindiwe->id)->value('active'));
        // Only platform staff: a school's Super Admin cannot reach it.
        $this->newBrowserSession()->actingAs($this->superAdmin())->get('/saas/staff')->assertForbidden();
    }

    private function expectValidation(callable $call, string $field): void
    {
        try {
            $call();
            $this->fail('Expected a validation error on '.$field);
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($field, $e->errors());
        }
    }
}
