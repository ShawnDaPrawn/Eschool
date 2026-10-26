<?php

declare(strict_types=1);

namespace Tests\Feature\Discipline;

use App\Listeners\Discipline\QueueDisciplineNotices;
use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Discipline\DisciplineNoticeIntent;
use App\Models\Notification;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolModule;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Discipline\DisciplineDetentions;
use App\Services\Discipline\DisciplineFrameworks;
use App\Services\Discipline\DisciplineNotices;
use App\Services\Discipline\DisciplineRecords;
use App\Services\ParentPolicyService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\NotificationVisibility;
use App\Services\Platform\Notifications\StaffPrivateNotices;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\UsesMasteryScenario;
use Tests\TestCase;

class DisciplineNoticesTest extends TestCase
{
    use UsesMasteryScenario;

    private int $grade;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
        Bus::fake();
        $this->grade = (int) TeachingGroup::findOrFail($this->group)->grade_level_id;
        SchoolModule::updateOrCreate(['module' => 'discipline'], ['in_package' => true, 'enabled' => true]);
        app(ModuleGate::class)->forget();
        foreach (['tara', 'theo', 'farid', 'mona', 'asha'] as $key) {
            $this->actor($key)->forceFill(['password_change_required' => false, 'email_verified_at' => now()])->save();
            ParentPolicyService::acceptCurrent($this->actor($key));
        }
        app(DisciplineFrameworks::class)->save($this->superAdmin(), $this->year, ['title' => 'Synthetic framework', 'expected_revision' => 0, 'counter_scope' => 'same_infraction', 'reset' => 'school_year',
            'levels' => [['code' => 'warning', 'name' => 'Warning', 'threshold' => 1, 'kind' => 'sanction', 'sanctions' => ['detention']],
                ['code' => 'review', 'name' => 'Review', 'threshold' => 2, 'kind' => 'sanction', 'sanctions' => ['detention']],
                ['code' => 'hearing', 'name' => 'Hearing', 'threshold' => 3, 'kind' => 'hearing', 'sanctions' => []]],
            'sanctions' => [['code' => 'detention', 'name' => 'Detention', 'kind' => 'detention']],
            'infractions' => [['code' => 'late', 'name' => 'Late arrival', 'base_level' => 'warning']]]);
    }

    private function record(): array
    {
        return app(DisciplineRecords::class)->create($this->actor(), $this->schoolA->ids['asha'], $this->year,
            ['occurred_on' => '2026-02-10', 'infraction_code' => 'late', 'description' => 'Private family factual account', 'staff_note' => 'SECRET staff-only investigation']);
    }

    private function appointHead(): void
    {
        AcademicLeadership::create(['user_id' => $this->actor()->id, 'kind' => 'grade_head', 'grade_level_id' => $this->grade,
            'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic head']);
    }

    private function linkIntents(): void
    {
        foreach (DisciplineNoticeIntent::whereNotNull('notification_id')->get() as $intent) {
            UserNotification::firstOrCreate(['notification_id' => $intent->notification_id, 'user_id' => $intent->user_id]);
        }
    }

    private function visible(User $actor): array
    {
        return NotificationVisibility::forUser(Notification::whereIn('type', DisciplineNotices::TYPES), (int) $actor->id, 2)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function notice(User $actor, string $type, ?int $record = null): Notification
    {
        return Notification::findOrFail(DisciplineNoticeIntent::where('user_id', $actor->id)->where('type', $type)
            ->when($record !== null, fn ($q) => $q->where('record_id', $record))->latest('created_at')->value('notification_id'));
    }

    public function test_record_notices_are_generic_once_and_recheck_current_sources_before_body_or_delivery(): void
    {
        $r = $this->record();
        $service = app(DisciplineNotices::class);
        $service->record($r['id'], 1);
        $count = DisciplineNoticeIntent::count();
        $fanouts = OutboxEvent::where('type', 'notification.requested')->count();
        $service->record($r['id'], 1);
        $this->assertSame($count, DisciplineNoticeIntent::count());
        $this->assertSame($fanouts, OutboxEvent::where('type', 'notification.requested')->count());
        $this->linkIntents();
        $staff = $this->notice($this->actor(), DisciplineNotices::STAFF);
        $family = $this->notice($this->actor('farid'), DisciplineNotices::FAMILY);
        foreach (Notification::whereIn('type', DisciplineNotices::TYPES)->get() as $notice) {
            $this->assertSame(['link', 'discipline_intent_id'], array_keys($notice->delivery_payload));
            $this->assertStringNotContainsString('Private family', $notice->title.$notice->message.json_encode($notice->delivery_payload));
            $this->assertStringNotContainsString('SECRET', $notice->title.$notice->message.json_encode($notice->delivery_payload));
        }
        $this->assertSame([(int) $staff->id], $this->visible($this->actor()));
        $this->assertSame([(int) $family->id], $this->visible($this->actor('farid')));
        $this->assertSame('discipline', app(StaffPrivateNotices::class)->inbox($this->actor())['items'][0]['feature']);
        app(StaffPrivateNotices::class)->markRead($this->actor(), (int) $staff->id);
        $this->assertSame(0, app(StaffPrivateNotices::class)->inbox($this->actor())['unread_count']);
        $this->appointHead();
        app(DisciplineRecords::class)->review($this->actor(), $r['id'], ['expected_revision' => 1, 'decision' => 'overturn', 'reason' => 'School corrected the evidence']);
        $this->assertSame([], $this->visible($this->actor('farid')));
        $this->assertFalse($service->allows($this->actor('farid'), $family->type, $family->delivery_payload));
        $service->record($r['id'], 1);
        $this->assertSame($count, DisciplineNoticeIntent::count());
        $service->record($r['id'], 2);
        $this->linkIntents();
        $this->assertCount(1, $this->visible($this->actor('farid')));
    }

    public function test_review_and_hearing_notices_require_current_specific_appointments(): void
    {
        $this->appointHead();
        $this->record();
        $second = $this->record();
        app(DisciplineNotices::class)->record($second['id'], 1);
        $this->linkIntents();
        $notice = $this->notice($this->actor(), DisciplineNotices::REVIEW);
        $this->assertTrue(app(DisciplineNotices::class)->allows($this->actor(), $notice->type, $notice->delivery_payload));
        $this->assertSame(0, DisciplineNoticeIntent::where('record_id', $second['id'])->where('type', DisciplineNotices::FAMILY)->count());
        AcademicLeadership::where('user_id', $this->actor()->id)->update(['ends_on' => '2026-02-09']);
        $this->assertFalse(app(DisciplineNotices::class)->allows($this->actor(), $notice->type, $notice->delivery_payload));
        $this->assertSame([], $this->visible($this->actor()));
        AcademicLeadership::where('user_id', $this->actor()->id)->update(['ends_on' => '2026-12-31']);
        app(DisciplineRecords::class)->review($this->actor(), $second['id'], ['expected_revision' => 1, 'decision' => 'confirm', 'reason' => 'Reviewed the evidence', 'sanction_code' => 'detention']);
        $third = $this->record();
        app(DisciplineRecords::class)->review($this->actor(), $third['id'], ['expected_revision' => 1, 'decision' => 'confirm', 'reason' => 'Refer for principal hearing']);
        AcademicLeadership::create(['user_id' => $this->superAdmin()->id, 'kind' => 'principal', 'grade_level_id' => null, 'session_year_id' => $this->year,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic principal']);
        app(DisciplineNotices::class)->record($third['id'], 2);
        $hearing = $this->notice($this->superAdmin(), DisciplineNotices::HEARING);
        $this->assertTrue(app(DisciplineNotices::class)->allows($this->superAdmin(), $hearing->type, $hearing->delivery_payload));
        $this->assertFalse(app(DisciplineNotices::class)->allows($this->actor(), $hearing->type, $hearing->delivery_payload));
    }

    public function test_family_guardian_and_approval_withdrawal_never_promotes_private_notices_to_broadcasts(): void
    {
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->actor('farid')->id], ['status' => 'active', 'can_approve' => true]);
        LearnerApproval::create(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->actor('farid')->id,
            'consent_version' => config('identity.consent_version'), 'consented_at' => now()]);
        $r = $this->record();
        app(DisciplineNotices::class)->record($r['id'], 1);
        $this->linkIntents();
        $parent = $this->notice($this->actor('farid'), DisciplineNotices::FAMILY);
        $this->assertCount(1, $this->visible($this->actor('asha')));
        $query = NotificationVisibility::forUser(Notification::whereIn('type', DisciplineNotices::TYPES), (int) $this->actor('farid')->id, 2);
        GuardianLink::where('guardian_user_id', $this->actor('farid')->id)->update(['status' => 'ended']);
        $this->assertSame(0, $query->count());
        $this->assertFalse(app(DisciplineNotices::class)->allows($this->actor('farid'), $parent->type, $parent->delivery_payload));
        LearnerApproval::query()->delete();
        $this->assertSame([], $this->visible($this->actor('asha')));
        $this->assertSame([], $this->visible($this->actor('theo')));
    }

    public function test_detention_supervisor_and_family_updates_follow_current_roster_and_session_revision(): void
    {
        $this->appointHead();
        $r = $this->record();
        $d = app(DisciplineDetentions::class);
        $s = $d->create($this->actor(), $this->year, $this->grade, ['supervisor_id' => $this->actor('theo')->id, 'on_date' => '2026-02-10',
            'starts_at' => '15:00', 'ends_at' => '16:00', 'location' => 'SECRET supervised room', 'capacity' => 10]);
        $d->enroll($this->actor(), $s['id'], $r['id'], 1);
        app(DisciplineNotices::class)->detention($s['id'], 2);
        $this->linkIntents();
        $supervisor = $this->notice($this->actor('theo'), DisciplineNotices::DETENTION);
        $family = $this->notice($this->actor('farid'), DisciplineNotices::FAMILY);
        $this->assertTrue(app(DisciplineNotices::class)->allows($this->actor('theo'), $supervisor->type, $supervisor->delivery_payload));
        $this->assertCount(1, $this->visible($this->actor('theo')));
        $d->cancel($this->actor(), $s['id'], 2, 'School changed the supervised arrangement');
        $this->assertFalse(app(DisciplineNotices::class)->allows($this->actor('theo'), $supervisor->type, $supervisor->delivery_payload));
        $this->assertFalse(app(DisciplineNotices::class)->allows($this->actor('farid'), $family->type, $family->delivery_payload));
        app(DisciplineNotices::class)->detention($s['id'], 3);
        $this->linkIntents();
        $this->assertCount(1, $this->visible($this->actor('farid')));
        $this->actor('theo')->syncRoles([SchoolRoles::FACILITATOR]);
        $this->assertSame([], $this->visible($this->actor('theo')));
    }

    public function test_prepared_queries_refuse_allocation_role_module_and_forged_link_before_body_reads(): void
    {
        $r = $this->record();
        app(DisciplineNotices::class)->record($r['id'], 1);
        $this->linkIntents();
        $notice = $this->notice($this->actor(), DisciplineNotices::STAFF);
        $source = $notice->delivery_payload;
        $notice->forceFill(['delivery_payload' => array_replace($source, ['link' => 'https://example.test/private'])])->save();
        $this->assertSame([], $this->visible($this->actor()));
        $notice->forceFill(['delivery_payload' => $source])->save();
        $query = NotificationVisibility::forUser(Notification::whereIn('type', DisciplineNotices::TYPES), (int) $this->actor()->id, 2);
        TeachingAllocation::query()->update(['ends_on' => '2026-02-09']);
        DB::enableQueryLog();
        $this->assertSame(0, $query->count());
        foreach (DB::getQueryLog() as $entry) {
            if (str_contains($entry['query'], '`discipline_records`')) {
                $this->assertStringNotContainsString('`description`', $entry['query']);
                $this->assertStringNotContainsString('`staff_note`', $entry['query']);
            }
        }
        DB::disableQueryLog();
        TeachingAllocation::query()->update(['ends_on' => '2026-12-31']);
        PlatformStaffIdentity::create(['user_id' => $this->actor()->id, 'active' => true]);
        $this->assertSame([], $this->visible($this->actor()));
        PlatformStaffIdentity::query()->delete();
        $this->actor()->syncRoles([SchoolRoles::COUNSELLOR]);
        $this->assertSame([], $this->visible($this->actor()));
        SchoolModule::where('module', 'discipline')->update(['enabled' => false]);
        $this->assertSame([], $this->visible($this->actor('farid')));
        SchoolModule::where('module', 'discipline')->update(['enabled' => true]);
        $parent = $this->actor('farid');
        $family = $this->notice($parent, DisciplineNotices::FAMILY);
        app(CurrentSchool::class)->set(2);
        $this->assertFalse(app(DisciplineNotices::class)->allows($parent, $family->type, $family->delivery_payload));
        app(CurrentSchool::class)->set(1);
    }

    public function test_outbox_listener_is_idempotent_restores_school_and_rollback_erases_all_notice_intents(): void
    {
        $r = $this->record();
        $event = OutboxEvent::where('type', 'discipline.record_changed')->latest('id')->firstOrFail();
        $this->assertContains(QueueDisciplineNotices::class, config('platform.outbox.listeners')['discipline.record_changed']);
        $listener = app(QueueDisciplineNotices::class);
        $listener->handle($event);
        $count = DisciplineNoticeIntent::count();
        $listener->handle($event);
        $this->assertSame($count, DisciplineNoticeIntent::count());
        app(CurrentSchool::class)->set(2);
        $listener->handle($event);
        $this->assertSame(2, app(CurrentSchool::class)->id());
        app(CurrentSchool::class)->set(1);
        $before = Notification::count();
        DB::beginTransaction();
        try {
            $second = $this->record();
            app(DisciplineNotices::class)->record($second['id'], 1);
            $this->assertGreaterThan($count, DisciplineNoticeIntent::count());
        } finally {
            DB::rollBack();
        }
        $this->assertSame($count, DisciplineNoticeIntent::count());
        $this->assertSame($before, Notification::count());
        $this->assertSame(1, $r['revision']);
    }
}
