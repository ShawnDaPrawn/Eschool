<?php

declare(strict_types=1);

namespace Tests\Feature\Growth;

use App\Jobs\Growth\CheckProgressBands;
use App\Listeners\Growth\QueueProgressBands;
use App\Models\Academics\MarkRelease;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Growth\ProgressBandAlert;
use App\Models\Growth\ProgressBandState;
use App\Models\Notification;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\NotificationDelivery;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\SchoolMembership;
use App\Models\Teacher;
use App\Services\Academics\MarkRecording;
use App\Services\Academics\MarkSnapshots;
use App\Services\Academics\TeachingGroups;
use App\Services\Growth\ProgressBands;
use App\Services\Growth\ProgressNotices;
use App\Services\Platform\Notifications\DeliveryGuard;
use App\Services\Platform\Notifications\DeliveryLog;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Bus;
use Tests\Concerns\UsesMasteryScenario;
use Tests\TestCase;

class ProgressAlertsTest extends TestCase
{
    use UsesMasteryScenario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
        Bus::fake();
    }

    private function notices(): ProgressNotices
    {
        return app(ProgressNotices::class);
    }

    private function parentApproval(): void
    {
        $parent = $this->actor('farid');
        $parent->forceFill(['email_verified_at' => now()])->save();
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $parent->id], ['status' => 'active', 'can_approve' => true]);
        LearnerApproval::create(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $parent->id, 'consent_version' => config('identity.consent_version'), 'consented_at' => now()]);
    }

    public function test_missing_evidence_is_not_zero_and_only_band_transitions_create_deduplicated_alerts(): void
    {
        $this->notices()->group($this->group);
        $this->assertSame(0, ProgressBandAlert::count());
        $this->assertSame('insufficient_evidence', ProgressBandState::sole()->band);
        $this->capture('8.00', '8.00');
        $this->notices()->group($this->group);
        $this->notices()->group($this->group);
        $this->assertSame(1, ProgressBandAlert::count());
        $this->assertSame(1, Notification::where('type', ProgressNotices::STAFF_TYPE)->count());
        $this->capture('9.00', '9.00', 1);
        $this->notices()->group($this->group);
        $this->assertSame(1, ProgressBandAlert::count());
        $this->capture('0.00', '0.00', 2);
        $this->notices()->group($this->group);
        $this->assertSame(2, ProgressBandAlert::count());
        $this->assertSame('support', ProgressBandState::sole()->band);
        $notice = Notification::where('type', ProgressNotices::STAFF_TYPE)->latest('id')->firstOrFail();
        $this->assertSame(['link', 'progress_alert_id'], array_keys($notice->delivery_payload));
        $this->assertStringNotContainsString('Asha', $notice->message);
        $this->assertTrue($this->notices()->allows($this->actor(), ProgressNotices::STAFF_TYPE, $notice->delivery_payload));
    }

    public function test_staff_alerts_require_fresh_role_allocation_membership_school_and_correct_audience(): void
    {
        $this->capture('8.00', '8.00');
        $this->notices()->group($this->group);
        $notice = Notification::where('type', ProgressNotices::STAFF_TYPE)->sole();
        $this->assertFalse($this->notices()->allows($this->actor('farid'), ProgressNotices::STAFF_TYPE, $notice->delivery_payload));
        $this->assertFalse($this->notices()->allows($this->actor('theo'), ProgressNotices::STAFF_TYPE, $notice->delivery_payload));
        $this->assertFalse($this->notices()->allows($this->actor(), ProgressNotices::TYPE, $notice->delivery_payload));
        $this->assertSame([], app(Notifier::class)->send($this->actor('farid'), ProgressNotices::STAFF_TYPE, 'Generic', 'Generic', null, $notice));
        $this->actor()->syncRoles([SchoolRoles::COUNSELLOR]);
        $this->assertFalse($this->notices()->allows($this->actor(), ProgressNotices::STAFF_TYPE, $notice->delivery_payload));
        $this->actor()->syncRoles([SchoolRoles::TEACHER]);
        TeachingAllocation::query()->update(['ends_on' => '2026-02-09']);
        $this->assertFalse($this->notices()->allows($this->actor(), ProgressNotices::STAFF_TYPE, $notice->delivery_payload));
        $actor = $this->actor();
        app(CurrentSchool::class)->run(2, function () use ($notice, $actor) {
            $this->assertFalse($this->notices()->allows($actor, ProgressNotices::STAFF_TYPE, $notice->delivery_payload));
            $this->expectException(ModelNotFoundException::class);
            $this->notices()->group($this->group);
        });
    }

    public function test_family_alerts_use_frozen_released_marks_and_disappear_after_approval_or_guardian_withdrawal(): void
    {
        $this->parentApproval();
        $this->capture('8.00', '8.00');
        $this->notices()->group($this->group);
        $this->assertSame(0, Notification::where('type', ProgressNotices::TYPE)->count());
        $this->release();
        $release = MarkRelease::latest('id')->firstOrFail();
        $this->notices()->released((int) $release->id);
        $this->notices()->released((int) $release->id);
        $notice = Notification::where('type', ProgressNotices::TYPE)->sole();
        $this->assertTrue($this->notices()->allows($this->actor('farid'), ProgressNotices::TYPE, $notice->delivery_payload));
        $this->assertFalse($this->notices()->allows($this->actor('gina'), ProgressNotices::TYPE, $notice->delivery_payload));
        $this->capture('0.00', '0.00', 1);
        $this->assertTrue($this->notices()->allows($this->actor('farid'), ProgressNotices::TYPE, $notice->delivery_payload));
        LearnerApproval::query()->update(['withdrawn_at' => now()]);
        $this->assertFalse($this->notices()->allows($this->actor('farid'), ProgressNotices::TYPE, $notice->delivery_payload));
        LearnerApproval::query()->update(['withdrawn_at' => null]);
        GuardianLink::query()->where('guardian_user_id', $this->actor('farid')->id)->update(['status' => 'ended']);
        $this->assertFalse($this->notices()->allows($this->actor('farid'), ProgressNotices::TYPE, $notice->delivery_payload));
    }

    public function test_queued_listener_and_worker_keep_school_context_and_skip_disabled_module(): void
    {
        $event = new OutboxEvent(['school_id' => 1, 'type' => 'academics.marks_changed', 'payload' => ['group_id' => $this->group]]);
        app(QueueProgressBands::class)->handle($event);
        Bus::assertDispatched(CheckProgressBands::class, fn ($j) => $j->schoolId === 1 && $j->groupId === $this->group && $j->releaseId === null);
        $this->capture('8.00', '8.00');
        app(CurrentSchool::class)->run(2, function () {
            (new CheckProgressBands(1, $this->group))->handle(app(CurrentSchool::class), $this->notices());
            $this->assertSame(2, app(CurrentSchool::class)->id());
        });
        $this->assertSame(1, ProgressBandAlert::count());
        SchoolMembership::where('user_id', $this->actor()->id)->update(['status' => 'ended']);
        $notice = Notification::where('type', ProgressNotices::STAFF_TYPE)->sole();
        $this->assertFalse($this->notices()->allows($this->actor(), ProgressNotices::STAFF_TYPE, $notice->delivery_payload));
    }

    public function test_transport_rechecks_current_source_and_destination_before_sending(): void
    {
        $this->capture('8.00', '8.00');
        $this->notices()->group($this->group);
        $actor = $this->actor();
        $actor->forceFill(['fcm_id' => 'synthetic-progress-device', 'device_type' => 'android'])->save();
        $notice = Notification::where('type', ProgressNotices::STAFF_TYPE)->sole();
        $delivery = new NotificationDelivery(['school_id' => 1, 'recipient_user_id' => $actor->id,
            'recipient_hash' => DeliveryLog::hash('synthetic-progress-device')]);
        $guard = app(DeliveryGuard::class);
        $this->assertTrue($guard->allows($delivery, ProgressNotices::STAFF_TYPE, 'push', 'synthetic-progress-device', $notice->delivery_payload));
        $this->assertTrue($guard->allows($delivery, ProgressNotices::STAFF_TYPE, 'push', 'synthetic-progress-device', array_map(fn ($v) => (string) $v, $notice->delivery_payload)));
        $this->assertFalse($this->notices()->allows($actor, ProgressNotices::STAFF_TYPE, ['progress_alert_id' => true]));
        $actor->forceFill(['fcm_id' => 'replacement-device'])->save();
        $this->assertFalse($guard->allows($delivery, ProgressNotices::STAFF_TYPE, 'push', 'synthetic-progress-device', $notice->delivery_payload));
        $actor->forceFill(['fcm_id' => 'synthetic-progress-device'])->save();
        $this->capture('0.00', '0.00', 1);
        $this->assertFalse($guard->allows($delivery, ProgressNotices::STAFF_TYPE, 'push', 'synthetic-progress-device', $notice->delivery_payload));
    }

    public function test_old_release_replay_cannot_undo_new_family_band_and_settings_queue_current_evidence(): void
    {
        $this->parentApproval();
        $this->capture('8.00', '8.00');
        $this->release();
        $older = (int) MarkRelease::latest('id')->value('id');
        $this->notices()->released($older);
        $this->capture('0.00', '0.00', 1);
        $this->release();
        $newer = (int) MarkRelease::latest('id')->value('id');
        $this->notices()->released($newer);
        $this->notices()->released($older);
        $this->assertSame('support', ProgressBandState::where('audience', 'family')->sole()->band);
        $this->assertSame(2, ProgressBandAlert::count());
        app(ProgressBands::class)->configure($this->superAdmin(), ['high_bp' => 8500, 'secure_bp' => 6500, 'minimum_coverage_bp' => 3000, 'expected_revision' => 0]);
        $event = OutboxEvent::where('type', 'growth.progress_bands_configured')->sole();
        app(QueueProgressBands::class)->handle($event);
        Bus::assertDispatched(CheckProgressBands::class, fn ($job) => $job->groupId === $this->group);
        Bus::assertDispatched(CheckProgressBands::class, fn ($job) => $job->releaseId === $newer);
    }

    public function test_parallel_group_snapshot_cannot_consume_another_learner_transition_or_starve_the_right_teacher(): void
    {
        $group = TeachingGroup::findOrFail($this->group);
        $b = app(TeachingGroups::class)->save($this->superAdmin(), ['name' => 'Parallel maths group', 'subject_id' => $this->subject,
            'grade_level_id' => $group->grade_level_id, 'session_year_id' => $this->year, 'class_section_id' => null, 'active' => true]);
        Enrolment::create(['learner_id' => $this->schoolA->ids['ben'], 'class_section_id' => $this->schoolA->ids['section_a'], 'session_year_id' => $this->year,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic parallel enrolment']);
        app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', ['teacher_id' => Teacher::where('user_id', $this->actor('theo')->id)->value('id'),
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Own parallel teaching allocation'], (int) $b->id);
        app(TeachingGroups::class)->dated($this->superAdmin(), 'memberships', ['learner_id' => $this->schoolA->ids['ben'], 'mark_rule' => 'catch_up',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Own parallel learner'], (int) $b->id);
        app(MarkRecording::class)->capture($this->actor('theo'), (int) $b->id, $this->task, ['rows' => [[
            'learner_id' => $this->schoolA->ids['ben'], 'expected_revision' => 0, 'disposition' => 'recorded', 'mark' => null,
            'question_marks' => [['question_id' => $this->questions[0], 'mark' => '8.00'], ['question_id' => $this->questions[1], 'mark' => '8.00']],
        ]]]);
        app(MarkSnapshots::class)->recompute((int) $b->id, $this->term);
        $this->capture('8.00', '8.00');
        $this->notices()->group($this->group);
        $this->assertFalse(ProgressBandState::where('learner_id', $this->schoolA->ids['ben'])->exists(), 'Another group must not consume this learner transition.');
        $this->notices()->group((int) $b->id);
        $state = ProgressBandState::where('learner_id', $this->schoolA->ids['ben'])->where('audience', 'staff')->sole();
        $alert = ProgressBandAlert::where('state_id', $state->id)->sole();
        $this->assertSame((int) $b->id, (int) $alert->group_id);
        $notice = Notification::findOrFail($alert->notification_id);
        $this->assertTrue($this->notices()->allows($this->actor('theo'), ProgressNotices::STAFF_TYPE, $notice->delivery_payload));
        $this->assertFalse($this->notices()->allows($this->actor(), ProgressNotices::STAFF_TYPE, $notice->delivery_payload));
        $fanout = OutboxEvent::where('type', 'notification.requested')->get()->first(fn ($event) => (int) $event->payload['notification_id'] === (int) $notice->id);
        $this->assertNotNull($fanout);
        $this->assertSame([(int) $this->actor('theo')->id], $fanout->payload['user_ids']);
    }

    public function test_ended_and_future_memberships_do_not_observe_existing_snapshots(): void
    {
        $this->capture('8.00', '8.00');
        app(MarkSnapshots::class)->recompute($this->group, $this->term);
        $membership = TeachingGroupMembership::where('teaching_group_id', $this->group)->where('learner_id', $this->schoolA->ids['asha'])->sole();
        $membership->update(['ends_on' => '2026-02-09']);
        $this->notices()->group($this->group);
        $this->assertSame(0, ProgressBandState::count());
        $membership->update(['starts_on' => '2026-02-11', 'ends_on' => '2026-12-31']);
        $this->notices()->group($this->group);
        $this->assertSame(0, ProgressBandState::count());
        $this->assertSame(0, ProgressBandAlert::count());
    }
}
