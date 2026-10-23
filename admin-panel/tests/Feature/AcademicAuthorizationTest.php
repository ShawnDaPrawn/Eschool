<?php

namespace Tests\Feature;

use App\Models\ClassSection;
use App\Models\ClassTeacher;
use App\Models\Exam;
use App\Models\ExamMarks;
use App\Models\ExamTimetable;
use App\Models\Grade;
use App\Models\Settings;
use App\Models\Staff;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\SubjectTeacher;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Permission and scope checks on the academic admin endpoints (students, class
 * teachers, exam marks, timetables). Only paths that answer without exit() are
 * requested in-process (see AdminPagesTest).
 */
class AcademicAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
    }

    public function test_teacher_cannot_permanently_delete_a_student(): void
    {
        $student = $this->student();

        $this->actingAs($this->teacherUser())
            ->deleteJson('/permanent-delete/'.$student->id)
            ->assertJson(['error' => true, 'message' => trans('no_permission_message')]);

        $this->assertNotNull(Students::withTrashed()->find($student->id));
        $this->assertNotNull(User::withTrashed()->find($student->user_id));
    }

    public function test_super_admin_can_permanently_delete_a_student(): void
    {
        $student = $this->student();

        $this->actingAs($this->superAdmin())
            ->deleteJson('/permanent-delete/'.$student->id)
            ->assertJson(['error' => false]);

        $this->assertNull(Students::withTrashed()->find($student->id));
        $this->assertTrue(User::withTrashed()->findOrFail($student->user_id)->trashed());
        $this->assertDatabaseHas('learner_identifiers', ['user_id' => $student->user_id, 'student_number' => $student->student_number]);
    }

    public function test_teacher_cannot_approve_an_online_registration(): void
    {
        $student = $this->student(['status' => 0]);

        $this->actingAs($this->teacherUser())
            ->postJson('/update-active-status', ['status' => 1, 'edit_id' => $student->user_id, 'class_id' => 1])
            ->assertJson(['error' => true, 'message' => trans('no_permission_message')]);

        $this->assertSame(0, (int) User::find($student->user_id)->status);
    }

    public function test_teacher_cannot_remove_a_class_teacher(): void
    {
        $classSection = ClassSection::forceCreate(['class_id' => 1, 'section_id' => 1]);
        $classTeacher = $this->teacher('other.teacher@example.com');
        $assignment = ClassTeacher::forceCreate([
            'class_section_id' => $classSection->id,
            'class_teacher_id' => $classTeacher->id,
            'session_year_id' => 1,
        ]);

        $this->actingAs($this->teacherUser())
            ->postJson('/remove-class-teacher/'.$classSection->id.'/'.$classTeacher->id)
            ->assertJson(['error' => true, 'message' => trans('no_permission_message')]);
        $this->assertNotNull(ClassTeacher::find($assignment->id));

        $this->actingAs($this->superAdmin())
            ->postJson('/remove-class-teacher/'.$classSection->id.'/'.$classTeacher->id)
            ->assertJson(['error' => false]);
        $this->assertNull(ClassTeacher::find($assignment->id));
    }

    public function test_remove_image_only_deletes_id_card_images_and_needs_the_setting_permission(): void
    {
        $this->actingAs($this->teacherUser())
            ->deleteJson('/remove-image/signature')
            ->assertJson(['error' => true, 'message' => trans('no_permission_message')]);

        // Even with the permission, other settings rows can't be deleted through it.
        $this->actingAs($this->superAdmin())
            ->deleteJson('/remove-image/session_year')
            ->assertJson(['error' => true]);
        $this->assertTrue(Settings::where('type', 'session_year')->exists());
    }

    public function test_monthly_attendance_needs_the_monthly_attendance_permission(): void
    {
        $this->actingAs($this->teacherUser())
            ->getJson('/attendance/monthly-ajax?class_section_id=1&month=2023-01')
            ->assertJson(['error' => true, 'message' => trans('no_permission_message')]);
    }

    public function test_search_text_is_bound_not_spliced_into_sql(): void
    {
        User::forceCreate([
            'first_name' => 'Sean', 'last_name' => "O'Brien", 'email' => 'sean@example.com',
            'password' => Hash::make('secret123'), 'gender' => 'Male', 'mobile' => '0', 'reset_request' => 1,
        ]);
        User::forceCreate([
            'first_name' => 'Ann', 'last_name' => 'Lee', 'email' => 'ann@example.com',
            'password' => Hash::make('secret123'), 'gender' => 'Female', 'mobile' => '0', 'reset_request' => 1,
        ]);
        $admin = $this->superAdmin();

        // A quote in a full-name search used to break the query (only the concat clause matches it).
        $this->searchResetRequests($admin, "Sean O'Brien")->assertOk()->assertJson(['total' => 1]);
        // An injected condition is treated as literal text.
        $this->searchResetRequests($admin, "zz' OR 1=1 -- ")->assertOk()->assertJson(['total' => 0]);
    }

    public function test_teacher_can_only_submit_marks_for_their_own_class_sections(): void
    {
        $teacherUser = $this->teacherUser();
        $ownSection = ClassSection::forceCreate(['class_id' => 1, 'section_id' => 1]);
        $otherSection = ClassSection::forceCreate(['class_id' => 1, 'section_id' => 2]);
        ClassTeacher::forceCreate(['class_section_id' => $ownSection->id, 'class_teacher_id' => $teacherUser->teacher->id, 'session_year_id' => 1]);
        $ownStudent = $this->student();
        $otherStudent = $this->student([], 'other.student@example.com');
        StudentSessions::forceCreate(['student_id' => $ownStudent->id, 'session_year_id' => 1, 'class_section_id' => $ownSection->id]);
        StudentSessions::forceCreate(['student_id' => $otherStudent->id, 'session_year_id' => 1, 'class_section_id' => $otherSection->id]);
        Grade::forceCreate(['starting_range' => 0, 'ending_range' => 100, 'grade' => 'A', 'session_year_id' => 1]);
        $exam = Exam::forceCreate(['name' => 'Term 1', 'session_year_id' => 1]);
        $timetable = $this->examTimetable($exam->id, 1);
        $otherTimetable = $this->examTimetable($exam->id, 2);
        $otherMarks = ExamMarks::forceCreate([
            'exam_timetable_id' => $otherTimetable->id, 'student_id' => $otherStudent->id, 'subject_id' => 2,
            'obtained_marks' => 10, 'passing_status' => 0, 'session_year_id' => 1,
        ]);
        $submit = fn (array $marks) => $this->actingAs($teacherUser)->postJson('/exams/submit-marks', [
            'exam_id' => $exam->id, 'class_id' => 1, 'subject_id' => 1,
            'exam_marks' => [1 => $marks + ['total_marks' => 100]],
        ]);

        // A student of another class section.
        $submit(['student_id' => $otherStudent->id, 'obtained_marks' => 90])
            ->assertJson(['error' => true, 'message' => trans('no_permission_message')]);
        // Overwriting another timetable's marks row through exam_marks_id.
        $submit(['student_id' => $ownStudent->id, 'obtained_marks' => 90, 'exam_marks_id' => $otherMarks->id])
            ->assertJson(['error' => true, 'message' => trans('no_permission_message')]);
        $this->assertSame(10, (int) $otherMarks->fresh()->obtained_marks);
        $this->assertSame(1, ExamMarks::count());

        // Their own student still works.
        $submit(['student_id' => $ownStudent->id, 'obtained_marks' => 90])->assertJson(['error' => false]);
        $this->assertSame(90, (int) ExamMarks::where('exam_timetable_id', $timetable->id)->where('student_id', $ownStudent->id)->value('obtained_marks'));
    }

    public function test_live_class_link_can_only_be_set_by_the_periods_teacher_with_a_web_url(): void
    {
        $owner = $this->teacherUser();
        $intruder = $this->teacher('intruder@example.com')->user;
        $subjectTeacher = SubjectTeacher::forceCreate(['class_section_id' => 1, 'subject_id' => 1, 'teacher_id' => $owner->teacher->id, 'session_year_id' => 1]);
        $period = Timetable::forceCreate([
            'subject_teacher_id' => $subjectTeacher->id, 'class_section_id' => 1, 'session_year_id' => 1,
            'start_time' => '09:00', 'end_time' => '10:00', 'day' => 1, 'day_name' => 'monday',
        ]);
        $update = fn (User $user, string $url) => $this->actingAs($user)->postJson('/link-url-update', [
            'edit_id' => $period->id, 'live_class_url' => $url, 'link_name' => 'Maths',
        ]);

        $update($intruder, 'https://meet.example.com/a')
            ->assertJson(['error' => true, 'message' => trans('no_permission_message')]);
        $update($owner, 'javascript:alert(1)')->assertJson(['error' => true]);
        $this->assertNull($period->fresh()->live_class_url);

        $update($owner, 'https://meet.example.com/a')->assertJson(['error' => false]);
        $this->assertSame('https://meet.example.com/a', $period->fresh()->live_class_url);
    }

    private function searchResetRequests(User $user, string $search)
    {
        // reset_password() reads the query string from $_GET, which in-process requests don't fill.
        $_GET = ['search' => $search];
        try {
            return $this->actingAs($user)->getJson('/reset-password-list?'.http_build_query(['search' => $search]));
        } finally {
            $_GET = [];
        }
    }

    private function examTimetable(int $examId, int $subjectId): ExamTimetable
    {
        return ExamTimetable::forceCreate([
            'exam_id' => $examId, 'class_id' => 1, 'subject_id' => $subjectId, 'total_marks' => 100,
            'passing_marks' => 33, 'date' => now()->subDays(2)->toDateString(), 'start_time' => '09:00',
            'end_time' => '10:00', 'session_year_id' => 1,
        ]);
    }

    private function student(array $userAttributes = [], string $email = 'student.test@example.com'): Students
    {
        $user = User::forceCreate($userAttributes + [
            'first_name' => 'Test', 'last_name' => 'Student', 'email' => $email,
            'password' => Hash::make('secret123'), 'gender' => 'Male', 'mobile' => '0', 'dob' => '2010-01-01',
        ]);
        $user->assignRole('Student');

        return Students::forceCreate([
            'user_id' => $user->id, 'category_id' => 1, 'admission_no' => 'ADM'.$user->id,
            'admission_date' => '2022-06-01',
        ]);
    }

    private function teacherUser(): User
    {
        return $this->teacher('teacher.test@example.com')->user;
    }

    private function teacher(string $email): Teacher
    {
        $user = User::forceCreate([
            'first_name' => 'Test', 'last_name' => 'Teacher', 'email' => $email,
            'password' => Hash::make('secret123'), 'gender' => 'Male', 'mobile' => '0',
        ]);
        $user->assignRole('Teacher');
        Staff::forceCreate(['user_id' => $user->id]);

        return Teacher::forceCreate(['user_id' => $user->id, 'qualification' => 'B.Ed']);
    }
}
