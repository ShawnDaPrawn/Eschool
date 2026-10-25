<?php

declare(strict_types=1);

namespace Tests\Feature\Consultations;

use App\Jobs\Platform\SendMail;
use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityStaff;
use App\Models\Consultations\Consultation;
use App\Models\Consultations\Participation;
use App\Models\Consultations\Response;
use App\Models\Notification;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Consultations\ConsultationNotices;
use App\Services\Consultations\Consultations;
use App\Services\ParentPolicyService;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\NotificationRules;
use App\Services\Platform\Notifications\NotificationVisibility;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Notifications\PrivateNoticeScopes;
use App\Services\Platform\Notifications\StaffPrivateNotices;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class ConsultationNoticesTest extends TestCase
{
    use UsesTwoSchools;

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10)->setTime(10, 0));
        Queue::fake();
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        app(ModuleGate::class)->change('consultations', true, $this->superAdmin());
        $this->parent = User::findOrFail($this->schoolA->ids['farid_user']);
        ParentPolicyService::acceptCurrent($this->parent);
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->schoolA->ids['session_year'],
            'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic notice electorate']);
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->parent->id], ['status' => 'active', 'relationship' => 'father']);
    }

    private function create(bool $scheduled = false, bool $staff = false, ?array $audience = null): Consultation
    {
        $service = app(Consultations::class);
        $receipt = $service->save($this->superAdmin(), null, ['operation_id' => (string) Str::uuid(), 'revision' => 0,
            'session_year_id' => $this->schoolA->ids['session_year'], 'type' => 'poll', 'title' => 'PRIVATE synthetic ballot title', 'instructions' => 'PRIVATE instructions',
            'anonymous' => true, 'results_visibility' => 'after_close', 'opens_at' => ($scheduled ? now()->addHour() : now()->subHour())->toISOString(), 'closes_at' => now()->addDay()->toISOString(),
            'audience' => $audience ?? ['kind' => 'school', 'parents' => true, 'include_staff' => $staff],
            'questions' => [['kind' => 'single', 'label' => 'PRIVATE question', 'required' => true, 'options' => [['label' => 'A'], ['label' => 'B']]]]]);
        $service->publish($this->superAdmin(), $receipt['id'], ['operation_id' => (string) Str::uuid(), 'revision' => 1]);

        return Consultation::findOrFail($receipt['id']);
    }

    private function notices(Consultation $row): void
    {
        app(ConsultationNotices::class)->opened((int) $row->id, (int) $row->revision);
    }

    private function familyNotice(): Notification
    {
        return Notification::where('type', 'consultations.opened')->sole();
    }

    private function linked(Notification $notice): void
    {
        UserNotification::firstOrCreate(['notification_id' => $notice->id, 'user_id' => $this->parent->id]);
    }

    private function visible(): Builder
    {
        return NotificationVisibility::forUser(Notification::query(), (int) $this->parent->id, 4);
    }

    public function test_scheduler_opens_once_and_outbox_fans_out_only_generic_metadata(): void
    {
        $row = $this->create(true);
        $this->notices($row);
        $this->assertSame(0, Notification::where('type', 'like', 'consultations.%')->count());
        $this->travel(61)->minutes();
        $this->assertSame(1, app(Consultations::class)->advance());
        $this->assertSame(0, app(Consultations::class)->advance());
        $event = OutboxEvent::where('type', 'consultations.opened')->sole();
        $this->assertSame('dispatched', Outbox::dispatchOne((int) $event->id));
        $notice = $this->familyNotice();
        $this->assertStringNotContainsString('PRIVATE', $notice->title.$notice->message.json_encode($notice->delivery_payload));
        $this->assertSame(['link', 'consultation_id', 'consultation_revision'], array_keys($notice->delivery_payload));
        $this->assertSame('/consultations/'.$row->id, $notice->delivery_payload['link']);
        $this->assertSame(0, Participation::count());
        $this->assertSame(0, Response::count());
        $this->notices($row->fresh());
        $this->assertSame(1, Notification::where('type', 'consultations.opened')->count());
    }

    public function test_delivery_and_inbox_recheck_guardian_before_any_private_body_read(): void
    {
        $row = $this->create();
        $this->notices($row);
        $notice = $this->familyNotice();
        $this->linked($notice);
        $guard = app(PrivateNoticeScopes::class);
        $this->assertTrue($guard->permitsDelivery($this->parent, $notice->type, $notice->delivery_payload));
        $this->assertNotNull($this->visible()->find($notice->id));
        GuardianLink::where('guardian_user_id', $this->parent->id)->update(['status' => 'ended']);
        $this->assertFalse($guard->permitsDelivery($this->parent, $notice->type, $notice->delivery_payload));
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->assertSame(0, $this->visible()->whereKey($notice->id)->count());
        foreach (DB::getQueryLog() as $query) {
            $this->assertStringNotContainsString('`participation`', $query['query']);
            $this->assertStringNotContainsString('`consultation_responses`', $query['query']);
            $this->assertStringNotContainsString('select * from `notifications`', strtolower($query['query']));
        }
        DB::disableQueryLog();
        $this->assertSame([], app(Notifier::class)->send($this->parent, $notice->type, $notice->title, $notice->message, null, $notice, ['in_app']));
    }

    public function test_prepared_inbox_query_rechecks_guardian_and_role_at_execution(): void
    {
        $row = $this->create();
        $this->notices($row);
        $notice = $this->familyNotice();
        $this->linked($notice);
        $prepared = $this->visible()->whereKey($notice->id);
        GuardianLink::where('guardian_user_id', $this->parent->id)->update(['status' => 'ended']);
        $this->assertSame(0, $prepared->count());
        GuardianLink::where('guardian_user_id', $this->parent->id)->update(['status' => 'active']);
        $prepared = $this->visible()->whereKey($notice->id);
        $this->parent->syncRoles(['Facilitator']);
        $this->assertSame(0, $prepared->count());
    }

    public function test_foreign_school_substituted_source_revision_and_link_never_grant_delivery(): void
    {
        $row = $this->create();
        $this->notices($row);
        $notice = $this->familyNotice();
        $guard = app(PrivateNoticeScopes::class);
        $this->assertFalse($guard->permitsDelivery($this->parent, $notice->type, array_replace($notice->delivery_payload, ['consultation_revision' => 999])));
        $this->assertFalse($guard->permitsDelivery($this->parent, $notice->type, array_replace($notice->delivery_payload, ['link' => '//foreign.example/'])));
        app(CurrentSchool::class)->run(2, fn () => $this->assertFalse($guard->permitsDelivery($this->parent, $notice->type, $notice->delivery_payload)));
        $this->linked($notice);
        app(CurrentSchool::class)->run(2, fn () => $this->assertSame(0, $this->visible()->whereKey($notice->id)->count()));
    }

    public function test_role_conversion_and_platform_identity_remove_retained_notice_access(): void
    {
        $row = $this->create();
        $this->notices($row);
        $notice = $this->familyNotice();
        $this->linked($notice);
        $this->parent->syncRoles(['Facilitator']);
        $this->assertSame(0, $this->visible()->whereKey($notice->id)->count());
        $this->parent->syncRoles(['Parent']);
        $this->assertNotNull($this->visible()->find($notice->id));
        PlatformStaffIdentity::create(['user_id' => $this->parent->id]);
        $this->assertNull($this->visible()->find($notice->id));
    }

    public function test_cancelled_expired_or_disabled_sources_cannot_generate_or_deliver_notices(): void
    {
        $row = $this->create();
        $this->notices($row);
        $notice = $this->familyNotice();
        $this->linked($notice);
        app(Consultations::class)->end($this->superAdmin(), (int) $row->id, 'cancel', ['operation_id' => (string) Str::uuid(), 'revision' => $row->revision, 'reason' => 'Synthetic cancellation']);
        $this->assertFalse(app(PrivateNoticeScopes::class)->permitsDelivery($this->parent, $notice->type, $notice->delivery_payload));
        $this->assertNull($this->visible()->find($notice->id));
        $this->notices($row->fresh());
        $this->assertSame(1, Notification::where('type', 'consultations.opened')->count());
        $this->travel(2)->days();
        $this->assertFalse(app(PrivateNoticeScopes::class)->permitsDelivery($this->parent, $notice->type, $notice->delivery_payload));
        app(ModuleGate::class)->change('consultations', false, $this->superAdmin());
        $this->assertFalse(app(PrivateNoticeScopes::class)->permitsDelivery($this->parent, $notice->type, $notice->delivery_payload));
    }

    public function test_notification_rule_optout_and_channel_claims_are_preserved(): void
    {
        $row = $this->create();
        $this->notices($row);
        $notice = $this->familyNotice();
        $rules = app(NotificationRules::class);
        $rules->choose($this->parent, $notice->type, 'in_app', false);
        $service = app(Notifier::class);
        $this->assertSame([], $service->send($this->parent, $notice->type, $notice->title, $notice->message, null, $notice, ['in_app']));
        $rules->choose($this->parent, $notice->type, 'in_app', true);
        $this->assertSame(['in_app'], $service->send($this->parent, $notice->type, $notice->title, $notice->message, null, $notice, ['in_app']));
        $this->assertSame([], $service->send($this->parent, $notice->type, $notice->title, $notice->message, null, $notice, ['in_app']));
    }

    public function test_real_queued_email_rechecks_guardian_and_suppresses_after_withdrawal(): void
    {
        $row = $this->create();
        $this->notices($row);
        $notice = $this->familyNotice();
        $this->parent->forceFill(['email_verified_at' => now()])->save();
        app(NotificationRules::class)->choose($this->parent, $notice->type, 'email', true);
        $this->assertSame(['email'], app(Notifier::class)->send($this->parent, $notice->type, $notice->title, $notice->message, $notice->delivery_payload['link'], $notice, ['email']));
        $job = Queue::pushed(SendMail::class)->sole();
        $this->assertSame(rtrim((string) config('identity.portal_url'), '/').'/consultations/'.$row->id, $job->mailable->noticeUrl);
        GuardianLink::where('guardian_user_id', $this->parent->id)->update(['status' => 'ended']);
        Mail::fake();
        $job->handle();
        Mail::assertNothingSent();
        $this->assertSame('suppressed', DB::table('notification_deliveries')->where('delivery_key', $job->deliveryKey)->value('status'));
    }

    public function test_staff_and_family_intents_have_correct_implemented_portal_paths(): void
    {
        $row = $this->create(false, true);
        $this->notices($row);
        $staff = Notification::where('type', 'consultations.staff_opened')->sole();
        $this->assertSame('/school-consultations/'.$row->id, $staff->delivery_payload['link']);
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->assertTrue(app(PrivateNoticeScopes::class)->permitsDelivery($teacher, $staff->type, $staff->delivery_payload));
        $this->assertFalse(app(PrivateNoticeScopes::class)->permitsDelivery($this->parent, $staff->type, $staff->delivery_payload));
        $this->assertStringNotContainsString('PRIVATE', $staff->title.$staff->message);
    }

    public function test_facilitator_staff_inbox_requires_actual_owned_group_and_withdraws_retained_intent(): void
    {
        app(ModuleGate::class)->change('groups', true, $this->superAdmin());
        $actor = User::findOrFail($this->schoolA->ids['tara_user']);
        $actor->syncRoles(['Facilitator']);
        $group = ActivityGroup::create(['session_year_id' => $this->schoolA->ids['session_year'], 'name' => 'Synthetic coached group', 'discipline' => 'Sport', 'season' => '2026',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'gender' => 'any', 'active' => true]);
        $grant = ActivityStaff::create(['activity_group_id' => $group->id, 'user_id' => $actor->id, 'role' => 'facilitator', 'cascade' => false,
            'active' => true, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic own group']);
        $row = $this->create(false, true, ['kind' => 'activity_group', 'target_id' => (int) $group->id, 'parents' => false, 'include_staff' => true]);
        $this->notices($row);
        $notice = Notification::where('type', 'consultations.staff_opened')->sole();
        UserNotification::create(['notification_id' => $notice->id, 'user_id' => $actor->id]);
        $service = app(StaffPrivateNotices::class);
        $inbox = $service->inbox($actor);
        $this->assertSame('consultations', $inbox['items'][0]['feature']);
        $this->assertSame(1, $inbox['unread_count']);
        $grant->update(['active' => false]);
        $this->assertSame([], $service->inbox($actor)['items']);
        $this->assertSame(0, $service->inbox($actor)['unread_count']);
        $this->assertFalse(app(PrivateNoticeScopes::class)->permitsDelivery($actor, $notice->type, $notice->delivery_payload));
    }

    public function test_a_failed_outer_opening_transaction_leaves_no_notice_or_event(): void
    {
        DB::beginTransaction();
        $row = $this->create();
        $this->notices($row);
        $this->assertSame(1, Notification::where('type', 'consultations.opened')->count());
        DB::rollBack();
        $this->assertSame(0, Notification::where('type', 'like', 'consultations.%')->count());
        $this->assertSame(0, OutboxEvent::where('type', 'consultations.opened')->count());
    }
}
