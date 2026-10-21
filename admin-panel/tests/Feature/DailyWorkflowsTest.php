<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\Lesson;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\RunsAppServer;
use Tests\Support\DemoSchool;
use Tests\TestCase;

/**
 * Day-to-day admin panel work (attendance, fees), as found by driving the
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
