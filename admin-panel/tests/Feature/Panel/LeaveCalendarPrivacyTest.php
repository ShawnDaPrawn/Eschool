<?php

declare(strict_types=1);

namespace Tests\Feature\Panel;

use App\Models\Leave;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class LeaveCalendarPrivacyTest extends TestCase
{
    use UsesTwoSchools;

    private const PRIVATE_REASON = 'Private medical reason for calendar qualification';

    private const PRIVATE_REJECTION = 'Private supporting decision note';

    private const PRIVATE_FILE = 'https://documents.example.invalid/private-medical-evidence.pdf';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
    }

    private function actor(string $name, array $permissions): User
    {
        $role = Role::create(['school_id' => 1, 'name' => $name, 'guard_name' => 'web']);
        $role->syncPermissions($permissions);
        $actor = User::forceCreate(['first_name' => $name, 'last_name' => 'Reader',
            'email' => md5($name).'@calendar.example.invalid', 'password' => Hash::make('Test-only-calendar-password'), 'status' => 1]);
        $actor->assignRole($role);
        SchoolMembership::updateOrCreate(['school_id' => 1, 'user_id' => $actor->id, 'role' => $name],
            ['status' => 'active', 'starts_on' => now()->subYear()->toDateString(), 'ends_on' => null]);

        return $actor;
    }

    private function privateStaffRequest(int $status = 1): Leave
    {
        $record = Leave::findOrFail($this->schoolA->ids['teacher_leave']);
        $record->forceFill(['status' => $status, 'reason' => self::PRIVATE_REASON, 'reason_of_rejection' => self::PRIVATE_REJECTION])->save();
        $record->file()->create(['file_name' => 'Private evidence', 'file_url' => self::PRIVATE_FILE, 'type' => 4]);

        return $record;
    }

    public function test_history_reader_sees_only_approved_dates_and_portions_without_loading_private_fields(): void
    {
        $reader = $this->actor('Calendar history reader', ['staff-leave-list']);
        $record = $this->privateStaffRequest();
        $this->assertFalse($reader->can('leave-approve'));
        $this->actingAs($reader)->get(route('leave-calendar.index', ['week' => $record->from_date, 'person_type' => 'staff']))
            ->assertOk()->assertSee(route('leave-calendar.show', $record->id), false)
            ->assertDontSee(self::PRIVATE_REASON)->assertDontSee(self::PRIVATE_FILE, false);
        $this->get(route('leave-calendar.show', $record->id))->assertOk()
            ->assertSee($record->user->full_name)->assertSee($record->from_date)->assertSee('Full')
            ->assertDontSee(self::PRIVATE_REASON)->assertDontSee(self::PRIVATE_REJECTION)->assertDontSee(self::PRIVATE_FILE, false)
            ->assertViewHas('privateDetails', false)
            ->assertViewHas('record', fn (Leave $row) => ! array_key_exists('reason', $row->getAttributes())
                && ! array_key_exists('reason_of_rejection', $row->getAttributes()) && ! $row->relationLoaded('file'));
    }

    public function test_owner_and_staff_reviewer_retain_private_request_details(): void
    {
        $record = $this->privateStaffRequest(0);
        $owner = User::findOrFail($record->user_id);
        $owner->givePermissionTo('leave-create');
        $reviewer = $this->actor('Calendar staff reviewer', ['leave-approve']);
        foreach ([$owner, $reviewer] as $actor) {
            $this->actingAs($actor)->get(route('leave-calendar.show', $record->id))->assertOk()
                ->assertViewHas('privateDetails', true)->assertSee(self::PRIVATE_REASON)
                ->assertSee(self::PRIVATE_REJECTION)->assertSee(self::PRIVATE_FILE, false);
        }
    }

    public function test_history_reader_cannot_open_pending_rejected_foreign_or_other_year_requests(): void
    {
        $reader = $this->actor('Restricted calendar history', ['staff-leave-list']);
        $record = $this->privateStaffRequest(0);
        $this->actingAs($reader)->get(route('leave-calendar.show', $record->id))->assertNotFound();
        $record->forceFill(['status' => 2])->save();
        $this->get(route('leave-calendar.show', $record->id))->assertNotFound();
        $record->forceFill(['status' => 1])->save();
        $old = SessionYear::create(['name' => 'Other calendar year', 'start_date' => '2024-01-01', 'end_date' => '2024-12-31']);
        $record->forceFill(['session_year_id' => $old->id])->save();
        $this->get(route('leave-calendar.show', $record->id))->assertNotFound();
        $foreign = app(CurrentSchool::class)->run(2, fn () => Leave::findOrFail($this->schoolB->ids['teacher_leave']));
        $this->get(route('leave-calendar.show', $foreign->id))->assertNotFound();
    }

    public function test_learner_approval_does_not_grant_private_staff_access_and_keeps_teacher_allocations(): void
    {
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $teacher->givePermissionTo(['student-leave-approve', 'staff-leave-list']);
        $this->assertFalse($teacher->can('leave-approve'));
        $staff = $this->privateStaffRequest();
        $staff->forceFill(['user_id' => $this->schoolA->ids['theo_user']])->save();
        $this->actingAs($teacher)->get(route('leave-calendar.show', $staff->id))->assertOk()
            ->assertViewHas('privateDetails', false)->assertDontSee(self::PRIVATE_REASON)->assertDontSee(self::PRIVATE_FILE, false);
        $learner = Leave::findOrFail($this->schoolA->ids['student_leave']);
        $learner->forceFill(['reason' => 'Allocated learner private reason'])->save();
        $this->get(route('leave-calendar.show', $learner->id))->assertOk()
            ->assertViewHas('privateDetails', true)->assertSee('Allocated learner private reason');
        $learner->forceFill(['user_id' => $this->schoolA->ids['cara_user']])->save();
        $this->get(route('leave-calendar.show', $learner->id))->assertNotFound();
    }

    public function test_own_request_permission_cannot_open_another_staff_request(): void
    {
        $outsider = $this->actor('Calendar own requests', ['leave-create']);
        $record = $this->privateStaffRequest();
        $this->actingAs($outsider)->get(route('leave-calendar.show', $record->id))->assertNotFound();
    }
}
