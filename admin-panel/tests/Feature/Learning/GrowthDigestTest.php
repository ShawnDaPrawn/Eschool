<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Jobs\Platform\DispatchOutbox;
use App\Models\Academics\CurriculumTopic;
use App\Models\Academics\TeachingGroup;
use App\Models\Growth\MasteryEvidence;
use App\Models\Growth\MasteryRecord;
use App\Models\Learning\DigestLanguageTemplate;
use App\Models\Learning\LearnerGrowthWeek;
use App\Models\Learning\PracticeDailySet;
use App\Models\Learning\PracticeReview;
use App\Models\Learning\PracticeSetEntry;
use App\Models\Learning\WeeklyParentDigest;
use App\Models\Notification;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\NotificationRule;
use App\Models\Platform\SchoolModule;
use App\Models\Recognition\AwardPresentation;
use App\Models\Recognition\RecognitionBadge;
use App\Models\Recognition\RecognitionBadgeSettings;
use App\Models\Students;
use App\Models\Subject;
use App\Models\User;
use App\Services\Growth\MasteryRecompute;
use App\Services\Learning\Digests\DigestTemplates;
use App\Services\Learning\Digests\GrowthMovement;
use App\Services\Learning\Digests\LearnerGrowthNotices;
use App\Services\Learning\Digests\WeeklyDigests;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\NotificationRules;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Notifications\PrivateNoticeScopes;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Recognition\AwardPresentations;
use App\Services\Recognition\PersistenceBadges;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\UsesPracticeScenario;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

/** Action Plan 4.6: engineering/specs/growth/growth-digest.md. */
class GrowthDigestTest extends TestCase
{
    use UsesPracticeScenario;
    use ValidatesOpenApiResponses;

    private const WEEK = '2026-02-09';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        SchoolModule::updateOrCreate(['module' => 'digest'], ['in_package' => true, 'enabled' => true]);
        SchoolModule::updateOrCreate(['module' => 'awards'], ['in_package' => true, 'enabled' => true]);
        app(ModuleGate::class)->forget();
        Bus::fake([DispatchOutbox::class]);
    }

    private function operation(array $data = []): array
    {
        return ['operation_id' => (string) Str::uuid()] + $data;
    }

    private function topic(int $index): int
    {
        return (int) $this->nodes[$this->keys[$index]];
    }

    private function evidence(int $topic, string $audience, string $at, int $achieved, int $times = 1): void
    {
        $grade = (int) TeachingGroup::findOrFail($this->group)->grade_level_id;
        for ($i = 0; $i < $times; $i++) {
            MasteryEvidence::create(['subject_id' => $this->subject, 'learner_id' => $this->schoolA->ids['asha'], 'topic_id' => $this->topic($topic),
                'grade_level_id' => $grade, 'session_year_id' => $this->year, 'audience' => $audience, 'source_type' => 'practice_review',
                'source_key' => 'synthetic:'.Str::uuid(), 'achieved_units' => $achieved, 'maximum_units' => 1, 'observed_at' => $at]);
        }
    }

    /**
     * Fractions rises (practice), Decimals falls (practice), Number foundation
     * rises only in staff-only and family released-mark evidence.
     */
    private function week(): void
    {
        $this->evidence(1, 'practice', '2026-02-02 08:00:00', 0, 2);
        $this->evidence(1, 'practice', '2026-02-10 08:00:00', 1, 3);
        $this->evidence(2, 'practice', '2026-02-02 08:00:00', 1, 2);
        $this->evidence(2, 'practice', '2026-02-11 08:00:00', 0, 2);
        foreach (['staff', 'family'] as $audience) {
            $this->evidence(0, $audience, '2026-02-03 08:00:00', 0, 2);
            $this->evidence(0, $audience, '2026-02-12 08:00:00', 1, 3);
        }
        app(MasteryRecompute::class)->learner($this->schoolA->ids['asha'], $this->subject);
    }

    private function monday(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-02-16T08:00:00+02:00'));
    }

    private function language(): void
    {
        $ref = app(DigestTemplates::class)->create($this->superAdmin(), $this->operation(['language' => 'en'] + config('weekly_digests.starting_templates.en')));
        app(DigestTemplates::class)->review($this->superAdmin(), $ref['id'], $this->operation(['revision' => 1, 'decision' => 'approve']));
    }

    private function digest(): WeeklyParentDigest
    {
        $this->language();
        $this->monday();

        return app(WeeklyDigests::class)->generate($this->actor('farid'), $this->schoolA->ids['asha'], CarbonImmutable::parse(self::WEEK));
    }

    private function grewNames(): array
    {
        return array_column(app(GrowthMovement::class)->grew($this->schoolA->ids['asha'], CarbonImmutable::parse(self::WEEK)), 'name');
    }

    private function badgeSettings(array $changes = []): RecognitionBadgeSettings
    {
        return app(PersistenceBadges::class)->configure($this->superAdmin(), $this->operation($changes + ['revision' => 0, 'practice_days_enabled' => true,
            'practice_days_required' => 3, 'comeback_enabled' => true, 'comeback_below_percent' => 50]));
    }

    private function practiseOn(array $days): void
    {
        $version = $this->approve()['version_id'];
        foreach ($days as $day) {
            $set = PracticeDailySet::create(['learner_id' => $this->schoolA->ids['asha'], 'teaching_group_id' => $this->group, 'practice_on' => $day, 'offline_until' => $day.' 23:59:59']);
            $entry = PracticeSetEntry::create(['practice_daily_set_id' => $set->id, 'practice_item_version_id' => $version, 'position' => 1]);
            PracticeReview::create(['learner_id' => $this->schoolA->ids['asha'], 'practice_set_entry_id' => $entry->id, 'answer' => '0.5', 'correct' => true, 'schedule' => [], 'reviewed_on' => $day]);
        }
    }

    public function test_grew_in_lists_only_rises_from_evidence_the_family_may_see(): void
    {
        $this->week();
        // Practice evidence only while the school keeps released marks out of family mastery.
        $this->assertSame(['Fractions'], $this->grewNames());
        $this->graph(true);
        $this->assertEqualsCanonicalizing(['Fractions', 'Number foundation'], $this->grewNames());
        $this->assertNotContains('Decimals', $this->grewNames());
    }

    public function test_growth_compares_like_with_like_within_one_model_version(): void
    {
        $this->week();
        MasteryRecord::where('topic_id', $this->topic(1))->where('audience', 'practice')->update(['policy_version' => 'other-model-v0']);
        $this->assertSame([], $this->grewNames());
        $this->assertSame('recent20-v1', app(GrowthMovement::class)->modelVersion());
    }

    public function test_first_evidence_is_unknown_not_growth(): void
    {
        $this->evidence(1, 'practice', '2026-02-10 08:00:00', 1, 3);
        app(MasteryRecompute::class)->learner($this->schoolA->ids['asha'], $this->subject);
        $this->assertSame([], $this->grewNames());
    }

    public function test_parent_digest_shows_growth_and_help_at_home_without_changing_the_api_shape(): void
    {
        $this->week();
        CurriculumTopic::where('key', $this->keys[2])->update(['week_from' => 7, 'week_to' => 7]);
        CurriculumTopic::where('key', $this->keys[0])->update(['week_from' => 2, 'week_to' => 3]);
        $digest = $this->digest();
        $maths = Subject::findOrFail($this->subject)->name;
        $this->assertStringContainsString('grew in: Fractions.', $digest->summary);
        $this->assertStringContainsString('to talk about at home: '.$maths.': Decimals.', $digest->summary);
        $this->assertStringNotContainsString('Number foundation', $digest->summary);
        $this->assertSame(['Fractions'], array_column($digest->growth['grew_in'], 'name'));
        $this->assertSame([['subject' => $maths, 'topic' => 'Decimals']], $digest->growth['help_at_home']);
        $this->assertSame(['recent20-v1', 'practice'], [$digest->growth['policy_version'], $digest->growth['audience']]);
        Sanctum::actingAs($this->actor('farid'));
        $this->withHeaders(['Accept' => 'application/json', 'X-School-Id' => '1', 'X-Account-Id' => (string) $this->actor('farid')->id]);
        $this->getJson('/api/v2/digests/'.$digest->id)->assertOk()->assertJsonMissingPath('data.growth')->assertJsonMissingPath('data.evidence.growth')
            ->assertJsonPath('data.summary', $digest->summary);
    }

    public function test_a_week_without_growth_leaves_the_growth_lines_out(): void
    {
        $digest = $this->digest();
        $this->assertStringNotContainsString('grew in', $digest->summary);
        $this->assertStringNotContainsString('talk about at home', $digest->summary);
        $this->assertSame([], $digest->growth['grew_in']);
    }

    public function test_learner_gets_own_growth_once_in_app_only_and_parent_cannot_read_it(): void
    {
        $this->week();
        $this->monday();
        $this->assertSame(1, app(WeeklyDigests::class)->monday()['learner_growth']);
        $this->assertSame(0, app(WeeklyDigests::class)->monday()['learner_growth']);
        $week = LearnerGrowthWeek::sole();
        $this->assertSame([['topic_id' => $this->topic(1), 'name' => 'Fractions', 'subject' => Subject::findOrFail($this->subject)->name]], $week->topics);
        $notice = Notification::where('type', 'growth.weekly')->sole();
        $this->assertSame('This week you grew in Fractions. Keep going!', $notice->message);
        $this->assertSame(['link' => '/growth/mastery', 'growth_week_id' => (int) $week->id], $notice->delivery_payload);
        $this->assertSame(['push' => 'off', 'email' => 'off', 'in_app' => 'default_on'], config('notification_types')['growth.weekly']['channels']);
        $asha = $this->actor('asha');
        $this->assertSame(1, app(PrivateNoticeScopes::class)->apply(Notification::query(), (int) $asha->id)->where('type', 'growth.weekly')->count());
        $this->assertTrue(app(LearnerGrowthNotices::class)->allows($asha, 'growth.weekly', $notice->delivery_payload));
        // Wrong owner: the parent (and any other account) never sees the learner's own note.
        $parent = $this->actor('farid');
        $this->assertSame(0, app(PrivateNoticeScopes::class)->apply(Notification::query(), (int) $parent->id)->where('type', 'growth.weekly')->count());
        $this->assertFalse(app(LearnerGrowthNotices::class)->allows($parent, 'growth.weekly', $notice->delivery_payload));
    }

    public function test_a_school_cannot_switch_push_or_email_on_for_the_learner_growth_note(): void
    {
        $rules = app(NotificationRules::class);
        foreach (['push', 'email'] as $channel) {
            foreach (['mandatory', 'default_on', 'default_off'] as $mode) {
                try {
                    $rules->change($this->superAdmin(), 'learner', 'growth.weekly', $channel, $mode);
                    $this->fail('A school must not switch '.$channel.' on for growth.weekly.');
                } catch (ValidationException) {
                }
            }
        }
        $this->assertSame(0, NotificationRule::where('type', 'growth.weekly')->count());
        $rules->change($this->superAdmin(), 'learner', 'growth.weekly', 'email', 'off');
        // A rule saved some other way (or before the lock) still has no effect.
        foreach (['push', 'email'] as $channel) {
            NotificationRule::updateOrCreate(['portal' => 'learner', 'type' => 'growth.weekly', 'channel' => $channel], ['mode' => 'mandatory', 'changed_by' => $this->superAdmin()->id]);
        }
        $rules->forget();
        $asha = $this->actor('asha');
        $asha->forceFill(['email' => 'asha.synthetic@example.test', 'email_verified_at' => now()])->save();
        foreach (['push', 'email'] as $channel) {
            $this->assertSame('off', $rules->mode('learner', 'growth.weekly', $channel));
            $this->assertFalse($rules->allows($asha, 'growth.weekly', $channel));
        }
        $this->week();
        $this->monday();
        app(WeeklyDigests::class)->monday();
        $notice = Notification::where('type', 'growth.weekly')->sole();
        $sent = app(Notifier::class)->send($asha, 'growth.weekly', $notice->title, $notice->message, LearnerGrowthNotices::LINK, $notice);
        $this->assertSame(['in_app'], $sent);
        $this->assertSame(['in_app'], DB::table('notification_dispatches')->where('notification_id', $notice->id)->pluck('channel')->all());
    }

    public function test_withdrawn_guardian_approval_stops_growth_digest_learner_note_and_badges(): void
    {
        $this->week();
        $this->practiseOn(['2026-02-09', '2026-02-10', '2026-02-11']);
        $this->badgeSettings();
        $this->language();
        $this->monday();
        LearnerApproval::where('learner_id', $this->schoolA->ids['asha'])->update(['withdrawn_at' => now()]);
        $learner = Students::findOrFail($this->schoolA->ids['asha']);
        $this->assertNull(app(LearnerGrowthNotices::class)->deliver($learner, CarbonImmutable::parse(self::WEEK)));
        $this->refuses(fn () => app(WeeklyDigests::class)->generate($this->actor('farid'), $this->schoolA->ids['asha'], CarbonImmutable::parse(self::WEEK)), 404);
        $this->assertSame(0, app(PersistenceBadges::class)->week(CarbonImmutable::parse(self::WEEK))['awarded']);
        $this->assertSame([0, 0, 0, 0], [LearnerGrowthWeek::count(), WeeklyParentDigest::count(), AwardPresentation::count(), RecognitionBadge::count()]);
    }

    public function test_withdrawn_approval_hides_an_already_delivered_learner_note(): void
    {
        $this->week();
        $this->monday();
        app(WeeklyDigests::class)->monday();
        $notice = Notification::where('type', 'growth.weekly')->sole();
        $asha = $this->actor('asha');
        $inbox = app(PrivateNoticeScopes::class)->apply(Notification::query(), (int) $asha->id)->where('type', 'growth.weekly');
        $this->assertSame(1, (clone $inbox)->count());
        LearnerApproval::where('learner_id', $this->schoolA->ids['asha'])->update(['withdrawn_at' => now()]);
        $this->assertSame(0, (clone $inbox)->count());
        $this->assertFalse(app(LearnerGrowthNotices::class)->allows($asha, 'growth.weekly', $notice->delivery_payload));
        SchoolModule::where('module', 'mastery')->update(['enabled' => false]);
        $this->assertSame(0, (clone $inbox)->count());
    }

    public function test_persistence_badges_are_awarded_once_through_recognition_and_never_again_after_a_correction(): void
    {
        $this->week();
        $this->practiseOn(['2026-02-09', '2026-02-10', '2026-02-11']);
        $this->monday();
        $start = CarbonImmutable::parse(self::WEEK);
        $this->assertSame(0, app(PersistenceBadges::class)->week($start)['awarded'], 'Badges start off.');
        $this->badgeSettings();
        $this->assertSame(2, app(PersistenceBadges::class)->week($start)['awarded']);
        $this->assertSame(0, app(PersistenceBadges::class)->week($start)['awarded']);
        $awards = AwardPresentation::orderBy('id')->get();
        $this->assertSame(['badge', 'badge'], $awards->pluck('source_kind')->all());
        $this->assertSame(['Kept practising on 3 days in one week', 'Came back to Fractions and grew'], $awards->pluck('title')->all());
        $this->assertSame(['2026-02-15', '2026-02-15'], $awards->map(fn ($a) => $a->presented_on->toDateString())->all());
        $this->assertSame((int) $this->superAdmin()->id, (int) $awards->first()->presented_by);
        $this->assertSame(2, Notification::where('type', 'recognition.award_presented')->count());
        $profile = app(AwardPresentations::class)->profile($this->actor('farid'), $this->schoolA->ids['asha'], true);
        $this->assertCount(2, $profile['items']);
        app(AwardPresentations::class)->void($this->superAdmin(), (int) $awards->first()->id, $this->operation(['revision' => 1, 'reason' => 'Synthetic correction']));
        $this->assertSame(0, app(PersistenceBadges::class)->week($start)['awarded']);
        $this->assertSame(1, AwardPresentation::where('state', 'active')->count());
        $this->assertSame(2, RecognitionBadge::count());
    }

    public function test_badges_follow_the_school_switch_threshold_and_awards_module(): void
    {
        $this->week();
        $this->practiseOn(['2026-02-09', '2026-02-10']);
        $this->monday();
        $start = CarbonImmutable::parse(self::WEEK);
        $this->badgeSettings(['comeback_enabled' => false]);
        $this->assertSame(0, app(PersistenceBadges::class)->week($start)['awarded'], 'Two days is below the school threshold of three.');
        SchoolModule::where('module', 'awards')->update(['enabled' => false]);
        RecognitionBadgeSettings::sole()->update(['practice_days_required' => 2]);
        $this->assertSame(0, app(PersistenceBadges::class)->week($start)['awarded'], 'Awards module off.');
        SchoolModule::where('module', 'awards')->update(['enabled' => true]);
        $this->assertSame(1, app(PersistenceBadges::class)->week($start)['awarded']);
    }

    public function test_only_award_managers_configure_badges_and_retries_replay(): void
    {
        $data = $this->operation(['revision' => 0, 'practice_days_enabled' => true, 'practice_days_required' => 4, 'comeback_enabled' => false, 'comeback_below_percent' => 50]);
        foreach ([$this->actor(), $this->actor('farid'), $this->actor('asha')] as $wrong) {
            $this->refuses(fn () => app(PersistenceBadges::class)->configure($wrong, $data), 403);
        }
        $this->assertSame(0, RecognitionBadgeSettings::count());
        $first = app(PersistenceBadges::class)->configure($this->superAdmin(), $data);
        $this->assertSame($first->id, app(PersistenceBadges::class)->configure($this->superAdmin(), $data)->id);
        $this->assertSame(1, $first->fresh()->revision);
        $this->refuses(fn () => app(PersistenceBadges::class)->configure($this->superAdmin(), $this->operation(['revision' => 0] + $data)), 409);
        $this->assertSame(['practice_days_enabled' => true, 'practice_days_required' => 4, 'comeback_enabled' => false, 'comeback_below_percent' => 50, 'revision' => 1], app(PersistenceBadges::class)->current());
    }

    public function test_panel_manager_saves_badges_and_teacher_or_other_school_cannot(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin)->get('/recognition/awards')->assertOk()->assertSee('Persistence badges')->assertSee('Different practice days needed in a week');
        $form = $this->operation(['expected_account_id' => $admin->id, 'expected_school_id' => 1, 'revision' => 0, 'practice_days_enabled' => 1, 'practice_days_required' => 5, 'comeback_enabled' => 1, 'comeback_below_percent' => 40]);
        $this->post('/recognition/awards/actions/badges', array_replace($form, ['expected_school_id' => 2]))->assertForbidden();
        $this->assertSame(0, RecognitionBadgeSettings::count());
        $this->post('/recognition/awards/actions/badges', array_replace($form, ['practice_days_required' => 9]))->assertUnprocessable();
        $this->assertSame(0, RecognitionBadgeSettings::count());
        $this->post('/recognition/awards/actions/badges', $form)->assertRedirect();
        $this->assertSame(5, RecognitionBadgeSettings::sole()->practice_days_required);
        SchoolModule::updateOrCreate(['module' => 'house_points'], ['in_package' => true, 'enabled' => true]);
        $this->post('/recognition/house_points/actions/badges', $this->operation($form))->assertNotFound();
        $teacher = $this->actor();
        $this->actingAs($teacher)->post('/recognition/awards/actions/badges', $this->operation(['expected_account_id' => $teacher->id, 'expected_school_id' => 1, 'revision' => 1] + $form))->assertForbidden();
        $this->assertSame(1, RecognitionBadgeSettings::sole()->revision);
    }

    public function test_school_b_never_sees_school_a_growth_settings_or_badges(): void
    {
        $this->week();
        $this->practiseOn(['2026-02-09', '2026-02-10', '2026-02-11']);
        $this->badgeSettings();
        $this->monday();
        app(WeeklyDigests::class)->monday();
        $asha = $this->schoolA->ids['asha'];
        $notice = Notification::where('type', 'growth.weekly')->sole();
        app(CurrentSchool::class)->run(2, function () use ($asha, $notice): void {
            SchoolModule::updateOrCreate(['module' => 'awards'], ['in_package' => true, 'enabled' => true]);
            app(ModuleGate::class)->forget();
            $this->assertSame([], app(GrowthMovement::class)->grew($asha, CarbonImmutable::parse(self::WEEK)));
            $this->assertSame(0, RecognitionBadgeSettings::count());
            $this->assertSame(0, app(PersistenceBadges::class)->current()['revision']);
            $this->assertSame(0, app(PersistenceBadges::class)->week(CarbonImmutable::parse(self::WEEK))['awarded']);
            $this->assertSame(0, LearnerGrowthWeek::count());
            $this->assertFalse(app(LearnerGrowthNotices::class)->allows(User::acrossSchools()->findOrFail($this->schoolA->ids['asha_user']), 'growth.weekly', $notice->delivery_payload));
        });
        // School B's learner id from School A's context is not a School A learner.
        $this->assertSame([], app(GrowthMovement::class)->grew($this->schoolB->ids['asha'], CarbonImmutable::parse(self::WEEK)));
        $this->assertSame(2, app(PersistenceBadges::class)->week(CarbonImmutable::parse(self::WEEK))['awarded']);
        $this->assertSame(0, app(CurrentSchool::class)->run(2, fn () => AwardPresentation::count() + RecognitionBadge::count()));
    }

    public function test_rollback_refuses_while_badges_or_growth_receipts_are_retained(): void
    {
        $this->badgeSettings();
        $migration = require database_path('migrations/2026_11_05_130000_add_growth_digest_and_persistence_badges.php');
        try {
            $migration->down();
            $this->fail('Retained badge settings must block rollback.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Retained', $e->getMessage());
        }
        $this->assertSame(1, RecognitionBadgeSettings::count());
    }

    public function test_retention_erases_expired_learner_growth_notes(): void
    {
        $this->week();
        $this->monday();
        app(WeeklyDigests::class)->monday();
        LearnerGrowthWeek::query()->update(['expires_at' => now()->subMinute()]);
        $this->artisan('digest:weekly', ['--retention' => true])->assertSuccessful();
        $this->assertSame(0, LearnerGrowthWeek::count());
    }

    public function test_starting_template_lines_render_only_when_there_is_something_to_show(): void
    {
        $this->language();
        $template = DigestLanguageTemplate::sole();
        $values = ['learner' => 'Synthetic learner', 'week_start' => '2026-02-09', 'week_end' => '2026-02-15', 'marks_count' => 0, 'attendance_present' => 0,
            'attendance_absent' => 0, 'practice_completed' => 0, 'practice_total' => 0, 'event_count' => 0, 'home_action' => 'Synthetic'];
        $none = app(DigestTemplates::class)->render($template, $values)['summary'];
        $this->assertStringEndsWith('At home: Synthetic', $none);
        $grew = app(DigestTemplates::class)->render($template, $values + ['grew_in' => 'Fractions'])['summary'];
        $this->assertStringEndsWith("At home: Synthetic\nThis week Synthetic learner grew in: Fractions.", $grew);
    }
}
