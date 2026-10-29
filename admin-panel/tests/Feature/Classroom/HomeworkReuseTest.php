<?php

namespace Tests\Feature\Classroom;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\File;
use App\Models\Notification;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\RunsAppServer;
use Tests\Support\DemoSchool;
use Tests\TestCase;

/**
 * Reusing homework (setting an assignment again for other classes) and the
 * marks teachers give when accepting it. DemoSchool: Tara teaches Maths in
 * Grade 5 A; Theo teaches Grade 6 C; the Fraction worksheet (total 10) is set
 * for Grade 5 A and Asha has submitted it.
 */
class HomeworkReuseTest extends TestCase
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
        Storage::disk('public')->put('assignment/questions.pdf', 'questions');
    }

    public static function tearDownAfterClass(): void
    {
        self::$demo = null;
        parent::tearDownAfterClass();
    }

    private function user(string $who): User
    {
        return User::findOrFail($this->school->ids[$who.'_user']);
    }

    private function dueDate(): string
    {
        return now()->addDays(5)->format('Y-m-d\TH:i');
    }

    private function sectionKey(string $section): string
    {
        return $this->school->ids['section_'.$section].':';
    }

    /** Let Tara teach Maths in Grade 6 C as well (Theo keeps his allocation). */
    private function taraAlsoTeachesGradeSix(): void
    {
        if (SchoolTable::query('subject_teachers')->where('teacher_id', $this->school->ids['tara_teacher'])
            ->where('class_section_id', $this->school->ids['section_c'])->exists()) {
            return;
        }
        SchoolTable::query('subject_teachers')->insert([
            'class_section_id' => $this->school->ids['section_c'], 'subject_id' => $this->school->ids['maths'],
            'teacher_id' => $this->school->ids['tara_teacher'], 'session_year_id' => $this->school->ids['session_year'],
        ]);
    }

    public function test_a_teacher_is_offered_only_the_classes_they_teach_the_subject_in(): void
    {
        $id = $this->school->ids['assignment'];
        $offered = fn () => collect($this->actingAs($this->user('tara'), 'web')->getJson("/assignment/$id/copy-targets")
            ->assertOk()->json('data'))->pluck('key')->all();

        $this->assertSame([$this->sectionKey('a')], $offered());

        $this->taraAlsoTeachesGradeSix();
        $this->assertEqualsCanonicalizing([$this->sectionKey('a'), $this->sectionKey('c')], $offered());

        // Another teacher cannot even see Tara's assignment.
        $this->actingAs($this->user('theo'), 'web')->getJson("/assignment/$id/copy-targets")->assertNotFound();
    }

    public function test_the_panel_sets_homework_again_for_other_classes_with_its_own_files_and_due_date(): void
    {
        $this->taraAlsoTeachesGradeSix();
        $source = Assignment::query()->findOrFail($this->school->ids['assignment']);
        $before = Assignment::query()->count();

        $response = $this->actingAs($this->user('tara'), 'web')->postJson("/assignment/{$source->id}/copy", [
            'targets' => [$this->sectionKey('c')],
            'due_date' => $this->dueDate(),
        ])->assertOk();
        $this->assertFalse($response->json('error'), (string) $response->json('message'));
        $this->assertSame($before + 1, Assignment::query()->count());

        $copy = Assignment::query()->latest('id')->firstOrFail();
        $this->assertSame((int) $this->school->ids['section_c'], (int) $copy->class_section_id);
        $this->assertSame($source->name, $copy->name);
        $this->assertSame($source->instructions, $copy->instructions);
        $this->assertSame(10, (int) $copy->points);
        $this->assertSame(now()->addDays(5)->format('Y-m-d H:i'), substr((string) $copy->due_date, 0, 16));
        $this->assertSame(0, AssignmentSubmission::query()->where('assignment_id', $copy->id)->count());

        // The copy's file is stored separately: deleting the copy leaves the original's file in place.
        $original = File::query()->where('modal_type', Assignment::class)->where('modal_id', $source->id)->firstOrFail();
        $copied = File::query()->where('modal_type', Assignment::class)->where('modal_id', $copy->id)->firstOrFail();
        $this->assertNotSame($original->getRawOriginal('file_url'), $copied->getRawOriginal('file_url'));
        Storage::disk('public')->assertExists($copied->getRawOriginal('file_url'));
        $copy->delete();
        Storage::disk('public')->assertExists($original->getRawOriginal('file_url'));
    }

    public function test_homework_from_an_earlier_year_is_reused_while_that_year_is_viewed(): void
    {
        $this->taraAlsoTeachesGradeSix();
        $current = (int) $this->school->ids['session_year'];
        $earlier = SchoolTable::query('session_years')->insertGetId([
            'name' => 'Earlier year', 'start_date' => now()->subYears(2)->toDateString(), 'end_date' => now()->subYear()->toDateString(),
            'default' => 0, 'free_app_use_date' => now()->subYears(2)->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $old = Assignment::query()->findOrFail($this->school->ids['assignment'])->replicate();
        $old->session_year_id = $earlier;
        $old->save();

        // The header shows the earlier year, so the panel is read-only for that year's data.
        $response = $this->actingAs($this->user('tara'), 'web')->withSession(['session_year' => $earlier])
            ->postJson("/assignment/{$old->id}/copy", [
                'targets' => [$this->sectionKey('c')],
                'due_date' => $this->dueDate(),
            ])->assertOk();
        $this->assertFalse($response->json('error'), (string) $response->json('message'));

        $copy = Assignment::query()->latest('id')->firstOrFail();
        $this->assertSame($current, (int) $copy->session_year_id);
        $this->assertSame((int) $this->school->ids['section_c'], (int) $copy->class_section_id);

        // Other writes stay refused while an earlier year is viewed.
        $this->actingAs($this->user('tara'), 'web')->withSession(['session_year' => $earlier])
            ->putJson("/assignment-submission/{$this->school->ids['submission']}", ['status' => 2, 'feedback' => 'x'])
            ->assertStatus(423);
    }

    public function test_homework_cannot_be_set_for_a_class_the_teacher_does_not_teach(): void
    {
        SchoolTable::query('subject_teachers')->where('teacher_id', $this->school->ids['tara_teacher'])
            ->where('class_section_id', $this->school->ids['section_c'])->delete();
        $before = Assignment::query()->count();

        $response = $this->actingAs($this->user('tara'), 'web')->postJson("/assignment/{$this->school->ids['assignment']}/copy", [
            'targets' => [$this->sectionKey('c')],
            'due_date' => $this->dueDate(),
        ])->assertOk();

        $this->assertTrue($response->json('error'));
        $this->assertSame($before, Assignment::query()->count());
    }

    public function test_a_due_date_in_the_past_is_refused(): void
    {
        $response = $this->actingAs($this->user('tara'), 'web')->postJson("/assignment/{$this->school->ids['assignment']}/copy", [
            'targets' => [$this->sectionKey('a')],
            'due_date' => now()->subDay()->format('Y-m-d\TH:i'),
        ])->assertOk();

        $this->assertTrue($response->json('error'));
    }

    public function test_a_school_admin_without_homework_rights_cannot_reuse_homework(): void
    {
        // In this school homework is set by teachers: the Super Admin role has no assignment-create.
        $this->actingAs($this->superAdmin(), 'web')->getJson("/assignment/{$this->school->ids['assignment']}/copy-targets")->assertForbidden();
        $response = $this->actingAs($this->superAdmin(), 'web')->postJson("/assignment/{$this->school->ids['assignment']}/copy", [
            'targets' => [$this->sectionKey('c')],
            'due_date' => $this->dueDate(),
        ])->assertOk();
        $this->assertTrue($response->json('error'));
    }

    public function test_in_a_class_with_terms_only_the_terms_the_teacher_is_allocated_to_are_offered(): void
    {
        $classC = $this->school->ids['class_c'];
        $year = $this->school->ids['session_year'];
        $term = $this->school->ids['term'];
        SchoolTable::query('class_session_configs')->where('class_id', $classC)->where('session_year_id', $year)->update(['include_terms' => 1]);
        SchoolTable::query('subject_teachers')->where('teacher_id', $this->school->ids['tara_teacher'])
            ->where('class_section_id', $this->school->ids['section_c'])->delete();
        $offered = fn () => collect($this->actingAs($this->user('tara'), 'web')->getJson("/assignment/{$this->school->ids['assignment']}/copy-targets")
            ->assertOk()->json('data'))->pluck('key')->all();

        try {
            // A row without a term does not let a teacher see homework in a term class, so nothing is offered there.
            $this->taraAlsoTeachesGradeSix();
            $this->assertNotContains($this->school->ids['section_c'].':', $offered());
            $this->assertNotContains($this->school->ids['section_c'].':'.$term, $offered());

            SchoolTable::query('subject_teachers')->where('teacher_id', $this->school->ids['tara_teacher'])
                ->where('class_section_id', $this->school->ids['section_c'])->update(['term_id' => $term]);
            $this->assertContains($this->school->ids['section_c'].':'.$term, $offered());

            $this->actingAs($this->user('tara'), 'web')->postJson("/assignment/{$this->school->ids['assignment']}/copy", [
                'targets' => [$this->school->ids['section_c'].':'.$term],
                'due_date' => $this->dueDate(),
            ])->assertOk()->assertJson(['error' => false]);
            $copy = Assignment::query()->latest('id')->firstOrFail();
            $this->assertSame((int) $term, (int) $copy->term_id);

            // The copy stays in the teacher's own list.
            $this->assertTrue(Assignment::query()->whereKey($copy->id)->exists());
            $this->actingAs($this->user('tara'), 'web');
            $this->assertTrue(Assignment::assignmentTeachers()->whereKey($copy->id)->exists());
        } finally {
            SchoolTable::query('class_session_configs')->where('class_id', $classC)->where('session_year_id', $year)->update(['include_terms' => 0]);
            SchoolTable::query('subject_teachers')->where('teacher_id', $this->school->ids['tara_teacher'])
                ->where('class_section_id', $this->school->ids['section_c'])->delete();
        }
    }

    public function test_another_schools_class_is_never_offered_or_accepted(): void
    {
        $school = School::forceCreate(['name' => 'Elsewhere School', 'slug' => 'homework-reuse-elsewhere', 'timezone' => 'Africa/Johannesburg']);
        app(SchoolRoles::class)->seed((int) $school->id);
        $teacherId = $this->school->ids['tara_teacher'];
        $other = app(CurrentSchool::class)->run((int) $school->id, function () use ($teacherId) {
            $demo = DemoSchool::build('Elsewhere');
            // A stray allocation of Tara's teacher id in the other school must not count.
            SchoolTable::query('subject_teachers')->insert([
                'class_section_id' => $demo->ids['section_a'], 'subject_id' => $demo->ids['maths'],
                'teacher_id' => $teacherId, 'session_year_id' => $demo->ids['session_year'],
            ]);

            return $demo;
        });
        $foreignKey = $other->ids['section_a'].':';
        $tara = $this->user('tara');

        $offered = collect($this->actingAs($tara, 'web')->getJson("/assignment/{$this->school->ids['assignment']}/copy-targets")
            ->assertOk()->json('data'))->pluck('key')->all();
        $this->assertNotContains($foreignKey, $offered);

        $before = Assignment::query()->count();
        $response = $this->actingAs($tara, 'web')->postJson("/assignment/{$this->school->ids['assignment']}/copy", [
            'targets' => [$foreignKey],
            'due_date' => $this->dueDate(),
        ])->assertOk();
        $this->assertTrue($response->json('error'));
        $this->assertSame($before, Assignment::query()->count());
    }

    public function test_a_mark_must_be_a_whole_number_within_the_total(): void
    {
        $submission = $this->school->ids['submission'];
        $mark = fn ($points) => $this->withToken($this->school->tokens['tara'])->postJson('/api/teacher/update-assignment-submission', [
            'assignment_submission_id' => $submission, 'status' => 1, 'points' => $points, 'feedback' => 'Good work',
        ]);

        $this->assertTrue($mark(11)->json('error'));
        $this->assertTrue($mark(-1)->json('error'));
        $this->assertTrue($mark(7.5)->json('error'));
        $this->assertNull(AssignmentSubmission::query()->findOrFail($submission)->points);

        $this->assertFalse($mark(8)->json('error'));
        $this->assertSame(8, (int) AssignmentSubmission::query()->findOrFail($submission)->points);

        // The panel applies the same rule.
        $panel = $this->actingAs($this->user('tara'), 'web')->putJson("/assignment-submission/$submission", [
            'status' => 1, 'points' => 12, 'feedback' => 'Too many',
        ])->assertOk();
        $this->assertTrue($panel->json('error'));
        $this->assertSame(8, (int) AssignmentSubmission::query()->findOrFail($submission)->points);
    }

    public function test_a_teacher_cannot_mark_another_teachers_homework_in_the_panel(): void
    {
        $submission = $this->school->ids['submission'];

        $this->actingAs($this->user('theo'), 'web')->putJson("/assignment-submission/$submission", [
            'status' => 1, 'points' => 1, 'feedback' => 'Not mine to mark',
        ]);

        $this->assertNotSame('Not mine to mark', AssignmentSubmission::query()->findOrFail($submission)->feedback);
    }

    public function test_reused_video_owns_its_thumbnail_and_deleting_it_preserves_the_original(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('assignment/synthetic-video.mp4', 'synthetic video');
        Storage::disk('public')->put('assignment/synthetic-thumb.jpg', 'synthetic thumbnail');
        $source = Assignment::findOrFail($this->school->ids['assignment'])->replicate();
        $source->save();
        File::create(['modal_type' => Assignment::class, 'modal_id' => $source->id, 'file_name' => 'Synthetic video', 'type' => 3,
            'file_url' => 'assignment/synthetic-video.mp4', 'file_thumbnail' => 'assignment/synthetic-thumb.jpg']);
        $this->actingAs($this->user('tara'), 'web')->postJson("/assignment/{$source->id}/copy", [
            'targets' => [$this->sectionKey('a')], 'due_date' => $this->dueDate(),
        ])->assertOk()->assertJsonPath('error', false);
        $copy = Assignment::latest('id')->firstOrFail();
        $attachment = File::where('modal_type', Assignment::class)->where('modal_id', $copy->id)->sole();
        $this->assertNotSame('assignment/synthetic-thumb.jpg', $attachment->getRawOriginal('file_thumbnail'));
        Storage::disk('public')->assertExists($attachment->getRawOriginal('file_url'));
        Storage::disk('public')->assertExists($attachment->getRawOriginal('file_thumbnail'));
        $copy->delete();
        Storage::disk('public')->assertExists('assignment/synthetic-video.mp4');
        Storage::disk('public')->assertExists('assignment/synthetic-thumb.jpg');
    }

    public function test_missing_thumbnail_rolls_back_homework_and_cleans_partially_copied_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('assignment/synthetic-video.mp4', 'synthetic video');
        $source = Assignment::findOrFail($this->school->ids['assignment'])->replicate();
        $source->save();
        File::create(['modal_type' => Assignment::class, 'modal_id' => $source->id, 'file_name' => 'Synthetic video', 'type' => 3,
            'file_url' => 'assignment/synthetic-video.mp4', 'file_thumbnail' => 'assignment/missing-thumb.jpg']);
        $before = Assignment::count();
        $this->actingAs($this->user('tara'), 'web')->postJson("/assignment/{$source->id}/copy", [
            'targets' => [$this->sectionKey('a')], 'due_date' => $this->dueDate(),
        ])->assertOk()->assertJsonPath('error', true);
        $this->assertSame($before, Assignment::count());
        $this->assertSame(['assignment/synthetic-video.mp4'], Storage::disk('public')->allFiles('assignment'));
    }

    public function test_notification_intent_failure_cannot_report_a_committed_copy_as_failed(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('assignment/synthetic-video.mp4', 'synthetic video');
        $source = Assignment::findOrFail($this->school->ids['assignment'])->replicate();
        $source->save();
        File::create(['modal_type' => Assignment::class, 'modal_id' => $source->id, 'file_name' => 'Synthetic video', 'type' => 3,
            'file_url' => 'assignment/synthetic-video.mp4']);
        $before = Assignment::count();
        Event::listen('eloquent.creating: '.Notification::class,
            static fn () => throw new \RuntimeException('Synthetic notification-intent failure'));
        $this->actingAs($this->user('tara'), 'web')->postJson("/assignment/{$source->id}/copy", [
            'targets' => [$this->sectionKey('a')], 'due_date' => $this->dueDate(),
        ])->assertOk()->assertJsonPath('error', true);
        $this->assertSame($before, Assignment::count());
        $this->assertSame(['assignment/synthetic-video.mp4'], Storage::disk('public')->allFiles('assignment'));
    }
}
