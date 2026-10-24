<?php

namespace Tests\Feature;

use App\Models\ExamMarks;
use App\Models\ExamTimetable;
use App\Models\File;
use App\Models\Notification;
use App\Models\Teacher;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\ParentPolicyService;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\RunsAppServer;
use Tests\Support\DemoSchool;
use Tests\TestCase;

/**
 * Bugs found while building the API contract snapshots (ApiContractTest).
 */
class ApiRegressionTest extends TestCase
{
    use RunsAppServer;

    private static DemoSchool $school;

    protected function setUp(): void
    {
        parent::setUp();

        if (! self::appServerRunning()) {
            $this->freshInstall();
            self::$school = DemoSchool::build();
            $this->startAppServer();
        }
    }

    public function test_profiles_and_parent_sign_in_work_for_people_without_custom_fields(): void
    {
        // dynamic_fields is null for imported or self-registered people; decoding it
        // under strict_types threw, so these all failed (parent sign-in with a 500).
        foreach (['asha' => 'student/get-profile-data', 'farid' => 'parent/get-profile-data', 'tara' => 'teacher/get-profile-details'] as $who => $uri) {
            $response = $this->apiClient(self::$school->tokens[$who])->get("/api/$uri");
            $this->assertFalse($response->json('error'), "$uri: ".$response->body());
        }

        $login = $this->apiClient()->post('/api/parent/login', ['email' => 'farid@example.com', 'password' => 'secret123']);
        $this->assertSame(200, $login->status(), $login->body());
        $this->assertFalse($login->json('error'), $login->body());
        // Signing in revokes the account's earlier parent-app tokens (one device per
        // app), so the shared fixture goes on with the new one.
        $this->assertSame(401, $this->apiClient(self::$school->tokens['farid'])->get('/api/parent/get-profile-data')->status());
        self::$school->tokens['farid'] = (string) $login->json('token');
    }

    public function test_the_student_profile_says_whether_fees_are_due(): void
    {
        // With compulsory fees on and the free period over, this assigned to an undefined $child.
        $profile = $this->apiClient(self::$school->tokens['asha'])->get('/api/student/get-profile-data');

        $this->assertFalse($profile->json('error'), $profile->body());
        $this->assertSame(0, $profile->json('data.is_fee_payment_due'), 'Asha has paid in full');
    }

    public function test_marks_are_saved_for_every_student_when_some_already_had_marks(): void
    {
        $id = self::$school->ids;
        $timetable = ExamTimetable::where(['exam_id' => $id['unit_test'], 'subject_id' => $id['science']])->firstOrFail();

        // Asha already has marks; Ben doesn't. Replying after Asha's update used to drop Ben's.
        $response = $this->apiClient(self::$school->tokens['tara'])->post('/api/teacher/submit-exam-marks/subject', [
            'class_section_id' => $id['section_a'], 'exam_id' => $id['unit_test'], 'subject_id' => $id['science'],
            'marks_data' => [['student_id' => $id['asha'], 'obtained_marks' => 45], ['student_id' => $id['ben'], 'obtained_marks' => 25]],
        ]);

        $this->assertFalse($response->json('error'), $response->body());
        $marks = ExamMarks::where('exam_timetable_id', $timetable->id)->pluck('obtained_marks', 'student_id')->all();
        $this->assertSame([$id['asha'] => 45, $id['ben'] => 25], $marks);
    }

    public function test_a_batch_of_marks_with_an_ungradable_row_saves_none_of_it(): void
    {
        $id = self::$school->ids;
        $science = ExamTimetable::where(['exam_id' => $id['unit_test'], 'subject_id' => $id['science']])->firstOrFail();
        $before = fn () => ExamMarks::where('exam_timetable_id', $science->id)->pluck('obtained_marks', 'student_id')->all();
        $saved = $before();

        // No grade covers 0 to 49%, so Ben's 5 out of 50 can't be graded. Asha's row, which
        // comes first and already has marks, used to be saved before his was refused.
        $band = DB::table('grades')->where('starting_range', 0)->first();
        DB::table('grades')->where('starting_range', 0)->delete();
        try {
            $response = $this->apiClient(self::$school->tokens['tara'])->post('/api/teacher/submit-exam-marks/subject', [
                'class_section_id' => $id['section_a'], 'exam_id' => $id['unit_test'], 'subject_id' => $id['science'],
                'marks_data' => [['student_id' => $id['asha'], 'obtained_marks' => 40], ['student_id' => $id['ben'], 'obtained_marks' => 5]],
            ]);
        } finally {
            SchoolTable::query('grades')->insert((array) $band);
        }

        $this->assertTrue($response->json('error'), $response->body());
        $this->assertSame($saved, $before());
    }

    public function test_marks_for_one_student_with_an_ungradable_subject_save_none_of_it(): void
    {
        $id = self::$school->ids;
        $science = ExamTimetable::where(['exam_id' => $id['unit_test'], 'subject_id' => $id['science']])->firstOrFail();
        $maths = ExamTimetable::forceCreate([
            'exam_id' => $id['unit_test'], 'class_id' => $science->class_id, 'subject_id' => $id['maths'], 'total_marks' => 50,
            'passing_marks' => 20, 'date' => $science->date, 'start_time' => '09:00:00', 'end_time' => '10:00:00', 'session_year_id' => 1,
        ]);
        $before = fn () => ExamMarks::whereIn('exam_timetable_id', [$science->id, $maths->id])
            ->where('student_id', $id['asha'])->pluck('obtained_marks', 'exam_timetable_id')->all();
        $saved = $before();

        $band = DB::table('grades')->where('starting_range', 0)->first();
        DB::table('grades')->where('starting_range', 0)->delete();
        try {
            $response = $this->apiClient(self::$school->tokens['tara'])->post('/api/teacher/submit-exam-marks/student', [
                'class_section_id' => $id['section_a'], 'exam_id' => $id['unit_test'], 'student_id' => $id['asha'],
                'marks_data' => [['subject_id' => $id['science'], 'obtained_marks' => 33], ['subject_id' => $id['maths'], 'obtained_marks' => 5]],
            ]);
        } finally {
            SchoolTable::query('grades')->insert((array) $band);
            $maths->delete();
        }

        $this->assertTrue($response->json('error'), $response->body());
        $this->assertSame($saved, $before());
    }

    public function test_a_lesson_can_be_saved_under_its_own_name(): void
    {
        $id = self::$school->ids;

        // The duplicate-name check was given the lesson id as the subject id and never
        // excluded the lesson itself.
        $response = $this->apiClient(self::$school->tokens['tara'])->post('/api/teacher/update-lesson', [
            'lesson_id' => $id['lesson'], 'class_section_id' => $id['section_a'], 'subject_id' => $id['maths'],
            'name' => 'Fractions', 'description' => 'Halves, quarters and thirds',
        ]);

        $this->assertFalse($response->json('error'), $response->body());
    }

    /*
     * Found by comparing what the apps parse with what the API sends.
     */

    public function test_the_apps_can_fetch_an_event_schedule_with_get(): void
    {
        // Both apps ask with GET; the route only took POST, so schedules never loaded.
        $response = $this->apiClient(self::$school->tokens['asha'])->get('/api/get-events-details', ['event_id' => self::$school->ids['event']]);

        $this->assertSame(200, $response->status(), $response->body());
        $this->assertFalse($response->json('error'), $response->body());
    }

    public function test_exam_timetables_carry_the_time_names_the_apps_read(): void
    {
        $id = self::$school->ids;
        $timetable = ExamTimetable::where('exam_id', $id['exam'])->orderBy('id')->firstOrFail();

        // Only the student endpoint renamed start_time/end_time; parents and teachers saw no times.
        $parent = $this->apiClient(self::$school->tokens['farid'])->get('/api/parent/get-exam-details', ['child_id' => $id['asha'], 'exam_id' => $id['exam']]);
        $teacher = $this->apiClient(self::$school->tokens['tara'])->get('/api/teacher/get-exam-details', ['exam_id' => $id['exam'], 'class_id' => $id['class_a']]);

        $this->assertSame($timetable->start_time, collect($parent->json('data'))->firstWhere('id', $timetable->id)['starting_time'] ?? null, $parent->body());
        $this->assertSame($timetable->end_time, collect($teacher->json('data.0.timetable'))->firstWhere('id', $timetable->id)['ending_time'] ?? null, $teacher->body());
    }

    public function test_teacher_topics_include_their_files(): void
    {
        $response = $this->apiClient(self::$school->tokens['tara'])->get('/api/teacher/get-topic', ['lesson_id' => self::$school->ids['lesson']]);

        $this->assertSame(['Practice'], collect($response->json('data.0.file'))->pluck('file_name')->all(), $response->body());
    }

    public function test_classes_say_whether_they_use_terms_the_way_the_teacher_app_reads_it(): void
    {
        // The teacher app compares include_terms with '1'; the column had been dropped.
        $classes = $this->apiClient(self::$school->tokens['tara'])->get('/api/teacher/classes')->json('data.class_teacher');

        $this->assertNotEmpty($classes);
        $this->assertSame(0, $classes[0]['class']['include_terms'] ?? null, json_encode($classes[0]));
    }

    public function test_a_parent_chat_preview_shows_an_attachment_only_message(): void
    {
        $id = self::$school->ids;
        $messageId = SchoolTable::query('chat_messages')->insertGetId([
            'modal_type' => 'App/Models/User', 'modal_id' => $id['farid_user'], 'sender_id' => $id['tara_user'],
            'body' => null, 'date' => now()->addMinute(), 'created_at' => now()->addMinute(), 'updated_at' => now()->addMinute(),
        ]);
        SchoolTable::query('chat_files')->insert(['message_id' => $messageId, 'file_type' => 1, 'file_name' => 'chat/photo.png', 'created_at' => now(), 'updated_at' => now()]);

        // The parent list didn't load the message's files, so the preview was blank.
        $teachers = $this->apiClient(self::$school->tokens['farid'])->get('/api/parent/get-user-list', ['offset' => 0, 'limit' => 10])->json('data.items');
        $tara = collect($teachers)->firstWhere('user_id', $id['tara_user']);

        $this->assertSame($messageId, $tara['last_message']['id'] ?? null, json_encode($tara));
        $this->assertStringEndsWith('storage/chat/photo.png', $tara['last_message']['files'][0] ?? '');
    }

    public function test_a_teacher_chat_preview_shows_a_parents_attachment_only_message(): void
    {
        $id = self::$school->ids;
        $messageId = SchoolTable::query('chat_messages')->insertGetId([
            'modal_type' => 'App/Models/User', 'modal_id' => $id['tara_user'], 'sender_id' => $id['gina_user'],
            'body' => null, 'date' => now()->addMinutes(2), 'created_at' => now()->addMinutes(2), 'updated_at' => now()->addMinutes(2),
        ]);
        SchoolTable::query('chat_files')->insert(['message_id' => $messageId, 'file_type' => 1, 'file_name' => 'chat/report.png', 'created_at' => now(), 'updated_at' => now()]);

        // The parent branch selected only body and date, so the message's files never loaded.
        $parents = $this->apiClient(self::$school->tokens['tara'])->get('/api/teacher/get-user-list', ['isParent' => 1, 'limit' => 50, 'offset' => 0])->json('data.items');
        $gina = collect($parents)->firstWhere('user_id', $id['gina_user']);

        $this->assertStringEndsWith('storage/chat/report.png', $gina['last_message']['files'][0] ?? '', json_encode($gina));
    }

    public function test_chat_keeps_the_order_messages_were_sent_in_when_the_time_zone_changes(): void
    {
        $id = self::$school->ids;
        // Sent before the school's time zone moved back (India to Johannesburg is 3.5 hours),
        // so its local time stamp is later than a message sent now.
        SchoolTable::query('chat_messages')->insert([
            'modal_type' => 'App/Models/User', 'modal_id' => $id['tara_user'], 'sender_id' => $id['mona_user'], 'body' => 'Before the change',
            'date' => now()->addHours(3), 'created_at' => now()->addHours(3), 'updated_at' => now()->addHours(3),
        ]);
        $tara = $this->apiClient(self::$school->tokens['tara']);
        $mona = fn () => collect($tara->get('/api/teacher/get-user-list', ['isParent' => 1, 'offset' => 0, 'limit' => 50])->json('data.items'))
            ->firstWhere('user_id', $id['mona_user']);

        $this->assertFalse($this->apiClient(self::$school->tokens['mona'])
            ->post('/api/parent/send-message', ['receiver_id' => $id['tara_user'], 'message' => 'After the change'])->json('error'));

        // Chat went by time stamp: the preview, the thread and "read all" all took the
        // older message as the latest, so the unread badge never cleared.
        $this->assertSame('After the change', $mona()['last_message']['body'] ?? null);
        $this->assertSame('After the change', $tara->post('/api/teacher/get-user-message', ['user_id' => $id['mona_user'], 'offset' => 0, 'limit' => 10])->json('data.items.0.body'));
        $tara->post('/api/teacher/read-all-message', ['user_id' => $id['mona_user']]);
        $this->assertSame(0, $mona()['unread_message']);
    }

    public function test_the_holiday_list_holds_only_the_current_years_holidays(): void
    {
        $lastYear = SchoolTable::query('session_years')->insertGetId([
            'name' => 'Last year', 'start_date' => now()->subYears(2), 'end_date' => now()->subYear(), 'fee_due_date' => now()->subYears(2),
            'fee_due_charges' => 0, 'include_fee_installments' => 0, 'default' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        SchoolTable::query('holidays')->insert(['session_year_id' => $lastYear, 'date' => now()->subYear()->subMonth(), 'title' => 'Old holiday', 'description' => '-']);

        // Every year's holidays were listed, unlike events, so the app calendar showed old ones.
        $titles = collect($this->apiClient(self::$school->tokens['asha'])->get('/api/holidays')->json('data'))->pluck('title')->all();

        $this->assertContains('Founders day', $titles);
        $this->assertNotContains('Old holiday', $titles);
    }

    public function test_the_parent_assigned_tab_lists_only_work_the_child_has_not_handed_in(): void
    {
        $id = self::$school->ids;
        $assigned = fn (string $parent, string $child) => collect(
            $this->apiClient(self::$school->tokens[$parent])->get('/api/parent/assignments', ['child_id' => $id[$child], 'is_submitted' => 0])->json('data.data')
        )->pluck('id')->all();

        // Asha handed the worksheet in; Ben didn't. "0" used to mean "no filter".
        $this->assertNotContains($id['assignment'], $assigned('farid', 'asha'));
        $this->assertContains($id['assignment'], $assigned('gina', 'ben'));
    }

    public function test_class_announcements_reach_the_notice_board_of_that_class_only(): void
    {
        $id = self::$school->ids;
        $board = fn (string $who, string $uri, array $query = []) => collect(
            $this->apiClient(self::$school->tokens[$who])->get("/api/$uri", $query)->json('data.data')
        )->pluck('id')->all();

        // The apps' notice board asks for type=noticeboard, which left out class announcements.
        $this->assertContains($id['class_announcement'], $board('asha', 'student/announcements', ['type' => 'noticeboard']));
        $this->assertContains($id['class_announcement'], $board('farid', 'parent/announcements', ['type' => 'noticeboard']));
        $this->assertNotContains($id['class_announcement'], $board('cara', 'student/announcements', ['type' => 'noticeboard']));

        // Without a type, a parent got every announcement in the school.
        $forCara = $board('farid', 'parent/announcements', ['child_id' => $id['cara']]);
        $this->assertContains($id['notice'], $forCara);
        $this->assertNotContains($id['class_announcement'], $forCara);
        $this->assertNotContains($id['subject_announcement'], $forCara);
    }

    /*
     * Defects the wiki listed as known.
     */

    public function test_absence_alerts_reach_every_parent_and_guardian_once(): void
    {
        $id = self::$school->ids;
        $before = Notification::where('title', 'Attendance Alert')->max('id') ?? 0;
        $absentBoth = [
            'class_section_id' => $id['section_a'], 'student_id' => [$id['asha'], $id['ben']], 'date' => now()->subDays(8)->toDateString(),
            'attendance' => [['student_id' => $id['asha'], 'type' => 0], ['student_id' => $id['ben'], 'type' => 0]],
        ];

        // Only the last absent student's parents were saved, guardians were skipped,
        // and every re-submission alerted again.
        foreach ([1, 2] as $attempt) {
            $response = $this->apiClient(self::$school->tokens['tara'])->post('/api/teacher/submit-attendance', $absentBoth);
            $this->assertFalse($response->json('error'), $response->body());
        }

        $alerts = Notification::where('title', 'Attendance Alert')->where('id', '>', $before)->get();
        $this->assertCount(2, $alerts, 'One alert per absent student, not repeated on re-submission');
        $this->assertSame([2], $alerts->pluck('send_to')->unique()->values()->all(), 'Only the parents, not everyone');
        $recipients = UserNotification::whereIn('notification_id', $alerts->pluck('id'))->pluck('user_id')->sort()->values()->all();
        $this->assertSame(collect([$id['farid_user'], $id['mona_user'], $id['gina_user']])->sort()->values()->all(), $recipients);
    }

    public function test_a_subject_teacher_sees_their_exams_and_parents_but_no_one_elses_leaves(): void
    {
        $id = self::$school->ids;
        $sam = User::forceCreate([
            'first_name' => 'Sam', 'last_name' => 'Subject', 'email' => 'sam@example.com', 'password' => Hash::make('secret123'),
            'gender' => 'Male', 'mobile' => '9876543210',
        ]);
        $sam->assignRole('Teacher');
        ParentPolicyService::acceptCurrent($sam);
        $teacher = Teacher::forceCreate(['user_id' => $sam->id, 'qualification' => 'B.Ed']);
        SchoolTable::query('subject_teachers')->insert([
            'class_section_id' => $id['section_a'], 'subject_id' => $id['maths'], 'teacher_id' => $teacher->id, 'session_year_id' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $api = $this->apiClient($sam->createToken('test')->plainTextToken);

        // Exams were listed for class teachers only, so subject teachers couldn't reach marks entry.
        $exams = collect($api->get('/api/teacher/get-exam-list')->json('data'))->pluck('id')->all();
        $this->assertContains($id['exam'], $exams);

        // With no class of their own, the dashboard listed every pending leave in the school.
        $this->assertSame([], $api->get('/api/teacher/dashboard')->json('data.student_leave_request'));

        // Only fathers were offered for chat; mothers and guardians were missing.
        $parents = collect($api->get('/api/teacher/get-user-list', ['isParent' => 1, 'limit' => 50, 'offset' => 0])->json('data.items'))->pluck('user_id')->all();
        foreach (['farid_user', 'mona_user', 'gina_user'] as $parent) {
            $this->assertContains($id[$parent], $parents, $parent);
        }
    }

    public function test_a_topic_link_from_the_app_can_be_saved(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        // The app sends type 4 for "other link"; the handler only knew 'other_link' and failed.
        $response = $this->apiClient(self::$school->tokens['tara'])
            ->attach('file[0][thumbnail]', $png, 'site.png')
            ->post('/api/teacher/create-topic', [
                'lesson_id' => self::$school->ids['lesson'], 'name' => 'Practice online', 'description' => 'Extra practice',
                'file[0][type]' => '4', 'file[0][name]' => 'Practice site', 'file[0][link]' => 'https://example.com/fractions',
            ]);

        $this->assertFalse($response->json('error'), $response->body());
        $this->assertSame('https://example.com/fractions', File::where('file_name', 'Practice site')->value('file_url'));
    }
}
