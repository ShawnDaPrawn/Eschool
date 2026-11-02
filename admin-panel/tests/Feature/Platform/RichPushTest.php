<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Jobs\Platform\FanoutNotification;
use App\Jobs\Platform\SendPush;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\TimetableLesson;
use App\Models\ClassSchool;
use App\Models\People\Enrolment;
use App\Models\Platform\NotificationDelivery;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Settings;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Academics\DayPatterns;
use App\Services\Academics\TeachingGroups;
use App\Services\Academics\TimetableConfiguration;
use App\Services\Academics\TimetableEngine;
use App\Services\Academics\TimetableRefreshPush;
use App\Services\Platform\Events\AcademicTimetableChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Notifications\FcmClient;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Notifications\PushDevices;
use App\Services\Platform\Notifications\PushPresentation;
use App\Services\Platform\Notifications\PushQueue;
use App\Services\Platform\Notifications\QuietHours;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use ReflectionMethod;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * Rich push (Action Plan 2.9) and silent timetable refresh pushes (1.9).
 * Specification: engineering/specs/platform/rich-push.md.
 */
class RichPushTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(CarbonImmutable::parse('2026-02-10 07:50:00', 'Africa/Johannesburg'));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        config(['platform.rich_push.timetable_refresh' => true, 'platform.rich_push.android_private_visibility' => true]);
    }

    /** Signs the person in on an Android phone and an iPhone; returns [android token, iOS token]. */
    private function devices(int $userId, string $tag, int $school = 1): array
    {
        app(CurrentSchool::class)->run($school, function () use ($userId, $tag) {
            $user = User::findOrFail($userId);
            foreach (['android', 'ios'] as $platform) {
                $signIn = $user->createToken($tag.'-'.$platform)->accessToken;
                app(PushDevices::class)->register($user, $tag.'-'.$platform, $platform, null, (int) $signIn->id);
            }
        });

        return [$tag.'-android', $tag.'-ios'];
    }

    /**
     * Builds one person's pushes for a notice exactly as Notifier sends them,
     * keyed by device token.
     *
     * @return array<string, array<string, mixed>>
     */
    private function build(int $userId, string $type, string $title, string $body, array $data = []): array
    {
        Queue::fake();
        $push = new ReflectionMethod(Notifier::class, 'push');
        $push->invoke(app(Notifier::class), User::findOrFail($userId), $type, $title, $body,
            $data + ['type' => $type, 'title' => $title, 'body' => $body, 'link' => '/notifications', 'image' => '']);

        return Queue::pushed(SendPush::class)->mapWithKeys(fn (SendPush $job) => [$job->message['message']['token'] => $job->message['message']])->all();
    }

    /** Sends a legacy notice through the outbox and fan-out; returns its queued pushes by token. */
    private function sendLegacy(int $userId, string $type, string $title, string $body, mixed $info = null): array
    {
        Queue::fake();
        sendSimpleNotification([$userId], $title, $body, $type, null, $info);
        Outbox::dispatchPending();
        foreach (Queue::pushed(FanoutNotification::class) as $job) {
            app()->call([$job, 'handle']);
        }

        return Queue::pushed(SendPush::class)->mapWithKeys(fn (SendPush $job) => [$job->message['message']['token'] => $job->message['message']])->all();
    }

    public function test_consent_and_urgent_notices_carry_their_buttons_on_both_platforms(): void
    {
        [$android, $ios] = $this->devices($this->schoolA->ids['farid_user'], 'farid');

        $consent = $this->build($this->schoolA->ids['farid_user'], 'consent.requested', 'Activity permission requested', 'Open Family consent and health to review a request.');
        $this->assertSame('consent_yes,consent_no', $consent[$android]['data']['actions']);
        $this->assertSame('consent', $consent[$android]['data']['category']);
        $this->assertSame('consent_yes,consent_no', $consent[$ios]['data']['actions']);
        $this->assertSame('consent', $consent[$ios]['apns']['payload']['aps']['category']);

        $urgent = $this->sendLegacy($this->schoolA->ids['farid_user'], 'comms.urgent', 'School closes at noon', 'Storm warning: collect learners by 12:00.');
        $this->assertSame('acknowledge', $urgent[$android]['data']['actions']);
        $this->assertSame('acknowledge', $urgent[$android]['data']['category']);
        $this->assertArrayNotHasKey('android', $urgent[$android], 'not private: no visibility block');
        $this->assertSame('acknowledge', $urgent[$ios]['apns']['payload']['aps']['category']);
        $this->assertSame('time-sensitive', $urgent[$ios]['apns']['payload']['aps']['interruption-level'], 'quiet hours hints are kept');
        $this->assertSame('School closes at noon', $urgent[$ios]['notification']['title']);

        $notice = $this->sendLegacy($this->schoolA->ids['farid_user'], 'notice', 'Sports day', 'Wear house colours.');
        $this->assertArrayNotHasKey('actions', $notice[$android]['data'], 'a type without buttons gets none');
        $this->assertArrayNotHasKey('category', $notice[$ios]['apns']['payload']['aps']);
    }

    public function test_actions_are_known_ids_that_only_open_the_app_never_urls(): void
    {
        foreach (config('notification_types') as $type => $definition) {
            if (! isset($definition['push_actions'])) {
                continue;
            }
            $this->assertNotSame([], PushPresentation::actions($type), "$type lists buttons with no category");
            foreach ($definition['push_actions'] as $id) {
                $this->assertContains($id, PushPresentation::ACTIONS, "$type: unknown button $id");
                $this->assertMatchesRegularExpression('/^[a-z_]+$/D', $id);
            }
        }
        foreach (PushPresentation::CATEGORIES as $category => $ids) {
            $this->assertMatchesRegularExpression('/^[a-z_]+$/D', $category);
        }
        $this->assertSame(['reply'], PushPresentation::actions('chat'), 'legacy chat is a direct message');
        $this->assertSame([], PushPresentation::actions('academics.timetable_changed'));

        [$android, $ios] = $this->devices($this->schoolA->ids['farid_user'], 'farid');
        $consent = $this->build($this->schoolA->ids['farid_user'], 'consent.requested', 'Activity permission requested', 'Open Family consent and health to review a request.', ['link' => '/family-forms']);
        foreach ([$android, $ios] as $token) {
            foreach (explode(',', $consent[$token]['data']['actions']) as $id) {
                $this->assertContains($id, PushPresentation::ACTIONS);
                $this->assertStringNotContainsString('/', $id);
                $this->assertStringNotContainsString(':', $id);
            }
            $this->assertSame('/family-forms', $consent[$token]['data']['link'], 'the tap opens the app path, where the person confirms');
        }
    }

    public function test_a_direct_message_names_its_sender_with_the_profile_photo_it_already_carries(): void
    {
        [$android, $ios] = $this->devices($this->schoolA->ids['farid_user'], 'farid');
        $photo = 'https://school.example/storage/users/tara.png';

        $chat = $this->sendLegacy($this->schoolA->ids['farid_user'], 'chat', 'Tara Demo', 'See you at 3', (object) [
            'user_id' => $this->schoolA->ids['tara_user'], 'first_name' => 'Tara', 'last_name' => 'Demo', 'image' => $photo,
        ]);
        $this->assertSame('reply', $chat[$android]['data']['actions']);
        $this->assertSame('Tara Demo', $chat[$android]['data']['sender_name']);
        $this->assertSame($photo, $chat[$android]['data']['sender_avatar']);
        $this->assertSame($photo, $chat[$ios]['data']['sender_avatar']);
        $this->assertSame('reply', $chat[$ios]['apns']['payload']['aps']['category']);
        $this->assertSame(1, $chat[$ios]['apns']['payload']['aps']['mutable-content']);

        // No photo (the accessor's bare storage folder) or a script URL is sent as empty.
        foreach (['https://school.example/storage/', 'javascript:alert(1)', 'https://user:pw@school.example/a.png', ''] as $bad) {
            $this->assertSame('', PushPresentation::sender(['sender_info' => json_encode(['first_name' => 'Tara', 'image' => $bad])])['sender_avatar'], $bad);
        }
        $plain = $this->build($this->schoolA->ids['farid_user'], 'communication.message', 'New school message', 'Open Messages to read a school message.', ['communication_message_id' => 5]);
        $this->assertSame('', $plain[$android]['data']['sender_avatar'], 'the school message producer has no sender');
        $this->assertSame('', $plain[$android]['data']['sender_name']);
        $this->assertSame('reply', $plain[$android]['data']['actions']);
        $this->assertArrayNotHasKey('mutable-content', $plain[$ios]['apns']['payload']['aps']);
    }

    public function test_private_notices_never_show_their_content_on_either_platform(): void
    {
        [$android, $ios] = $this->devices($this->schoolA->ids['farid_user'], 'farid');
        $generic = PushPresentation::genericText();

        foreach (['wellbeing.report_reply', 'discipline.family_update', 'group_chat.family_update', 'consent.requested', 'fees.ledger_reminder', 'progress.band_changed'] as $type) {
            $this->assertTrue(PushPresentation::private($type), $type);
            $pushes = $this->build($this->schoolA->ids['farid_user'], $type, 'Asha reported bullying', 'Asha told Ms Tara about Ben.', [
                'image' => 'https://school.example/storage/secret.png', 'sender_info' => json_encode(['first_name' => 'Ben', 'image' => 'https://school.example/storage/ben.png']),
            ]);
            $this->assertSame($generic['title'], $pushes[$android]['data']['title'], $type);
            $this->assertSame($generic['body'], $pushes[$android]['data']['body'], $type);
            $this->assertSame('', $pushes[$android]['data']['image'], $type);
            $this->assertSame('private', $pushes[$android]['data']['visibility'], $type);
            $this->assertSame(['title' => $generic['title'], 'body' => $generic['body'], 'visibility' => 'PRIVATE'], $pushes[$android]['android']['notification'], $type);
            $this->assertSame($generic, $pushes[$ios]['notification'], $type);
            $this->assertSame($generic['title'], $pushes[$ios]['data']['title'], $type);
            $this->assertArrayNotHasKey('sender_avatar', $pushes[$android]['data']);
            foreach ([$android, $ios] as $token) {
                $visible = json_encode([$pushes[$token]['notification'] ?? null, $pushes[$token]['android'] ?? null, $pushes[$token]['data']['title'], $pushes[$token]['data']['body'], $pushes[$token]['data']['image']]);
                $this->assertStringNotContainsString('Asha', $visible, $type);
                $this->assertStringNotContainsString('secret.png', $visible, $type);
            }
        }

        // Ordinary notices keep their text; the Android visibility block waits for the apps.
        $this->assertFalse(PushPresentation::private('comms.notice'));
        $open = $this->build($this->schoolA->ids['farid_user'], 'comms.notice', 'Sports day', 'Wear house colours.');
        $this->assertSame('Sports day', $open[$android]['data']['title']);
        $this->assertSame('Sports day', $open[$ios]['notification']['title']);
        config(['platform.rich_push.android_private_visibility' => false]);
        $off = $this->build($this->schoolA->ids['farid_user'], 'wellbeing.report_reply', 'Asha reported bullying', 'Details');
        $this->assertArrayNotHasKey('android', $off[$android]);
        $this->assertSame($generic['title'], $off[$android]['data']['title'], 'the generic text never depends on the switch');
    }

    // --- 1.9: silent timetable refresh ---

    private function timetable(): array
    {
        $admin = $this->superAdmin();
        $year = $this->schoolA->ids['session_year'];
        $grade = (int) GradeLevel::where('code', '7')->value('id');
        $teacher = (int) Teacher::where('user_id', $this->schoolA->ids['tara_user'])->value('id');
        SessionYear::whereKey($year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => $grade]);
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Refresh rehearsal']);
        $groups = app(TeachingGroups::class);
        $group = $groups->save($admin, ['name' => 'Maths 7A', 'subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => $grade, 'session_year_id' => $year, 'class_section_id' => $this->schoolA->ids['section_a'], 'active' => true]);
        $dates = ['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Refresh allocation'];
        $groups->dated($admin, 'teachers', $dates + ['teacher_id' => $teacher], $group->id);
        $groups->dated($admin, 'memberships', $dates + ['learner_id' => $this->schoolA->ids['asha'], 'mark_rule' => 'catch_up'], $group->id);
        $room = (int) app(TimetableConfiguration::class)->room($admin, ['name' => 'Room 1', 'capacity' => 35, 'type' => 'classroom', 'active' => true])->id;
        $bells = fn (string $name, array $periods) => app(TimetableConfiguration::class)->bells($admin, ['name' => $name, 'section' => 'Senior', 'session_year_id' => $year,
            'periods' => array_map(fn ($p) => ['name' => $p[0], 'starts_at' => $p[1], 'ends_at' => $p[2], 'is_break' => false], $periods)])->load('periods');
        $normal = $bells('Normal Senior', [['P1', '07:00', '07:40'], ['P2', '07:50', '08:20']]);
        $short = $bells('Short Senior', [['S1', '07:00', '07:25'], ['S2', '07:35', '08:00']]);
        $engine = app(TimetableEngine::class);
        $preview = $engine->preview($admin, [['session_year_id' => $year, 'term_id' => null, 'teaching_group_id' => (int) $group->id, 'class_section_id' => null,
            'teacher_id' => $teacher, 'room_id' => $room, 'bell_period_id' => (int) $normal->periods->first()->id, 'starts_on' => '2026-02-10', 'ends_on' => '2026-03-31',
            'weekday' => 2, 'week_pattern' => 'all', 'active' => true]]);
        $this->assertTrue($preview['can_save'], json_encode($preview['conflicts']));
        $saved = $engine->save($admin, $preview['rows'], $preview['fingerprint']);

        return ['lesson' => (int) $saved['lessons'][0]['id'], 'normal' => (int) $normal->id, 'short' => (int) $short->id];
    }

    /** @return list<array<string, mixed>> the timetable.refresh messages queued */
    private function refreshes(): array
    {
        Outbox::dispatchPending();

        return Queue::pushed(SendPush::class)->map(fn (SendPush $job) => $job->message['message'])
            ->filter(fn ($message) => ($message['data']['type'] ?? null) === TimetableRefreshPush::TYPE)->values()->all();
    }

    public function test_a_timetable_change_silently_refreshes_the_learner_guardian_and_teacher_devices(): void
    {
        $tokens = array_merge(
            $this->devices($this->schoolA->ids['asha_user'], 'asha'),
            $this->devices($this->schoolA->ids['farid_user'], 'farid'),
            $this->devices($this->schoolA->ids['tara_user'], 'tara'),
        );
        $this->devices($this->schoolA->ids['ben_user'], 'ben');
        Queue::fake();
        $this->timetable();

        $refreshes = $this->refreshes();
        $byToken = collect($refreshes)->keyBy('token');
        $this->assertEqualsCanonicalizing($tokens, $byToken->keys()->all(), 'Ben is not in the group');
        foreach ($tokens as $token) {
            $message = $byToken[$token];
            $this->assertSame(['type' => 'timetable.refresh', 'school_id' => '1'], $message['data']);
            $this->assertArrayNotHasKey('notification', $message, 'shows nothing');
            if (str_ends_with($token, '-ios')) {
                $this->assertSame(['apns-push-type' => 'background', 'apns-priority' => '5'], $message['apns']['headers']);
                $this->assertSame(['aps' => ['content-available' => 1]], $message['apns']['payload']);
                $this->assertArrayNotHasKey('android', $message);
            } else {
                $this->assertSame(['priority' => 'normal'], $message['android']);
                $this->assertArrayNotHasKey('apns', $message);
            }
            $this->assertStringNotContainsString('Maths', json_encode($message));
            $this->assertStringNotContainsString('Asha', json_encode($message));
        }
        $this->assertSame(count($tokens), NotificationDelivery::where('type', 'push.timetable.refresh')->where('school_id', 1)->count());
    }

    public function test_a_short_day_also_refreshes_and_the_switch_turns_it_off(): void
    {
        $tokens = $this->devices($this->schoolA->ids['asha_user'], 'asha');
        Queue::fake();
        $ids = $this->timetable();
        $this->refreshes();
        $before = count(Queue::pushed(SendPush::class));

        app(DayPatterns::class)->save($this->superAdmin(), ['on_date' => '2026-02-17', 'reason' => 'Staff development',
            'replacements' => [['bell_schedule_id' => $ids['normal'], 'replacement_bell_schedule_id' => $ids['short']]]]);
        $after = array_slice(Queue::pushed(SendPush::class)->all(), $before);
        Outbox::dispatchPending();
        $new = Queue::pushed(SendPush::class)->slice($before)->map(fn ($job) => $job->message['message'])->filter(fn ($m) => ($m['data']['type'] ?? null) === 'timetable.refresh');
        $this->assertEqualsCanonicalizing($tokens, $new->pluck('token')->values()->all());
        $this->assertSame([], $after, 'nothing is queued before the change commits and the outbox runs');

        config(['platform.rich_push.timetable_refresh' => false]);
        $this->assertSame(0, app(TimetableRefreshPush::class)->queue([$ids['lesson']]));
    }

    public function test_reassigning_a_lesson_refreshes_both_the_old_and_the_new_teacher_and_roster(): void
    {
        $old = array_merge($this->devices($this->schoolA->ids['asha_user'], 'asha'), $this->devices($this->schoolA->ids['tara_user'], 'tara'));
        $new = array_merge($this->devices($this->schoolA->ids['ben_user'], 'ben'), $this->devices($this->schoolA->ids['gina_user'], 'gina'),
            $this->devices($this->schoolA->ids['theo_user'], 'theo'));
        $this->devices($this->schoolA->ids['cara_user'], 'cara');
        $schoolB = array_merge($this->devices($this->schoolB->ids['asha_user'], 'baobab-asha', self::SCHOOL_B_ID),
            $this->devices($this->schoolB->ids['tara_user'], 'baobab-tara', self::SCHOOL_B_ID));
        Queue::fake();
        $ids = $this->timetable();
        $this->refreshes();
        $before = count(Queue::pushed(SendPush::class));

        // Move the lesson to Science 7A (Ben's group), taught by Theo.
        $admin = $this->superAdmin();
        $lesson = TimetableLesson::findOrFail($ids['lesson']);
        $groups = app(TeachingGroups::class);
        $science = $groups->save($admin, ['name' => 'Science 7A', 'subject_id' => $this->schoolA->ids['science'], 'grade_level_id' => (int) GradeLevel::where('code', '7')->value('id'),
            'session_year_id' => $lesson->session_year_id, 'class_section_id' => $this->schoolA->ids['section_a'], 'active' => true]);
        Enrolment::create(['learner_id' => $this->schoolA->ids['ben'], 'session_year_id' => $lesson->session_year_id, 'class_section_id' => $this->schoolA->ids['section_a'],
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Refresh rehearsal']);
        $dates = ['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Moved lesson'];
        $groups->dated($admin, 'teachers', $dates + ['teacher_id' => $this->schoolA->ids['theo_teacher']], $science->id);
        $groups->dated($admin, 'memberships', $dates + ['learner_id' => $this->schoolA->ids['ben'], 'mark_rule' => 'catch_up'], $science->id);
        $engine = app(TimetableEngine::class);
        $preview = $engine->preview($admin, [['id' => $lesson->id, 'revision' => $lesson->revision, 'session_year_id' => $lesson->session_year_id, 'term_id' => null,
            'teaching_group_id' => (int) $science->id, 'class_section_id' => null, 'teacher_id' => $this->schoolA->ids['theo_teacher'], 'room_id' => $lesson->room_id,
            'bell_period_id' => $lesson->bell_period_id, 'starts_on' => $lesson->starts_on->toDateString(), 'ends_on' => $lesson->ends_on->toDateString(),
            'weekday' => $lesson->weekday, 'week_pattern' => $lesson->week_pattern, 'active' => true]]);
        $this->assertTrue($preview['can_save'], json_encode($preview['conflicts']));
        $engine->save($admin, $preview['rows'], $preview['fingerprint']);

        $event = OutboxEvent::where('type', 'academics.timetable_changed')->latest('id')->firstOrFail();
        $previous = [['teacher_id' => (int) $lesson->teacher_id, 'teaching_group_id' => (int) $lesson->teaching_group_id, 'class_section_id' => null,
            'session_year_id' => (int) $lesson->session_year_id]];
        // JSON object key order can change in MySQL; keep exact keys, types and values.
        $this->assertSame(Arr::sortRecursive($previous), Arr::sortRecursive($event->payload['previous']), 'ids only, no learner list');
        Outbox::dispatchPending();
        $tokens = Queue::pushed(SendPush::class)->slice($before)->map(fn ($job) => $job->message['message'])
            ->filter(fn ($m) => ($m['data']['type'] ?? null) === 'timetable.refresh')->pluck('token')->values()->all();
        $this->assertEqualsCanonicalizing(array_merge($old, $new), $tokens, 'old and new audiences, never Cara');
        $this->assertSame([], array_values(array_intersect($tokens, $schoolB)));

        // A previous placement naming School B's teacher and homeroom finds nobody from School A's event.
        $year = $this->schoolB->ids['session_year'];
        app(CurrentSchool::class)->run(self::SCHOOL_B_ID, fn () => Enrolment::create(['learner_id' => $this->schoolB->ids['asha'], 'session_year_id' => $year,
            'class_section_id' => $this->schoolB->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Baobab roster']));
        $foreign = ['teacher_id' => $this->schoolB->ids['tara_teacher'], 'teaching_group_id' => null, 'class_section_id' => $this->schoolB->ids['section_a'], 'session_year_id' => $year];
        $this->assertContains($this->schoolB->ids['asha'], app(CurrentSchool::class)->run(self::SCHOOL_B_ID, fn () => app(TimetableEngine::class)->learners($foreign, '2026-02-10')),
            'the placement is real in School B');
        $count = count(Queue::pushed(SendPush::class));
        Outbox::record(new AcademicTimetableChanged(1, [$ids['lesson']], [$foreign]));
        Outbox::dispatchPending();
        $forged = Queue::pushed(SendPush::class)->slice($count)->map(fn ($job) => $job->message['message']['token'])->values()->all();
        $this->assertNotEmpty($forged, 'School A\'s own lesson is still refreshed');
        $this->assertSame([], array_values(array_intersect($forged, $schoolB)));
        $this->assertSame(0, NotificationDelivery::where('school_id', self::SCHOOL_B_ID)->where('type', 'push.timetable.refresh')->count());
    }

    public function test_a_school_a_change_never_reaches_school_b_devices(): void
    {
        $this->devices($this->schoolA->ids['asha_user'], 'asha');
        $schoolB = array_merge(
            $this->devices($this->schoolB->ids['asha_user'], 'baobab-asha', self::SCHOOL_B_ID),
            $this->devices($this->schoolB->ids['farid_user'], 'baobab-farid', self::SCHOOL_B_ID),
        );
        Queue::fake();
        $ids = $this->timetable();
        $tokens = collect($this->refreshes())->pluck('token')->all();
        $this->assertNotEmpty($tokens);
        $this->assertSame([], array_intersect($tokens, $schoolB));

        // A School B lesson id named in a School A event finds nothing, and the other way round.
        $row = DB::table('timetable_lessons')->where('id', $ids['lesson'])->first();
        $foreign = DB::table('timetable_lessons')->insertGetId(array_replace((array) $row, ['id' => null, 'school_id' => self::SCHOOL_B_ID]));
        $deliveries = NotificationDelivery::count();
        $this->assertSame(0, app(TimetableRefreshPush::class)->queue([$foreign]));
        $this->assertSame(0, app(CurrentSchool::class)->run(self::SCHOOL_B_ID, fn () => app(TimetableRefreshPush::class)->queue([$ids['lesson']])));
        Outbox::record(new AcademicTimetableChanged(1, [$foreign]));
        $this->assertSame([], array_values(array_intersect(collect($this->refreshes())->pluck('token')->all(), $schoolB)));
        $this->assertSame($deliveries, NotificationDelivery::count(), 'no School B delivery rows');
        $this->assertSame(0, NotificationDelivery::where('school_id', self::SCHOOL_B_ID)->where('type', 'push.timetable.refresh')->count());
    }

    public function test_a_silent_push_is_not_held_by_quiet_hours_but_stops_for_a_departed_person_or_moved_device(): void
    {
        Settings::create(['type' => 'project_id', 'message' => 'demo-project']);
        $this->app->bind(FcmClient::class, fn () => new class extends FcmClient
        {
            public function accessToken(): ?string
            {
                return 'test-access-token';
            }
        });
        Http::fake(['fcm.googleapis.com/*' => Http::response(['name' => 'projects/demo/messages/0:1'])]);
        config(['queue.default' => 'database']);
        $this->travelTo(CarbonImmutable::parse('2026-02-10 23:00:00', 'Africa/Johannesburg'));
        [$android] = $this->devices($this->schoolA->ids['farid_user'], 'farid');
        $farid = User::findOrFail($this->schoolA->ids['farid_user']);
        app(QuietHours::class)->save($farid, ['enabled' => true, 'starts_at' => '21:00', 'ends_at' => '06:30', 'timezone' => 'Africa/Johannesburg']);
        Queue::fake();

        $push = fn (int $userId, string $token) => PushQueue::toDevice('timetable.refresh', $userId, $token, ['message' => TimetableRefreshPush::message($token, 'android', 1)]);
        $push((int) $farid->id, $android);
        $job = Queue::pushed(SendPush::class)->last();
        app()->call([$job, 'handle']);
        Http::assertSentCount(1);
        $this->assertCount(1, Queue::pushed(SendPush::class), 'not held for quiet hours');
        $this->assertSame(NotificationDelivery::SENT, NotificationDelivery::where('delivery_key', $job->deliveryKey)->value('status'));

        // The device moves to another account before the job runs.
        $push((int) $farid->id, $android);
        $moved = Queue::pushed(SendPush::class)->last();
        app(PushDevices::class)->register(User::findOrFail($this->schoolA->ids['mona_user']), $android, 'android');
        app()->call([$moved, 'handle']);
        Http::assertSentCount(1);
        $this->assertSame('suppressed', NotificationDelivery::where('delivery_key', $moved->deliveryKey)->value('status'));

        // A person who has left the school.
        [$second] = $this->devices($this->schoolA->ids['tara_user'], 'tara2');
        $push((int) $this->schoolA->ids['tara_user'], $second);
        $left = Queue::pushed(SendPush::class)->last();
        SchoolMembership::where('user_id', $this->schoolA->ids['tara_user'])->update(['ends_on' => '2026-02-01']);
        app()->call([$left, 'handle']);
        Http::assertSentCount(1);
        $this->assertSame('suppressed', NotificationDelivery::where('delivery_key', $left->deliveryKey)->value('status'));
    }
}
