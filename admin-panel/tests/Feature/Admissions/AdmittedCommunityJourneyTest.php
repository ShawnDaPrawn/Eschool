<?php

declare(strict_types=1);

namespace Tests\Feature\Admissions;

use App\Models\Academics\GradeLevel;
use App\Models\Admissions\ApplicationLink;
use App\Models\Admissions\IntakeGrade;
use App\Models\Cards\CardScanLog;
use App\Models\ClassSection;
use App\Models\Notification;
use App\Models\Parents;
use App\Models\Platform\GuardianInvitation;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\ProfileFieldDefinition;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\Activities\ActivityGroups;
use App\Services\Activities\Chat\GroupChatAccess;
use App\Services\Activities\Chat\GroupChatNotices;
use App\Services\Activities\Chat\GroupChatThreads;
use App\Services\Admissions\AdmissionOffers;
use App\Services\Admissions\ApplicationLinks;
use App\Services\Admissions\Applications;
use App\Services\Admissions\EnrolApplication;
use App\Services\Admissions\IntakeCatalogue;
use App\Services\Cards\CardPortraits;
use App\Services\Cards\CardReaders;
use App\Services\Cards\Cards;
use App\Services\Cards\CardScans;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\GuardianInvitations;
use App\Services\Platform\Identity\LearnerInvitations;
use App\Services\Platform\Notifications\DeliveryGuard;
use App\Services\Platform\Notifications\DeliveryLog;
use App\Services\Platform\Notifications\NotificationVisibility;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/** One composed identity journey; no pre-approved demo learner substitutes for enrolment. */
class AdmittedCommunityJourneyTest extends TestCase
{
    use UsesTwoSchools;

    private function operation(array $input): array
    {
        return ['operation_id' => (string) Str::uuid()] + $input;
    }

    private function refuses(int $status, callable $action): void
    {
        try {
            $action();
            $this->fail('Expected the retired or foreign community authority to be refused.');
        } catch (HttpExceptionInterface $error) {
            $this->assertSame($status, $error->getStatusCode());
        } catch (ModelNotFoundException) {
            $this->assertSame(404, $status);
        }
    }

    public function test_new_admission_needs_approval_for_group_card_and_notices_and_rechecks_revocation_and_school(): void
    {
        $this->setUpTwoSchools();
        $this->travelTo('2026-02-10 12:00:00 UTC');
        Queue::fake();
        foreach (['intake', 'groups', 'chat', 'cards'] as $module) {
            config(['modules.'.$module => ['always_on' => true]]);
        }
        // These community boundaries must enforce approval even on a legacy login setting.
        config(['identity.approval_enforced' => false]);
        $types = config('notification_types');
        $types[GroupChatNotices::FAMILY]['channels']['email'] = 'default_on';
        config(['notification_types' => $types]);
        SchoolMembership::query()->update(['starts_on' => '2026-01-01', 'ends_on' => null]);
        $admin = $this->superAdmin();
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $year = $this->schoolA->ids['session_year'];
        $grade = (int) GradeLevel::where('code', '5')->value('id');
        ClassSection::with('class')->findOrFail($this->schoolA->ids['section_a'])->class->forceFill(['grade_level_id' => $grade])->save();
        SessionYear::whereKey($year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $fields = ProfileFieldDefinition::where('profile_type', 'learner')->whereIn('source', ['user.first_name', 'user.last_name', 'user.dob'])->pluck('id', 'source');
        $intake = app(IntakeCatalogue::class)->save($admin, $this->operation(['revision' => 0, 'session_year_id' => $year,
            'name' => 'Synthetic joined admission', 'state' => 'open', 'opens_at' => '2026-02-01T00:00', 'closes_at' => '2026-03-01T00:00',
            'application_fee_cents' => 0, 'keep_for_days' => 30, 'field_ids' => $fields->values()->all(), 'required_documents' => [],
            'grades' => [['grade_level_id' => $grade, 'places' => 1]]]));
        $ownerKey = bin2hex(random_bytes(32));
        $application = app(Applications::class)->begin($intake, (int) IntakeGrade::where('intake_id', $intake->id)->value('id'), (string) Str::uuid(), $ownerKey,
            ['first_name' => 'Synthetic', 'last_name' => 'Guardian', 'email' => 'joined-admission@example.test', 'relationship' => 'guardian'],
            [$fields['user.first_name'] => 'Joined learner', $fields['user.last_name'] => 'Synthetic', $fields['user.dob'] => '2015-01-10']);
        $resumeToken = bin2hex(random_bytes(32));
        ApplicationLink::create(['application_id' => $application->id, 'token_hash' => hash('sha256', $resumeToken), 'email_hash' => $application->email_hash, 'expires_at' => now()->addMinutes(30)]);
        $resume = app(ApplicationLinks::class)->consume($intake, $resumeToken);
        $application = app(Applications::class)->submit((int) $application->id, $resume['key'], 1);
        app(AdmissionOffers::class)->issue($admin, $this->operation(['application_ids' => [$application->id]]));
        $application = app(AdmissionOffers::class)->decide((int) $application->id, $resume['key'], (int) $application->fresh()->revision, 'accept');
        $application = app(EnrolApplication::class)->enrol($admin, (int) $application->id,
            $this->operation(['revision' => $application->revision, 'starts_on' => '2026-02-10', 'category_id' => $this->schoolA->ids['category']]));
        $learner = Students::with('user')->findOrFail($application->learner_id);
        $student = $learner->user;
        $parent = User::where('email', 'joined-admission@example.test')->firstOrFail();
        $this->assertNull($parent->mobile);
        $this->assertSame('', Parents::where('user_id', $parent->id)->value('mobile'));
        $approvals = app(ApprovalService::class);
        $this->assertFalse($approvals->approved($learner));
        $invitation = GuardianInvitation::where('guardian_user_id', $parent->id)->sole();
        app(GuardianInvitations::class)->accept($invitation->encrypted_token, 'JoinedGuardian2038!Safe', Request::create('/api/guardian-invitations/accept', 'POST'));
        $parent = $parent->fresh();
        $this->assertNotNull($parent->email_verified_at);
        $this->assertFalse($parent->password_change_required);
        [$setup] = app(LearnerInvitations::class)->issueLink($student, $parent, $admin);
        $this->assertTrue(app(LearnerInvitations::class)->complete($setup, 'JoinedLearner2038!Safe'));
        $this->assertFalse($student->fresh()->password_change_required);
        $this->assertFalse($approvals->approved($learner));

        $groups = app(ActivityGroups::class);
        $dates = ['starts_on' => '2026-02-10', 'ends_on' => '2026-12-31'];
        $group = $groups->save($admin, null, $this->operation($dates + ['revision' => 0, 'session_year_id' => $year,
            'name' => 'Synthetic new admission group', 'discipline' => 'Culture', 'season' => '2026', 'gender' => 'any', 'active' => true]));
        $id = $group['id'];
        $groups->allocation($admin, $id, null, true, $this->operation($dates + ['revision' => 0, 'user_id' => $teacher->id, 'role' => 'facilitator', 'cascade' => false, 'reason' => 'Synthetic joined staff allocation']));
        $groups->allocation($admin, $id, null, false, $this->operation($dates + ['revision' => 0, 'learner_id' => $learner->id, 'role' => 'member', 'reason' => 'Actual newly enrolled learner']));
        $access = app(GroupChatAccess::class);
        $cards = app(Cards::class);
        $issue = $this->operation(['person_id' => $student->id, 'type' => 'learner']);
        $this->refuses(404, fn () => $access->group($student, $id, 'student'));
        $this->refuses(404, fn () => $access->group($parent, $id, 'parent'));
        $this->refuses(403, fn () => $cards->issue($admin, $issue));

        $approvals->decide($parent, $learner, 'approve', config('identity.consent_version'));
        $this->assertSame($id, (int) $access->group($student, $id, 'student')->id);
        $this->assertSame($id, (int) $access->group($parent, $id, 'parent')->id);
        Storage::fake(CardPortraits::DISK);
        $this->app->bind(UploadScanner::class, fn () => new class implements UploadScanner
        {
            public function scan(string $bytes): array
            {
                return ['clean' => true, 'engine' => 'synthetic-test-only', 'signature' => null];
            }
        });
        app(CardPortraits::class)->upload($parent, (int) $student->id, 'learner', (string) Str::uuid(), UploadedFile::fake()->image('portrait.jpg', 120, 160));
        $card = $cards->issue($admin, $issue);
        $proof = $cards->credential($student, $card['id']);
        $this->assertSame((int) $student->id, $cards->credential($parent, $card['id'])['card']['person_id']);
        $reader = app(CardReaders::class)->register($admin, $this->operation(['name' => 'Synthetic joined gate reader', 'kind' => 'gate',
            'public_key' => base64_encode(sodium_crypto_box_publickey(sodium_crypto_box_keypair())), 'days' => 1]));
        $scanIntent = $this->operation(['credential' => $proof['credential']]);
        $scan = app(CardScans::class)->qr($reader['credential'], $scanIntent);
        $this->assertSame((int) $student->id, $scan['person_id']);

        $notices = [];
        foreach ([[$student, 'student', 'learners', 3], [$parent, 'parent', 'guardians', 4]] as [$actor, $persona, $audience, $portal]) {
            $message = app(GroupChatThreads::class)->create($teacher, $id, $this->operation(['persona' => 'staff', 'kind' => 'announcement', 'audience' => $audience,
                'title' => 'Synthetic joined notice', 'body' => 'Synthetic admitted-only group text', 'language' => 'en']));
            $event = OutboxEvent::where('type', 'group_chat.message_posted')->where('payload->message_id', $message['message_id'])->sole();
            $this->assertSame('dispatched', Outbox::dispatchOne((int) $event->id));
            $notice = Notification::where('source_key', 'group-chat:'.$message['message_id'].':'.$actor->id.':'.$persona)->sole();
            $this->assertSame(['in_app'], app(Notifier::class)->send($actor, $notice->type, $notice->title, $notice->message, null, $notice, ['in_app']));
            $query = NotificationVisibility::forUser(Notification::whereKey($notice->id), (int) $actor->id, $portal);
            $this->assertSame(1, (clone $query)->count());
            $notices[] = [$actor, $notice, $query];
        }
        $parentNotice = $notices[1][1];
        $key = DeliveryLog::queued('mail', $parentNotice->type, (int) $parent->id, $parent->email);
        $delivery = DeliveryLog::attempt($key, 1);
        $deliveryGuard = app(DeliveryGuard::class);
        $this->assertTrue($deliveryGuard->allows($delivery, $parentNotice->type, 'email', $parent->email, $parentNotice->delivery_payload));

        app(CurrentSchool::class)->run(2, function () use ($access, $student, $parent, $id, $cards, $card, $notices): void {
            $this->refuses(404, fn () => $access->group($student, $id, 'student'));
            $this->refuses(404, fn () => $cards->credential($parent, $card['id']));
            foreach ($notices as [$actor, $notice, $query]) {
                $this->assertSame(0, (clone $query)->count());
                $this->assertFalse(app(GroupChatNotices::class)->allows($actor, $notice->type, $notice->delivery_payload));
            }
        });
        $this->assertSame($id, (int) $access->group($student, $id, 'student')->id);
        $approvals->decide($parent, $learner, 'withdraw', config('identity.consent_version'));
        $this->refuses(404, fn () => $access->group($student, $id, 'student'));
        $this->refuses(404, fn () => $access->group($parent, $id, 'parent'));
        $this->refuses(403, fn () => $cards->credential($parent, $card['id']));
        $this->refuses(403, fn () => app(CardScans::class)->qr($reader['credential'], $scanIntent));
        $this->assertSame(1, CardScanLog::count());
        foreach ($notices as [$actor, $notice, $query]) {
            $this->assertSame(0, (clone $query)->count());
            $this->assertFalse(app(GroupChatNotices::class)->allows($actor, $notice->type, $notice->delivery_payload));
            $this->assertSame([], app(Notifier::class)->send($actor, $notice->type, $notice->title, $notice->message, null, $notice));
        }
        $this->assertFalse($deliveryGuard->allows($delivery, $parentNotice->type, 'email', $parent->email, $parentNotice->delivery_payload));
    }
}
