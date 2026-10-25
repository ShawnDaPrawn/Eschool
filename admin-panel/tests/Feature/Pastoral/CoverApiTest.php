<?php

declare(strict_types=1);

namespace Tests\Feature\Pastoral;

use App\Models\Notification;
use App\Models\Pastoral\CoverAbsence;
use App\Models\Pastoral\CoverAssignment;
use App\Models\Pastoral\CoverOperation;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Academics\TimetableEngine;
use App\Services\Academics\TimetableQueries;
use App\Services\Pastoral\CoverAvailability;
use App\Services\Pastoral\CoverNotices;
use App\Services\Pastoral\CoverTimetable;
use App\Services\Pastoral\ReliefCover;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Str;
use Tests\Concerns\UsesCoverScenario;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class CoverApiTest extends TestCase
{
    use UsesCoverScenario, ValidatesOpenApiResponses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCoverScenario();
        config(['cache.default' => 'array']);
    }

    private function window(): string
    {
        return '?session_year_id='.$this->year.'&from=2026-02-10&to=2026-02-17';
    }

    public function test_teacher_manager_and_substitute_complete_typed_private_workflow(): void
    {
        $url = '/api/v2/pastoral/cover/';
        $this->actingAs($this->person())->getJson($url.'configuration'.$this->window())->assertOk()->assertJsonPath('data.can_manage', false)->assertJsonCount(1, 'data.teachers');
        $body = $this->input() + ['operation_id' => (string) Str::uuid()];
        $id = $this->postJson($url.'absences', $body)->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('data.reason', 'Private cover reason')->json('data.id');
        $this->postJson($url.'absences', $body)->assertOk()->assertJsonPath('data.id', $id);
        $cover = CoverAssignment::orderBy('id')->firstOrFail();
        $this->actingAs($this->superAdmin())->getJson($url.'occurrences/'.$cover->id.'/candidates')->assertOk()->assertJsonPath('data.teachers.0.id', $this->substitute);
        $assignment = ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'substitute_teacher_id' => $this->substitute];
        $this->putJson($url.'occurrences/'.$cover->id.'/assignment', $assignment)->assertOk()->assertJsonPath('data.state', 'assigned');
        $this->actingAs($this->person('theo'))->getJson($url.'mine'.$this->window())->assertOk()->assertJsonPath('data.occurrences.0.id', (int) $cover->id)->assertDontSee('Private cover reason');
        $this->getJson($url.'occurrences/'.$cover->id)->assertOk()->assertDontSee('Private cover reason');
        $this->getJson($url.'absences/'.$id)->assertForbidden();
        $this->actingAs($this->person())->postJson($url.'absences/'.$id.'/refresh', ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'reason' => 'Review sources'])->assertOk()->assertJsonPath('data.revision', 2);
        $this->postJson($url.'absences/'.$id.'/cancel', ['operation_id' => (string) Str::uuid(), 'revision' => 2, 'reason' => 'No longer absent'])->assertOk()->assertJsonPath('data.state', 'cancelled');
        $this->actingAs($this->person('theo'))->getJson($url.'mine'.$this->window())->assertOk()->assertJsonCount(0, 'data.occurrences');
    }

    public function test_wrong_owner_role_school_and_revoked_replay_are_refused(): void
    {
        $url = '/api/v2/pastoral/cover/';
        $body = $this->input() + ['operation_id' => (string) Str::uuid()];
        $this->actingAs($this->person('theo'))->postJson($url.'absences', $body)->assertForbidden();
        $this->actingAs($this->person('farid'))->getJson($url.'configuration'.$this->window())->assertForbidden();
        $id = $this->actingAs($this->person())->postJson($url.'absences', $body)->assertOk()->json('data.id');
        $this->actingAs($this->person('theo'))->getJson($url.'absences/'.$id)->assertForbidden();
        $other = app(CurrentSchool::class)->run(2, fn () => User::findOrFail($this->schoolB->ids['tara_user']));
        $this->actingAs($other)->withHeader('X-School-Id', '2')->getJson($url.'absences/'.$id)->assertNotFound();
        $this->withHeader('X-School-Id', '1')->actingAs($this->person())->postJson($url.'absences', $body)->assertOk();
        SchoolMembership::where('user_id', $this->person()->id)->where('role', 'Teacher')->update(['status' => 'inactive']);
        $this->postJson($url.'absences', $body)->assertForbidden();
        $this->assertSame(1, CoverAbsence::count());
        $this->assertSame(1, CoverOperation::count());
    }

    public function test_bounds_unknown_staff_and_platform_operator_are_refused(): void
    {
        $url = '/api/v2/pastoral/cover/';
        $this->actingAs($this->person())->getJson($url.'configuration?session_year_id='.$this->year.'&from=2026-02-10&to=2026-04-10')->assertUnprocessable();
        $this->getJson($url.'mine')->assertUnprocessable();
        PlatformStaffIdentity::create(['user_id' => $this->person()->id]);
        $this->getJson($url.'configuration'.$this->window())->assertForbidden();
    }

    public function test_dated_timetable_shows_substitute_and_preview_reserves_cover(): void
    {
        $absence = $this->request();
        $cover = CoverAssignment::orderBy('id')->firstOrFail();
        app(ReliefCover::class)->assign($this->superAdmin(), $cover->id, $this->substitute, 1, (string) Str::uuid());
        $row = app(TimetableQueries::class)->week($this->person('theo'), $this->year, '2026-02-09')['lessons'][0];
        $this->assertSame((int) $cover->id, $row['cover_id']);
        $this->assertSame($this->substitute, $row['teacher_id']);
        $this->assertSame($this->teacher, $row['original_teacher_id']);
        $engine = app(TimetableEngine::class);
        $candidate = app(CoverAvailability::class)->candidate($this->substitute, $this->lesson, '2026-02-10');
        $this->assertNotEmpty(app(CoverTimetable::class)->clashes($candidate, 0, $engine));
        app(ReliefCover::class)->change($this->person(), $absence->id, 1, 'cancel', 'No cover needed', (string) Str::uuid());
        $this->assertSame([], app(TimetableQueries::class)->week($this->person('theo'), $this->year, '2026-02-09')['lessons']);
    }

    public function test_generic_notices_are_deduplicated_and_recheck_withdrawn_assignment(): void
    {
        $this->request();
        $cover = CoverAssignment::orderBy('id')->firstOrFail();
        $uuid = (string) Str::uuid();
        $service = app(ReliefCover::class);
        $service->assign($this->superAdmin(), $cover->id, $this->substitute, 1, $uuid);
        $service->assign($this->superAdmin(), $cover->id, $this->substitute, 1, $uuid);
        $notices = Notification::where('type', 'pastoral.cover_assigned')->get();
        $this->assertCount(1, $notices);
        $notice = $notices->sole();
        $this->assertStringNotContainsString('Private cover reason', json_encode($notice));
        $guard = app(CoverNotices::class);
        $this->assertTrue($guard->allows($this->person('theo'), $notice->type, $notice->delivery_payload));
        $this->assertFalse($guard->allows($this->person(), $notice->type, $notice->delivery_payload));
        $cover->fresh()->update(['revision' => 3]);
        $this->assertFalse($guard->allows($this->person('theo'), $notice->type, $notice->delivery_payload));
        $this->assertSame([], app(Notifier::class)->send($this->person('theo'), $notice->type, $notice->title, $notice->message, '/pastoral/cover', $notice));
    }
}
