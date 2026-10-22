<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\User;
use App\Services\ParentPolicyService;
use App\Services\Platform\Tenancy\SchoolOwnedTables;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * A teacher can only act on the classes, subjects, students and files they teach.
 *
 * Teacher A is class teacher and subject teacher of section A; teacher B of
 * section B (another class). The API is called in-process with each teacher's
 * Sanctum token, inside a RefreshDatabase transaction. (It used to need a
 * separate server, RunsAppServer, because ResponseService called exit().)
 */
class TeacherApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, int> */
    private static array $ids = [];

    /** @var array<string, string> */
    private static array $tokens = [];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seedLikeInstaller();
        $this->createSchool();
    }

    /** The answer now goes back through the middleware, which adds its headers. */
    public function test_api_answers_pass_through_the_response_middleware(): void
    {
        $response = $this->teacherRequest('B', 'get', 'student-details', ['student_id' => self::$ids['student_a']]);

        $response->assertOk()->assertJson(['error' => true, 'code' => 403]);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-RateLimit-Limit', '60');
    }

    public function test_only_the_owner_can_delete_an_assignment(): void
    {
        $assignment = $this->insert('assignments', [
            'class_section_id' => self::$ids['section_a'], 'subject_id' => self::$ids['subject'],
            'name' => 'Homework', 'due_date' => now()->addWeek(), 'session_year_id' => 1,
        ]);

        $this->assertError($this->api('B', 'post', 'delete-assignment', ['assignment_id' => $assignment]));
        $this->assertNull(DB::table('assignments')->find($assignment)->deleted_at);

        $this->assertSuccess($this->api('A', 'post', 'delete-assignment', ['assignment_id' => $assignment]));
        $this->assertNotNull(DB::table('assignments')->find($assignment)->deleted_at);
    }

    public function test_student_details_are_only_for_the_students_teachers(): void
    {
        $this->assertError($this->api('B', 'get', 'student-details', ['student_id' => self::$ids['student_a']]), 403);
        $this->assertError($this->api('B', 'get', 'get-student-result-pdf', ['student_id' => self::$ids['student_a']]), 403);

        $details = $this->api('A', 'get', 'student-details', ['student_id' => self::$ids['student_a']]);
        $this->assertSuccess($details);
        $this->assertSame('Father', $details['father_data'][0]['first_name']);
    }

    public function test_only_the_class_teacher_sees_and_decides_student_leaves(): void
    {
        $leave = $this->insert('leaves', [
            'user_id' => self::$ids['student_a_user'], 'reason' => 'Sick', 'from_date' => now()->toDateString(),
            'to_date' => now()->toDateString(), 'status' => '0', 'session_year_id' => 1,
        ]);
        $list = ['class_section_id' => self::$ids['section_a'], 'session_year_id' => 1];

        $this->assertError($this->api('B', 'post', 'get-student-leave-list', $list), 403);
        $this->assertSame(1, $this->api('A', 'post', 'get-student-leave-list', $list)['data']['total_leave_requests']);

        $this->assertError($this->api('B', 'post', 'student-leave-status-update', ['leave_id' => $leave, 'status' => 1]), 403);
        $this->assertSame('0', (string) DB::table('leaves')->find($leave)->status);

        $this->assertSuccess($this->api('A', 'post', 'student-leave-status-update', ['leave_id' => $leave, 'status' => 2, 'reason_of_rejection' => 'No']));
        $this->assertSame('2', (string) DB::table('leaves')->find($leave)->status);
    }

    public function test_only_the_lessons_teacher_can_change_its_files(): void
    {
        $lesson = $this->insert('lessons', [
            'name' => 'Fractions', 'description' => 'Intro', 'class_section_id' => self::$ids['section_a'], 'subject_id' => self::$ids['subject'],
        ]);
        $file = $this->insert('files', [
            'modal_type' => 'App\Models\Lesson', 'modal_id' => $lesson, 'file_name' => 'Video', 'type' => 2, 'file_url' => 'https://example.com/v',
        ]);

        $this->assertError($this->api('B', 'post', 'update-file', ['file_id' => $file, 'name' => 'Hacked', 'link' => 'https://evil.example']), 403);
        $this->assertError($this->api('B', 'post', 'delete-file', ['file_id' => $file]), 403);
        $this->assertSame('Video', DB::table('files')->find($file)->file_name);

        $this->assertSuccess($this->api('A', 'post', 'delete-file', ['file_id' => $file]));
        $this->assertNull(DB::table('files')->find($file));
    }

    public function test_attendance_is_only_for_the_class_teachers_own_class(): void
    {
        $date = now()->toDateString();

        $this->assertError($this->api('B', 'get', 'get-attendance', ['class_section_id' => self::$ids['section_a'], 'date' => $date]), 403);
        $this->assertSuccess($this->api('A', 'get', 'get-attendance', ['class_section_id' => self::$ids['section_a'], 'date' => $date]));

        $this->assertError($this->api('B', 'post', 'submit-attendance', [
            'class_section_id' => self::$ids['section_a'], 'date' => $date,
            'attendance' => [['student_id' => self::$ids['student_a'], 'type' => 0]],
        ]), 403);
        // A's own class, but a student of another class.
        $this->assertError($this->api('A', 'post', 'submit-attendance', [
            'class_section_id' => self::$ids['section_a'], 'date' => $date,
            'attendance' => [['student_id' => self::$ids['student_b'], 'type' => 0]],
        ]), 403);
        $this->assertSame(0, DB::table('attendances')->count());
    }

    public function test_exam_marks_need_the_class_or_subject_teacher_and_their_students(): void
    {
        $marks = fn (string $student) => [
            'class_section_id' => self::$ids['section_a'], 'exam_id' => 999_999, 'subject_id' => self::$ids['subject'],
            'marks_data' => [['student_id' => self::$ids[$student], 'obtained_marks' => 5]],
        ];

        $this->assertError($this->api('B', 'post', 'submit-exam-marks/subject', $marks('student_a')), 403);
        $this->assertError($this->api('A', 'post', 'submit-exam-marks/subject', $marks('student_b')), 403);
        $this->assertError($this->api('B', 'get', 'get-exam-details', ['exam_id' => 999_999, 'class_id' => self::$ids['class_a']]), 403);
    }

    public function test_only_the_teachers_own_timetable_link_can_be_changed(): void
    {
        $timetable = $this->insert('timetables', [
            'subject_teacher_id' => self::$ids['subject_teacher_a'], 'class_section_id' => self::$ids['section_a'],
            'start_time' => '09:00:00', 'end_time' => '10:00:00',
            'day' => 1, 'day_name' => 'Monday', 'session_year_id' => 1,
        ]);
        $link = ['timetable_id' => $timetable, 'live_class_link' => 'https://meet.example/x', 'link_name' => 'Meet'];

        $this->assertError($this->api('B', 'post', 'update-timetable-link', $link), 403);
        $this->assertNull(DB::table('timetables')->find($timetable)->live_class_url);

        $this->assertSuccess($this->api('A', 'post', 'update-timetable-link', $link));
        $this->assertSame('https://meet.example/x', DB::table('timetables')->find($timetable)->live_class_url);
    }

    public function test_chat_is_limited_to_own_students_and_parents_and_files_never_collide(): void
    {
        $this->assertError($this->api('B', 'post', 'send-message', ['receiver_id' => self::$ids['student_a_user'], 'message' => 'Hi']), 403);
        $this->assertSame(0, DB::table('chat_messages')->where('sender_id', self::$ids['teacher_b_user'])->count());

        $this->assertSuccess($this->api('A', 'post', 'send-message', ['receiver_id' => self::$ids['father_user'], 'message' => 'Hello']));

        // Two uploads with the same name are both kept, each under that name.
        $paths = [];
        foreach (['first', 'second'] as $content) {
            $this->app['auth']->forgetGuards();
            $response = $this->withToken(self::$tokens['A'])
                ->withHeader('Accept', 'application/json')
                ->post('/api/teacher/send-message', [
                    'receiver_id' => self::$ids['student_a_user'],
                    'file' => [UploadedFile::fake()->createWithContent('notes.txt', $content)],
                ])
                ->json();
            $this->assertSuccess($response);
            $this->assertStringEndsWith('/notes.txt', $response['data']['files'][0]);
            $paths[$content] = DB::table('chat_files')->latest('id')->value('file_name');
        }
        $this->assertNotSame($paths['first'], $paths['second']);
        foreach ($paths as $content => $path) {
            $this->assertStringStartsWith('chatfile/', $path);
            $this->assertSame($content, Storage::disk('public')->get($path));
        }
    }

    public function test_a_teacher_user_without_a_teacher_record_gets_nothing(): void
    {
        $this->insert('assignments', [
            'class_section_id' => self::$ids['section_a'], 'subject_id' => self::$ids['subject'],
            'name' => 'Scoped', 'due_date' => now()->addWeek(), 'session_year_id' => 1,
        ]);

        $this->assertError($this->api('X', 'get', 'student-details', ['student_id' => self::$ids['student_a']]), 403);
        $assignments = $this->api('X', 'get', 'get-assignment');
        $this->assertSuccess($assignments);
        $this->assertSame(0, $assignments['data']['total']);

        $this->actingAs(User::findOrFail(self::$ids['teacher_x_user']));
        $this->assertSame(0, Assignment::assignmentTeachers()->count());

        $this->actingAs(User::findOrFail(self::$ids['teacher_a_user']));
        $this->assertGreaterThan(0, Assignment::assignmentTeachers()->count());

        $this->actingAs($this->superAdmin());
        $this->assertSame(Assignment::count(), Assignment::assignmentTeachers()->count());
    }

    private function api(string $teacher, string $method, string $uri, array $data = []): array
    {
        $response = $this->teacherRequest($teacher, $method, $uri, $data);

        $this->assertSame(200, $response->status(), "$uri: ".$response->getContent());

        return $response->json();
    }

    /** One in-process request to the teacher API with the teacher's token. */
    private function teacherRequest(string $teacher, string $method, string $uri, array $data = []): TestResponse
    {
        // Each request authenticates by its own token; the guard would otherwise
        // keep the user of the previous request.
        $this->app['auth']->forgetGuards();
        $client = $this->withToken(self::$tokens[$teacher]);

        return $method === 'get'
            ? $client->getJson('/api/teacher/'.$uri.($data ? '?'.http_build_query($data) : ''))
            : $client->postJson('/api/teacher/'.$uri, $data);
    }

    private function assertError(array $response, ?int $code = null): void
    {
        $this->assertTrue($response['error'], json_encode($response));
        if ($code !== null) {
            $this->assertSame($code, $response['code'], json_encode($response));
        }
    }

    private function assertSuccess(array $response): void
    {
        $this->assertFalse($response['error'], json_encode($response));
    }

    /**
     * Two classes with one section each. Teacher A is class teacher and subject
     * teacher of section A, teacher B of section B; each section has a student.
     * Teacher X has the Teacher role but no teacher record.
     */
    private function createSchool(): void
    {
        $medium = $this->insert('mediums', ['name' => 'English']);
        $subject = $this->insert('subjects', ['name' => 'Maths', 'bg_color' => '#fff', 'image' => 'x.png', 'medium_id' => $medium, 'type' => 'Theory']);
        $fatherUser = $this->user('father', 'Parent');
        $father = $this->insert('parents', [
            'user_id' => $fatherUser->id, 'first_name' => 'Father', 'last_name' => 'A', 'gender' => 'Male', 'mobile' => '1', 'occupation' => 'x',
        ]);

        self::$ids = ['subject' => $subject, 'father_user' => $fatherUser->id];

        foreach (['A', 'B'] as $name) {
            $key = strtolower($name);
            $class = $this->insert('classes', ['name' => "Class $name", 'medium_id' => $medium]);
            $section = $this->insert('class_sections', ['class_id' => $class, 'section_id' => $this->insert('sections', ['name' => $name])]);
            $this->insert('class_subjects', ['class_id' => $class, 'subject_id' => $subject, 'type' => 'Compulsory', 'session_year_id' => 1]);

            $teacherUser = $this->user("teacher$key", 'Teacher');
            $teacher = $this->insert('teachers', ['user_id' => $teacherUser->id, 'qualification' => 'B.Ed']);
            $subjectTeacher = $this->insert('subject_teachers', [
                'class_section_id' => $section, 'subject_id' => $subject, 'teacher_id' => $teacher, 'session_year_id' => 1,
            ]);
            $this->insert('class_teachers', ['class_section_id' => $section, 'class_teacher_id' => $teacher, 'session_year_id' => 1]);

            $studentUser = $this->user("student$key", 'Student');
            $student = $this->insert('students', [
                'user_id' => $studentUser->id, 'class_section_id' => $section, 'class_id' => $class, 'category_id' => 1,
                'admission_no' => "ADM-$name", 'admission_date' => now()->toDateString(), 'roll_number' => 1,
                'father_id' => $name === 'A' ? $father : null,
            ]);
            $this->insert('student_sessions', ['student_id' => $student, 'class_section_id' => $section, 'session_year_id' => 1]);

            self::$ids += [
                "class_$key" => $class, "section_$key" => $section, "subject_teacher_$key" => $subjectTeacher,
                "teacher_{$key}_user" => $teacherUser->id, "student_$key" => $student, "student_{$key}_user" => $studentUser->id,
            ];
            self::$tokens[$name] = $teacherUser->createToken('test')->plainTextToken;
        }

        $teacherX = $this->user('teacherx', 'Teacher');
        self::$ids['teacher_x_user'] = $teacherX->id;
        self::$tokens['X'] = $teacherX->createToken('test')->plainTextToken;
    }

    private function user(string $name, string $role): User
    {
        $user = User::create([
            'first_name' => ucfirst($name), 'last_name' => 'Test', 'email' => "$name@example.com",
            'password' => Hash::make('secret123'), 'gender' => 'Male', 'mobile' => '0000000000',
        ]);
        $user->assignRole($role);
        if ($role === 'Teacher') {
            ParentPolicyService::acceptCurrent($user);
        }

        return $user;
    }

    private function insert(string $table, array $values): int
    {
        $query = SchoolOwnedTables::isSchoolOwned($table) ? SchoolTable::query($table) : DB::table($table);

        return $query->insertGetId($values + ['created_at' => now(), 'updated_at' => now()]);
    }
}
