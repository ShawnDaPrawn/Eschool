<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Jobs\Platform\SendMail;
use App\Mail\Platform\StaffInvitationMail;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\StaffInvitation;
use App\Models\User;
use App\Services\Platform\Identity\StaffInvitations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * Staff choose their own password from an emailed one-use link.
 * Specification: engineering/specs/platform/staff-invitations.md.
 */
class StaffInvitationsTest extends TestCase
{
    use UsesTwoSchools;

    private const PASSWORD = 'Synthetic-staff-password-582!';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        Queue::fake();
    }

    private function person(string $name): User
    {
        return User::findOrFail($this->schoolA->ids[$name.'_user']);
    }

    /**
     * The email for the person's latest invitation. Each test runs in a
     * transaction that never commits, so the after-commit send is replayed here.
     */
    private function mailFor(User $person): SendMail
    {
        app(StaffInvitations::class)->deliver(StaffInvitation::where('user_id', $person->id)->latest('id')->firstOrFail());
        $job = Queue::pushed(SendMail::class)->last(fn ($job) => $job->mailable instanceof StaffInvitationMail && $job->mailable->to[0]['address'] === $person->email);
        $this->assertNotNull($job, 'no invitation was queued for '.$person->email);

        return $job;
    }

    private function path(SendMail|StaffInvitation $source): string
    {
        $url = $source instanceof SendMail ? $source->mailable->invitationUrl : app(StaffInvitations::class)->url((string) $source->encrypted_token);

        return (string) parse_url($url, PHP_URL_PATH);
    }

    public function test_a_teacher_chooses_their_password_from_the_emailed_link_and_every_old_sign_in_ends(): void
    {
        $tara = $this->person('tara');
        $tara->createToken('old phone');
        DB::table('sessions')->insert(['id' => 'old-session', 'user_id' => $tara->id, 'payload' => '', 'last_activity' => time()]);
        $this->actingAs($this->superAdmin(), 'web')->post(route('people-profiles.invitation', $tara->id))->assertRedirect();
        $path = $this->path($this->mailFor($tara));
        auth()->forgetGuards();

        $this->get($path)->assertOk()->assertSee('Choose your password')->assertSee($tara->email);
        $this->post($path, ['password' => self::PASSWORD, 'password_confirmation' => 'Something-else-123'])->assertSessionHasErrors('password');
        $this->post($path, ['password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD])->assertRedirect(route('login'));

        $tara->refresh();
        $this->assertTrue(Hash::check(self::PASSWORD, $tara->password));
        $this->assertNotNull($tara->email_verified_at);
        $this->assertFalse((bool) $tara->password_change_required);
        $this->assertSame(0, $tara->tokens()->count(), 'app sign-ins end');
        $this->assertSame(0, DB::table('sessions')->where('user_id', $tara->id)->count(), 'browser sessions end');
        $this->get($path)->assertNotFound();
        $this->post($path, ['password' => 'Another-password-991!', 'password_confirmation' => 'Another-password-991!'])->assertNotFound();
        $this->assertNotNull(StaffInvitation::where('user_id', $tara->id)->sole()->accepted_at);
    }

    public function test_the_link_is_bound_to_the_address_the_school_and_current_staff_membership(): void
    {
        $tara = $this->person('tara');
        $email = $tara->email;
        $issue = fn () => app(StaffInvitations::class)->issue($tara->fresh(), $this->superAdmin());
        $issue();
        $first = $this->path($this->mailFor($tara));

        // A newer link cancels the older one.
        $issue();
        $this->get($first)->assertNotFound();
        $job = $this->mailFor($tara);
        $this->get($this->path($job))->assertOk();

        // A changed email address, an expired link or an ended membership opens nothing, and the queued email is not sent.
        foreach (['email', 'expiry', 'membership'] as $change) {
            $job = $this->mailFor($tara);
            if ($change === 'email') {
                DB::table('users')->where('id', $tara->id)->update(['email' => 'moved@example.org']);
            } elseif ($change === 'expiry') {
                StaffInvitation::whereKey($job->mailable->invitationId)->update(['expires_at' => now()->subMinute()]);
            } else {
                SchoolMembership::query()->where('school_id', 1)->where('user_id', $tara->id)->update(['status' => 'ended']);
            }
            $this->get($this->path($job))->assertNotFound();
            Mail::fake();
            $job->handle();
            Mail::assertNothingSent();
            DB::table('users')->where('id', $tara->id)->update(['email' => $email]);
            SchoolMembership::query()->where('school_id', 1)->where('user_id', $tara->id)->update(['status' => 'active']);
            $issue();
        }
        $this->assertFalse(Hash::check(self::PASSWORD, $tara->fresh()->password));
    }

    public function test_only_permitted_staff_can_send_invitations_and_never_to_family_or_other_schools(): void
    {
        $tara = $this->person('tara');
        $theo = $this->person('theo');
        $this->actingAs($tara, 'web')->post(route('people-profiles.invitation', $theo->id))->assertForbidden();
        $this->actingAs($this->superAdmin(), 'web');
        $this->post(route('people-profiles.invitation', $this->person('farid')->id))->assertNotFound();
        $this->post(route('people-profiles.invitation', $this->person('asha')->id))->assertNotFound();
        $this->post(route('people-profiles.invitation', $this->superAdmin()->id))->assertForbidden();
        $this->post(route('people-profiles.invitation', $this->schoolB->ids['tara_user']))->assertNotFound();
        $this->assertSame(0, StaffInvitation::acrossSchools()->count());

        // A staff editor may invite a teacher, but never a Super Admin: a changed email must not hand that account over.
        $theo->givePermissionTo('staff-edit', 'teacher-edit');
        $this->actingAs($theo->fresh(), 'web')->post(route('people-profiles.invitation', $this->superAdmin()->id))->assertForbidden();
        $this->post(route('people-profiles.invitation', $tara->id))->assertRedirect();
        $this->assertSame(1, StaffInvitation::where('user_id', $tara->id)->count());
    }

    public function test_the_profile_page_offers_the_invitation_only_to_people_who_may_send_it(): void
    {
        $tara = $this->person('tara');
        $this->actingAs($this->superAdmin(), 'web')->get(route('people-profiles.index', $tara->id))->assertOk()->assertSee('Send sign-in invitation');
        $this->get(route('people-profiles.index', $this->person('farid')->id))->assertDontSee('Send sign-in invitation');
    }

    public function test_the_invitation_page_and_email_never_carry_the_token_into_the_audit_log(): void
    {
        $tara = $this->person('tara');
        app(StaffInvitations::class)->issue($tara, $this->superAdmin());
        $job = $this->mailFor($tara);
        $token = basename($this->path($job));
        $this->assertStringContainsString($token, $job->mailable->render());

        $this->assertStringNotContainsString($token, json_encode(DB::table('audit_log')->get()));
        $this->assertStringNotContainsString($token, json_encode(StaffInvitation::query()->get()->toArray()));
        $this->assertSame(hash('sha256', $token), DB::table('staff_invitations')->value('token_hash'));
    }
}
