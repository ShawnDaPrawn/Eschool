<?php

declare(strict_types=1);

namespace Tests\Feature\Leave;

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivitySession;
use App\Models\Activities\ActivityStaff;
use App\Models\Leave;
use App\Models\Leave\FacilitatorAbsence;
use App\Models\Leave\FacilitatorSessionCover;
use App\Models\LeaveDetail;
use App\Models\LeaveMaster;
use App\Models\Platform\SchoolMembership;
use App\Models\Recognition\AwardCategory;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Leave\FacilitatorLeave;
use App\Services\Panel\ModuleReadiness;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class FacilitatorLeaveTest extends TestCase
{
    use UsesTwoSchools;

    private User $facilitator;

    private User $substitute;

    private ActivitySession $session;

    private ActivityGroup $group;

    private int $year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->year = $this->schoolA->ids['session_year'];
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $this->superAdmin()->givePermissionTo(['activity-groups-manage', 'leave-approve']);
        app(ModuleGate::class)->change('groups', true, $this->superAdmin());
        $this->facilitator = $this->makeFacilitator('tara');
        $this->substitute = $this->makeFacilitator('theo');
        $this->group = ActivityGroup::create(['session_year_id' => $this->year, 'name' => 'Junior debate', 'discipline' => 'Debate', 'season' => '2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'gender' => 'any', 'active' => true, 'revision' => 1]);
        foreach ([$this->facilitator, $this->substitute] as $person) {
            ActivityStaff::create(['activity_group_id' => $this->group->id, 'user_id' => $person->id, 'role' => 'facilitator', 'cascade' => false, 'active' => true, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic appointment', 'revision' => 1]);
        }
        $this->session = ActivitySession::create(['activity_group_id' => $this->group->id, 'on_date' => '2026-02-12', 'name' => 'Debate practice', 'venue' => 'Hall', 'starts_at' => '14:30', 'ends_at' => '15:30', 'status' => 'scheduled', 'revision' => 1]);
        Storage::fake('leave_private');
    }

    private function makeFacilitator(string $person): User
    {
        $actor = User::findOrFail($this->schoolA->ids[$person.'_user']);
        SchoolMembership::create(['school_id' => 1, 'user_id' => $actor->id, 'role' => 'Facilitator', 'status' => 'active', 'starts_on' => '2026-01-01']);
        $actor->assignRole('Facilitator');

        return $actor;
    }

    private function requestFields(array $overrides = []): array
    {
        return $overrides + ['operation_id' => (string) Str::uuid(), 'session_year_id' => $this->year, 'from_date' => '2026-02-12', 'to_date' => '2026-02-12', 'portions' => ['2026-02-12' => 'second_half'], 'reason' => 'Private facilitator absence reason'];
    }

    private function createRequest(): FacilitatorAbsence
    {
        return app(FacilitatorLeave::class)->request($this->facilitator, $this->requestFields());
    }

    public function test_request_approval_and_session_cover_preserve_facilitator_identity_and_group_appointment(): void
    {
        $fields = $this->requestFields();
        $service = app(FacilitatorLeave::class);
        $this->actingAs($this->facilitator)->get('/facilitator-leave/create?from=2026-02-12&to=2026-02-12')->assertOk()->assertSee('Debate practice')->assertSee('portions[2026-02-12]', false);
        $this->postJson('/facilitator-leave', $fields)->assertOk()->assertJsonPath('error', false);
        $this->postJson('/facilitator-leave', $fields)->assertOk();
        $this->assertSame(1, FacilitatorAbsence::count());
        $row = FacilitatorAbsence::firstOrFail();
        $this->assertSame('pending', $row->status);
        $this->assertSame($this->facilitator->id, (int) $row->user_id);
        $this->assertNotSame($row->reason, $row->getRawOriginal('reason'));
        $this->actingAs($this->superAdmin())->postJson('/facilitator-leave/'.$row->id.'/decision', ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'decision' => 'approved'])->assertOk();
        $this->assertSame(0, FacilitatorSessionCover::count());
        $this->get('/facilitator-leave/'.$row->id.'?session='.$this->session->id)->assertOk()->assertSee($this->substitute->full_name);
        $before = ActivityStaff::get()->toArray();
        $coverFields = ['operation_id' => (string) Str::uuid(), 'session_revision' => 1, 'cover_revision' => 0, 'replacement_user_id' => $this->substitute->id, 'reason' => 'Approved absence cover'];
        $this->postJson('/facilitator-leave/'.$row->id.'/sessions/'.$this->session->id.'/cover', $coverFields)->assertOk();
        $this->postJson('/facilitator-leave/'.$row->id.'/sessions/'.$this->session->id.'/cover', $coverFields)->assertOk();
        $this->assertSame(1, FacilitatorSessionCover::count());
        $this->assertSame($before, ActivityStaff::get()->toArray());
        $this->get('/facilitator-leave/'.$row->id)->assertOk()->assertSee('Temporary cover')->assertSee($this->substitute->full_name);
    }

    public function test_private_record_and_files_require_owner_or_dual_permission_reviewer_and_current_school(): void
    {
        $service = app(FacilitatorLeave::class);
        $row = $service->request($this->facilitator, $this->requestFields(), [UploadedFile::fake()->create('private-note.pdf', 8, 'application/pdf')]);
        $this->actingAs($this->substitute)->get('/facilitator-leave/'.$row->id)->assertNotFound();
        $this->get('/facilitator-leave/'.$row->id.'/files/0')->assertNotFound();
        $this->actingAs($this->facilitator)->get('/facilitator-leave/'.$row->id.'/files/0')->assertOk()->assertDownload('private-note.pdf');
        $this->postJson('/facilitator-leave/'.$row->id.'/decision', ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'decision' => 'approved'])->assertForbidden();
        $foreign = app(CurrentSchool::class)->run(2, fn () => FacilitatorAbsence::create([
            'user_id' => $this->schoolB->ids['tara_user'], 'session_year_id' => $this->schoolB->ids['session_year'],
            'from_date' => '2026-02-12', 'to_date' => '2026-02-12', 'portions' => ['2026-02-12' => 'full'],
            'reason' => 'Private Baobab request', 'attachments' => [], 'status' => 'pending', 'revision' => 1,
        ]));
        $this->actingAs($this->facilitator)->withSession(['school_id' => 1])->get('/facilitator-leave/'.$foreign->id)->assertNotFound();
        $this->get('/facilitator-leave/'.$foreign->id.'/files/0')->assertNotFound();
    }

    public function test_overlap_wrong_portion_and_stale_decisions_are_rejected_without_changing_records(): void
    {
        $this->actingAs($this->facilitator)->postJson('/facilitator-leave', $this->requestFields(['portions' => ['2026-02-12' => 'first_half']]))->assertUnprocessable();
        $row = $this->createRequest();
        $this->postJson('/facilitator-leave', $this->requestFields())->assertConflict();
        $this->actingAs($this->superAdmin())->postJson('/facilitator-leave/'.$row->id.'/decision', ['operation_id' => (string) Str::uuid(), 'revision' => 9, 'decision' => 'approved'])->assertConflict();
        $this->assertSame('pending', $row->fresh()->status);
        $this->postJson('/facilitator-leave/'.$row->id.'/decision', ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'decision' => 'rejected'])->assertOk();
        $this->postJson('/facilitator-leave/'.$row->id.'/decision', ['operation_id' => (string) Str::uuid(), 'revision' => 2, 'decision' => 'approved'])->assertConflict();
        $this->assertSame('rejected', $row->fresh()->status);
    }

    public function test_cover_checks_session_revision_conflicts_and_historical_year(): void
    {
        $row = $this->createRequest();
        $service = app(FacilitatorLeave::class);
        $service->decide($this->superAdmin(), $row->id, ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'decision' => 'approved']);
        $fields = ['operation_id' => (string) Str::uuid(), 'session_revision' => 1, 'cover_revision' => 0, 'replacement_user_id' => $this->substitute->id, 'reason' => 'Reviewed cover'];
        $this->session->update(['revision' => 2]);
        $this->actingAs($this->superAdmin())->postJson('/facilitator-leave/'.$row->id.'/sessions/'.$this->session->id.'/cover', $fields)->assertConflict();
        $other = ActivityGroup::create(['session_year_id' => $this->year, 'name' => 'Conflicting group', 'discipline' => 'Sport', 'season' => '2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'gender' => 'any', 'active' => true, 'revision' => 1]);
        ActivityStaff::create(['activity_group_id' => $other->id, 'user_id' => $this->substitute->id, 'role' => 'facilitator', 'cascade' => false, 'active' => true, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Other appointment', 'revision' => 1]);
        ActivitySession::create(['activity_group_id' => $other->id, 'on_date' => '2026-02-12', 'name' => 'Other session', 'venue' => 'Field', 'starts_at' => '14:00', 'ends_at' => '15:00', 'status' => 'scheduled', 'revision' => 1]);
        $fields['session_revision'] = 2;
        $this->postJson('/facilitator-leave/'.$row->id.'/sessions/'.$this->session->id.'/cover', $fields)->assertUnprocessable();
        $this->assertSame(0, FacilitatorSessionCover::count());
        $old = SessionYear::create(['name' => 'Previous year', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31']);
        $this->actingAs($this->facilitator)->postJson('/facilitator-leave', $this->requestFields(['session_year_id' => $old->id]))->assertStatus(423);
    }

    public function test_reviewers_can_reject_a_retained_request_after_facilitator_membership_ends_but_cannot_approve_it(): void
    {
        $row = $this->createRequest();
        SchoolMembership::where('school_id', 1)->where('user_id', $this->facilitator->id)->where('role', 'Facilitator')->update(['status' => SchoolMembership::ENDED]);
        $this->actingAs($this->superAdmin())->postJson('/facilitator-leave/'.$row->id.'/decision', ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'decision' => 'approved'])->assertForbidden();
        $this->assertSame('pending', $row->fresh()->status);
        $this->postJson('/facilitator-leave/'.$row->id.'/decision', ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'decision' => 'rejected'])->assertOk();
        $this->assertSame('rejected', $row->fresh()->status);
        $this->assertSame(0, FacilitatorSessionCover::count());
    }

    public function test_calendar_omits_private_notes_and_unrelated_facilitator_requests(): void
    {
        $row = $this->createRequest();
        $this->actingAs($this->facilitator)->get('/leave-calendar?week=2026-02-12&person_type=facilitators')->assertOk()->assertSee($this->facilitator->full_name)->assertDontSee('Private facilitator absence reason');
        $this->actingAs($this->substitute)->get('/leave-calendar?week=2026-02-12&person_type=facilitators')->assertOk()->assertDontSee(route('facilitator-leave.show', $row->id), false);
        $this->actingAs($this->superAdmin())->get('/leave-calendar?week=2026-02-12&person_type=facilitators')->assertOk()->assertSee(route('facilitator-leave.show', $row->id), false)->assertDontSee('Private facilitator absence reason');
    }

    public function test_facilitator_cover_checks_each_approved_legacy_leave_portion(): void
    {
        $absence = $this->createRequest();
        $service = app(FacilitatorLeave::class);
        $master = new LeaveMaster;
        $master->forceFill(['session_year_id' => $this->year, 'total_leave' => '2', 'holiday_days' => ''])->save();
        $leave = Leave::create(['user_id' => $this->substitute->id, 'leave_master_id' => $master->id, 'reason' => 'Synthetic morning leave', 'from_date' => '2026-02-12', 'to_date' => '2026-02-12', 'status' => 1, 'session_year_id' => $this->year]);
        LeaveDetail::create(['leave_id' => $leave->id, 'date' => '2026-02-12', 'type' => 'First Half']);
        $this->assertTrue($service->candidates($this->superAdmin(), $absence, $this->session)->contains('id', $this->substitute->id));
        LeaveDetail::create(['leave_id' => $leave->id, 'date' => '2026-02-12', 'type' => 'Second Half']);
        $this->assertFalse($service->candidates($this->superAdmin(), $absence, $this->session)->contains('id', $this->substitute->id));
    }

    public function test_readiness_does_not_confuse_enabled_module_with_configuration_or_other_school_data(): void
    {
        app(ModuleGate::class)->change('awards', true, $this->superAdmin());
        $service = app(ModuleReadiness::class);
        $award = collect($service->forUser($this->superAdmin()))->firstWhere('key', 'awards');
        $this->assertTrue($award['availability']['enabled']);
        $this->assertSame('Configuration needed', $award['status']);
        $this->assertFalse($award['tasks'][0]['done']);
        app(CurrentSchool::class)->run(2, fn () => AwardCategory::create(['name' => 'Baobab private category', 'scope_kind' => 'school', 'active' => true, 'revision' => 1]));
        $award = collect($service->forUser($this->superAdmin()))->firstWhere('key', 'awards');
        $this->assertFalse($award['tasks'][0]['done']);
        $this->actingAs($this->superAdmin())->get('/school-modules/readiness')->assertOk()->assertSee('Configuration needed')->assertDontSee('Baobab private category');
        $this->actingAs($this->substitute)->get('/school-modules/readiness')->assertForbidden();
        $withoutAuthority = collect($service->forUser($this->substitute))->firstWhere('key', 'awards');
        $this->assertSame('Review configuration', $withoutAuthority['status']);
        $this->assertSame([], $withoutAuthority['tasks']);
    }
}
