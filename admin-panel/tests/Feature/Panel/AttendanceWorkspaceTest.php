<?php

declare(strict_types=1);

namespace Tests\Feature\Panel;

use App\Exports\StudentsExport;
use App\Models\Teacher;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class AttendanceWorkspaceTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->superAdmin()->givePermissionTo(['attendance-list', 'attendance-report']);
    }

    public function test_permission_authorised_office_and_teacher_class_options_preserve_their_source_scope(): void
    {
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $teacher->givePermissionTo(['attendance-list', 'attendance-report']);
        foreach (['/attendance', '/view-attendance', '/add-bulk-attendance'] as $url) {
            $response = $this->actingAs($teacher)->get($url)->assertOk();
            $response->assertViewHas('class_sections', fn ($classes) => $classes->pluck('id')->all() === [$this->schoolA->ids['section_a']]);
            $this->actingAs($this->superAdmin())->get($url)->assertOk()->assertViewHas('class_sections', fn ($classes) => $classes->contains('id', $this->schoolA->ids['section_c']) && ! $classes->contains('id', $this->schoolB->ids['section_a']));
        }
        Teacher::where('user_id', $teacher->id)->delete();
        foreach (['/attendance', '/view-attendance', '/add-bulk-attendance', '/attendance-report'] as $url) {
            $this->actingAs($teacher->fresh())->get($url)->assertOk()->assertViewHas('class_sections', fn ($classes) => $classes->isEmpty());
        }
    }

    public function test_native_date_range_download_preserves_actual_school_scoped_roster_and_legacy_date_contract(): void
    {
        Excel::fake();
        $this->actingAs($this->superAdmin());
        $payload = ['class_section_id' => $this->schoolA->ids['section_a'], 'from_date' => '2026-02-10', 'to_date' => '2026-02-11'];
        $this->post('/student/export', $payload)->assertOk();
        Excel::assertDownloaded('student_list.csv', function (StudentsExport $export) {
            $rows = $export->collection();

            return $rows->count() === 4 && $rows->pluck('student_id')->unique()->sort()->values()->all() === collect([$this->schoolA->ids['asha'], $this->schoolA->ids['ben']])->sort()->values()->all()
                && $rows->pluck('date')->unique()->values()->all() === ['10-02-2026', '11-02-2026'];
        });
        $this->post('/student/export', ['class_section_id' => $this->schoolA->ids['section_a'], 'date' => '2026-02-10 - 2026-02-11'])->assertOk();
        $this->post('/student/export', array_replace($payload, ['class_section_id' => $this->schoolB->ids['section_a']]))->assertOk();
        Excel::assertDownloaded('student_list.csv', fn (StudentsExport $export) => $export->collection()->isEmpty());
    }

    public function test_native_template_range_validation_and_wrong_role_do_not_export_records(): void
    {
        Excel::shouldReceive('download')->never();
        $this->actingAs($this->superAdmin());
        $this->postJson('/student/export', ['class_section_id' => $this->schoolA->ids['section_a'], 'from_date' => '2026-02-11', 'to_date' => '2026-02-10'])->assertJsonPath('error', true);
        $this->post('/student/export', ['class_section_id' => $this->schoolA->ids['section_a'], 'from_date' => '2026-01-01', 'to_date' => '2028-01-01'])->assertSessionHasErrors('to_date');
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->actingAs($parent)->postJson('/student/export', ['class_section_id' => $this->schoolA->ids['section_a'], 'from_date' => '2026-02-10', 'to_date' => '2026-02-11'])->assertRedirect(route('login'));
    }
}
