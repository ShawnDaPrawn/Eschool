<?php

declare(strict_types=1);

namespace Tests\Feature\Growth;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\TeachingGroup;
use App\Models\Platform\SchoolModule;
use App\Models\User;
use App\Services\Discipline\DisciplineFrameworks;
use App\Services\Discipline\DisciplineRecords;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Support\Facades\Bus;
use Tests\Concerns\UsesMasteryScenario;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class MyGradeApiTest extends TestCase
{
    use UsesMasteryScenario, ValidatesOpenApiResponses;

    private int $grade;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
        Bus::fake();
        $this->grade = (int) TeachingGroup::findOrFail($this->group)->grade_level_id;
        SchoolModule::updateOrCreate(['module' => 'my_grade'], ['in_package' => true, 'enabled' => true]);
        app(ModuleGate::class)->forget();
        $this->actor()->forceFill(['password_change_required' => false])->save();
        AcademicLeadership::create(['user_id' => $this->actor()->id, 'kind' => 'grade_head', 'grade_level_id' => $this->grade,
            'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic head']);
    }

    private function api(User $actor): static
    {
        return $this->actingAs($actor)->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $actor->id]);
    }

    public function test_real_private_access_dashboard_and_feature_context_are_currently_owned(): void
    {
        $this->api($this->actor())->getJson('/api/v2/my-grade/access')->assertOk()->assertJsonPath('data.session_year_id', $this->year)
            ->assertJsonPath('data.grades.0.id', $this->grade)->assertHeader('Cache-Control', 'no-store, private')->assertHeader('Referrer-Policy', 'no-referrer');
        $this->getJson('/api/v2/my-grade/grades/'.$this->grade)->assertOk()->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.learners.0.learner_id', $this->schoolA->ids['asha'])->assertJsonPath('data.learners.0.attendance_state', 'unrecorded');
        $this->getJson('/api/v2/me/context')->assertOk()->assertJsonPath('data.feature_flags.my_grade', true);
        AcademicLeadership::where('user_id', $this->actor()->id)->update(['ends_on' => '2026-02-09']);
        $this->getJson('/api/v2/my-grade/grades/'.$this->grade)->assertForbidden()->assertHeader('Cache-Control', 'no-store, private');
        $this->getJson('/api/v2/me/context')->assertOk()->assertJsonPath('data.feature_flags.my_grade', false);
    }

    public function test_cookie_account_school_parent_and_unappointed_staff_are_refused(): void
    {
        $path = '/api/v2/my-grade/grades/'.$this->grade;
        foreach ([$this->actor('theo'), $this->actor('farid'), $this->actor('asha'), $this->superAdmin()] as $actor) {
            $this->api($actor)->getJson($path)->assertForbidden();
        }
        $this->api($this->actor())->getJson($path, ['X-Account-Id' => (string) $this->actor('theo')->id])->assertForbidden();
        $this->api($this->actor())->getJson($path, ['X-School-Id' => '2'])->assertForbidden();
    }

    public function test_pending_review_projection_contains_only_owned_queue_metadata(): void
    {
        SchoolModule::updateOrCreate(['module' => 'discipline'], ['in_package' => true, 'enabled' => true]);
        app(ModuleGate::class)->forget();
        app(DisciplineFrameworks::class)->save($this->superAdmin(), $this->year, ['title' => 'Synthetic framework', 'expected_revision' => 0,
            'counter_scope' => 'same_infraction', 'reset' => 'school_year',
            'levels' => [['code' => 'warning', 'name' => 'Warning', 'threshold' => 1, 'kind' => 'sanction', 'sanctions' => ['notice']],
                ['code' => 'hearing', 'name' => 'Hearing', 'threshold' => 2, 'kind' => 'hearing', 'sanctions' => []]],
            'sanctions' => [['code' => 'notice', 'name' => 'Notice', 'kind' => 'notice']],
            'infractions' => [['code' => 'late', 'name' => 'Late arrival', 'base_level' => 'warning']]]);
        $input = ['occurred_on' => '2026-02-10', 'infraction_code' => 'late', 'description' => 'PRIVATE factual family description', 'staff_note' => 'SECRET staff context'];
        $records = app(DisciplineRecords::class);
        $records->create($this->actor(), $this->schoolA->ids['asha'], $this->year, $input);
        $pending = $records->create($this->actor(), $this->schoolA->ids['asha'], $this->year, $input);
        $reply = $this->api($this->actor())->getJson('/api/v2/my-grade/grades/'.$this->grade)->assertOk()
            ->assertJsonPath('data.discipline.total', 1)->assertJsonPath('data.discipline.items.0.id', $pending['id']);
        $this->assertSame(['id', 'learner_id', 'revision', 'occurred_on', 'status', 'learner_name'], array_keys($reply->json('data.discipline.items.0')));
        $this->assertStringNotContainsString('SECRET', $reply->getContent());
        $this->assertStringNotContainsString('PRIVATE', $reply->getContent());
    }

    public function test_date_page_and_unknown_query_refusals_are_private_without_old_input(): void
    {
        $this->api($this->actor());
        foreach (['?on_date=not-a-date', '?on_date=2026-02-11', '?page=0', '?discipline_page=0', '?session_year_id=999'] as $query) {
            $this->getJson('/api/v2/my-grade/grades/'.$this->grade.$query)->assertUnprocessable()->assertHeader('Cache-Control', 'no-store, private');
            $this->assertFalse(session()->has('_old_input'));
        }
        $this->getJson('/api/v2/my-grade/grades/'.$this->grade.'?on_date=')->assertOk();
    }

    public function test_unauthenticated_private_routes_never_cache(): void
    {
        foreach (['/api/v2/my-grade/access', '/api/v2/my-grade/grades/'.$this->grade] as $path) {
            $this->getJson($path)->assertUnauthorized()->assertHeader('Cache-Control', 'no-store, private')
                ->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Referrer-Policy', 'no-referrer');
        }
    }
}
