<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\Concerns\RunsAppServer;
use Tests\Support\ApiShape;
use Tests\Support\DemoSchool;
use Tests\TestCase;

/**
 * The mobile apps already on people's phones depend on every API response
 * keeping its shape. This calls each endpoint against a demo school and
 * compares the response's shape (keys and value types, see ApiShape) plus its
 * error flag, message and code with the snapshot in tests/snapshots/api.
 *
 * After an intended API change, regenerate the snapshots and review the diff:
 *     UPDATE_API_SNAPSHOTS=1 php artisan test --filter=ApiContractTest
 *
 * Set API_RESPONSES_DIR to also save every full response there, e.g. to diff
 * the actual data before and after a refactor.
 */
class ApiContractTest extends TestCase
{
    use RunsAppServer;

    private const SNAPSHOTS = 'tests/snapshots/api';

    public function test_api_responses_keep_their_shape(): void
    {
        $this->freshInstall();
        $school = DemoSchool::build();
        $this->startAppServer();

        $update = (bool) getenv('UPDATE_API_SNAPSHOTS');
        $responsesDir = getenv('API_RESPONSES_DIR') ?: null;
        $changed = [];
        $seen = [];

        foreach ($this->calls($school->ids) as $name => [$who, $method, $uri, $params]) {
            $response = $this->apiClient($who === null ? null : $school->tokens[$who])->{$method}('/api/'.$uri, $params);
            $json = $response->json();
            $actual = [
                'request' => strtoupper($method).' /api/'.$uri,
                'status' => $response->status(),
                'error' => $json['error'] ?? null,
                'message' => $json['message'] ?? null,
                'code' => $json['code'] ?? null,
                'shape' => ApiShape::of($json),
            ];
            if ($responsesDir) {
                File::ensureDirectoryExists(dirname("$responsesDir/$name.json"));
                File::put("$responsesDir/$name.json", json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
            }

            $file = base_path(self::SNAPSHOTS."/$name.json");
            $seen[] = $file;
            $encoded = json_encode($actual, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";
            if ($update) {
                File::ensureDirectoryExists(dirname($file));
                File::put($file, $encoded);
            } elseif (! File::exists($file) || File::get($file) !== $encoded) {
                $changed[] = $name;
                File::ensureDirectoryExists(storage_path('framework/testing/api-snapshots/'.dirname($name)));
                File::put(storage_path("framework/testing/api-snapshots/$name.json"), $encoded);
            }
        }

        $stale = array_diff(File::glob(base_path(self::SNAPSHOTS.'/*/*.json')), $seen);
        if ($update) {
            File::delete($stale);
        }

        $this->assertSame([], $changed, 'These API responses changed shape. The new ones are in storage/framework/testing/api-snapshots; '
            .'if the change is intended, run UPDATE_API_SNAPSHOTS=1 php artisan test --filter=ApiContractTest');
        $this->assertSame([], array_values($stale), 'Snapshots for calls that no longer exist; run with UPDATE_API_SNAPSHOTS=1 to remove them');
    }

    /**
     * Every endpoint the apps call, in order: reads first, then changes, then
     * the calls that sign people out.
     *
     * @param  array<string, int>  $id
     * @return array<string, array{0: ?string, 1: string, 2: string, 3: array}>
     */
    private function calls(array $id): array
    {
        $year = $id['session_year'];
        $month = (int) now()->format('n');
        $asha = ['child_id' => $id['asha']];
        $page = ['limit' => 10, 'offset' => 0]; // the apps always page lists

        $calls = [
            // Shared by all apps
            'common/settings-app' => [null, 'get', 'settings', ['type' => 'app_settings']],
            'common/settings-fees' => [null, 'get', 'settings', ['type' => 'fees_settings']],
            'common/settings-about' => [null, 'get', 'settings', ['type' => 'about_us']],
            'common/settings-invalid' => [null, 'get', 'settings', ['type' => 'nope']],
            'common/sliders' => ['asha', 'get', 'sliders', []],
            'common/holidays' => ['asha', 'get', 'holidays', []],
            'common/session-years' => ['asha', 'get', 'get-session-year', []],
            'common/current-session-year' => ['asha', 'get', 'current-session-year', []],
            'common/semesters' => ['asha', 'get', 'get-semesters', []],
            'common/events' => ['asha', 'get', 'get-events-list', []],
            'common/event-details' => ['asha', 'post', 'get-events-details', ['event_id' => $id['event']]],
            'common/gallery-albums' => ['asha', 'get', 'get-gallery-albums', []],
            'common/gallery-images' => ['asha', 'get', 'get-gallery-images', ['album_id' => $id['album']]],
            'common/gallery-videos' => ['asha', 'get', 'get-gallery-videos', []],
            'common/no-token' => [null, 'get', 'student/dashboard', []],

            // Chat messages first, so the chat lists below have something to show
            'teacher/send-message' => ['tara', 'post', 'teacher/send-message', ['receiver_id' => $id['farid_user'], 'message' => 'Asha did well today']],
            'parent/send-message' => ['farid', 'post', 'parent/send-message', ['receiver_id' => $id['tara_user'], 'message' => 'Thank you']],
            'student/send-message' => ['asha', 'post', 'student/send-message', ['receiver_id' => $id['tara_user'], 'message' => 'Is homework due Friday?']],

            // Student app, as a student
            'student/dashboard' => ['asha', 'get', 'student/dashboard', []],
            'student/profile' => ['asha', 'get', 'student/get-profile-data', []],
            'student/parent-details' => ['asha', 'get', 'student/parent-details', []],
            'student/subjects' => ['asha', 'get', 'student/subjects', []],
            'student/class-subjects' => ['asha', 'get', 'student/class-subjects', []],
            'student/timetable' => ['asha', 'get', 'student/timetable', []],
            'student/lessons' => ['asha', 'get', 'student/lessons', ['subject_id' => $id['maths']]],
            'student/lesson-topics' => ['asha', 'get', 'student/lesson-topics', ['lesson_id' => $id['lesson']]],
            'student/assignments' => ['asha', 'get', 'student/assignments', []],
            'student/assignments-submitted' => ['asha', 'get', 'student/assignments', ['is_submitted' => 1]],
            'student/attendance' => ['asha', 'get', 'student/attendance', ['month' => $month, 'year' => (int) now()->format('Y')]],
            'student/attendance-report' => ['asha', 'get', 'student/attendance-report', ['session_year_id' => $year, 'month' => $month]],
            'student/announcements' => ['asha', 'get', 'student/announcements', []],
            'student/announcements-subject' => ['asha', 'get', 'student/announcements', ['type' => 'subject', 'subject_id' => $id['maths']]],
            'student/exams' => ['asha', 'get', 'student/get-exam-list', []],
            'student/exams-with-timetable' => ['asha', 'get', 'student/get-exam-list', ['get_timetable' => 1]],
            'student/exam-details' => ['asha', 'get', 'student/get-exam-details', ['exam_id' => $id['exam']]],
            'student/exam-marks' => ['asha', 'get', 'student/exam-marks', []],
            'student/online-exams' => ['asha', 'get', 'student/get-online-exam-list', []],
            'student/online-exam-questions' => ['asha', 'get', 'student/get-online-exam-questions', ['exam_id' => $id['online_exam'], 'exam_key' => 1234]],
            'student/online-exam-results' => ['asha', 'get', 'student/get-online-exam-result-list', []],
            'student/online-exam-result' => ['asha', 'get', 'student/get-online-exam-result', ['online_exam_id' => $id['past_online_exam']]],
            'student/fees-details' => ['asha', 'get', 'student/fees-details', []],
            'student/fees-paid' => ['asha', 'get', 'student/fees-paid-list', []],
            'student/fees-receipt-pdf' => ['asha', 'get', 'student/fees-paid-receipt-pdf', []],
            'student/fees-transactions' => ['asha', 'get', 'student/fees-transactions-list', []],
            'student/fees-report' => ['asha', 'get', 'student/fees-report', ['session_year_id' => $year]],
            'student/leave-report' => ['asha', 'get', 'student/leave-report', ['session_year_id' => $year]],
            'student/assignment-report' => ['asha', 'get', 'student/assignment-report', ['session_year_id' => $year]],
            'student/offline-exam-report' => ['asha', 'get', 'student/offline-exam-report', ['session_year_id' => $year]],
            'student/online-exam-report' => ['asha', 'get', 'student/online-exam-report', ['session_year_id' => $year]],
            'student/leaves' => ['asha', 'post', 'student/get-leave-list', []],
            'student/notifications' => ['asha', 'get', 'student/get-notification', []],
            'student/chat-users' => ['asha', 'get', 'student/get-user-list', $page],
            'student/chat-messages' => ['asha', 'post', 'student/get-user-message', ['user_id' => $id['tara_user']] + $page],
            'student/read-all-messages' => ['asha', 'post', 'student/read-all-message', ['user_id' => $id['tara_user']]],
            'student/academic-calendar-pdf' => ['asha', 'get', 'student/academic-calendar-pdf', []],
            'student/id-card-pdf' => ['asha', 'get', 'student/id-card-pdf', []],
            'student/validation-error' => ['asha', 'get', 'student/lessons', []],

            // Student app, as a parent
            'parent/profile' => ['farid', 'get', 'parent/get-profile-data', []],
            'parent/subjects' => ['farid', 'get', 'parent/subjects', $asha],
            'parent/class-subjects' => ['farid', 'get', 'parent/class-subjects', $asha],
            'parent/teachers' => ['farid', 'get', 'parent/teachers', $asha],
            'parent/timetable' => ['farid', 'get', 'parent/timetable', $asha],
            'parent/lessons' => ['farid', 'get', 'parent/lessons', $asha + ['subject_id' => $id['maths']]],
            'parent/lesson-topics' => ['farid', 'get', 'parent/lesson-topics', $asha + ['lesson_id' => $id['lesson']]],
            'parent/assignments' => ['farid', 'get', 'parent/assignments', $asha],
            'parent/attendance' => ['farid', 'get', 'parent/attendance', $asha + ['month' => $month, 'year' => (int) now()->format('Y')]],
            'parent/attendance-report' => ['farid', 'get', 'parent/attendance-report', $asha + ['session_year_id' => $year, 'month' => $month]],
            'parent/announcements' => ['farid', 'get', 'parent/announcements', ['type' => 'noticeboard']],
            'parent/announcements-class' => ['farid', 'get', 'parent/announcements', $asha + ['type' => 'class']],
            'parent/exams' => ['farid', 'get', 'parent/get-exam-list', $asha],
            'parent/exam-details' => ['farid', 'get', 'parent/get-exam-details', $asha + ['exam_id' => $id['exam']]],
            'parent/exam-marks' => ['farid', 'get', 'parent/exam-marks', $asha],
            'parent/online-exams' => ['farid', 'get', 'parent/get-online-exam-list', $asha],
            'parent/online-exam-results' => ['farid', 'get', 'parent/get-online-exam-result-list', $asha],
            'parent/online-exam-result' => ['farid', 'get', 'parent/get-online-exam-result', $asha + ['online_exam_id' => $id['past_online_exam']]],
            'parent/fees-details' => ['farid', 'get', 'parent/fees-details', $asha],
            'parent/fees-paid' => ['farid', 'get', 'parent/fees-paid-list', $asha],
            'parent/fees-receipt-pdf' => ['farid', 'get', 'parent/fees-paid-receipt-pdf', ['fees_paid_id' => $id['fees_paid']]],
            'parent/fees-transactions' => ['farid', 'get', 'parent/fees-transactions-list', []],
            'parent/fees-report' => ['farid', 'get', 'parent/fees-report', $asha + ['session_year_id' => $year]],
            'parent/leave-report' => ['farid', 'get', 'parent/leave-report', $asha + ['session_year_id' => $year]],
            'parent/assignment-report' => ['farid', 'get', 'parent/assignment-report', $asha + ['session_year_id' => $year]],
            'parent/offline-exam-report' => ['farid', 'get', 'parent/offline-exam-report', $asha + ['session_year_id' => $year]],
            'parent/online-exam-report' => ['farid', 'get', 'parent/online-exam-report', $asha + ['session_year_id' => $year]],
            'parent/leaves' => ['farid', 'post', 'parent/get-leave-list', $asha],
            'parent/notifications' => ['farid', 'get', 'parent/get-notification', []],
            'parent/chat-users' => ['farid', 'get', 'parent/get-user-list', $page],
            'parent/chat-messages' => ['farid', 'post', 'parent/get-user-message', ['user_id' => $id['tara_user']] + $page],
            'parent/read-all-messages' => ['farid', 'post', 'parent/read-all-message', ['user_id' => $id['tara_user']]],
            'parent/academic-calendar-pdf' => ['farid', 'get', 'parent/academic-calendar-pdf', $asha],
            'parent/id-card-pdf' => ['farid', 'get', 'parent/id-card-pdf', $asha],
            'parent/guardian-profile' => ['gina', 'get', 'parent/get-profile-data', []],
            'parent/other-child' => ['gina', 'get', 'parent/subjects', $asha],

            // Teacher app
            'teacher/dashboard' => ['tara', 'get', 'teacher/dashboard', []],
            'teacher/profile' => ['tara', 'get', 'teacher/get-profile-details', []],
            'teacher/classes' => ['tara', 'get', 'teacher/classes', []],
            'teacher/subjects' => ['tara', 'get', 'teacher/subjects', ['class_section_id' => $id['section_a']]],
            'teacher/timetable' => ['tara', 'get', 'teacher/teacher_timetable', []],
            'teacher/assignments' => ['tara', 'get', 'teacher/get-assignment', ['class_section_id' => $id['section_a']]],
            'teacher/assignment-submissions' => ['tara', 'get', 'teacher/get-assignment-submission', ['assignment_id' => $id['assignment']]],
            'teacher/lessons' => ['tara', 'get', 'teacher/get-lesson', ['class_section_id' => $id['section_a'], 'subject_id' => $id['maths']]],
            'teacher/topics' => ['tara', 'get', 'teacher/get-topic', ['lesson_id' => $id['lesson']]],
            'teacher/announcements' => ['tara', 'get', 'teacher/get-announcement', ['class_section_id' => $id['section_a'], 'subject_id' => $id['maths']]],
            'teacher/attendance' => ['tara', 'get', 'teacher/get-attendance', ['class_section_id' => $id['section_a'], 'date' => now()->subDay()->toDateString()]],
            'teacher/attendance-report' => ['tara', 'get', 'teacher/attendance-report', ['month' => $month, 'year' => (int) now()->format('Y')]],
            'teacher/students' => ['tara', 'get', 'teacher/student-list', ['class_section_id' => $id['section_a']]],
            'teacher/student-details' => ['tara', 'get', 'teacher/student-details', ['student_id' => $id['asha']]],
            'teacher/exams' => ['tara', 'get', 'teacher/get-exam-list', ['class_section_id' => $id['section_a']]],
            'teacher/exams-with-timetable' => ['tara', 'get', 'teacher/get-exam-list', ['class_section_id' => $id['section_a'], 'get_timetable' => 1]],
            'teacher/exam-details' => ['tara', 'get', 'teacher/get-exam-details', ['exam_id' => $id['exam'], 'class_id' => $id['class_a']]],
            'teacher/student-result' => ['tara', 'get', 'teacher/get-student-result', ['student_id' => $id['asha']]],
            'teacher/student-marks' => ['tara', 'get', 'teacher/get-student-marks', ['student_id' => $id['asha']]],
            'teacher/student-result-pdf' => ['tara', 'get', 'teacher/get-student-result-pdf', ['student_id' => $id['asha']]],
            'teacher/leaves' => ['tara', 'post', 'teacher/get-leave-list', []],
            'teacher/student-leaves' => ['tara', 'post', 'teacher/get-student-leave-list', ['class_section_id' => $id['section_a'], 'session_year_id' => $year]],
            'teacher/notifications' => ['tara', 'get', 'teacher/get-notification', []],
            'teacher/chat-users' => ['tara', 'get', 'teacher/get-user-list', $page],
            'teacher/chat-users-parents' => ['tara', 'get', 'teacher/get-user-list', ['isParent' => 1] + $page],
            'teacher/chat-messages' => ['tara', 'post', 'teacher/get-user-message', ['user_id' => $id['farid_user']] + $page],
            'teacher/read-all-messages' => ['tara', 'post', 'teacher/read-all-message', ['user_id' => $id['farid_user']]],
            'teacher/academic-calendar-pdf' => ['tara', 'get', 'teacher/academic-calendar-pdf', []],
            'teacher/id-card-pdf' => ['tara', 'get', 'teacher/id-card-pdf', []],
            'teacher/other-class' => ['theo', 'get', 'teacher/student-details', ['student_id' => $id['asha']]],

            // Changes
            'student/submit-assignment' => ['ben', 'post', 'student/submit-assignment', ['assignment_id' => $id['assignment'], 'text_submission' => 'Done']],
            'student/submit-online-exam' => ['asha', 'post', 'student/submit-online-exam-answers', [
                'online_exam_id' => $id['online_exam'], 'answers_data' => [['question_id' => $id['question'], 'option_id' => [$id['option_1']]]],
            ]],
            'student/apply-leave' => ['asha', 'post', 'student/apply-leave', ['reason' => 'Fever', 'leave_details' => [['date' => now()->addDays(20)->format('d-m-Y'), 'type' => 'Full']]]],
            'student/delete-leave' => ['ben', 'post', 'student/delete-leave', ['leave_id' => $id['pending_student_leave']]],
            'student/store-fees-unknown' => ['asha', 'post', 'student/store-fees', ['transaction_id' => 999999]],
            'student/fail-payment-unknown' => ['asha', 'post', 'student/fail-payment-transaction', ['payment_transaction_id' => 999999]],
            'parent/apply-leave' => ['farid', 'post', 'parent/apply-leave', $asha + ['reason' => 'Wedding', 'leave_details' => [['date' => now()->addDays(21)->format('d-m-Y'), 'type' => 'Full']]]],
            'parent/store-fees-unknown' => ['farid', 'post', 'parent/store-fees', $asha + ['transaction_id' => 999999]],
            'teacher/create-assignment' => ['tara', 'post', 'teacher/create-assignment', [
                'class_section_id' => $id['section_a'], 'subject_id' => $id['science'], 'name' => 'Plant diary',
                'instructions' => 'Draw a leaf', 'due_date' => now()->addDays(5)->format('d-m-Y').' 17:00', 'points' => 5,
            ]],
            'teacher/update-assignment' => ['tara', 'post', 'teacher/update-assignment', [
                'assignment_id' => $id['assignment'], 'class_section_id' => $id['section_a'], 'subject_id' => $id['maths'],
                'name' => 'Fraction worksheet', 'due_date' => now()->addDays(8)->format('Y-m-d').' 17:00:00', 'points' => 10,
            ]],
            'teacher/review-submission' => ['tara', 'post', 'teacher/update-assignment-submission', [
                'assignment_submission_id' => $id['submission'], 'status' => 2, 'points' => 9, 'feedback' => 'Redo question 3',
            ]],
            'student/delete-assignment-submission' => ['asha', 'post', 'student/delete-assignment-submission', ['assignment_submission_id' => $id['submission']]],
            'teacher/create-lesson' => ['tara', 'post', 'teacher/create-lesson', [
                'class_section_id' => $id['section_a'], 'subject_id' => $id['science'], 'name' => 'Plants', 'description' => 'Parts of a plant',
            ]],
            'teacher/update-lesson' => ['tara', 'post', 'teacher/update-lesson', [
                'lesson_id' => $id['lesson'], 'class_section_id' => $id['section_a'], 'subject_id' => $id['maths'], 'name' => 'Fractions', 'description' => 'Halves',
            ]],
            'teacher/create-topic' => ['tara', 'post', 'teacher/create-topic', ['lesson_id' => $id['lesson'], 'name' => 'Comparing fractions', 'description' => 'Bigger or smaller']],
            'teacher/update-topic' => ['tara', 'post', 'teacher/update-topic', ['topic_id' => $id['topic'], 'lesson_id' => $id['lesson'], 'name' => 'Adding fractions', 'description' => 'Same base']],
            'teacher/update-file' => ['tara', 'post', 'teacher/update-file', ['file_id' => $id['lesson_file'], 'name' => 'Worksheet 1']],
            'teacher/send-announcement' => ['tara', 'post', 'teacher/send-announcement', [
                'class_section_id' => $id['section_a'], 'subject_id' => $id['maths'], 'title' => 'Bring a ruler', 'description' => 'Tomorrow',
            ]],
            'teacher/update-announcement' => ['tara', 'post', 'teacher/update-announcement', [
                'announcement_id' => $id['subject_announcement'], 'class_section_id' => $id['section_a'], 'subject_id' => $id['maths'], 'title' => 'Maths test moved',
            ]],
            'teacher/submit-attendance' => ['tara', 'post', 'teacher/submit-attendance', [
                'class_section_id' => $id['section_a'], 'student_id' => [$id['asha'], $id['ben']], 'date' => now()->toDateString(),
                'attendance' => [['student_id' => $id['asha'], 'type' => 1], ['student_id' => $id['ben'], 'type' => 0]],
            ]],
            'teacher/submit-marks-by-subject' => ['tara', 'post', 'teacher/submit-exam-marks/subject', [
                'class_section_id' => $id['section_a'], 'exam_id' => $id['unit_test'], 'subject_id' => $id['science'],
                'marks_data' => [['student_id' => $id['asha'], 'obtained_marks' => 40], ['student_id' => $id['ben'], 'obtained_marks' => 35]],
            ]],
            'teacher/submit-marks-by-student' => ['tara', 'post', 'teacher/submit-exam-marks/student', [
                'class_section_id' => $id['section_a'], 'exam_id' => $id['unit_test'], 'student_id' => $id['ben'],
                'marks_data' => [['subject_id' => $id['science'], 'obtained_marks' => 30]],
            ]],
            'teacher/update-timetable-link' => ['tara', 'post', 'teacher/update-timetable-link', [
                'timetable_id' => $id['timetable_maths'], 'live_class_link' => 'https://meet.example.com/maths', 'link_name' => 'Maths class',
            ]],
            'teacher/decide-student-leave' => ['tara', 'post', 'teacher/student-leave-status-update', ['leave_id' => $id['leave_to_decide'], 'status' => 2, 'reason_of_rejection' => 'Exam day']],
            'teacher/apply-leave' => ['tara', 'post', 'teacher/apply-leave', ['reason' => 'Training', 'leave_details' => [['date' => now()->addDays(22)->format('d-m-Y'), 'type' => 'Full']]]],
            'teacher/delete-leave' => ['tara', 'post', 'teacher/delete-leave', ['leave_id' => $id['teacher_leave']]],
            'teacher/delete-file' => ['tara', 'post', 'teacher/delete-file', ['file_id' => $id['lesson_file']]],
            'teacher/delete-topic' => ['tara', 'post', 'teacher/delete-topic', ['topic_id' => $id['topic']]],
            'teacher/delete-announcement' => ['tara', 'post', 'teacher/delete-announcement', ['announcement_id' => $id['subject_announcement']]],
            'teacher/delete-assignment' => ['tara', 'post', 'teacher/delete-assignment', ['assignment_id' => $id['assignment']]],
            'teacher/delete-lesson' => ['tara', 'post', 'teacher/delete-lesson', ['lesson_id' => $id['lesson']]],

            // Sign-in, passwords and sign-out last: they replace or revoke tokens.
            'student/login' => [null, 'post', 'student/login', ['gr_number' => 'GR-ASHA', 'password' => 'secret123']],
            'student/login-wrong-password' => [null, 'post', 'student/login', ['gr_number' => 'GR-ASHA', 'password' => 'wrong']],
            'parent/login' => [null, 'post', 'parent/login', ['email' => 'farid@example.com', 'password' => 'secret123']],
            'teacher/login' => [null, 'post', 'teacher/login', ['email' => 'tara@example.com', 'password' => 'secret123']],
            'student/forgot-password' => [null, 'post', 'student/forgot-password', ['gr_no' => 'GR-ASHA', 'dob' => '2015-06-01']],
            'common/forgot-password' => [null, 'post', 'forgot-password', ['email' => 'tara@example.com']],
            'common/change-password' => ['cara', 'post', 'change-password', ['current_password' => 'secret123', 'new_password' => 'newsecret123', 'new_confirm_password' => 'newsecret123']],
            'common/logout' => ['theo', 'post', 'logout', []],
        ];

        foreach (array_keys($calls) as $name) {
            $this->assertMatchesRegularExpression('#^[a-z]+/[a-z0-9-]+$#', $name, 'Snapshot names are <app>/<call>');
        }

        return $calls;
    }
}
