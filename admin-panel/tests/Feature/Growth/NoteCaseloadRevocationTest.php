<?php

declare(strict_types=1);

namespace Tests\Feature\Growth;

use App\Models\Academics\AcademicLeadership;
use App\Services\Growth\LearnerNotes;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Support\Str;
use Tests\Concerns\UsesMasteryScenario;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class NoteCaseloadRevocationTest extends TestCase
{
    use UsesMasteryScenario, ValidatesOpenApiResponses;

    public function test_principal_ends_same_day_access_and_replays_only_metadata_without_removing_staff_role(): void
    {
        $this->setUpMasteryScenario();
        AcademicLeadership::create(['user_id' => $this->superAdmin()->id, 'kind' => 'principal', 'grade_level_id' => null, 'session_year_id' => $this->year,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic principal']);
        $this->actor()->assignRole(SchoolRoles::COUNSELLOR);
        app(LearnerNotes::class)->assign($this->superAdmin(), $this->schoolA->ids['asha'], $this->actor()->id,
            ['starts_on' => '2026-02-10', 'ends_on' => '2026-12-31', 'expected_revision' => 0]);
        $note = app(LearnerNotes::class)->create($this->actor(), $this->schoolA->ids['asha'], $this->subject, $this->year, 'counselling', 'Private revocable note');
        $url = '/api/v2/growth/progress/learners/'.$this->schoolA->ids['asha'].'/assignments/'.$this->actor()->id.'/revoke';
        $input = ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1];
        $saved = $this->actingAs($this->superAdmin())->postJson($url, $input)->assertOk()->assertJsonPath('data.revoked', true)->assertJsonPath('data.starts_on', '2026-02-10')->assertJsonPath('data.revision', 2)->assertHeader('Cache-Control', 'no-store, private');
        $this->postJson($url, $input)->assertOk()->assertExactJson($saved->json());
        $this->actingAs($this->actor())->getJson('/api/v2/growth/progress/notes/'.$note['id'])->assertNotFound();
        $this->getJson('/api/v2/growth/progress/caseload')->assertOk()->assertJsonPath('data.total', 0);
        $this->postJson($url, $input)->assertForbidden();
        $this->actingAs($this->superAdmin())->postJson('/api/v2/growth/progress/learners/'.$this->schoolB->ids['asha'].'/assignments/'.$this->actor()->id.'/revoke', $input)->assertNotFound();
        $list = app(LearnerNotes::class)->listing($this->superAdmin(), $this->schoolA->ids['asha'], null, $this->year, 'staff');
        $this->assertSame([], $list['notes']);
    }
}
