<?php

declare(strict_types=1);

namespace Tests\Feature\People;

use App\Models\ClassTeacher;
use App\Models\Platform\Role;
use App\Models\SessionYear;
use App\Models\StudentSessions;
use App\Models\User;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class LearnerReportAccessTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
    }

    private function teacher(): User
    {
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $teacher->givePermissionTo(['student-report', 'student-list']);
        $this->assertFalse($teacher->can('student-create'));

        return $teacher;
    }

    public function test_teacher_direct_and_ajax_reports_are_limited_to_the_choosers_class_allocation(): void
    {
        $this->actingAs($this->teacher());
        foreach (['personal', 'attendance', 'leave', 'exam', 'assignment'] as $tab) {
            $this->get('/student-report/'.$this->schoolA->ids['asha'].'?tab='.$tab)->assertOk();
            $this->get('/student-report/'.$this->schoolA->ids['cara'].'?tab='.$tab)->assertNotFound();
        }
        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->get('/student-report/'.$this->schoolA->ids['cara'].'?tab=attendance&calendar_only=1')->assertNotFound();
    }

    public function test_the_globally_selected_year_requires_its_own_learner_and_teacher_allocation(): void
    {
        $year = SessionYear::create(['name' => 'Prior report year', 'start_date' => now()->subYears(2)->startOfYear()->toDateString(), 'end_date' => now()->subYears(2)->endOfYear()->toDateString(), 'default' => 0]);
        StudentSessions::create(['student_id' => $this->schoolA->ids['asha'], 'class_section_id' => $this->schoolA->ids['section_a'], 'session_year_id' => $year->id]);
        $this->actingAs($this->teacher())->withSession(['session_year' => $year->id]);
        $this->get('/student-report/'.$this->schoolA->ids['asha'].'?tab=attendance')->assertNotFound();
        ClassTeacher::create(['class_teacher_id' => $this->schoolA->ids['tara_teacher'], 'class_section_id' => $this->schoolA->ids['section_a'], 'session_year_id' => $year->id]);
        $this->get('/student-report/'.$this->schoolA->ids['asha'].'?tab=attendance')->assertOk()->assertSee('data-resolved-session-year-id="'.$year->id.'"', false);
        // A class appointment alone does not grant a learner with no placement in that year.
        $this->get('/student-report/'.$this->schoolA->ids['ben'].'?tab=personal')->assertNotFound();
    }

    public function test_foreign_learner_and_foreign_year_are_not_report_authority(): void
    {
        $this->actingAs($this->teacher());
        $this->get('/student-report/'.$this->schoolB->ids['asha'])->assertNotFound();
        $this->get('/student-report/'.$this->schoolA->ids['asha'].'?session_year_id='.$this->schoolB->ids['session_year'])->assertNotFound();
        $this->getJson('/student-report/'.$this->schoolA->ids['asha'].'?session_year_id=invalid')->assertUnprocessable();
        $this->getJson('/student-report/'.$this->schoolA->ids['asha'].'?tab=unknown')->assertUnprocessable();
    }

    public function test_custom_staff_report_permission_does_not_require_a_teacher_appointment(): void
    {
        $role = Role::create(['name' => 'Report office', 'guard_name' => 'web', 'custom_role' => 1]);
        $role->givePermissionTo('student-report');
        $staff = User::findOrFail($this->schoolA->ids['tara_user']);
        $staff->syncRoles([$role]);
        $this->assertFalse($staff->hasRole('Teacher'));
        $this->actingAs($staff)->get('/student-report/'.$this->schoolA->ids['cara'].'?tab=attendance')->assertOk();
        $this->get('/student-report/'.$this->schoolB->ids['cara'].'?tab=attendance')->assertNotFound();
    }

    public function test_report_chooser_validates_and_preserves_the_requested_record_section(): void
    {
        $this->actingAs($this->superAdmin());
        foreach (['attendance', 'leave'] as $tab) {
            $this->get('/students-report?report_tab='.$tab)->assertOk()->assertSee('data-student-report-tab="'.$tab.'"', false);
        }
        $this->getJson('/students-report?report_tab=unknown')->assertUnprocessable();
    }
}
