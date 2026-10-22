<?php

namespace Tests\Feature;

use App\Services\Platform\Tenancy\SchoolTable;
use Tests\Concerns\RunsAppServer;
use Tests\Support\DemoSchool;
use Tests\TestCase;

/**
 * Limits on what one app user can make the API do: guessing online exam keys,
 * loading whole chat histories, a query per event. The API controllers exit()
 * after responding, so this talks to a separate server (see RunsAppServer).
 */
class ApiRequestLimitsTest extends TestCase
{
    use RunsAppServer;

    private static ?DemoSchool $school = null;

    protected function setUp(): void
    {
        parent::setUp();
        if (! self::appServerRunning()) {
            $this->freshInstall();
            self::$school = DemoSchool::build();
            // The wrong-key count has to outlive a request; the array cache doesn't.
            $this->startAppServer(['CACHE_DRIVER' => 'file', 'CACHE_STORE' => 'file']);
        }
    }

    public static function tearDownAfterClass(): void
    {
        self::$school = null;
        parent::tearDownAfterClass();
    }

    public function test_an_exam_that_is_not_open_says_so_before_checking_the_key(): void
    {
        $id = self::$school->ids;
        $upcoming = SchoolTable::query('online_exams')->insertGetId([
            'model_type' => 'App\Models\ClassSection', 'model_id' => $id['section_a'], 'subject_id' => $id['maths'],
            'title' => 'Next week', 'exam_key' => 1234, 'duration' => 30, 'session_year_id' => 1,
            'start_date' => now()->addDays(2), 'end_date' => now()->addDays(3),
        ]);
        $asha = $this->apiClient(self::$school->tokens['asha']);

        // A wrong key used to be the only answer, so keys could be tried before the exam opened.
        $notOpen = $asha->get('/api/student/get-online-exam-questions', ['exam_id' => $upcoming, 'exam_key' => '9999']);
        $this->assertSame(106, $notOpen->json('code'), $notOpen->body());

        $ended = $asha->get('/api/student/get-online-exam-questions', ['exam_id' => $id['past_online_exam'], 'exam_key' => '9999']);
        $this->assertSame('This exam has ended.', $ended->json('message'), $ended->body());
    }

    public function test_wrong_exam_keys_are_limited_per_student_and_exam(): void
    {
        $exam = self::$school->ids['online_exam'];
        $asha = $this->apiClient(self::$school->tokens['asha']);
        $ben = $this->apiClient(self::$school->tokens['ben']);
        $questions = fn ($client, string $key) => $client->get('/api/student/get-online-exam-questions', ['exam_id' => $exam, 'exam_key' => $key])->json();

        for ($i = 1; $i <= 5; $i++) {
            $this->assertSame(103, $questions($asha, '9999')['code'], "Wrong key $i");
        }
        // Then even the right key waits.
        $locked = $questions($asha, '1234');
        $this->assertTrue($locked['error']);
        $this->assertSame(429, $locked['code']);

        // Another student has a count of their own, and the right key resets it.
        for ($i = 1; $i <= 4; $i++) {
            $this->assertSame(103, $questions($ben, '9999')['code']);
        }
        $this->assertFalse($questions($ben, '1234')['error']);
        for ($i = 1; $i <= 5; $i++) {
            $this->assertSame(103, $questions($ben, '9999')['code'], "Wrong key $i after the right one");
        }
    }

    public function test_events_say_which_have_a_day_schedule(): void
    {
        // has_day_schedule is now read with the events instead of one query per event.
        $multiDay = SchoolTable::query('events')->insertGetId([
            'session_year_id' => 1, 'title' => 'Science week', 'type' => 'multiple',
            'start_date' => now()->addDays(20)->toDateString(), 'end_date' => now()->addDays(22)->toDateString(),
        ]);
        SchoolTable::query('multiple_events')->insert([
            ['event_id' => $multiDay, 'title' => 'Opening', 'date' => now()->addDays(20)->toDateString()],
            ['event_id' => $multiDay, 'title' => 'Fair', 'date' => now()->addDays(21)->toDateString()],
        ]);

        $events = collect($this->apiClient(self::$school->tokens['asha'])->get('/api/get-events-list')->json('data'));

        $parents = $events->whereNull('event_id')->pluck('has_day_schedule', 'id');
        $this->assertSame(1, $parents[$multiDay]);
        $this->assertSame(0, $parents[self::$school->ids['event']]);
        $this->assertCount(2, $events->where('event_id', $multiDay));
    }

    public function test_chat_messages_come_a_page_at_a_time_without_a_limit(): void
    {
        $id = self::$school->ids;
        SchoolTable::query('chat_messages')->insert(array_map(fn (int $i) => [
            'modal_type' => 'App/Models/User', 'modal_id' => $id['tara_user'], 'sender_id' => $id['farid_user'],
            'body' => "Message $i", 'date' => now()->subMinutes(200 - $i), 'created_at' => now(), 'updated_at' => now(),
        ], range(1, 120)));

        foreach ([
            'parent' => ['farid', 'tara_user'],
            'teacher' => ['tara', 'farid_user'],
        ] as $app => [$who, $other]) {
            $client = $this->apiClient(self::$school->tokens[$who])->asForm();
            $path = "/api/$app/get-user-message";

            $default = $client->post($path, ['user_id' => $id[$other]])->json('data');
            $this->assertCount(100, $default['items'], "$app: no limit");
            $this->assertSame(120, $default['total_items']);

            // A limit the app sends still applies, above the default too.
            $this->assertCount(15, $client->post($path, ['user_id' => $id[$other], 'limit' => 15])->json('data.items'));
            $this->assertCount(120, $client->post($path, ['user_id' => $id[$other], 'limit' => 150])->json('data.items'));
        }

        // The contact lists are short here; they come back the same with or without a limit.
        foreach (['farid' => '/api/parent/get-user-list', 'tara' => '/api/teacher/get-user-list'] as $who => $path) {
            $client = $this->apiClient(self::$school->tokens[$who]);
            $all = $client->get($path)->json('data.items');
            $this->assertNotEmpty($all, $path);
            $this->assertSame($all, $client->get($path, ['limit' => 1000])->json('data.items'));
        }
    }
}
