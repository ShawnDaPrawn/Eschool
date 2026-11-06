<?php

declare(strict_types=1);

namespace Tests\Feature\FamilySummary;

use App\Jobs\Platform\DispatchOutbox;
use App\Models\Academics\GradeLevel;
use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Activities\ActivityStaff;
use App\Models\Ai\AiUsage;
use App\Models\ClassSchool;
use App\Models\Family\FamilyDailySummary;
use App\Models\Family\FamilyDailySummaryPart;
use App\Models\Family\FamilySummaryPreference;
use App\Models\Notification;
use App\Models\Parents;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Teacher;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Academics\DayPatterns;
use App\Services\Academics\TeachingGroups;
use App\Services\Academics\TimetableConfiguration;
use App\Services\Academics\TimetableEngine;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\InferenceProvider;
use App\Services\Ai\Inference\ModerationContext;
use App\Services\Ai\Inference\ProviderRequest;
use App\Services\Ai\Inference\ProviderResponse;
use App\Services\Ai\Providers\OpenAiModerator;
use App\Services\FamilyForms\Consents;
use App\Services\FamilySummary\FamilyDailySummaries;
use App\Services\FamilySummary\FamilySummaryPreferences;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Notifications\QuietHours;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolTable;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

/**
 * The daily family summary (Action Plan 5.11):
 * engineering/specs/notifications/family-daily-summary.md.
 *
 * Farid (School A) is the father of Asha and Cara at School A and, for these
 * tests, of Baobab Asha at School B. Gina is Ben's guardian at School A only.
 * The clock is Tuesday 10 February 2026, 14:00 in Johannesburg.
 */
class FamilyDailySummaryTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private const SURNAME = 'Surnamecanary';

    private User $farid;

    private User $gina;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        Bus::fake([DispatchOutbox::class]);
        $this->travelTo(CarbonImmutable::parse('2026-02-10 12:00:00', 'UTC'));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01', 'ends_on' => null]);
        foreach ([1, self::SCHOOL_B_ID] as $school) {
            app(CurrentSchool::class)->run($school, fn () => SessionYear::query()->whereKey((int) schoolSetting('session_year'))->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']));
        }
        $this->farid = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->farid->forceFill(['email_verified_at' => now()])->save();
        $this->gina = User::findOrFail($this->schoolA->ids['gina_user']);
        User::query()->whereKey($this->schoolA->ids['asha_user'])->update(['last_name' => self::SURNAME]);
        // Farid is also Baobab Asha's father at School B.
        app(CurrentSchool::class)->run(self::SCHOOL_B_ID, function () {
            $this->farid->assignRole('Parent');
            SchoolMembership::query()->where('user_id', $this->farid->id)->update(['starts_on' => '2026-01-01']);
            $parent = Parents::query()->forceCreate(['user_id' => $this->farid->id, 'first_name' => 'Farid', 'last_name' => 'Parent', 'gender' => 'Male', 'email' => $this->farid->email, 'mobile' => '0123456789']);
            Students::query()->whereKey($this->schoolB->ids['asha'])->update(['father_id' => $parent->id]);
        });
        $this->homework(1, $this->schoolA, 'Fraction worksheet two', '2026-02-11 17:00:00');
        $this->homework(self::SCHOOL_B_ID, $this->schoolB, 'Baobab reading log', '2026-02-10 18:00:00');
        $this->optIn($this->farid);
        $this->ai();
        FamilySummaryFakeProvider::reset();
    }

    private function homework(int $school, $demo, string $title, string $due): void
    {
        app(CurrentSchool::class)->run($school, fn () => SchoolTable::query('assignments')->insert([
            'class_section_id' => $demo->ids['section_a'], 'subject_id' => $demo->ids['maths'], 'name' => $title, 'instructions' => 'Synthetic instructions',
            'due_date' => $due, 'points' => 10, 'resubmission' => 0, 'extra_days_for_resubmission' => 0, 'session_year_id' => $demo->ids['session_year'],
            'created_at' => now(), 'updated_at' => now(),
        ]));
    }

    private function optIn(User $user, string $time = '07:00'): void
    {
        FamilySummaryPreference::query()->updateOrCreate(['user_id' => $user->id], ['enabled' => true, 'send_at' => $time.':00', 'timezone' => 'Africa/Johannesburg', 'revision' => 1]);
    }

    private function ai(bool $enabled = true, array $changes = []): void
    {
        config(['ai.inference.enabled' => $enabled, 'ai.inference.features.family_summary' => $changes + array_replace(config('ai.inference.features.family_summary'), [
            'enabled' => true, 'provider' => 'synthetic', 'provider_class' => FamilySummaryFakeProvider::class, 'model' => 'synthetic-summary-model', 'cents_per_million_tokens' => 100,
        ])]);
        // The shared moderation provider is the only network call faked; the summary's own answer rules still run.
        $this->app->instance(OpenAiModerator::class, new FamilySummaryFakeModerator);
    }

    private function summarise(?CarbonImmutable $at = null): array
    {
        return app(FamilyDailySummaries::class)->run($at ?? CarbonImmutable::now());
    }

    private function summary(User $user): FamilyDailySummary
    {
        return FamilyDailySummary::query()->where('user_id', $user->id)->sole();
    }

    /** @return array<int, FamilyDailySummaryPart> by school */
    private function parts(FamilyDailySummary $summary): array
    {
        return FamilyDailySummaryPart::acrossSchools()->where('family_daily_summary_id', $summary->id)->orderBy('school_id')->get()->keyBy('school_id')->all();
    }

    private function asA(string $person): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($this->schoolA->tokens[$person]);
    }

    public function test_one_summary_covers_every_child_at_both_schools_and_each_school_is_written_in_its_own_school(): void
    {
        $this->assertSame(1, $this->summarise()['sent']);

        $summary = $this->summary($this->farid);
        $this->assertSame([FamilyDailySummary::SENT, '2026-02-10', 1], [$summary->state, $summary->summary_date->toDateString(), $summary->notice_school_id]);
        $parts = $this->parts($summary);
        $this->assertSame([1, self::SCHOOL_B_ID], array_keys($parts));
        $this->assertSame(['ai', 'ai'], [$parts[1]->wording, $parts[self::SCHOOL_B_ID]->wording]);
        $this->assertSame(['Asha', 'Cara'], array_column($parts[1]->facts['children'], 'first_name'));
        $this->assertSame([['day' => 'tomorrow', 'subject' => 'Maths', 'title' => 'Fraction worksheet two', 'time' => '17:00']], $parts[1]->facts['children'][0]['homework']);
        $this->assertSame('Baobab reading log', $parts[self::SCHOOL_B_ID]->facts['children'][0]['homework'][0]['title']);

        // One model call per school, each with only that school's facts.
        $this->assertCount(2, FamilySummaryFakeProvider::$inputs);
        [$inputA, $inputB] = FamilySummaryFakeProvider::$inputs;
        $this->assertStringContainsString('Fraction worksheet two', $inputA);
        $this->assertStringNotContainsStringIgnoringCase(self::SCHOOL_B_MARKER, $inputA);
        $this->assertStringContainsString('Baobab reading log', $inputB);
        $this->assertStringNotContainsString('Fraction worksheet two', $inputB);
        $this->assertSame(1, AiUsage::query()->where('feature', 'family_summary')->count(), 'School A meters only its own part');
        app(CurrentSchool::class)->run(self::SCHOOL_B_ID, fn () => $this->assertSame(1, AiUsage::query()->where('feature', 'family_summary')->count()));

        // One private notice that names no one, delivered only to Farid.
        $notice = Notification::query()->where('type', 'family.daily_summary')->sole();
        $this->assertSame(['link' => '/family/daily-summary/'.$summary->id, 'summary_id' => $summary->id], $notice->delivery_payload);
        $this->assertNull($notice->body);
        $this->assertStringNotContainsString('Asha', $notice->title.' '.$notice->message.' '.$notice->excerpt);
        $this->assertContains('in_app', app(Notifier::class)->send($this->farid, $notice->type, $notice->title, $notice->message, $notice->delivery_payload['link'], $notice));
        $this->assertSame([], app(Notifier::class)->send($this->gina, $notice->type, $notice->title, $notice->message, $notice->delivery_payload['link'], $notice));
        $this->assertSame(1, UserNotification::query()->where('notification_id', $notice->id)->count());

        $response = $this->asA('farid')->getJson('/api/v2/me/family-summaries/'.$summary->id)->assertOk();
        $this->assertSame([1, self::SCHOOL_B_ID], array_column($response->json('data.sections'), 'school_id'));
        $this->assertStringContainsString('A calm day ahead for Asha and Cara', $response->json('data.sections.0.text'));
        $this->assertStringContainsString(self::SCHOOL_B_MARKER, $response->json('data.sections.1.text'));
        $this->asA('farid')->getJson('/api/v2/me/notifications')->assertOk()->assertJsonPath('data.items.0.type', 'family.daily_summary');
    }

    public function test_timetable_changes_and_forms_waiting_reach_the_summary(): void
    {
        $this->shortDayTomorrow();
        $this->consentWaiting();
        $this->ai(false);

        $this->summarise();
        $facts = $this->parts($this->summary($this->farid))[1]->facts;
        $this->assertSame([['day' => 'tomorrow', 'kind' => 'short_day', 'pattern' => 'Short Senior']], $facts['children'][0]['timetable']);
        $this->assertSame([['title' => 'Synthetic museum trip', 'reply_by' => '2026-02-19']], $facts['children'][0]['forms']);
        $text = $this->parts($this->summary($this->farid))[1]->text;
        $this->assertStringContainsString('Tomorrow: the school follows the Short Senior bells.', $text);
        $this->assertStringContainsString('A form is waiting for you: Synthetic museum trip. Please reply by 19 February.', $text);
        $this->assertStringContainsString('Nothing new for Cara today.', $text);
    }

    public function test_a_guardian_at_one_school_sees_nothing_from_the_other(): void
    {
        $this->optIn($this->gina);
        $baobabGina = User::acrossSchools()->findOrFail($this->schoolB->ids['gina_user']);
        $this->optIn($baobabGina);
        $this->summarise();

        $gina = $this->summary($this->gina);
        $this->assertSame([1], array_keys($this->parts($gina)));
        $this->assertStringNotContainsStringIgnoringCase(self::SCHOOL_B_MARKER, json_encode($this->parts($gina)[1]->facts, JSON_THROW_ON_ERROR).$this->parts($gina)[1]->text);
        $response = $this->asA('gina')->getJson('/api/v2/me/family-summaries/'.$gina->id)->assertOk();
        $this->assertStringNotContainsStringIgnoringCase(self::SCHOOL_B_MARKER, (string) $response->getContent());
        $this->assertStringContainsString('Ben', (string) $response->getContent());

        $other = $this->summary($baobabGina);
        $this->assertSame([self::SCHOOL_B_ID], array_keys($this->parts($other)));
        $this->assertStringNotContainsString('Fraction worksheet two', json_encode($this->parts($other)[self::SCHOOL_B_ID]->facts, JSON_THROW_ON_ERROR));
        $this->assertSame(self::SCHOOL_B_ID, $other->notice_school_id);

        // Wrong owner: another guardian's summary is not found, even by id.
        $this->asA('gina')->getJson('/api/v2/me/family-summaries/'.$this->summary($this->farid)->id)->assertNotFound();
        $this->asA('gina')->getJson('/api/v2/me/family-summaries/'.$other->id)->assertNotFound();
    }

    public function test_a_non_guardian_gets_no_summary(): void
    {
        $tara = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->optIn($tara);
        $lonely = User::forceCreate(['first_name' => 'Synthetic', 'last_name' => 'Parent without children', 'email' => 'no-children@example.test', 'password' => 'SyntheticOnly2026', 'mobile' => '0123456789', 'status' => 1]);
        $lonely->assignRole('Parent');
        SchoolMembership::query()->where('user_id', $lonely->id)->update(['starts_on' => '2026-01-01']);
        $this->optIn($lonely);

        $counts = $this->summarise();
        $this->assertSame(2, $counts['not_a_guardian']);
        $this->assertFalse(FamilyDailySummary::query()->whereIn('user_id', [$tara->id, $lonely->id])->exists());
        $this->assertSame(1, Notification::query()->where('type', 'family.daily_summary')->count(), 'Only Farid');
    }

    public function test_a_removed_guardian_no_longer_gets_or_reads_that_schools_part(): void
    {
        $this->summarise();
        $first = $this->summary($this->farid);
        app(CurrentSchool::class)->run(self::SCHOOL_B_ID, fn () => GuardianLink::query()->updateOrCreate(
            ['learner_id' => $this->schoolB->ids['asha'], 'guardian_user_id' => $this->farid->id], ['relationship' => 'father', 'status' => 'ended', 'can_approve' => false]));

        // Reading the earlier summary shows only the school where Farid is still a guardian.
        $response = $this->asA('farid')->getJson('/api/v2/me/family-summaries/'.$first->id)->assertOk();
        $this->assertSame([1], array_column($response->json('data.sections'), 'school_id'));
        $this->assertStringNotContainsStringIgnoringCase(self::SCHOOL_B_MARKER, (string) $response->getContent());

        // The next day's summary has no School B part at all.
        $this->travelTo(CarbonImmutable::parse('2026-02-11 12:00:00', 'UTC'));
        $this->homework(1, $this->schoolA, 'Spelling list', '2026-02-12 08:00:00');
        $this->assertSame(1, $this->summarise()['sent']);
        $second = FamilyDailySummary::query()->where('user_id', $this->farid->id)->whereDate('summary_date', '2026-02-11')->sole();
        $this->assertSame([1], array_keys($this->parts($second)));
    }

    public function test_no_private_detail_reaches_the_model_and_private_updates_are_only_counted(): void
    {
        foreach (['academics.marks_released' => 'Asha scored 91 MARKCANARY', 'fees.receipt' => 'FEECANARY payment of R1000'] as $type => $title) {
            $notice = app(Notifier::class)->makeNotice($type, $title, $title.' details', '/notifications');
            UserNotification::query()->create(['notification_id' => $notice->id, 'user_id' => $this->farid->id]);
        }
        $this->summarise();

        $part = $this->parts($this->summary($this->farid))[1];
        $this->assertSame(2, $part->facts['private_updates']);
        $this->assertStringContainsString('You have 2 private updates. Open your notifications to read them.', $part->text);
        $everything = implode("\n", FamilySummaryFakeProvider::$inputs);
        foreach (['MARKCANARY', 'FEECANARY', '91', '78', 'Good work', self::SURNAME, 'private_updates', 'unread_messages', 'Farid', 'learner', '"id"'] as $secret) {
            $this->assertStringNotContainsString($secret, $everything, "The model saw $secret");
        }
        foreach (FamilySummaryFakeProvider::$inputs as $input) {
            $decoded = json_decode($input, true, 16, JSON_THROW_ON_ERROR);
            $this->assertSame(['language', 'school', 'children'], array_keys($decoded));
            foreach ($decoded['children'] as $child) {
                $this->assertSame(['first_name', 'timetable', 'homework', 'forms'], array_keys($child));
            }
        }
        $this->assertStringNotContainsString('MARKCANARY', json_encode($part->facts, JSON_THROW_ON_ERROR).$part->text);
    }

    public static function fallbacks(): array
    {
        return [
            'AI switched off' => ['off', 'inference_disabled'],
            'AI over its cap' => ['capped', 'actor_daily_cap'],
            'provider failing' => ['failing', 'inference_unavailable'],
            'answer about a private topic' => ['private', 'inference_quarantined'],
        ];
    }

    #[DataProvider('fallbacks')]
    public function test_the_plain_template_is_sent_when_the_ai_cannot_write_it(string $mode, string $reason): void
    {
        match ($mode) {
            'off' => $this->ai(false),
            'capped' => $this->ai(true, ['actor_daily_tokens' => 10]),
            default => FamilySummaryFakeProvider::$mode = $mode,
        };
        $this->assertSame(1, $this->summarise()['sent']);

        $part = $this->parts($this->summary($this->farid))[1];
        $this->assertSame(['template', $reason], [$part->wording, $part->fallback_reason]);
        $this->assertSame("Jacaranda Demo School\n\nAsha\n• Tomorrow: Maths homework due, Fraction worksheet two.\n\nNothing new for Cara today.", $part->text);
        $this->assertStringNotContainsString('marks', $part->text);
        $this->assertSame(1, Notification::query()->where('type', 'family.daily_summary')->count());
    }

    public function test_quiet_hours_hold_the_summary_and_the_time_must_be_outside_them(): void
    {
        app(QuietHours::class)->save($this->farid, ['enabled' => true, 'starts_at' => '13:00', 'ends_at' => '15:00', 'timezone' => 'Africa/Johannesburg']);
        $this->assertSame(1, $this->summarise()['quiet_hours']);
        $this->assertFalse(FamilyDailySummary::query()->exists());

        // After they end, the same day's summary goes.
        $this->assertSame(1, $this->summarise(CarbonImmutable::parse('2026-02-10 13:05:00', 'UTC'))['sent']);

        try {
            app(FamilySummaryPreferences::class)->save($this->farid, ['enabled' => true, 'send_at' => '14:30']);
            $this->fail('A time inside quiet hours was accepted.');
        } catch (ValidationException $error) {
            $this->assertSame(['Choose a time outside your quiet hours (13:00 to 15:00).'], $error->errors()['send_at']);
        }
        $this->asA('farid')->putJson('/api/v2/me/family-summary/preferences', ['enabled' => true, 'send_at' => '14:30'])->assertStatus(422);
        $this->asA('farid')->putJson('/api/v2/me/family-summary/preferences', ['enabled' => true, 'send_at' => '18:15'])->assertOk()
            ->assertJsonPath('data.send_at', '18:15')->assertJsonPath('data.quiet_hours.starts_at', '13:00');
    }

    /**
     * Review 4495631348: quiet hours are compared in their own time zone. 20:00 UTC
     * is 22:00 in Johannesburg, inside 21:00 to 06:30 there, so it would be held
     * every evening and then never be due again that day.
     */
    public function test_the_chosen_time_is_checked_against_quiet_hours_in_their_own_time_zone(): void
    {
        app(QuietHours::class)->save($this->farid, ['enabled' => true, 'starts_at' => '21:00', 'ends_at' => '06:30', 'timezone' => 'Africa/Johannesburg']);
        try {
            app(FamilySummaryPreferences::class)->save($this->farid, ['enabled' => true, 'send_at' => '20:00', 'timezone' => 'UTC']);
            $this->fail('20:00 UTC (22:00 in the quiet hours\' zone) was accepted.');
        } catch (ValidationException $error) {
            $this->assertSame(['Choose a time outside your quiet hours (21:00 to 06:30, Africa/Johannesburg).'], $error->errors()['send_at']);
        }
        $this->asA('farid')->putJson('/api/v2/me/family-summary/preferences', ['enabled' => true, 'send_at' => '05:00', 'timezone' => 'Asia/Kolkata'])->assertStatus(422);

        // 18:00 UTC is 20:00 there: accepted, and actually sent that day.
        $this->asA('farid')->putJson('/api/v2/me/family-summary/preferences', ['enabled' => true, 'send_at' => '18:00', 'timezone' => 'UTC'])->assertOk()
            ->assertJsonPath('data.timezone', 'UTC')->assertJsonPath('data.quiet_hours.timezone', 'Africa/Johannesburg');
        $this->assertSame(1, $this->summarise(CarbonImmutable::parse('2026-02-10 17:45:00', 'UTC'))['not_due']);
        $this->assertSame(1, $this->summarise(CarbonImmutable::parse('2026-02-10 18:00:00', 'UTC'))['sent']);
    }

    /**
     * Review 4495631338: the job runs every 15 minutes, so a time between two runs
     * after the day's last run (23:50) would never be due. Only quarter hours are
     * offered, and the last one is sent by the day's last run.
     */
    public function test_only_quarter_hour_times_are_offered_and_the_last_one_of_the_day_is_sent(): void
    {
        foreach (['23:50', '07:10', '17:59'] as $time) {
            try {
                app(FamilySummaryPreferences::class)->save($this->farid, ['enabled' => true, 'send_at' => $time]);
                $this->fail("$time is between two runs of the job and was accepted.");
            } catch (ValidationException $error) {
                $this->assertSame(['Choose a time on the quarter hour, such as 17:30.'], $error->errors()['send_at']);
            }
        }
        $this->asA('farid')->putJson('/api/v2/me/family-summary/preferences', ['enabled' => true, 'send_at' => '23:50'])->assertStatus(422);
        $this->asA('farid')->putJson('/api/v2/me/family-summary/preferences', ['enabled' => true, 'send_at' => '23:45'])->assertOk()->assertJsonPath('data.send_at', '23:45');

        // The day's last scheduled run (23:45 in Johannesburg) sends it.
        $this->assertSame(1, $this->summarise(CarbonImmutable::parse('2026-02-10 21:30:00', 'UTC'))['not_due']);
        $this->assertSame(1, $this->summarise(CarbonImmutable::parse('2026-02-10 21:45:00', 'UTC'))['sent']);
    }

    public function test_a_summary_is_sent_once_per_guardian_per_day(): void
    {
        $this->optIn($this->farid, '15:00');
        $this->assertSame(1, $this->summarise()['not_due'], 'Before the chosen time nothing goes');
        $this->assertSame(1, $this->summarise(CarbonImmutable::parse('2026-02-10 13:00:00', 'UTC'))['sent']);
        $this->assertSame(1, $this->summarise(CarbonImmutable::parse('2026-02-10 13:15:00', 'UTC'))['already']);
        $this->assertSame(1, $this->summarise(CarbonImmutable::parse('2026-02-10 21:45:00', 'UTC'))['already']);
        $this->assertSame(1, Notification::query()->where('type', 'family.daily_summary')->count());
        $this->assertCount(2, FamilySummaryFakeProvider::$inputs, 'No second model call for the same day');

        // An interrupted day is finished on the next run, still once, with the stored receipt.
        $summary = $this->summary($this->farid);
        $summary->update(['state' => FamilyDailySummary::COMPOSING, 'notification_id' => null]);
        Notification::query()->where('type', 'family.daily_summary')->delete();
        $operation = $this->parts($summary)[1]->operation_id;
        $this->assertSame(1, $this->summarise(CarbonImmutable::parse('2026-02-10 13:30:00', 'UTC'))['sent']);
        $this->assertSame($operation, $this->parts($summary)[1]->operation_id);
        $this->assertSame(1, Notification::query()->where('type', 'family.daily_summary')->count());

        // The next local day is a new summary.
        $this->travelTo(CarbonImmutable::parse('2026-02-11 13:00:00', 'UTC'));
        $this->homework(1, $this->schoolA, 'Spelling list', '2026-02-12 08:00:00');
        $this->assertSame(1, $this->summarise()['sent']);
        $this->assertSame(['2026-02-10', '2026-02-11'], FamilyDailySummary::query()->where('user_id', $this->farid->id)->orderBy('summary_date')->get()->map(fn ($s) => $s->summary_date->toDateString())->all());

        // Retention erases the summaries and every school's part.
        $this->assertSame(2, app(FamilyDailySummaries::class)->prune(CarbonImmutable::parse('2026-03-01')));
        $this->assertSame(0, FamilyDailySummaryPart::acrossSchools()->count());
        $this->assertSame(0, AiUsage::acrossSchools()->where('feature', 'family_summary')->whereNotNull('inference_output')->count(), 'The AI answers go with them');
        $this->assertGreaterThan(0, AiUsage::acrossSchools()->where('feature', 'family_summary')->count(), 'Metering stays');
    }

    public function test_only_a_guardian_chooses_a_summary_or_reads_one(): void
    {
        $this->asA('tara')->getJson('/api/v2/me/family-summary/preferences')->assertForbidden();
        $this->asA('tara')->putJson('/api/v2/me/family-summary/preferences', ['enabled' => true, 'send_at' => '18:00'])->assertForbidden();
        $this->asA('asha')->getJson('/api/v2/me/family-summary/preferences')->assertForbidden();
        $this->asA('farid')->getJson('/api/v2/me/family-summary/preferences')->assertOk()->assertJsonPath('data.enabled', true)->assertJsonPath('data.send_at', '07:00');
        $this->asA('gina')->putJson('/api/v2/me/family-summary/preferences', ['enabled' => true, 'send_at' => '19:45'])->assertOk()->assertJsonPath('data.revision', 1);
        $this->assertTrue(FamilySummaryPreference::query()->where('user_id', $this->gina->id)->value('enabled'));

        $this->summarise();
        $id = $this->summary($this->farid)->id;
        $this->asA('tara')->getJson('/api/v2/me/family-summaries/'.$id)->assertForbidden();
        $this->asA('asha')->getJson('/api/v2/me/family-summaries/'.$id)->assertForbidden();
        $this->expectException(HttpException::class);
        app(CurrentSchool::class)->run(self::SCHOOL_B_ID, fn () => app(FamilySummaryPreferences::class)->get(User::acrossSchools()->findOrFail($this->schoolB->ids['tara_user'])));
    }

    private function shortDayTomorrow(): void
    {
        $year = $this->schoolA->ids['session_year'];
        $grade = (int) GradeLevel::where('code', '5')->value('id');
        $teacher = (int) Teacher::where('user_id', $this->schoolA->ids['tara_user'])->value('id');
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => $grade]);
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic enrolment']);
        $groups = app(TeachingGroups::class);
        $group = $groups->save($this->superAdmin(), ['name' => 'Maths 5A', 'subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => $grade, 'session_year_id' => $year, 'class_section_id' => $this->schoolA->ids['section_a'], 'active' => true]);
        $dates = ['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic allocation'];
        $groups->dated($this->superAdmin(), 'teachers', $dates + ['teacher_id' => $teacher], $group->id);
        $groups->dated($this->superAdmin(), 'memberships', $dates + ['learner_id' => $this->schoolA->ids['asha'], 'mark_rule' => 'catch_up'], $group->id);
        $room = (int) app(TimetableConfiguration::class)->room($this->superAdmin(), ['name' => 'Room 1', 'capacity' => 35, 'type' => 'classroom', 'active' => true])->id;
        $bells = fn (string $name, array $periods) => app(TimetableConfiguration::class)->bells($this->superAdmin(), ['name' => $name, 'section' => 'Senior', 'session_year_id' => $year,
            'periods' => array_map(fn ($p) => ['name' => $p[0], 'starts_at' => $p[1], 'ends_at' => $p[2], 'is_break' => false], $periods)])->load('periods');
        $normal = $bells('Normal Senior', [['P1', '07:30', '08:10'], ['P2', '08:10', '08:50']]);
        $short = $bells('Short Senior', [['S1', '07:30', '08:00'], ['S2', '08:00', '08:30']]);
        $engine = app(TimetableEngine::class);
        $rows = [['session_year_id' => $year, 'term_id' => null, 'teaching_group_id' => $group->id, 'class_section_id' => null, 'teacher_id' => $teacher, 'room_id' => $room,
            'bell_period_id' => (int) $normal->periods->first()->id, 'starts_on' => '2026-02-10', 'ends_on' => '2026-03-31', 'weekday' => 3, 'week_pattern' => 'all', 'active' => true]];
        $preview = $engine->preview($this->superAdmin(), $rows);
        $this->assertTrue($preview['can_save'], json_encode($preview['conflicts']));
        $engine->save($this->superAdmin(), $preview['rows'], $preview['fingerprint']);
        app(DayPatterns::class)->save($this->superAdmin(), ['on_date' => '2026-02-11', 'reason' => 'Synthetic staff afternoon',
            'replacements' => [['bell_schedule_id' => (int) $normal->id, 'replacement_bell_schedule_id' => (int) $short->id]]]);
    }

    private function consentWaiting(): void
    {
        config(['modules.family_forms' => ['always_on' => true], 'modules.groups' => ['always_on' => true]]);
        $learner = $this->schoolA->ids['asha'];
        $year = $this->schoolA->ids['session_year'];
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        if (! Enrolment::where('learner_id', $learner)->exists()) {
            Enrolment::create(['learner_id' => $learner, 'session_year_id' => $year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
        }
        GuardianLink::updateOrCreate(['learner_id' => $learner, 'guardian_user_id' => $this->farid->id], ['status' => 'active', 'relationship' => 'father', 'can_approve' => true]);
        app(ApprovalService::class)->decide($this->farid, Students::findOrFail($learner), 'approve', config('identity.consent_version'));
        $group = ActivityGroup::create(['session_year_id' => $year, 'name' => 'Synthetic museum group', 'discipline' => 'Culture', 'season' => '2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true]);
        ActivityStaff::create(['activity_group_id' => $group->id, 'user_id' => $teacher->id, 'role' => 'facilitator', 'cascade' => false, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic allocation', 'revision' => 1]);
        ActivityMembership::create(['activity_group_id' => $group->id, 'learner_id' => $learner, 'role' => 'member', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic group', 'revision' => 1]);
        app(Consents::class)->create($teacher, ['operation_id' => (string) Str::uuid(), 'activity_group_id' => $group->id, 'title' => 'Synthetic museum trip',
            'wording' => 'Synthetic exact consent wording', 'event_starts_at' => '2026-02-20T10:00:00+02:00', 'event_ends_at' => '2026-02-20T12:00:00+02:00',
            'respond_by' => '2026-02-19T12:00:00+02:00', 'learner_ids' => [$learner]]);
        DB::table('consent_requests')->count();
    }
}

/** A synthetic provider for tests only: it records exactly what the model would have seen. */
class FamilySummaryFakeProvider implements InferenceProvider
{
    /** @var list<string> */
    public static array $inputs = [];

    public static string $mode = 'ok';

    public static function reset(): void
    {
        self::$inputs = [];
        self::$mode = 'ok';
    }

    public function preflight(ProviderRequest $request): void {}

    public function infer(ProviderRequest $request): ProviderResponse
    {
        self::$inputs[] = (string) $request->input;
        if (self::$mode === 'failing') {
            throw new RuntimeException('Synthetic provider outage.');
        }
        if (self::$mode === 'private') {
            return new ProviderResponse('Asha got good marks today.', 20);
        }
        $facts = json_decode((string) $request->input, true, 16, JSON_THROW_ON_ERROR);

        return new ProviderResponse('A calm day ahead for '.implode(' and ', array_column($facts['children'], 'first_name')).' at '.$facts['school'].'.', 20);
    }
}

/** Stands in for the shared external moderation call only. */
class FamilySummaryFakeModerator extends OpenAiModerator
{
    public function preflight(Capability $capability, string|array $input, ModerationContext $context): void {}

    public function flags(Capability $capability, string $direction, string|array $content, ModerationContext $context): array
    {
        return [];
    }
}
