<?php

namespace Tests\Feature;

use App\Models\ExamMarks;
use App\Models\ExamTimetable;
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

    public function test_classes_say_whether_they_use_semesters_the_way_the_teacher_app_reads_it(): void
    {
        // The teacher app compares include_semesters with '1'; the column had been dropped.
        $classes = $this->apiClient(self::$school->tokens['tara'])->get('/api/teacher/classes')->json('data.class_teacher');

        $this->assertNotEmpty($classes);
        $this->assertSame(0, $classes[0]['class']['include_semesters'] ?? null, json_encode($classes[0]));
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
}
