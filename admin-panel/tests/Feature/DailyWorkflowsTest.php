<?php

namespace Tests\Feature;

use App\Imports\AttendanceImport;
use App\Imports\StudentsImport;
use App\Models\File;
use App\Models\Lesson;
use App\Models\Notification;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Concerns\RunsAppServer;
use Tests\Support\DemoSchool;
use Tests\TestCase;

/**
 * Day-to-day admin panel work (attendance, fees, imports), as found by driving the
 * panel in a browser. Requested in-process (these pages don't exit()), on a
 * DemoSchool built once per class: its ids assume a freshly migrated database.
 */
class DailyWorkflowsTest extends TestCase
{
    use RunsAppServer;

    private static ?DemoSchool $demo = null;

    private DemoSchool $school;

    protected function setUp(): void
    {
        parent::setUp();
        if (self::$demo === null) {
            $this->freshInstall();
            self::$demo = DemoSchool::build();
        }
        $this->school = self::$demo;
    }

    public static function tearDownAfterClass(): void
    {
        self::$demo = null;
        parent::tearDownAfterClass();
    }

    public function test_a_new_attendance_day_starts_with_everyone_present(): void
    {
        $tara = User::findOrFail($this->school->ids['tara_user']);
        $sheet = fn (string $date) => collect($this->actingAs($tara)->getJson('/student-list?'.http_build_query([
            'class_section_id' => $this->school->ids['section_a'], 'date' => $date,
        ]))->assertOk()->json('rows'))->pluck('type')->all();

        // Nothing was checked, so every row had to be clicked or the form refused to save.
        $newDay = $sheet(now()->subDays(10)->format('d-m-Y'));
        $this->assertCount(2, $newDay);
        foreach ($newDay as $type) {
            $this->assertMatchesRegularExpression('/value="1" checked/', $type);
        }

        // A day that already has attendance shows what was saved (Asha was absent two days ago).
        $saved = $sheet(now(schoolSetting('time_zone', 'UTC'))->subDays(2)->format('d-m-Y'));
        $this->assertMatchesRegularExpression('/value="0" checked/', $saved[0].$saved[1]);
    }

    public function test_students_of_a_class_with_only_optional_fees_can_be_paid_for(): void
    {
        // Grade 6 has no fees; give it an optional one only.
        DB::table('fees_classes')->insert([
            'session_year_id' => 1, 'class_id' => $this->school->ids['class_c'], 'fees_type_id' => $this->school->ids['fees_bus'],
            'choiceable' => 1, 'amount' => 200,
        ]);

        // The Fees Paid list only showed classes with compulsory fees, so Cara's bus fee couldn't be recorded.
        $rows = $this->actingAs($this->superAdmin())->getJson('/fees/paid/list?session_year_id=1&limit=50')->assertOk()->json('rows');

        $names = array_map(fn ($row) => $row['student_name'] ?? $row['full_name'] ?? null, $rows);
        $this->assertContains('Cara Demo', $names);
    }

    public function test_marking_a_student_absent_in_the_panel_alerts_their_guardian(): void
    {
        $id = $this->school->ids;
        $date = now()->subDays(9)->format('d-m-Y');
        $before = Notification::max('id') ?? 0;
        $form = [
            'class_section_id' => $id['section_a'], 'date' => $date, 'student_id' => [$id['asha'], $id['ben']],
            'type'.$id['asha'] => 1, 'type'.$id['ben'] => 0,
        ];

        // The check ran after save(), compared the new type with itself and never alerted.
        $tara = User::findOrFail($id['tara_user']);
        $this->actingAs($tara)->postJson('/attendance', $form)->assertJson(['error' => false]);
        $this->postJson('/attendance', $form)->assertJson(['error' => false]);

        $alerts = Notification::where('id', '>', $before)->where('title', 'Attendance Alert')->get();
        $this->assertCount(1, $alerts, 'Ben once, not again when the day is saved again');
        $this->assertSame(2, (int) $alerts[0]->send_to, 'Only his guardian, not every parent');
        $this->assertSame([$id['gina_user']], UserNotification::where('notification_id', $alerts[0]->id)->pluck('user_id')->all());
    }

    public function test_the_downloaded_student_sample_imports_students_who_can_use_the_app(): void
    {
        $csv = $this->actingAs($this->superAdmin())->get('/students/download-sample')->assertOk()->streamedContent();
        $path = tempnam(sys_get_temp_dir(), 'students');
        file_put_contents($path, $csv);

        // The welcome email rendered a template that only prints $body, so every import rolled back.
        Excel::import(new StudentsImport($this->school->ids['section_a']), new UploadedFile($path, 'students.csv', 'text/csv', null, true));
        unlink($path);

        $imported = Students::whereHas('user', fn ($q) => $q->whereIn('last_name', ['Naidoo', 'Mokoena']))->pluck('id');
        $this->assertCount(2, $imported);
        // Editing a student requires height and weight, which the sample left blank.
        $this->assertSame(0, Students::whereIn('id', $imported)->where(fn ($q) => $q->whereNull('height')->orWhereNull('weight'))->count());
        // Imported students had no enrolment for the year, so the app turned them away.
        $this->assertSame(2, StudentSessions::whereIn('student_id', $imported)
            ->where(['session_year_id' => 1, 'class_section_id' => $this->school->ids['section_a'], 'status' => 1])->count());

        $sent = collect(Mail::mailer()->getSymfonyTransport()->messages())->map(fn ($m) => $m->getOriginalMessage());
        $this->assertSame(['ravi.naidoo@example.com', 'priya.naidoo@example.com', 'lerato.mokoena@example.com'],
            $sent->map(fn ($m) => $m->getTo()[0]->getAddress())->all());
        $this->assertStringContainsString('Thabo Mokoena', $sent->last()->getHtmlBody());
    }

    public function test_an_uploaded_attendance_sheet_is_saved_like_the_attendance_form(): void
    {
        $id = $this->school->ids;
        $date = now()->subDays(20)->format('d-m-Y');
        $sheet = function (array $rows) {
            $path = tempnam(sys_get_temp_dir(), 'attendance');
            file_put_contents($path, "student_id,admission_no,roll_number,name,date,type\n".implode("\n", $rows));

            return new UploadedFile($path, 'attendance.csv', 'text/csv', null, true);
        };
        $before = Notification::max('id') ?? 0;

        Excel::import(new AttendanceImport($id['section_a']), $sheet(["{$id['asha']},GR-ASHA,1,Asha,$date,1", "{$id['ben']},GR-BEN,2,Ben,$date,0"]));

        // Uploaded absences never alerted anyone.
        $alerts = Notification::where('id', '>', $before)->where('title', 'Attendance Alert')->get();
        $this->assertCount(1, $alerts);
        $this->assertSame([$id['gina_user']], UserNotification::where('notification_id', $alerts[0]->id)->pluck('user_id')->all());

        // Any mistake was "Oops! Error occurred", and a student from another class was saved under this one.
        foreach ([
            'Row 3: The type must be' => ["{$id['asha']},GR-ASHA,1,Asha,$date,1", "{$id['ben']},GR-BEN,2,Ben,$date,7"],
            'Row 2: '.trans('student_not_in_this_class_section') => ["{$id['cara']},GR-CARA,1,Cara,$date,1"],
        ] as $message => $rows) {
            try {
                Excel::import(new AttendanceImport($id['section_a']), $sheet($rows));
                $this->fail("Accepted a sheet that should fail with: $message");
            } catch (ValidationException $e) {
                $this->assertStringStartsWith($message, $e->validator->errors()->first());
            }
        }
        $this->assertSame(0, DB::table('attendances')->where('student_id', $id['cara'])->where('class_section_id', $id['section_a'])->count());
    }

    public function test_lists_can_be_searched_for_text(): void
    {
        $tara = User::findOrFail($this->school->ids['tara_user']);

        // Any search that wasn't a date crashed the first lists: date() was given strtotime()'s false.
        // The others searched columns their tables don't have.
        foreach ([
            [$this->superAdmin(), '/teacher_list'], [$this->superAdmin(), '/fees-type/1'], [$this->superAdmin(), '/exam-timetable/1'],
            [$tara, '/lesson/1'], [$tara, '/online-exam/1'],
            [$this->superAdmin(), '/events/1'], [$this->superAdmin(), '/notifications/1'], [$this->superAdmin(), '/photos-list'],
            [$this->superAdmin(), '/videos-list'], [$this->superAdmin(), '/leave-master/1'], [$this->superAdmin(), '/sliders/1'],
        ] as [$user, $url]) {
            // These lists read $_GET, which in-process requests don't fill.
            $_GET = ['search' => 'Tara', 'limit' => 10, 'offset' => 0];
            $response = $this->actingAs($user)->getJson($url.'?'.http_build_query($_GET));
            $_GET = [];
            $response->assertOk();
            if ($url === '/teacher_list') {
                $this->assertStringContainsString('Tara', $response->getContent());
            }
        }
    }

    public function test_a_notification_to_chosen_users_is_pushed_to_their_devices(): void
    {
        User::whereKey($this->school->ids['farid_user'])->update(['fcm_id' => 'farid-device', 'device_type' => 'android']);
        Log::spy();

        $this->actingAs($this->superAdmin())->postJson('/notifications', [
            'send_to' => [3], 'user_id' => [$this->school->ids['farid_user']], 'title' => 'Trip forms', 'message' => 'Hand them in by Friday.',
        ])->assertJson(['error' => false]);

        // The push to selected users was commented out; only the list entry was saved. Without
        // FCM credentials here the attempt shows up as a send refused for want of a token.
        Log::shouldHaveReceived('error')->with('Cannot send FCM notification: Access token is null');
    }

    public function test_moving_a_student_to_another_class_moves_this_years_enrolment(): void
    {
        $id = $this->school->ids;

        $this->actingAs($this->superAdmin())->putJson("/students/{$id['asha_user']}", [
            'edit_id' => $id['asha_user'], 'first_name' => 'Asha', 'last_name' => 'Demo', 'dob' => '01-06-2015',
            'class_section_id' => $id['section_c'], 'category_id' => $id['category'], 'admission_no' => 'GR-ASHA', 'roll_number' => 1,
            'admission_date' => '01-01-2026', 'current_address' => '1 Road', 'permanent_address' => '1 Road', 'height' => 140, 'weight' => 35,
            'edit_parent_guardian_type' => 'parent', 'father_email' => $id['farid'], 'mother_email' => $id['mona'],
        ])->assertJson(['error' => false]);

        // Only the student row moved; the apps, attendance, marks and fees kept the old class.
        $enrolment = StudentSessions::where(['student_id' => $id['asha'], 'session_year_id' => 1]);
        $this->assertSame($id['section_c'], $enrolment->value('class_section_id'));

        // Put her back for the other tests on this school.
        Students::whereKey($id['asha'])->update(['class_section_id' => $id['section_a']]);
        $enrolment->update(['class_section_id' => $id['section_a']]);
    }

    public function test_editing_a_parent_keeps_their_gender_stored_as_elsewhere(): void
    {
        $id = $this->school->ids;

        // The edit form's radios are lower case; parents are stored as Male/Female everywhere else.
        $this->actingAs($this->superAdmin())->putJson("/parents/{$id['gina']}", [
            'edit_id' => $id['gina'], 'first_name' => 'Gina', 'last_name' => 'Parent', 'gender' => 'female',
            'email' => 'gina@example.com', 'mobile' => '9876543210', 'dob' => '01-02-1980', 'occupation' => 'Nurse',
        ])->assertJson(['error' => false]);

        $this->assertSame('Female', DB::table('parents')->where('id', $id['gina'])->value('gender'));
        $this->assertSame('Female', User::find($id['gina_user'])->gender);
    }

    public function test_a_list_search_stays_within_the_list_filters(): void
    {
        $lastYear = DB::table('session_years')->insertGetId([
            'name' => 'Old year', 'start_date' => now()->subYears(2), 'end_date' => now()->subYear(), 'fee_due_date' => now()->subYears(2),
            'fee_due_charges' => 0, 'include_fee_installments' => 0, 'default' => 0,
        ]);
        DB::table('holidays')->insert(['session_year_id' => $lastYear, 'date' => now()->subYear()->subMonth(), 'title' => 'Old founders day', 'description' => '-']);

        $_GET = ['search' => 'founders', 'limit' => 10, 'offset' => 0];
        $response = $this->actingAs($this->superAdmin())->getJson('/holiday/1?'.http_build_query($_GET));
        $_GET = [];

        // The search was OR-ed onto the session year filter, so other years' holidays matched.
        $this->assertSame(['Founders day'], collect($response->assertOk()->json('rows'))->pluck('title')->all());
    }

    public function test_searching_exam_timetables_keeps_the_exam_filter(): void
    {
        $_GET = ['search' => 'Science', 'exam_id' => $this->school->ids['unit_test'], 'limit' => 10, 'offset' => 0];
        $response = $this->actingAs($this->superAdmin())->getJson('/exam-timetable/1?'.http_build_query($_GET));
        $_GET = [];

        // The search was OR-ed with every other condition, so it listed other exams (and years).
        $this->assertSame(['Unit test'], collect($response->assertOk()->json('rows'))->pluck('exam_name')->unique()->values()->all());
    }

    public function test_deleting_a_lesson_removes_its_and_its_topics_stored_files(): void
    {
        Storage::fake('public');
        $lesson = Lesson::forceCreate(['name' => 'Decimals', 'description' => 'Tenths', 'class_section_id' => $this->school->ids['section_a'], 'subject_id' => $this->school->ids['maths']]);
        $topic = $lesson->topic()->forceCreate(['name' => 'Tenths', 'description' => 'One place']);
        foreach (['lessons/video.mp4', 'lessons/video-thumb.png', 'lessons/sheet.pdf'] as $path) {
            Storage::disk('public')->put($path, 'x');
        }
        File::forceCreate(['modal_type' => Lesson::class, 'modal_id' => $lesson->id, 'file_name' => 'Video', 'type' => 3, 'file_url' => 'lessons/video.mp4', 'file_thumbnail' => 'lessons/video-thumb.png']);
        File::forceCreate(['modal_type' => get_class($topic), 'modal_id' => $topic->id, 'file_name' => 'Sheet', 'type' => 1, 'file_url' => 'lessons/sheet.pdf']);

        // The hooks compared the URL accessor with the disk, so nothing was ever removed,
        // and a lesson's topics were deleted without their files.
        $lesson->delete();

        foreach (['lessons/video.mp4', 'lessons/video-thumb.png', 'lessons/sheet.pdf'] as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }
}
