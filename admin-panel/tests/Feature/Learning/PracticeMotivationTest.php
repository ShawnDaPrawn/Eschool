<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Jobs\Platform\DispatchOutbox;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingGroup;
use App\Models\Growth\MasteryEvidence;
use App\Models\Growth\MasteryGapClosure;
use App\Models\Growth\MasteryTopicGap;
use App\Models\Holiday;
use App\Models\Learning\DigestLanguageTemplate;
use App\Models\Learning\PracticeDailySet;
use App\Models\Learning\PracticeGoal;
use App\Models\Learning\PracticeGoalWeek;
use App\Models\Learning\PracticeMotivationSettings;
use App\Models\Learning\PracticeNudge;
use App\Models\Notification;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\SchoolModule;
use App\Models\Platform\SchoolStage;
use App\Models\Recognition\AwardPresentation;
use App\Models\Recognition\RecognitionBadge;
use App\Models\Students;
use App\Models\Term;
use App\Models\User;
use App\Services\Growth\GapClosures;
use App\Services\Growth\MasteryRecompute;
use App\Services\Learning\Digests\DigestTemplates;
use App\Services\Learning\Digests\WeeklyDigests;
use App\Services\Learning\Motivation\MotivationNotices;
use App\Services\Learning\Motivation\MotivationSettings;
use App\Services\Learning\Motivation\PracticeGoals;
use App\Services\Learning\Motivation\PracticeNudges;
use App\Services\Learning\Motivation\PracticeStreaks;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\NotificationRules;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Notifications\PrivateNoticeScopes;
use App\Services\Platform\Notifications\QuietHours;
use App\Services\Platform\Stages\SchoolStages;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Recognition\PersistenceBadges;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPracticeScenario;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

/** Action Plan 3.13: engineering/specs/learning/practice-motivation.md. */
class PracticeMotivationTest extends TestCase
{
    use UsesPracticeScenario;
    use ValidatesOpenApiResponses;

    private const BASE = '/api/v2/learning/practice';

    private int $tick = 0;

    /** Words a learner or parent never reads in these notices. */
    private const NEVER = ['rank', 'position', 'top ', 'average', 'behind', 'missed', 'failed', 'only ', 'compared', 'class', '%'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        SchoolModule::updateOrCreate(['module' => 'awards'], ['in_package' => true, 'enabled' => true]);
        SchoolModule::updateOrCreate(['module' => 'digest'], ['in_package' => true, 'enabled' => true]);
        app(ModuleGate::class)->forget();
        Bus::fake([DispatchOutbox::class]);
    }

    private function asha(): int
    {
        return $this->schoolA->ids['asha'];
    }

    private function signIn(string $name, string $school = '1'): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->withToken($this->schoolA->tokens[$name])->withHeaders(['X-School-Id' => $school, 'X-Account-Id' => (string) $this->actor($name)->id]);
    }

    private function operation(array $data = []): array
    {
        return ['operation_id' => (string) Str::uuid()] + $data;
    }

    private function practiseOn(array $days, ?int $learner = null): void
    {
        foreach ($days as $day) {
            PracticeDailySet::create(['learner_id' => $learner ?? $this->asha(), 'teaching_group_id' => $this->group, 'practice_on' => $day,
                'offline_until' => $day.' 23:59:59', 'completed_at' => $day.' 12:00:00', 'completed_on' => $day]);
        }
    }

    private function holiday(string $date): void
    {
        (new Holiday)->forceFill(['date' => $date, 'title' => 'Synthetic school closure', 'session_year_id' => $this->year])->save();
    }

    private function at(string $local): void
    {
        $this->travelTo(CarbonImmutable::parse($local, 'Africa/Johannesburg'));
    }

    private function fractions(): int
    {
        return (int) $this->nodes[$this->keys[1]];
    }

    private function evidence(string $audience, int $achieved, int $times, ?string $at = null): void
    {
        $grade = (int) TeachingGroup::findOrFail($this->group)->grade_level_id;
        for ($i = 0; $i < $times; $i++) {
            MasteryEvidence::create(['subject_id' => $this->subject, 'learner_id' => $this->asha(), 'topic_id' => $this->fractions(),
                'grade_level_id' => $grade, 'session_year_id' => $this->year, 'audience' => $audience, 'source_type' => 'practice_review',
                'source_key' => 'synthetic:'.Str::uuid(), 'achieved_units' => $achieved, 'maximum_units' => 1, 'observed_at' => $at ?? now()->subDay()->addSeconds(++$this->tick)]);
        }
        app(MasteryRecompute::class)->learner($this->asha(), $this->subject);
    }

    private function inbox(string $name, string $type): int
    {
        return app(PrivateNoticeScopes::class)->apply(Notification::query(), (int) $this->actor($name)->id)->where('type', $type)->count();
    }

    /** @return list<int> */
    private function recipients(Notification $notice): array
    {
        $ids = [];
        foreach (OutboxEvent::query()->where('type', 'notification.requested')->get() as $row) {
            $payload = $row->payload;
            if ((int) ($payload['notification_id'] ?? 0) === (int) $notice->id) {
                $ids = array_merge($ids, $payload['user_ids'] ?? []);
            }
        }
        sort($ids);

        return array_map('intval', $ids);
    }

    private function assertGentle(Notification $notice): void
    {
        foreach ([$notice->title, $notice->message] as $text) {
            foreach (self::NEVER as $word) {
                $this->assertStringNotContainsStringIgnoringCase($word, $text);
            }
            $this->assertDoesNotMatchRegularExpression('/\d/', $text, 'No counts or scores in the notice text.');
            $this->assertStringNotContainsString('Fractions', $text);
        }
        $this->assertSame(['link', 'motivation_id'], array_keys($notice->delivery_payload));
    }

    /** Recursively: no field that ranks, compares or scores a child. */
    private function assertNoComparisons(array $payload): void
    {
        array_walk_recursive($payload, function ($value, $key): void {
            $this->assertDoesNotMatchRegularExpression('/rank|position|percentile|average|compar|peer|class_|leader|score|level|confidence/i', (string) $key);
        });
    }

    private function nudgeSettings(array $changes = []): array
    {
        return app(MotivationSettings::class)->configure($this->superAdmin(), $this->operation($changes + ['revision' => 0, 'guardian_nudge_enabled' => true,
            'learner_nudge_enabled' => true, 'nudge_after_school_days' => 3]));
    }

    public function test_a_run_counts_practice_days_kindly_with_one_rest_day_a_week(): void
    {
        // Monday 2 to Saturday 7 February, missing Wednesday 4 (the week's rest day), then Monday 9.
        $this->practiseOn(['2026-02-02', '2026-02-03', '2026-02-05', '2026-02-06', '2026-02-07', '2026-02-09']);
        $streaks = app(PracticeStreaks::class);
        $this->assertSame(['current_days' => 6, 'best_days' => 6, 'rest_day_available' => true, 'peak_days' => 6], $streaks->summary($this->asha()));
        // Today (Tuesday 10) is not over, so not practising yet never breaks the run.
        $this->signIn('asha');
        $this->getJson(self::BASE.'/learners/'.$this->asha().'/motivation')->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('data.streak', ['current_days' => 6, 'best_days' => 6, 'rest_day_available' => true]);
        // A holiday or school closure is never a missed school day.
        PracticeDailySet::whereDate('completed_on', '2026-02-09')->delete();
        $this->holiday('2026-02-09');
        $this->assertSame(5, $streaks->current($this->asha()));
        // A second missed school day in the same week ends the run; it starts again on Friday.
        PracticeDailySet::whereDate('completed_on', '2026-02-05')->delete();
        $this->assertSame(['current_days' => 2, 'best_days' => 2, 'rest_day_available' => true, 'peak_days' => 2], $streaks->summary($this->asha()));
        // Days outside every term (a term break) are not school days either.
        Term::whereKey($this->term)->update(['start_date' => '2026-02-08']);
        $this->assertSame(4, $streaks->current($this->asha()), 'Before the term started, the missed days were not school days.');
        // Monday missed uses this week's rest day: the run holds, with no rest day left this week.
        Term::whereKey($this->term)->update(['start_date' => '2026-01-01']);
        Holiday::query()->delete();
        PracticeDailySet::query()->delete();
        $this->practiseOn(['2026-02-02', '2026-02-03', '2026-02-04', '2026-02-05', '2026-02-06']);
        $this->assertSame(['current_days' => 5, 'best_days' => 5, 'rest_day_available' => false, 'peak_days' => 5], $streaks->summary($this->asha()));
    }

    public function test_learner_sets_a_weekly_goal_sees_progress_and_is_celebrated_once(): void
    {
        $this->signIn('asha');
        $path = self::BASE.'/learners/'.$this->asha().'/practice-goal';
        $input = $this->operation(['revision' => 0, 'days_per_week' => 2]);
        $first = $this->postJson($path, $input)->assertOk()->assertJsonPath('data.goal.days_per_week', 2)->assertJsonPath('data.goal.met', false)
            ->assertJsonPath('data.goal.practised_days_this_week', 0)->assertJsonPath('data.can_set_goal', true)->json('data');
        $this->assertNoComparisons($first);
        $this->assertSame(['learner_id', 'can_set_goal', 'streak', 'goal', 'secure_topics'], array_keys($first));
        // An exact retry replays; a stale revision is refused.
        $this->postJson($path, $input)->assertOk()->assertJsonPath('data.goal.revision', 1);
        $this->assertSame(1, PracticeGoal::sole()->revision);
        $this->postJson($path, $this->operation(['revision' => 0, 'days_per_week' => 3]))->assertConflict();
        $this->postJson($path, $this->operation(['revision' => 1, 'days_per_week' => 8]))->assertUnprocessable();
        // Monday practised; completing today's set reaches the goal and celebrates once.
        $this->practiseOn(['2026-02-09']);
        $this->approve();
        $set = $this->issue();
        $this->assertSame(0, PracticeGoalWeek::count());
        $this->review($set);
        $week = PracticeGoalWeek::sole();
        $this->assertSame(['2026-02-09', 2], [$week->week_start->toDateString(), $week->days_target]);
        $notice = Notification::where('type', PracticeGoals::TYPE)->sole();
        $this->assertGentle($notice);
        $this->assertSame('You reached your practice goal for this week. Well done for keeping at it!', $notice->message);
        $this->assertSame([(int) $this->actor('asha')->id], $this->recipients($notice));
        $this->assertFalse(app(PracticeGoals::class)->celebrate(Students::findOrFail($this->asha()))->wasRecentlyCreated);
        // Lowering the goal in the same week never celebrates twice.
        $this->signIn('asha');
        $this->postJson($path, $this->operation(['revision' => 1, 'days_per_week' => 1]))->assertOk()->assertJsonPath('data.goal.met', true);
        $this->assertSame([1, 1], [PracticeGoalWeek::count(), Notification::where('type', PracticeGoals::TYPE)->count()]);
        // Only the learner sees their own celebration.
        $this->assertSame(1, $this->inbox('asha', PracticeGoals::TYPE));
        $this->assertTrue(app(MotivationNotices::class)->allows($this->actor('asha'), PracticeGoals::TYPE, $notice->delivery_payload));
        $this->assertSame(0, $this->inbox('farid', PracticeGoals::TYPE));
        $this->assertFalse(app(MotivationNotices::class)->allows($this->actor('farid'), PracticeGoals::TYPE, $notice->delivery_payload));
        // Clearing the goal is allowed; the celebration stays in the inbox.
        $this->postJson($path, $this->operation(['revision' => 2, 'days_per_week' => null]))->assertOk()->assertJsonPath('data.goal.days_per_week', null)
            ->assertJsonPath('data.goal.met', false)->assertJsonPath('data.goal.revision', 3);
        $this->assertSame([null, 3], [PracticeGoal::sole()->days_per_week, PracticeGoal::sole()->revision]);
        $this->assertNull(app(PracticeGoals::class)->celebrate(Students::findOrFail($this->asha())), 'A cleared goal is no goal.');
    }

    /** Review fix: a cleared goal keeps its row, so revisions only increase and a stale tab always gets 409. */
    public function test_clearing_a_goal_keeps_its_revision_so_a_stale_write_is_refused(): void
    {
        $this->signIn('asha');
        $path = self::BASE.'/learners/'.$this->asha().'/practice-goal';
        $this->postJson($path, $this->operation(['revision' => 0, 'days_per_week' => 2]))->assertOk()->assertJsonPath('data.goal.revision', 1);
        $this->postJson($path, $this->operation(['revision' => 1, 'days_per_week' => null]))->assertOk()->assertJsonPath('data.goal.revision', 2)
            ->assertJsonPath('data.goal.days_per_week', null)->assertJsonPath('data.goal.met', false);
        $this->postJson($path, $this->operation(['revision' => 2, 'days_per_week' => 4]))->assertOk()->assertJsonPath('data.goal.revision', 3);
        // A tab still holding revision 1 of the first goal can neither overwrite nor clear the new one.
        foreach ([5, null] as $days) {
            $this->postJson($path, $this->operation(['revision' => 1, 'days_per_week' => $days]))->assertConflict();
        }
        $this->assertSame([4, 3], [PracticeGoal::sole()->days_per_week, PracticeGoal::sole()->revision]);
        // Clearing an already-cleared goal still bumps the revision consistently.
        $this->postJson($path, $this->operation(['revision' => 3, 'days_per_week' => null]))->assertOk()->assertJsonPath('data.goal.revision', 4);
        $this->postJson($path, $this->operation(['revision' => 4, 'days_per_week' => null]))->assertOk()->assertJsonPath('data.goal.revision', 5);
        $this->postJson($path, $this->operation(['revision' => 2, 'days_per_week' => 3]))->assertConflict();
        $this->assertSame([null, 5, 1], [PracticeGoal::sole()->days_per_week, PracticeGoal::sole()->revision, PracticeGoal::count()]);
        // A cleared goal is never met or celebrated, however much the learner practises.
        $this->practiseOn(['2026-02-09', '2026-02-10']);
        $this->assertNull(app(PracticeGoals::class)->celebrate(Students::findOrFail($this->asha())));
        $this->assertSame(0, PracticeGoalWeek::count());
        // A guardian still cannot write, at the current revision or any other.
        $this->signIn('farid');
        $this->postJson($path, $this->operation(['revision' => 5, 'days_per_week' => 2]))->assertNotFound();
        $this->getJson(self::BASE.'/learners/'.$this->asha().'/motivation')->assertOk()->assertJsonPath('data.goal.days_per_week', null)
            ->assertJsonPath('data.goal.met', false)->assertJsonPath('data.can_set_goal', false);
        $this->assertSame([null, 5], [PracticeGoal::sole()->days_per_week, PracticeGoal::sole()->revision]);
        // School B can neither read nor write it.
        $this->signIn('asha', '2');
        $this->assertContains($this->getJson(self::BASE.'/learners/'.$this->asha().'/motivation')->status(), [403, 404]);
        $this->assertContains($this->postJson($path, $this->operation(['revision' => 5, 'days_per_week' => 2]))->status(), [403, 404]);
        app(CurrentSchool::class)->run(self::SCHOOL_B_ID, fn () => $this->assertSame(0, PracticeGoal::count()));
        $this->assertSame(5, PracticeGoal::sole()->revision);
    }

    public function test_guardians_read_but_never_set_and_other_people_cannot_read_a_streak_or_goal(): void
    {
        $this->practiseOn(['2026-02-09']);
        PracticeGoal::create(['learner_id' => $this->asha(), 'days_per_week' => 3, 'set_by' => $this->actor('asha')->id]);
        $read = self::BASE.'/learners/'.$this->asha().'/motivation';
        $write = self::BASE.'/learners/'.$this->asha().'/practice-goal';
        $this->signIn('farid');
        $parent = $this->getJson($read)->assertOk()->assertJsonPath('data.goal.days_per_week', 3)->assertJsonPath('data.can_set_goal', false)
            ->assertJsonPath('data.streak.current_days', 1)->json('data');
        $this->assertNoComparisons($parent);
        $this->postJson($write, $this->operation(['revision' => 1, 'days_per_week' => 5]))->assertNotFound();
        $this->assertSame(3, PracticeGoal::sole()->days_per_week);
        // Wrong role: the teacher. Wrong owner: another family's guardian, another learner.
        foreach (['tara', 'gina', 'ben'] as $other) {
            $this->actor($other)->forceFill(['password_change_required' => false])->save();
            $this->signIn($other);
            $status = $this->getJson($read)->status();
            $this->assertContains($status, [403, 404], $other.' must not read the streak');
            $this->assertContains($this->postJson($write, $this->operation(['revision' => 1, 'days_per_week' => 5]))->status(), [403, 404]);
        }
        $this->assertSame(3, PracticeGoal::sole()->days_per_week);
        // Wrong school: the same people in School B's context find nothing.
        $this->signIn('asha', '2');
        $this->assertContains($this->getJson($read)->status(), [403, 404]);
        $this->assertContains($this->postJson($write, $this->operation(['revision' => 1, 'days_per_week' => 5]))->status(), [403, 404]);
        app(CurrentSchool::class)->run(self::SCHOOL_B_ID, function (): void {
            $this->assertSame(0, app(PracticeStreaks::class)->current($this->asha()));
            $this->assertSame(0, PracticeGoal::count());
        });
        // Withdrawn guardian approval hides it from everyone.
        LearnerApproval::where('learner_id', $this->asha())->update(['withdrawn_at' => now()]);
        $this->signIn('farid');
        $this->getJson($read)->assertNotFound();
    }

    public function test_early_years_learners_get_no_goal_streak_or_nudge(): void
    {
        $grade = (int) GradeLevel::where('code', '7')->value('id');
        SchoolStage::updateOrCreate(['grade_level_id' => $grade], ['stage' => SchoolStages::EARLY_YEARS]);
        app(SchoolStages::class)->forget();
        $this->practiseOn(['2026-02-02']);
        $this->signIn('asha');
        $this->assertContains($this->getJson(self::BASE.'/learners/'.$this->asha().'/motivation')->status(), [403, 404]);
        $this->assertContains($this->postJson(self::BASE.'/learners/'.$this->asha().'/practice-goal', $this->operation(['revision' => 0, 'days_per_week' => 2]))->status(), [403, 404]);
        $this->signIn('farid');
        $this->getJson(self::BASE.'/learners/'.$this->asha().'/motivation')->assertNotFound();
        $this->nudgeSettings();
        $this->at('2026-02-11 16:00');
        $this->assertSame(['guardian' => 0, 'learner' => 0, 'held' => 0], app(PracticeNudges::class)->run());
        $this->assertSame([0, 0], [PracticeGoal::count(), PracticeNudge::count()]);
    }

    public function test_a_family_visible_gap_that_becomes_secure_is_celebrated_once_a_term(): void
    {
        // Unknown is never a gap: a first secure result is not a closed gap.
        $this->evidence('practice', 1, 4);
        $this->assertSame([0, 0], [MasteryTopicGap::count(), MasteryGapClosure::count()]);
        MasteryEvidence::query()->delete();
        $this->evidence('practice', 0, 2);
        $this->assertSame(1, MasteryTopicGap::count());
        $this->evidence('practice', 1, 2);
        $this->assertSame(0, MasteryGapClosure::count(), 'Level 2 is still a gap.');
        $this->evidence('practice', 1, 6);
        $closure = MasteryGapClosure::sole();
        $this->assertSame([$this->fractions(), $this->term, 'practice'], [$closure->topic_id, $closure->term_id, $closure->audience]);
        $notice = Notification::where('type', GapClosures::TYPE)->sole();
        $this->assertGentle($notice);
        $family = [(int) $this->actor('asha')->id, (int) $this->actor('farid')->id, (int) $this->actor('mona')->id];
        sort($family);
        $this->assertSame($family, $this->recipients($notice));
        // Recomputation noise: a rebuild that changes nothing, and a dip and rise again, never notify twice this term.
        app(MasteryRecompute::class)->learner($this->asha(), $this->subject);
        $this->evidence('practice', 0, 12);
        $this->assertSame(1, MasteryTopicGap::count());
        $this->evidence('practice', 1, 20);
        $this->assertSame([1, 1], [MasteryGapClosure::count(), Notification::where('type', GapClosures::TYPE)->count()]);
        // The learner and their guardians see it, in plain words with no score.
        foreach (['asha', 'farid'] as $name) {
            $this->assertSame(1, $this->inbox($name, GapClosures::TYPE));
            $this->signIn($name);
            $summary = $this->getJson(self::BASE.'/learners/'.$this->asha().'/motivation')->assertOk()
                ->assertJsonPath('data.secure_topics.0.name', 'Fractions')->json('data');
            $this->assertSame(['topic_id', 'name', 'subject', 'secured_on'], array_keys($summary['secure_topics'][0]));
            $this->assertNoComparisons($summary);
        }
        foreach (['tara', 'gina'] as $other) {
            $this->assertSame(0, $this->inbox($other, GapClosures::TYPE));
            $this->assertFalse(app(MotivationNotices::class)->allows($this->actor($other), GapClosures::TYPE, $notice->delivery_payload));
        }
        // A new term may celebrate the same topic again, once.
        Term::create(['name' => 'Synthetic term 2', 'session_year_id' => $this->year, 'start_date' => '2026-04-01', 'end_date' => '2026-06-30']);
        $this->at('2026-04-14 10:00');
        $this->evidence('practice', 0, 20);
        $this->evidence('practice', 1, 20);
        $this->assertSame(2, MasteryGapClosure::count());
    }

    public function test_staff_only_mastery_never_celebrates_and_the_minimum_evidence_holds_a_gap_open(): void
    {
        $this->evidence('staff', 0, 3);
        $this->evidence('staff', 1, 20);
        $this->assertSame([0, 0], [MasteryTopicGap::count(), MasteryGapClosure::count()], 'Unreleased marks are never used.');
        config(['motivation.gap_closed.min_evidence' => 25]);
        MasteryEvidence::query()->delete();
        $this->evidence('practice', 0, 2);
        $this->evidence('practice', 1, 18);
        $this->assertSame([1, 0], [MasteryTopicGap::count(), MasteryGapClosure::count()]);
        // The mastery map switched off: nothing is observed at all.
        SchoolModule::where('module', 'mastery')->update(['enabled' => false]);
        app(ModuleGate::class)->forget();
        config(['motivation.gap_closed.min_evidence' => 3]);
        $this->evidence('practice', 1, 5);
        $this->assertSame(0, MasteryGapClosure::count());
    }

    public function test_gap_closed_uses_rules_opt_outs_and_quiet_hours(): void
    {
        $this->evidence('practice', 0, 2);
        $this->evidence('practice', 1, 10);
        $notice = Notification::where('type', GapClosures::TYPE)->sole();
        $farid = $this->actor('farid');
        $this->assertSame(['in_app'], app(Notifier::class)->send($farid, GapClosures::TYPE, $notice->title, $notice->message, GapClosures::LINK, $notice));
        // An opt-out is respected.
        $asha = $this->actor('asha');
        app(NotificationRules::class)->choose($asha, GapClosures::TYPE, 'in_app', false);
        $this->assertSame([], app(Notifier::class)->send($asha, GapClosures::TYPE, $notice->title, $notice->message, GapClosures::LINK, $notice));
        // Pushes wait for the end of quiet hours (it is not a safety notice).
        $this->assertFalse(QuietHours::exempt(GapClosures::TYPE));
        app(QuietHours::class)->save($farid, ['enabled' => true, 'starts_at' => '00:00', 'ends_at' => '23:59', 'timezone' => 'Africa/Johannesburg']);
        $this->assertNotNull(app(QuietHours::class)->holdUntil((int) $farid->id, GapClosures::TYPE));
        // Withdrawn approval stops delivery and hides it.
        LearnerApproval::where('learner_id', $this->asha())->update(['withdrawn_at' => now()]);
        $this->assertSame(0, $this->inbox('farid', GapClosures::TYPE));
        $this->assertFalse(app(MotivationNotices::class)->allows($farid, GapClosures::TYPE, $notice->delivery_payload));
    }

    public function test_guardian_and_learner_nudges_follow_school_days_once_a_week_never_in_quiet_hours(): void
    {
        $this->practiseOn(['2026-02-05']);
        $this->at('2026-02-11 16:00');
        $this->assertSame(['guardian' => 0, 'learner' => 0, 'held' => 0], app(PracticeNudges::class)->run(), 'Nudges start off.');
        $this->nudgeSettings();
        $this->at('2026-02-10 16:00');
        $this->assertSame(['guardian' => 0, 'learner' => 0, 'held' => 0], app(PracticeNudges::class)->run(), 'Only two school days (the weekend is not counted).');
        $this->at('2026-02-11 10:00');
        $this->assertSame(['guardian' => 0, 'learner' => 0, 'held' => 0], app(PracticeNudges::class)->run(), 'Afternoons only.');
        // Mona is in quiet hours: nothing at all is created for her now.
        app(QuietHours::class)->save($this->actor('mona'), ['enabled' => true, 'starts_at' => '15:00', 'ends_at' => '17:30', 'timezone' => 'Africa/Johannesburg']);
        $this->at('2026-02-11 16:00');
        $this->assertSame(['guardian' => 1, 'learner' => 1, 'held' => 1], app(PracticeNudges::class)->run());
        $this->assertSame(0, PracticeNudge::where('user_id', $this->actor('mona')->id)->count());
        $this->assertSame(['guardian' => 0, 'learner' => 0, 'held' => 1], app(PracticeNudges::class)->run(), 'Once a week.');
        $this->at('2026-02-11 17:45');
        $this->assertSame(['guardian' => 1, 'learner' => 0, 'held' => 0], app(PracticeNudges::class)->run(), 'After her quiet hours.');
        $this->at('2026-02-12 16:00');
        $this->assertSame(['guardian' => 0, 'learner' => 0, 'held' => 0], app(PracticeNudges::class)->run());
        foreach (Notification::where('type', PracticeNudges::TYPE)->get() as $notice) {
            $this->assertGentle($notice);
        }
        $this->assertSame(1, $this->inbox('farid', PracticeNudges::TYPE));
        $this->assertSame(1, $this->inbox('asha', PracticeNudges::TYPE));
        $this->assertSame(0, $this->inbox('gina', PracticeNudges::TYPE));
        $nudge = PracticeNudge::where('user_id', $this->actor('farid')->id)->sole();
        $this->assertFalse(app(MotivationNotices::class)->allows($this->actor('mona'), PracticeNudges::TYPE, Notification::findOrFail($nudge->notification_id)->delivery_payload), 'Each nudge is its recipient\'s own.');
        // A new week; a holiday is not a school day; practising again stops nudges.
        $this->holiday('2026-02-16');
        $this->at('2026-02-16 16:00');
        $this->assertSame(['guardian' => 0, 'learner' => 0, 'held' => 0], app(PracticeNudges::class)->run());
        $this->practiseOn(['2026-02-17']);
        $this->at('2026-02-17 16:00');
        $this->assertSame(['guardian' => 0, 'learner' => 0, 'held' => 0], app(PracticeNudges::class)->run());
    }

    public function test_nudge_opt_outs_and_learners_who_never_practised_are_left_alone(): void
    {
        $this->nudgeSettings();
        $this->at('2026-02-11 16:00');
        $this->assertSame(['guardian' => 0, 'learner' => 0, 'held' => 0], app(PracticeNudges::class)->run(), 'Never practised: no nudge.');
        $this->practiseOn(['2026-02-05']);
        foreach (['in_app', 'push'] as $channel) {
            app(NotificationRules::class)->choose($this->actor('asha'), PracticeNudges::TYPE, $channel, false);
            app(NotificationRules::class)->choose($this->actor('farid'), PracticeNudges::TYPE, $channel, false);
        }
        $this->assertSame(['guardian' => 1, 'learner' => 0, 'held' => 0], app(PracticeNudges::class)->run(), 'Only Mona has not switched it off.');
        $this->assertSame([(int) $this->actor('mona')->id], PracticeNudge::pluck('user_id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_only_school_managers_configure_nudges_and_school_b_never_acts_on_school_a(): void
    {
        $data = $this->operation(['revision' => 0, 'guardian_nudge_enabled' => true, 'learner_nudge_enabled' => false, 'nudge_after_school_days' => 4]);
        foreach (['tara', 'farid', 'asha'] as $wrong) {
            $this->refuses(fn () => app(MotivationSettings::class)->configure($this->actor($wrong), $data), 403);
        }
        $this->assertSame(0, PracticeMotivationSettings::count());
        $this->assertSame(1, app(MotivationSettings::class)->configure($this->superAdmin(), $data)['revision']);
        $this->assertSame(1, app(MotivationSettings::class)->configure($this->superAdmin(), $data)['revision'], 'An exact retry replays.');
        $this->refuses(fn () => app(MotivationSettings::class)->configure($this->superAdmin(), $this->operation(['revision' => 0] + $data)), 409);
        // Panel: manager saves; teacher refused; invalid value refused.
        $admin = $this->superAdmin();
        $this->actingAs($admin)->get('/practice-motivation')->assertOk()->assertSee('Practice reminders')->assertSee('Remind guardians');
        $form = $this->operation(['revision' => 1, 'guardian_nudge_enabled' => 1, 'learner_nudge_enabled' => 1, 'nudge_after_school_days' => 11]);
        $this->put('/practice-motivation', $form)->assertSessionHasErrors('nudge_after_school_days');
        $this->put('/practice-motivation', array_replace($form, ['nudge_after_school_days' => 5]))->assertRedirect();
        $this->assertSame([5, true, 2], [PracticeMotivationSettings::sole()->nudge_after_school_days, PracticeMotivationSettings::sole()->learner_nudge_enabled, PracticeMotivationSettings::sole()->revision]);
        $this->actingAs($this->actor())->get('/practice-motivation')->assertForbidden();
        $this->actingAs($this->actor())->put('/practice-motivation', $this->operation(['revision' => 2] + $form))->assertForbidden();
        $this->assertSame(2, PracticeMotivationSettings::sole()->revision);
        // School B: no settings, nudges or rows of School A; its run never reaches School A's learners.
        $this->practiseOn(['2026-02-05']);
        // Five school days after Thursday 5 February (the weekend is not counted).
        $this->at('2026-02-13 16:00');
        app(CurrentSchool::class)->run(self::SCHOOL_B_ID, function (): void {
            SchoolModule::updateOrCreate(['module' => 'daily_practice'], ['in_package' => true, 'enabled' => true]);
            app(ModuleGate::class)->forget();
            $this->assertSame(0, app(MotivationSettings::class)->current()['revision']);
            $this->assertSame(['guardian' => 0, 'learner' => 0, 'held' => 0], app(PracticeNudges::class)->run());
            $this->assertSame([0, 0, 0], [PracticeMotivationSettings::count(), PracticeNudge::count(), MasteryGapClosure::count()]);
        });
        $this->assertSame(['guardian' => 2, 'learner' => 1, 'held' => 0], app(PracticeNudges::class)->run());
        $this->assertSame(0, app(CurrentSchool::class)->run(self::SCHOOL_B_ID, fn () => PracticeNudge::count() + Notification::where('type', PracticeNudges::TYPE)->count()));
        $notice = Notification::where('type', PracticeNudges::TYPE)->firstOrFail();
        app(CurrentSchool::class)->run(self::SCHOOL_B_ID, function () use ($notice): void {
            $this->assertFalse(app(MotivationNotices::class)->allows(User::acrossSchools()->findOrFail($this->schoolA->ids['farid_user']), PracticeNudges::TYPE, $notice->delivery_payload));
        });
    }

    public function test_streak_badges_start_off_and_are_presented_once_a_year_through_recognition(): void
    {
        $this->practiseOn(['2026-02-02', '2026-02-03', '2026-02-04', '2026-02-05', '2026-02-06']);
        $start = CarbonImmutable::parse('2026-02-02');
        $badges = app(PersistenceBadges::class);
        $settings = ['revision' => 0, 'practice_days_enabled' => false, 'practice_days_required' => 4, 'comeback_enabled' => false, 'comeback_below_percent' => 50];
        $badges->configure($this->superAdmin(), $this->operation($settings + ['practice_days_enabled' => true]));
        $this->assertFalse($badges->current()['streak_enabled'], 'Streak badges start off.');
        $badges->configure($this->superAdmin(), $this->operation(['revision' => 1, 'streak_enabled' => true] + $settings));
        $this->assertSame(1, $badges->week($start)['awarded']);
        $this->assertSame(0, $badges->week($start)['awarded']);
        $award = AwardPresentation::sole();
        $this->assertSame(['badge', 'Kept a practice run going for 5 days'], [$award->source_kind, $award->title]);
        $this->assertSame('streak', RecognitionBadge::sole()->kind);
        $this->assertSame(1, Notification::where('type', 'recognition.award_presented')->count(), 'Families hear about it through the award notice.');
        // The run keeps going next week: the same milestone is never presented again this year.
        $this->practiseOn(['2026-02-09']);
        $this->travelTo(now()->addWeek());
        $this->assertSame(0, $badges->week($start->addWeek())['awarded']);
        $this->assertSame(1, RecognitionBadge::count());
        // Panel form shows the switch.
        $this->actingAs($this->superAdmin())->get('/recognition/awards')->assertOk()->assertSee('Practice run badges');
    }

    public function test_parent_digest_adds_a_reached_goal_and_a_run_but_never_a_missed_one(): void
    {
        $ref = app(DigestTemplates::class)->create($this->superAdmin(), $this->operation(['language' => 'en'] + config('weekly_digests.starting_templates.en')));
        app(DigestTemplates::class)->review($this->superAdmin(), $ref['id'], $this->operation(['revision' => 1, 'decision' => 'approve']));
        $this->practiseOn(['2026-02-02', '2026-02-03', '2026-02-04', '2026-02-05', '2026-02-06']);
        PracticeGoalWeek::create(['learner_id' => $this->asha(), 'user_id' => $this->actor('asha')->id, 'week_start' => '2026-02-02', 'days_target' => 3]);
        $digest = app(WeeklyDigests::class)->generate($this->actor('farid'), $this->asha(), CarbonImmutable::parse('2026-02-02'));
        $this->assertStringContainsString('reached their weekly practice goal of 3 days. Well done!', $digest->summary);
        $this->assertStringContainsString('has kept a practice run going for 5 days.', $digest->summary);
        $this->assertSame(['goal_met_days' => 3, 'streak_days' => 5], $digest->growth['practice']);
        // A week without either leaves both lines out: no missed goal, no zero.
        $quiet = app(WeeklyDigests::class)->generate($this->actor('farid'), $this->asha(), CarbonImmutable::parse('2026-01-19'));
        $this->assertStringNotContainsString('practice goal', $quiet->summary);
        $this->assertStringNotContainsString('practice run', $quiet->summary);
        $this->assertSame(1, DigestLanguageTemplate::count());
    }

    public function test_rollback_refuses_while_motivation_records_are_retained(): void
    {
        PracticeGoal::create(['learner_id' => $this->asha(), 'days_per_week' => 3, 'set_by' => $this->actor('asha')->id]);
        $migration = require database_path('migrations/2026_11_07_131300_create_practice_motivation.php');
        try {
            $migration->down();
            $this->fail('Retained goals must block rollback.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Retained', $e->getMessage());
        }
        $this->assertSame(1, PracticeGoal::count());
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('recognition_badge_settings', 'streak_enabled'));
    }
}
