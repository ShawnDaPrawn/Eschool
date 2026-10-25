<?php

declare(strict_types=1);

namespace Tests\Feature\Pastoral;

use App\Models\Pastoral\CoverAbsence;
use App\Models\Pastoral\CoverAssignment;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Str;
use Tests\Concerns\UsesCoverScenario;
use Tests\TestCase;

class CoverPanelTest extends TestCase
{
    use UsesCoverScenario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCoverScenario();
    }

    private function fields(): array
    {
        return $this->input() + ['from' => '2026-02-10', 'to' => '2026-02-17', 'operation_id' => (string) Str::uuid()];
    }

    public function test_request_named_assignment_and_cancel_panel_workflow(): void
    {
        $this->actingAs($this->person())->get('/relief-cover')->assertOk()->assertSee('Request cover')->assertHeader('Cache-Control', 'no-store, private');
        $this->post('/relief-cover/requests', $this->fields())->assertRedirect()->assertHeader('Cache-Control', 'no-store, private');
        $absence = CoverAbsence::firstOrFail();
        $cover = CoverAssignment::orderBy('id')->firstOrFail();
        $this->actingAs($this->superAdmin())->get('/relief-cover/'.$absence->id.'?choose='.$cover->id)->assertOk()->assertSee('Available substitute')->assertSee($this->person('theo')->full_name);
        $this->post('/relief-cover/occurrences/'.$cover->id.'/assignment', $this->fields() + ['substitute_teacher_id' => $this->substitute, 'revision' => 1])->assertRedirect();
        $this->actingAs($this->person('theo'))->get('/relief-cover')->assertOk()->assertSee('Cover maths')->assertDontSee('Private cover reason');
        $this->get('/relief-cover/'.$absence->id)->assertForbidden();
        $this->actingAs($this->person())->post('/relief-cover/'.$absence->id.'/cancel', $this->fields() + ['revision' => 1])->assertRedirect();
        $this->assertSame('cancelled', $absence->fresh()->state);
    }

    public function test_invalid_and_conflicting_drafts_remain_private_and_are_not_flashed(): void
    {
        $fields = $this->fields();
        $fields['ends_on'] = '2026-02-01';
        $this->actingAs($this->person())->post('/relief-cover/requests', $fields)->assertUnprocessable()->assertSee('Private cover reason')->assertHeader('Cache-Control', 'no-store, private')->assertSessionMissing('_old_input.reason');
        $this->assertSame(0, CoverAbsence::count());
        $absence = $this->request();
        $this->post('/relief-cover/'.$absence->id.'/cancel', $this->fields() + ['revision' => 7])->assertStatus(409)->assertSee('Compare the current record')->assertSee('Private cover reason')->assertHeader('Cache-Control', 'no-store, private')->assertSessionMissing('_old_input.reason');
        $this->assertSame('requested', $absence->fresh()->state);
        $this->post('/relief-cover/'.$absence->id.'/cancel', $this->fields() + ['revision' => 1, 'review_required' => '1'])->assertUnprocessable();
    }

    public function test_wrong_owner_role_school_module_and_historical_write_are_refused(): void
    {
        $absence = $this->request();
        $this->actingAs($this->person('theo'))->get('/relief-cover/'.$absence->id)->assertForbidden();
        $this->actingAs($this->person('farid'))->get('/relief-cover')->assertRedirect(route('login'));
        $other = app(CurrentSchool::class)->run(2, fn () => User::findOrFail($this->schoolB->ids['tara_user']));
        $this->actingAs($other)->withSession(['school_id' => 2])->get('/relief-cover/'.$absence->id)->assertNotFound();
        $this->withSession(['school_id' => 1])->actingAs($this->person());
        config(['modules.relief_cover' => ['default' => false]]);
        $this->get('/relief-cover')->assertNotFound();
        config(['modules.relief_cover' => ['always_on' => true]]);
        $year = SessionYear::create(['name' => 'Historical cover', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31']);
        $fields = $this->fields();
        $fields['session_year_id'] = $year->id;
        $this->post('/relief-cover/requests', $fields)->assertForbidden();
    }

    public function test_malformed_input_uses_a_private_recoverable_error_view(): void
    {
        $fields = $this->fields();
        $fields['operation_id'] = ['invalid'];
        $fields['reason'] = ['private'];
        $this->actingAs($this->person())->post('/relief-cover/requests', $fields)->assertUnprocessable()->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame(0, CoverAbsence::count());
    }

    public function test_header_year_refusal_never_flashes_a_private_reason(): void
    {
        $year = SessionYear::create(['name' => 'Previous cover year', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31']);
        $this->actingAs($this->person())->withSession(['session_year' => $year->id])->post('/relief-cover/requests', $this->fields())
            ->assertStatus(423)->assertSee('Private cover reason')->assertHeader('Cache-Control', 'no-store, private')->assertSessionMissing('_old_input.reason');
        $this->assertSame(0, CoverAbsence::count());
    }
}
