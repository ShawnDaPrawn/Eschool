<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Models\Academics\CalendarFeed;
use App\Models\Academics\GradeLevel;
use App\Models\ClassSchool;
use App\Models\ExamTimetable;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use App\Services\Academics\CalendarFeeds;
use App\Services\Academics\Ical;
use App\Services\Academics\TeachingGroups;
use App\Services\Academics\TimetableConfiguration;
use App\Services\Academics\TimetableEngine;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

/**
 * A learner's calendar subscription (Action Plan 1.10).
 * engineering/specs/academics/calendar-feed.md.
 */
class CalendarFeedTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private int $year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(CarbonImmutable::parse('2026-02-10 07:50:00', 'Africa/Johannesburg'));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->year = $this->schoolA->ids['session_year'];
        $grade = (int) GradeLevel::where('code', '7')->value('id');
        $teacher = (int) Teacher::where('user_id', $this->schoolA->ids['tara_user'])->value('id');
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        Term::whereKey($this->schoolA->ids['term'])->update(['start_date' => '2026-01-01', 'end_date' => '2026-03-31']);
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => $grade]);
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Calendar rehearsal']);

        $groups = app(TeachingGroups::class);
        $group = $groups->save($this->superAdmin(), ['name' => 'Maths 7A', 'subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => $grade, 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'active' => true]);
        $dates = ['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Calendar allocation'];
        $groups->dated($this->superAdmin(), 'teachers', $dates + ['teacher_id' => $teacher], $group->id);
        $groups->dated($this->superAdmin(), 'memberships', $dates + ['learner_id' => $this->schoolA->ids['asha'], 'mark_rule' => 'catch_up'], $group->id);
        $room = (int) app(TimetableConfiguration::class)->room($this->superAdmin(), ['name' => 'Room 1', 'capacity' => 35, 'type' => 'classroom', 'active' => true])->id;
        $period = app(TimetableConfiguration::class)->bells($this->superAdmin(), ['name' => 'Bells', 'section' => 'Senior', 'session_year_id' => $this->year,
            'periods' => [['name' => 'Period 1', 'starts_at' => '08:00', 'ends_at' => '08:45', 'is_break' => false]]])->periods->sole()->id;
        $engine = app(TimetableEngine::class);
        $preview = $engine->preview($this->superAdmin(), [['session_year_id' => $this->year, 'term_id' => $this->schoolA->ids['term'], 'teaching_group_id' => (int) $group->id, 'class_section_id' => null,
            'teacher_id' => $teacher, 'room_id' => $room, 'bell_period_id' => (int) $period, 'starts_on' => '2026-02-10', 'ends_on' => '2026-03-31', 'weekday' => 2, 'week_pattern' => 'all', 'active' => true]]);
        $this->assertTrue($preview['can_save'], json_encode($preview['conflicts']));
        $engine->save($this->superAdmin(), $preview['rows'], $preview['fingerprint']);

        (new ExamTimetable)->forceFill(['exam_id' => $this->schoolA->ids['exam'], 'class_id' => $this->schoolA->ids['class_a'], 'subject_id' => $this->schoolA->ids['maths'], 'total_marks' => 50, 'passing_marks' => 20,
            'date' => '2026-02-20', 'start_time' => '09:00:00', 'end_time' => '11:00:00', 'venue' => 'Hall, east wing', 'session_year_id' => $this->year])->save();
    }

    private function person(string $key): User
    {
        return User::findOrFail($this->schoolA->ids[$key.'_user']);
    }

    private function api(string $person, int $school = 1): array
    {
        $this->app['auth']->forgetGuards();
        $tokens = $school === 1 ? $this->schoolA->tokens : $this->schoolB->tokens;

        return ['Authorization' => 'Bearer '.$tokens[$person], 'X-School-Id' => (string) $school, 'Idempotency-Key' => (string) Str::uuid()];
    }

    /** The path of a feed URL, so the test client fetches it without any sign-in. */
    private function fetch(string $url): TestResponse
    {
        // As a calendar app: no token, no session.
        $this->withoutToken();
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');

        return $this->call('GET', (string) parse_url($url, PHP_URL_PATH));
    }

    public function test_a_guardian_subscribes_to_exams_and_lessons_and_the_feed_needs_no_sign_in(): void
    {
        $made = $this->postJson('/api/v2/academics/calendar-feeds', ['learner_id' => $this->schoolA->ids['asha'], 'include_lessons' => true], $this->api('farid'))->assertOk();
        $url = (string) $made->json('data.url');
        $this->assertMatchesRegularExpression('#/calendar/[A-Za-z0-9]{48}\.ics$#', $url);
        $token = basename((string) parse_url($url, PHP_URL_PATH), '.ics');
        $this->assertSame(0, CalendarFeed::query()->where('token_hash', $token)->count(), 'only the hash is stored');
        $this->assertSame(1, CalendarFeed::query()->where('token_hash', hash('sha256', $token))->count());

        $ics = $this->fetch($url)->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=utf-8')->assertHeader('Cache-Control', 'no-store, private')->getContent();
        $this->assertStringStartsWith("BEGIN:VCALENDAR\r\n", $ics);
        $this->assertStringContainsString('SUMMARY:Exam: Maths', $ics);
        $this->assertStringContainsString("DTSTART:20260220T070000Z\r\n", $ics);
        $this->assertStringContainsString('LOCATION:Hall\, east wing', $ics);
        // Lessons for four weeks: Tuesdays 10, 17, 24 Feb and 3 Mar, 08:00 school time.
        $this->assertSame(4, substr_count($ics, 'LOCATION:Room 1'));
        $this->assertStringContainsString("DTSTART:20260210T060000Z\r\n", $ics);
        $this->assertStringNotContainsString('20260310T060000Z', $ics);
        $this->assertStringContainsString('X-WR-CALNAME:', $ics);

        $list = $this->getJson('/api/v2/academics/calendar-feeds', $this->api('farid'))->assertOk();
        $this->assertCount(1, $list->json('data.items'));
        $this->assertNotNull($list->json('data.items.0.last_used_at'));
        $this->assertArrayNotHasKey('url', $list->json('data.items.0'));

        // Exams only, unless lessons are asked for. A learner may subscribe for themselves.
        $own = app(CalendarFeeds::class)->create($this->person('asha'), $this->schoolA->ids['asha'], false);
        $ics = $this->fetch($own['url'])->assertOk()->getContent();
        $this->assertStringContainsString('Exam: Maths', $ics);
        $this->assertStringNotContainsString('Room 1', $ics);
    }

    public function test_only_people_who_may_see_the_learner_can_make_a_link_and_only_its_maker_can_remove_it(): void
    {
        $before = CalendarFeed::query()->count();
        // Wrong owner: Gina is Ben's guardian, not Asha's; Asha cannot subscribe to Ben.
        $this->assertContains($this->postJson('/api/v2/academics/calendar-feeds', ['learner_id' => $this->schoolA->ids['asha']], $this->api('gina'))->status(), [403, 404]);
        $this->assertContains($this->refused(fn () => app(CalendarFeeds::class)->create($this->person('asha'), $this->schoolA->ids['ben'], false)), [403, 404]);
        // Wrong role: a teacher is not a family member of the learner.
        $this->assertContains($this->refused(fn () => app(CalendarFeeds::class)->create($this->person('tara'), $this->schoolA->ids['asha'], false)), [403, 404, 422]);
        $this->assertSame($before, CalendarFeed::query()->count());

        $feed = app(CalendarFeeds::class)->create($this->person('farid'), $this->schoolA->ids['asha'], false);
        $this->deleteJson('/api/v2/academics/calendar-feeds/'.$feed['id'], [], $this->api('gina'))->assertNotFound();
        $this->assertSame([], $this->getJson('/api/v2/academics/calendar-feeds', $this->api('gina'))->assertOk()->json('data.items'));
        $this->fetch($feed['url'])->assertOk();

        $this->deleteJson('/api/v2/academics/calendar-feeds/'.$feed['id'], [], $this->api('farid'))->assertOk()->assertJsonPath('data.revoked', true);
        $this->fetch($feed['url'])->assertNotFound();
        $this->assertSame([], $this->getJson('/api/v2/academics/calendar-feeds', $this->api('farid'))->assertOk()->json('data.items'));
    }

    public function test_the_link_stops_when_its_maker_loses_access(): void
    {
        $guardian = app(CalendarFeeds::class)->create($this->person('farid'), $this->schoolA->ids['asha'], true);
        $learner = app(CalendarFeeds::class)->create($this->person('asha'), $this->schoolA->ids['asha'], false);
        $this->fetch($guardian['url'])->assertOk();

        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->person('farid')->id], ['relationship' => 'father', 'status' => 'ended', 'can_approve' => false]);
        $this->fetch($guardian['url'])->assertNotFound()->assertDontSee('Maths');
        $this->fetch($learner['url'])->assertOk();

        SchoolMembership::query()->where('school_id', 1)->where('user_id', $this->person('asha')->id)->update(['ends_on' => '2026-02-01']);
        $this->fetch($learner['url'])->assertNotFound();
        $this->person('asha')->forceFill(['status' => 0])->save();
        $this->fetch($learner['url'])->assertNotFound();
    }

    public function test_another_school_cannot_list_remove_or_read_through_a_link(): void
    {
        $feed = app(CalendarFeeds::class)->create($this->person('farid'), $this->schoolA->ids['asha'], false);
        $this->assertSame([], $this->getJson('/api/v2/academics/calendar-feeds', $this->api('farid', 2))->assertOk()->json('data.items'));
        $this->deleteJson('/api/v2/academics/calendar-feeds/'.$feed['id'], [], $this->api('farid', 2))->assertNotFound();
        // School B's learner id through School A: not a learner Farid may see here.
        $this->assertContains($this->postJson('/api/v2/academics/calendar-feeds', ['learner_id' => $this->schoolB->ids['asha']], $this->api('farid'))->status(), [403, 404]);
        // The feed reads only its own school, whatever the request says.
        $plain = $this->fetch($feed['url'])->assertOk()->getContent();
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
        $asked = $this->withoutToken()->withHeader('X-School-Id', '2')->get((string) parse_url($feed['url'], PHP_URL_PATH))->assertOk()->getContent();
        $this->assertSame(preg_replace('/DTSTAMP:\S+/', '', $plain), preg_replace('/DTSTAMP:\S+/', '', $asked));
        $this->assertSame(1, DB::table('calendar_feeds')->where('school_id', 1)->count());
        $this->assertSame(0, DB::table('calendar_feeds')->where('school_id', 2)->count());
    }

    public function test_limits_bad_tokens_and_ical_formatting(): void
    {
        for ($i = 0; $i < CalendarFeeds::MAX_PER_LEARNER; $i++) {
            app(CalendarFeeds::class)->create($this->person('farid'), $this->schoolA->ids['asha'], false);
        }
        $this->postJson('/api/v2/academics/calendar-feeds', ['learner_id' => $this->schoolA->ids['asha']], $this->api('farid'))->assertStatus(422);
        $this->fetch('/calendar/'.str_repeat('a', 48).'.ics')->assertNotFound();
        $this->fetch('/calendar/short.ics')->assertNotFound();

        $this->assertSame('a\\, b\\; c\\\\ d\\ne', Ical::text("a, b; c\\ d\ne"));
        $folded = Ical::fold('SUMMARY:'.str_repeat('é', 60));
        foreach (explode("\r\n", $folded) as $line) {
            $this->assertLessThanOrEqual(75, strlen($line));
            $this->assertTrue(mb_check_encoding($line, 'UTF-8'));
        }
        $this->assertSame('SUMMARY:'.str_repeat('é', 60), str_replace("\r\n ", '', $folded));
    }

    public function test_creation_is_never_replayed_or_stored_and_result_projections_are_not_exams(): void
    {
        $key = (string) Str::uuid();
        $first = $this->postJson('/api/v2/academics/calendar-feeds', ['learner_id' => $this->schoolA->ids['asha']], ['Idempotency-Key' => $key] + $this->api('farid'))->assertOk();
        $second = $this->postJson('/api/v2/academics/calendar-feeds', ['learner_id' => $this->schoolA->ids['asha']], ['Idempotency-Key' => $key] + $this->api('farid'))->assertOk();
        $this->assertNotSame($first->json('data.url'), $second->json('data.url'), 'a retry makes a new link, never a stored reply');
        $token = basename((string) parse_url((string) $first->json('data.url'), PHP_URL_PATH), '.ics');
        foreach (DB::table('idempotency_keys')->get() as $row) {
            $this->assertStringNotContainsString($token, json_encode($row));
        }

        // Released assessment results are projected into an exam row at midnight; it is not a sitting.
        $exam = DB::table('exams')->insertGetId(['name' => 'Term 1 results', 'session_year_id' => $this->year, 'publish' => 1, 'school_id' => 1, 'created_at' => now(), 'updated_at' => now()]);
        (new ExamTimetable)->forceFill(['exam_id' => $exam, 'class_id' => $this->schoolA->ids['class_a'], 'subject_id' => $this->schoolA->ids['science'], 'total_marks' => 100, 'passing_marks' => 50,
            'date' => '2026-02-25', 'start_time' => '00:00:00', 'end_time' => '00:00:00', 'session_year_id' => $this->year])->save();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('academic_exam_links')->insert(['school_id' => 1, 'subject_id' => $this->schoolA->ids['science'], 'grade_level_id' => (int) GradeLevel::where('code', '7')->value('id'),
            'session_year_id' => $this->year, 'term_id' => null, 'period_key' => 'term-1', 'exam_id' => $exam, 'mark_release_id' => 999999, 'created_at' => now(), 'updated_at' => now()]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        $ics = $this->fetch((string) $first->json('data.url'))->assertOk()->getContent();
        $this->assertStringContainsString('Exam: Maths', $ics);
        $this->assertStringNotContainsString('20260225', $ics);
    }

    private function refused(callable $fn): int
    {
        try {
            $fn();
        } catch (HttpExceptionInterface $e) {
            return $e->getStatusCode();
        } catch (ModelNotFoundException) {
            return 404;
        }
        $this->fail('Expected a refusal');
    }
}
