<?php

declare(strict_types=1);

namespace Tests\Feature\FamilyForms;

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Activities\ActivityStaff;
use App\Models\FamilyForms\ConsentRecipient;
use App\Models\FamilyForms\ConsentRequest;
use App\Models\Notification;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\Students;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\FamilyForms\ConsentNotices;
use App\Services\FamilyForms\Consents;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\DeliveryGuard;
use App\Services\Platform\Notifications\DeliveryLog;
use App\Services\Platform\Notifications\NotificationVisibility;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class ConsentNoticesTest extends TestCase
{
    use UsesTwoSchools;

    private User $admin;

    private User $parent;

    private User $teacher;

    private int $learner;

    private ActivityGroup $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        Queue::fake();
        $this->travelTo('2026-02-10 12:00:00 UTC');
        SchoolMembership::query()->update(['starts_on' => '2026-01-01', 'ends_on' => null]);
        config(['modules.family_forms' => ['always_on' => true], 'modules.groups' => ['always_on' => true]]);
        $this->admin = $this->superAdmin();
        $this->parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->parent->forceFill(['email_verified_at' => now()])->save();
        $this->teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->learner = $this->schoolA->ids['asha'];
        $year = $this->schoolA->ids['session_year'];
        Enrolment::create(['learner_id' => $this->learner, 'session_year_id' => $year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
        GuardianLink::updateOrCreate(['learner_id' => $this->learner, 'guardian_user_id' => $this->parent->id], ['status' => 'active', 'relationship' => 'father', 'can_approve' => true]);
        app(ApprovalService::class)->decide($this->parent, Students::findOrFail($this->learner), 'approve', config('identity.consent_version'));
        $this->group = ActivityGroup::create(['session_year_id' => $year, 'name' => 'Synthetic consent group', 'discipline' => 'Culture', 'season' => '2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true]);
        ActivityStaff::create(['activity_group_id' => $this->group->id, 'user_id' => $this->teacher->id, 'role' => 'facilitator', 'cascade' => false, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic allocation', 'revision' => 1]);
        ActivityMembership::create(['activity_group_id' => $this->group->id, 'learner_id' => $this->learner, 'role' => 'member', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic group', 'revision' => 1]);
    }

    private function create(array $changes = [], ?User $actor = null): array
    {
        return app(Consents::class)->create($actor ?? $this->teacher, $changes + ['operation_id' => (string) Str::uuid(), 'activity_group_id' => $this->group->id,
            'title' => 'Synthetic private trip', 'wording' => 'Synthetic exact consent wording <script>unsafe()</script>', 'event_starts_at' => '2026-02-20T10:00:00+02:00', 'event_ends_at' => '2026-02-20T12:00:00+02:00', 'respond_by' => '2026-02-19T12:00:00+02:00', 'learner_ids' => [$this->learner]]);
    }

    private function response(array $request, array $changes = []): array
    {
        return $changes + ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'fingerprint' => $request['fingerprint'], 'decision' => 'yes', 'acknowledged' => true, 'signed_name' => $this->parent->full_name];
    }

    private function notice(): Notification
    {
        return Notification::where('type', ConsentNotices::TYPE)->sole();
    }

    private function inbox(User $actor): Builder
    {
        return NotificationVisibility::forUser(Notification::where('type', ConsentNotices::TYPE), (int) $actor->id, 4);
    }

    public function test_publication_replay_and_recovery_record_one_generic_notice_without_private_text(): void
    {
        $id = (string) Str::uuid();
        $one = $this->create(['operation_id' => $id]);
        $this->assertSame($one, $this->create(['operation_id' => $id]));
        app(ConsentNotices::class)->requested($one['id']);
        $notice = $this->notice();
        $this->assertSame('Activity permission requested', $notice->title);
        $this->assertSame('/family-forms', $notice->delivery_payload['link']);
        $this->assertSame(['link', 'consent_recipient_id', 'consent_notice_id', 'consent_revision'], array_keys($notice->delivery_payload));
        $this->assertStringNotContainsString('Synthetic exact consent wording', json_encode($notice->getAttributes(), JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('Synthetic private trip', $notice->title.' '.$notice->message);
        $this->assertTrue(app(ConsentNotices::class)->allows($this->parent, ConsentNotices::TYPE, $notice->delivery_payload));
        $this->assertFalse(app(ConsentNotices::class)->allows($this->teacher, ConsentNotices::TYPE, $notice->delivery_payload));
        $notifier = app(Notifier::class);
        $this->assertContains('in_app', $notifier->send($this->parent, $notice->type, $notice->title, $notice->message, '/family-forms', $notice));
        $this->assertSame([], $notifier->send($this->parent, $notice->type, $notice->title, $notice->message, '/family-forms', $notice));
        $this->assertSame(1, UserNotification::where('notification_id', $notice->id)->count());
        $this->assertSame(1, $this->inbox($this->parent)->count());
    }

    public function test_parent_http_response_binds_only_new_consent_actions_to_the_actual_recipient(): void
    {
        $this->create();
        $notice = $this->notice();
        UserNotification::firstOrCreate(['notification_id' => $notice->id, 'user_id' => $this->parent->id]);
        $legacy = app(Notifier::class)->makeNotice('custom', 'Synthetic legacy notice', 'Generic text', '/notifications');
        UserNotification::firstOrCreate(['notification_id' => $legacy->id, 'user_id' => $this->parent->id]);
        $answer = $this->withToken($this->schoolA->tokens['farid'])->getJson('/api/parent/get-notification')
            ->assertOk()->assertJsonPath('error', false);
        $rows = collect($answer->json('data.data'))->keyBy('id');
        $this->assertSame((int) $this->parent->id, $rows[$notice->id]['reader_id']);
        $this->assertSame(1, $rows[$notice->id]['school_id']);
        $this->assertArrayNotHasKey('reader_id', $rows[$legacy->id]);
        GuardianLink::where('learner_id', $this->learner)->where('guardian_user_id', $this->parent->id)->update(['can_approve' => false]);
        $this->app['auth']->forgetGuards();
        $this->withToken($this->schoolA->tokens['farid'])->getJson('/api/parent/get-notification')
            ->assertOk()->assertDontSee('Activity permission requested')->assertSee('Synthetic legacy notice');
    }

    public function test_guardian_withdrawal_rechecks_prepared_inbox_and_queued_transport_before_text(): void
    {
        $this->create();
        $notice = $this->notice();
        UserNotification::create(['notification_id' => $notice->id, 'user_id' => $this->parent->id]);
        $query = $this->inbox($this->parent);
        $this->assertSame(1, (clone $query)->count());
        $key = DeliveryLog::queued('mail', $notice->type, (int) $this->parent->id, $this->parent->email);
        $delivery = DeliveryLog::attempt($key, 1);
        $this->assertTrue(app(DeliveryGuard::class)->allows($delivery, $notice->type, 'email', $this->parent->email, $notice->delivery_payload));
        GuardianLink::where('learner_id', $this->learner)->where('guardian_user_id', $this->parent->id)->update(['status' => 'inactive']);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->assertSame(0, (clone $query)->count());
        foreach (DB::getQueryLog() as $sql) {
            if (str_contains($sql['query'], 'from `consent_requests`')) {
                $this->assertStringNotContainsString('`wording`', $sql['query']);
            }
            if (str_contains($sql['query'], 'from `notifications`')) {
                $this->assertStringNotContainsString('`message`', $sql['query']);
                $this->assertStringNotContainsString('`title`', $sql['query']);
            }
        }
        DB::disableQueryLog();
        $this->assertFalse(app(DeliveryGuard::class)->allows($delivery, $notice->type, 'email', $this->parent->email, $notice->delivery_payload));
        $this->assertSame([], app(Notifier::class)->send($this->parent, $notice->type, $notice->title, $notice->message, '/family-forms', $notice));
    }

    public function test_answer_cancellation_deadline_and_module_readonly_retire_actionable_notices(): void
    {
        $request = $this->create();
        $notice = $this->notice();
        UserNotification::create(['notification_id' => $notice->id, 'user_id' => $this->parent->id]);
        app(Consents::class)->respond($this->parent, $request['id'], $this->learner, $this->response($request));
        $this->assertFalse(app(ConsentNotices::class)->allows($this->parent, $notice->type, $notice->delivery_payload));
        $this->assertSame(0, $this->inbox($this->parent)->count());
        $second = $this->create();
        $secondNotice = Notification::where('type', ConsentNotices::TYPE)->latest('id')->firstOrFail();
        app(Consents::class)->cancel($this->teacher, $second['id'], ['operation_id' => (string) Str::uuid(), 'revision' => 1]);
        $this->assertFalse(app(ConsentNotices::class)->allows($this->parent, $secondNotice->type, $secondNotice->delivery_payload));
        $third = $this->create();
        $thirdNotice = Notification::where('type', ConsentNotices::TYPE)->latest('id')->firstOrFail();
        ConsentRequest::whereKey($third['id'])->update(['respond_by' => now()->subSecond()->utc()]);
        $this->assertFalse(app(ConsentNotices::class)->allows($this->parent, $thirdNotice->type, $thirdNotice->delivery_payload));
        $fourth = $this->create();
        $fourthNotice = Notification::where('type', ConsentNotices::TYPE)->latest('id')->firstOrFail();
        config(['modules.family_forms' => ['default' => true, 'legacy' => true]]);
        SchoolModule::updateOrCreate(['school_id' => 1, 'module' => 'family_forms'], ['enabled' => true, 'read_only' => true, 'in_package' => true]);
        app(ModuleGate::class)->forget();
        $this->assertFalse(app(ConsentNotices::class)->allows($this->parent, $fourthNotice->type, $fourthNotice->delivery_payload));
    }

    public function test_learner_and_event_scope_withdrawal_recheck_an_already_prepared_notice_query(): void
    {
        $this->create();
        $notice = $this->notice();
        UserNotification::firstOrCreate(['notification_id' => $notice->id, 'user_id' => $this->parent->id]);
        foreach ([
            fn () => SchoolMembership::where('user_id', $this->schoolA->ids['asha_user'])->where('role', 'Student')->update(['status' => 'suspended']),
            fn () => Enrolment::where('learner_id', $this->learner)->update(['ends_on' => '2026-02-09']),
            fn () => ActivityMembership::where('learner_id', $this->learner)->update(['active' => false]),
            fn () => ActivityGroup::whereKey($this->group->id)->update(['active' => false]),
        ] as $withdraw) {
            $query = $this->inbox($this->parent);
            $this->assertSame(1, (clone $query)->count());
            DB::beginTransaction();
            try {
                $withdraw();
                $this->assertSame(0, (clone $query)->count());
                $this->assertFalse(app(ConsentNotices::class)->allows($this->parent, $notice->type, $notice->delivery_payload));
            } finally {
                DB::rollBack();
            }
        }
    }

    public function test_role_password_platform_and_cross_school_cannot_borrow_parent_notice_references(): void
    {
        $this->create();
        $notice = $this->notice();
        $payload = $notice->delivery_payload;
        $other = app(CurrentSchool::class)->run(2, fn () => User::findOrFail($this->schoolB->ids['farid_user']));
        $this->assertFalse(app(ConsentNotices::class)->allows($other, $notice->type, $payload));
        $this->assertFalse(app(CurrentSchool::class)->run(2, fn () => app(ConsentNotices::class)->allows($other, $notice->type, $payload)));
        $this->assertFalse(app(CurrentSchool::class)->acrossSchools(fn () => app(ConsentNotices::class)->allows($this->parent, $notice->type, $payload)));
        app(CurrentSchool::class)->run(1, function () use ($notice, $payload): void {
            app(CurrentSchool::class)->impersonate(app(CurrentSchool::class)->school());
            $this->assertFalse(app(ConsentNotices::class)->allows($this->parent, $notice->type, $payload));
        });
        $this->parent->forceFill(['password_change_required' => true])->save();
        $this->assertFalse(app(ConsentNotices::class)->allows($this->parent, $notice->type, $payload));
        $this->parent->forceFill(['password_change_required' => false])->save();
        PlatformStaffIdentity::create(['user_id' => $this->parent->id, 'active' => true]);
        $this->assertFalse(app(ConsentNotices::class)->allows($this->parent, $notice->type, $payload));
        PlatformStaffIdentity::query()->delete();
        $this->parent->syncRoles(['Facilitator']);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->assertFalse(app(ConsentNotices::class)->allows($this->parent, $notice->type, $payload));
        foreach (DB::getQueryLog() as $sql) {
            $this->assertStringNotContainsString('from `consent_recipients`', $sql['query']);
        }
        DB::disableQueryLog();
    }

    public function test_open_recovery_reaches_a_later_verified_guardian_and_never_revives_deleted_notice(): void
    {
        $mother = User::findOrFail($this->schoolA->ids['mona_user']);
        $mother->forceFill(['email_verified_at' => now()])->save();
        GuardianLink::updateOrCreate(['learner_id' => $this->learner, 'guardian_user_id' => $mother->id], ['status' => 'active', 'can_approve' => true]);
        app(ApprovalService::class)->decide($mother, Students::findOrFail($this->learner), 'approve', config('identity.consent_version'));
        $this->parent->forceFill(['email_verified_at' => null])->save();
        $this->create();
        $own = fn () => Notification::where('type', ConsentNotices::TYPE)->where('source_key', 'like', '%:'.$this->parent->id);
        $this->assertSame(0, $own()->count());
        $motherNotice = $this->notice();
        $this->parent->forceFill(['email_verified_at' => now()])->save();
        $this->artisan('consents:notify-open --limit=1')->assertSuccessful();
        $notice = $own()->sole();
        $this->assertTrue(app(ConsentNotices::class)->allows($this->parent, $notice->type, $notice->delivery_payload));
        $this->assertFalse(app(ConsentNotices::class)->allows($this->parent, $motherNotice->type, $motherNotice->delivery_payload));
        $this->assertFalse(app(ConsentNotices::class)->allows($mother, $notice->type, $notice->delivery_payload));
        $notice->delete();
        $this->artisan('consents:notify-open --limit=1')->assertSuccessful();
        $this->assertSame(0, $own()->count());
        $this->assertSame(2, Notification::withTrashed()->where('type', ConsentNotices::TYPE)->count());
    }

    public function test_rolled_back_publication_leaves_no_notice_outbox_or_recipient_intent(): void
    {
        $before = OutboxEvent::count();
        DB::beginTransaction();
        $this->create();
        $this->assertSame(1, Notification::where('type', ConsentNotices::TYPE)->count());
        DB::rollBack();
        $this->assertSame(0, Notification::where('type', ConsentNotices::TYPE)->count());
        $this->assertSame($before, OutboxEvent::count());
        $this->assertSame(0, ConsentRecipient::count());
    }
}
