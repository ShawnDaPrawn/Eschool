<?php

declare(strict_types=1);

namespace Tests\Feature\Growth;

use App\Models\Academics\MarkRelease;
use App\Models\Academics\TeachingAllocation;
use App\Models\Notification;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Growth\ProgressNotices;
use App\Services\ParentPolicyService;
use App\Services\Platform\Notifications\NotificationVisibility;
use App\Services\Platform\Notifications\PrivateNoticeScopes;
use App\Services\Platform\Notifications\StaffPrivateNotices;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Bus;
use Tests\Concerns\UsesMasteryScenario;
use Tests\TestCase;

class ProgressNoticeVisibilityTest extends TestCase
{
    use UsesMasteryScenario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
        Bus::fake();
        foreach (['tara', 'farid', 'theo', 'asha'] as $key) {
            $this->actor($key)->forceFill(['password_change_required' => false, 'email_verified_at' => now()])->save();
            ParentPolicyService::acceptCurrent($this->actor($key));
        }
    }

    private function link(Notification $notice, User $actor): void
    {
        UserNotification::firstOrCreate(['user_id' => $actor->id, 'notification_id' => $notice->id]);
    }

    private function staffNotice(): Notification
    {
        $this->capture('8.00', '8.00');
        app(ProgressNotices::class)->group($this->group);
        $notice = Notification::where('type', ProgressNotices::STAFF_TYPE)->sole();
        $this->link($notice, $this->actor());

        return $notice;
    }

    private function visible(User $actor): array
    {
        return NotificationVisibility::forUser(Notification::whereIn('type', [ProgressNotices::STAFF_TYPE, ProgressNotices::TYPE]), (int) $actor->id, 2)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function test_staff_inbox_filters_source_before_counts_body_and_read_after_allocation_or_role_withdrawal(): void
    {
        $notice = $this->staffNotice();
        $inbox = app(StaffPrivateNotices::class)->inbox($this->actor());
        $this->assertSame(1, $inbox['unread_count']);
        $this->assertSame('progress', $inbox['items'][0]['feature']);
        $this->assertSame(['id', 'feature', 'title', 'message', 'writable', 'read_at'], array_keys($inbox['items'][0]));
        $this->assertSame([(int) $notice->id], $this->visible($this->actor()));
        app(StaffPrivateNotices::class)->markRead($this->actor(), (int) $notice->id);
        $this->assertSame(0, app(StaffPrivateNotices::class)->inbox($this->actor())['unread_count']);
        TeachingAllocation::query()->update(['ends_on' => '2026-02-09']);
        $this->assertSame([], $this->visible($this->actor()));
        $this->assertSame([], app(StaffPrivateNotices::class)->inbox($this->actor())['items']);
        TeachingAllocation::query()->update(['ends_on' => '2026-12-31']);
        $this->actor()->syncRoles([SchoolRoles::COUNSELLOR]);
        $this->assertSame([], $this->visible($this->actor()));
        $this->assertSame([], app(StaffPrivateNotices::class)->inbox($this->actor())['items']);
        $this->expectException(ModelNotFoundException::class);
        app(StaffPrivateNotices::class)->markRead($this->actor(), (int) $notice->id);
    }

    public function test_stale_transition_and_forged_link_or_source_never_load_an_inbox_body(): void
    {
        $notice = $this->staffNotice();
        $original = $notice->delivery_payload;
        $notice->forceFill(['delivery_payload' => ['progress_alert_id' => 999999, 'link' => '/learner-notes']])->save();
        $this->assertSame([], $this->visible($this->actor()));
        $notice->refresh()->forceFill(['delivery_payload' => array_replace($original, ['link' => 'https://example.test/private'])])->save();
        $this->assertSame([], $this->visible($this->actor()));
        $this->assertFalse(app(PrivateNoticeScopes::class)->permitsDelivery($this->actor(), ProgressNotices::STAFF_TYPE, $notice->delivery_payload));
        $notice->refresh()->forceFill(['delivery_payload' => $original])->save();
        $this->capture('0.00', '0.00', 1);
        $this->assertSame([], $this->visible($this->actor()));
    }

    public function test_family_progress_counts_require_own_frozen_release_approval_and_current_guardian_link(): void
    {
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->actor('farid')->id], ['status' => 'active', 'can_approve' => true]);
        LearnerApproval::create(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->actor('farid')->id, 'consent_version' => config('identity.consent_version'), 'consented_at' => now()]);
        $this->capture('8.00', '8.00');
        $this->release();
        app(ProgressNotices::class)->released((int) MarkRelease::latest('id')->value('id'));
        $notice = Notification::where('type', ProgressNotices::TYPE)->sole();
        $this->link($notice, $this->actor('farid'));
        $this->link($notice, $this->actor('theo'));
        $this->assertSame([(int) $notice->id], $this->visible($this->actor('farid')));
        $this->assertSame([], $this->visible($this->actor('theo')));
        $this->capture('0.00', '0.00', 1);
        $this->assertSame([(int) $notice->id], $this->visible($this->actor('farid')), 'Unreleased changes cannot revoke a frozen family projection.');
        GuardianLink::where('guardian_user_id', $this->actor('farid')->id)->update(['status' => 'ended']);
        $this->assertSame([], $this->visible($this->actor('farid')));
    }

    public function test_built_queries_still_observe_allocation_role_and_state_revocation_in_sql(): void
    {
        $notice = $this->staffNotice();
        $query = fn () => NotificationVisibility::forUser(Notification::where('type', ProgressNotices::STAFF_TYPE), (int) $this->actor()->id, 2);
        $allocated = $query();
        TeachingAllocation::query()->update(['ends_on' => '2026-02-09']);
        $this->assertSame([], $allocated->pluck('id')->all());
        TeachingAllocation::query()->update(['ends_on' => '2026-12-31']);
        $role = $query();
        $this->actor()->syncRoles([SchoolRoles::COUNSELLOR]);
        $this->assertSame([], $role->pluck('id')->all());
        $this->actor()->syncRoles([SchoolRoles::TEACHER]);
        $state = $query();
        $this->capture('0.00', '0.00', 1);
        app(ProgressNotices::class)->group($this->group);
        $this->assertSame([], $state->whereKey($notice->id)->pluck('id')->all());
    }

    public function test_other_school_platform_identity_and_impersonation_cannot_reuse_progress_receipts(): void
    {
        $this->staffNotice();
        $actor = $this->actor();
        app(CurrentSchool::class)->run(2, function () use ($actor) {
            $this->assertSame([], $this->visible($actor));
        });
        app(CurrentSchool::class)->impersonate(School::findOrFail(1));
        $this->assertSame([], $this->visible($this->actor()));
        app(CurrentSchool::class)->set(1);
        PlatformStaffIdentity::create(['user_id' => $this->actor()->id, 'active' => true]);
        $this->assertSame([], $this->visible($this->actor()));
    }
}
