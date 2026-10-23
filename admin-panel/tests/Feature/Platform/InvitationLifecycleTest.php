<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Jobs\Platform\SendMail;
use App\Mail\Platform\GuardianInvitationMail;
use App\Mail\Platform\LearnerInvitationMail;
use App\Mail\Platform\MessageRecorder;
use App\Mail\Platform\ViewMail;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerInvitation;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Services\MailService;
use App\Services\Platform\Identity\GuardianInvitations;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Identity\LearnerInvitations;
use App\Services\Platform\Identity\StudentNumbers;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class InvitationLifecycleTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        Queue::fake();
        app(GuardianService::class)->syncLegacy(Students::findOrFail($this->schoolA->ids['asha']));
    }

    private function link(): array
    {
        return app(LearnerInvitations::class)->issueLink(User::findOrFail($this->schoolA->ids['asha_user']), User::findOrFail($this->schoolA->ids['farid_user']), $this->superAdmin());
    }

    public function test_learner_setup_link_stops_when_guardian_mailbox_changes(): void
    {
        [$invitation, $link] = $this->link();
        DB::table('users')->where('id', $this->schoolA->ids['farid_user'])->update(['email' => 'changed@example.org']);
        $this->get($link)->assertOk()->assertSee(trans('learner_setup_link_invalid_title'));
        $this->assertFalse(app(LearnerInvitations::class)->complete($invitation, 'NeverAcceptedPassword123'));
        $this->assertFalse(Hash::check('NeverAcceptedPassword123', User::findOrFail($this->schoolA->ids['asha_user'])->password));
    }

    public function test_ended_or_non_approving_guardians_cannot_reset_a_learner_via_legacy_parent_fields(): void
    {
        [$invitation, $link] = $this->link();
        GuardianLink::query()->where('learner_id', $this->schoolA->ids['asha'])->where('guardian_user_id', $this->schoolA->ids['farid_user'])->update(['can_approve' => false]);
        $this->get($link)->assertSee(trans('learner_setup_link_invalid_title'));
        $this->assertFalse(app(LearnerInvitations::class)->complete($invitation, 'NeverAcceptedPassword123'));
        GuardianLink::query()->where('learner_id', $this->schoolA->ids['asha'])->where('guardian_user_id', $this->schoolA->ids['farid_user'])->update(['status' => 'ended', 'can_approve' => true]);
        $this->assertNotContains($this->schoolA->ids['farid_user'], app(LearnerInvitations::class)->guardiansOf(User::findOrFail($this->schoolA->ids['asha_user']))->pluck('id')->all());
    }

    public function test_an_unbound_legacy_email_secret_requires_replacement(): void
    {
        [$invitation, $link] = $this->link();
        $invitation->update(['email_hash' => null]);
        $this->get($link)->assertSee(trans('learner_setup_link_invalid_title'));
        $guardian = app(GuardianInvitations::class)->issue(User::findOrFail($this->schoolA->ids['farid_user']), $this->superAdmin());
        $guardian->update(['email_hash' => null]);
        $this->getJson('/api/v2/guardian/invitations/'.$guardian->encrypted_token)->assertNotFound();
    }

    public function test_email_edit_clears_verification_and_retires_links_even_if_the_address_changes_back(): void
    {
        [$invitation] = $this->link();
        $guardian = User::findOrFail($this->schoolA->ids['farid_user']);
        $guardian->forceFill(['email_verified_at' => now()])->save();
        $accountInvitation = app(GuardianInvitations::class)->issue($guardian, $this->superAdmin());
        $old = $guardian->email;
        $guardian->forceFill(['email' => 'changed@example.org'])->save();
        $this->assertNull($guardian->email_verified_at);
        $this->assertNotNull($invitation->fresh()->revoked_at);
        $this->assertNotNull($accountInvitation->fresh()->used_at);
        $guardian->forceFill(['email' => $old])->save();
        $this->assertFalse(app(LearnerInvitations::class)->complete($invitation, 'NeverAcceptedPassword123'));
    }

    public function test_ended_membership_stops_guardian_account_acceptance(): void
    {
        $invitation = app(GuardianInvitations::class)->issue(User::findOrFail($this->schoolA->ids['farid_user']), $this->superAdmin());
        SchoolMembership::query()->where('school_id', 1)->where('user_id', $this->schoolA->ids['farid_user'])->update(['status' => 'ended']);
        $path = '/api/v2/guardian/invitations/'.$invitation->encrypted_token;
        $this->getJson($path)->assertNotFound();
        $this->postJson($path.'/accept', ['password' => 'SafeInvitationPassword123'])->assertNotFound();
    }

    public function test_second_school_link_and_printed_code_choose_their_school_by_their_secret(): void
    {
        [$invitation, $url, $codeInvitation, $code, $number] = app(CurrentSchool::class)->run(2, function () {
            $student = Students::findOrFail($this->schoolB->ids['asha']);
            app(GuardianService::class)->syncLegacy($student);
            $service = app(LearnerInvitations::class);
            [$invitation, $url] = $service->issueLink($student->user, User::findOrFail($this->schoolB->ids['farid_user']));
            [$printed, $code] = $service->issueCode($student->user);

            return [$invitation, $url, $printed, $code, app(StudentNumbers::class)->assign($student)];
        });
        $this->get($url)->assertOk()->assertSee('Baobab');
        $found = app(LearnerInvitations::class)->findOpenByCode($number, $code);
        $this->assertSame($codeInvitation->id, $found?->id);
        $this->assertTrue(app(LearnerInvitations::class)->complete($invitation, 'SecondSchoolPassword123'));
        $this->assertSame(1, app(CurrentSchool::class)->id());
        $this->assertDatabaseHas('audit_log', ['school_id' => 2, 'subject_id' => $this->schoolB->ids['asha_user']]);
        $this->assertTrue(Hash::check('SecondSchoolPassword123', User::acrossSchools()->findOrFail($this->schoolB->ids['asha_user'])->password));
        $this->assertFalse(Hash::check('SecondSchoolPassword123', User::findOrFail($this->schoolA->ids['asha_user'])->password));
    }

    public function test_mailbox_change_suppresses_reminders_instead_of_sending_to_a_new_address(): void
    {
        $invitation = app(GuardianInvitations::class)->issue(User::findOrFail($this->schoolA->ids['farid_user']), $this->superAdmin());
        $invitation->update(['created_at' => now()->subDays(3)]);
        DB::table('users')->where('id', $invitation->guardian_user_id)->update(['email' => 'changed@example.org']);
        $this->assertSame(0, app(GuardianInvitations::class)->remind());
        Queue::assertNothingPushed();
    }

    public static function changesWhileQueued(): array
    {
        return [['email'], ['rights'], ['membership'], ['expiry'], ['used']];
    }

    #[DataProvider('changesWhileQueued')]
    public function test_queued_learner_invitation_rechecks_its_authority_before_mail(string $change): void
    {
        app(LearnerInvitations::class)->inviteGuardians(User::findOrFail($this->schoolA->ids['asha_user']));
        $job = Queue::pushed(SendMail::class)->first(fn ($job) => $job->mailable instanceof LearnerInvitationMail
            && $job->mailable->to[0]['address'] === User::findOrFail($this->schoolA->ids['farid_user'])->email);
        $this->assertNotNull($job);
        if ($change === 'email') {
            DB::table('users')->where('id', $this->schoolA->ids['farid_user'])->update(['email' => 'new@example.org']);
        } elseif ($change === 'rights') {
            GuardianLink::query()->where('guardian_user_id', $this->schoolA->ids['farid_user'])->update(['status' => 'ended']);
        } elseif ($change === 'membership') {
            SchoolMembership::query()->where('school_id', 1)->where('user_id', $this->schoolA->ids['farid_user'])->update(['status' => 'ended']);
        } else {
            LearnerInvitation::query()->whereKey($job->mailable->invitationId)->update($change === 'expiry' ? ['expires_at' => now()->subMinute()] : ['used_at' => now()]);
        }
        Mail::fake();
        $job->handle();
        Mail::assertNothingSent();
        $this->assertDatabaseHas('notification_deliveries', ['delivery_key' => $job->deliveryKey, 'status' => 'suppressed']);
    }

    public function test_queued_guardian_invitation_stops_after_relationship_ends(): void
    {
        $invitation = app(GuardianInvitations::class)->issue(User::findOrFail($this->schoolA->ids['farid_user']), $this->superAdmin());
        $invitation->update(['created_at' => now()->subDays(3)]);
        app(GuardianInvitations::class)->remind();
        $job = Queue::pushed(SendMail::class)->first();
        $this->assertInstanceOf(GuardianInvitationMail::class, $job->mailable);
        GuardianLink::query()->where('guardian_user_id', $invitation->guardian_user_id)->update(['status' => 'ended']);
        Mail::fake();
        $job->handle();
        Mail::assertNothingSent();
        $this->assertDatabaseHas('notification_deliveries', ['delivery_key' => $job->deliveryKey, 'status' => 'suppressed']);
    }

    public function test_legacy_unbound_queued_learner_mail_is_suppressed_without_disclosing_its_body(): void
    {
        $recorded = new MessageRecorder;
        $recorded->to('old@example.org')->subject('Old setup');
        $mail = new ViewMail('emails.learner-invitation', ['body' => 'Private learner details', 'school_name' => 'Test school'], $recorded);
        $this->assertTrue(MailService::queue($mail));
        Mail::fake();
        $job = Queue::pushed(SendMail::class)->first();
        $job->handle();
        Mail::assertNothingSent();
        $this->assertDatabaseHas('notification_deliveries', ['delivery_key' => $job->deliveryKey, 'status' => 'suppressed']);
    }
}
