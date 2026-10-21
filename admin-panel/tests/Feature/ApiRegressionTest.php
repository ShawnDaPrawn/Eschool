<?php

namespace Tests\Feature;

use App\Models\ExamMarks;
use App\Models\ExamTimetable;
use Illuminate\Support\Facades\DB;
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
            DB::table('grades')->insert((array) $band);
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
            DB::table('grades')->insert((array) $band);
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
}
