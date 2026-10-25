<?php

declare(strict_types=1);

namespace Tests\Feature\Wellbeing;

use App\Models\People\Enrolment;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Models\Wellbeing\CaseAssignment;
use App\Models\Wellbeing\IncidentReport;
use App\Models\Wellbeing\IncidentThread;
use App\Services\ParentPolicyService;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Wellbeing\WellbeingSettings;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class WellbeingApiTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private User $professional;

    private array $category;

    private string $slug;

    private string $secret;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        app(ModuleGate::class)->change('wellbeing', true, $this->superAdmin());
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->schoolA->ids['session_year'], 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic wellbeing API enrolment']);
        $this->professional = User::create(['first_name' => 'Synthetic', 'last_name' => 'Professional', 'email' => 'wellbeing-api@example.test', 'password' => Hash::make('SyntheticCase2026'), 'status' => 1, 'password_change_required' => false]);
        SchoolMembership::create(['school_id' => 1, 'user_id' => $this->professional->id, 'role' => 'Counsellor', 'status' => 'active', 'starts_on' => '2026-01-01']);
        $this->professional->assignRole('Counsellor');
        ParentPolicyService::acceptCurrent($this->professional);
        $this->category = app(WellbeingSettings::class)->saveCategory($this->superAdmin(), null, $this->operation(['revision' => 0, 'name' => 'Safety concern', 'emergency_guidance' => 'Speak to a trusted adult now if unsafe.', 'active' => true]));
        $this->slug = School::findOrFail(1)->slug;
        $this->secret = bin2hex(random_bytes(32));
    }

    private function operation(array $input): array
    {
        return $input + ['operation_id' => (string) Str::uuid()];
    }

    private function api(User $actor): self
    {
        $this->app['auth']->forgetGuards();
        $name = $actor->id === $this->professional->id ? AppTokens::FACILITATOR : 'wellbeing-api-test';

        return $this->flushHeaders()->withToken($actor->createToken($name)->plainTextToken)->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $actor->id]);
    }

    private function submit(array $headers = []): array
    {
        return $this->flushHeaders()->withHeaders($headers)->postJson('/api/v2/wellbeing/public/'.$this->slug.'/reports', ['secret' => $this->secret, 'anonymous' => true, 'category_id' => $this->category['id'], 'body' => 'Synthetic anonymous concern.', 'urgency' => 'urgent'])->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data');
    }

    private function grant(): array
    {
        return $this->operation(['revision' => 0, 'user_id' => $this->professional->id, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Explicit API fixture assignment']);
    }

    public function test_public_anonymous_transport_ignores_portal_identity_has_no_session_cookie_and_uses_post_body_code(): void
    {
        config(['sanctum.stateful' => ['localhost']]);
        $this->actingAs($this->superAdmin())->withHeaders(['Origin' => 'http://localhost', 'Referer' => 'http://localhost/']);
        $response = $this->submit(['Origin' => 'http://localhost', 'Referer' => 'http://localhost/']);
        $this->assertTrue($response['received']);
        $row = IncidentReport::firstOrFail();
        $this->assertNull($row->reporter_user_id);
        $this->assertNull($row->learner_id);
        $read = $this->postJson('/api/v2/wellbeing/public/'.$this->slug.'/follow-up', ['secret' => $this->secret])->assertOk()->assertJsonPath('data.anonymous', true)->assertJsonPath('data.body', 'Synthetic anonymous concern.');
        $this->assertSame([], $read->headers->getCookies());
        $reply = $this->operation(['secret' => $this->secret, 'body' => 'Synthetic follow-up']);
        $one = $this->postJson('/api/v2/wellbeing/public/'.$this->slug.'/follow-up/replies', $reply)->assertOk()->json('data');
        $this->assertSame($one, $this->postJson('/api/v2/wellbeing/public/'.$this->slug.'/follow-up/replies', $reply)->assertOk()->json('data'));
        $this->assertSame(1, IncidentThread::count());
        $this->postJson('/api/v2/wellbeing/public/'.$this->slug.'/follow-up?secret='.$this->secret, [])->assertUnprocessable();
        $this->postJson('/api/v2/wellbeing/public/'.$this->slug.'/follow-up?secret='.str_repeat('0', 64), ['secret' => $this->secret])->assertUnprocessable();
        $this->postJson('/api/v2/wellbeing/public/'.$this->slug.'/follow-up', ['secret' => str_repeat('0', 64)])->assertNotFound();
        $this->postJson('/api/v2/wellbeing/public/baobab-high/follow-up', ['secret' => $this->secret])->assertNotFound();
    }

    public function test_management_choices_are_named_and_do_not_grant_report_or_case_body_access(): void
    {
        $this->submit();
        $this->api($this->superAdmin())->getJson('/api/v2/wellbeing/access')->assertOk()->assertJsonPath('data.can_configure', true)->assertJsonPath('data.professional', false);
        $this->getJson('/api/v2/wellbeing/configuration')->assertOk()->assertJsonPath('data.professional_choices.0.name', 'Synthetic Professional');
        $this->postJson('/api/v2/wellbeing/categories/'.$this->category['id'].'/team', $this->grant())->assertOk();
        $this->getJson('/api/v2/wellbeing/categories/'.$this->category['id'].'/team')->assertOk()->assertJsonPath('data.items.0.name', 'Synthetic Professional');
        $case = $this->postJson('/api/v2/wellbeing/management/cases', $this->operation(['learner_id' => $this->schoolA->ids['asha']]))->assertOk()->json('data');
        $this->postJson('/api/v2/wellbeing/management/cases/'.$case['id'].'/assignments', $this->grant())->assertOk();
        $this->getJson('/api/v2/wellbeing/management/cases')->assertOk()->assertJsonStructure(['data' => ['items' => [['learner_name']]]]);
        $this->getJson('/api/v2/wellbeing/reports/'.IncidentReport::firstOrFail()->id)->assertForbidden();
        $this->getJson('/api/v2/wellbeing/cases/'.$case['id'])->assertForbidden();
        $this->api($this->professional)->getJson('/api/v2/wellbeing/configuration')->assertForbidden();
    }

    public function test_configuration_pagination_excludes_professionals_from_other_schools(): void
    {
        for ($index = 0; $index < 31; $index++) {
            $outside = User::create(['first_name' => 'AAA outside', 'last_name' => 'Professional '.$index,
                'email' => 'outside-clinical-'.$index.'@example.test', 'password' => 'NotALoginCredential', 'status' => 1]);
            SchoolMembership::create(['school_id' => 2, 'user_id' => $outside->id, 'role' => 'Counsellor', 'status' => 'active', 'starts_on' => '2026-01-01']);
        }
        $this->api($this->superAdmin())->getJson('/api/v2/wellbeing/configuration')->assertOk()
            ->assertJsonCount(1, 'data.professional_choices')->assertJsonPath('data.professional_choices.0.id', $this->professional->id)
            ->assertJsonPath('data.professionals_last_page', 1);
    }

    public function test_private_route_catalogue_refuses_wrong_school_and_nonclinical_roles_before_validation(): void
    {
        $operations = json_decode(file_get_contents(base_path('../engineering/contracts/wellbeing-operations.json')), true, 512, JSON_THROW_ON_ERROR);
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $count = 0;
        foreach ($operations as $operation) {
            if (str_starts_with($operation['action'], 'public.')) {
                continue;
            }
            $path = '/api/v2'.preg_replace('/\{\w+\}/', '999999', $operation['path']);
            $this->api($this->professional)->withHeader('X-School-Id', '2')->json($operation['method'], $path, [])->assertForbidden();
            if ($operation['action'] !== 'access' && ! str_starts_with($operation['action'], 'identified-') && $operation['action'] !== 'identify') {
                $this->api($parent)->json($operation['method'], $path, [])->assertForbidden();
            }
            $count++;
        }
        $this->assertSame(27, $count);
    }

    public function test_assigned_professional_facilitator_profile_can_triage_and_write_case_without_teacher_record(): void
    {
        app(WellbeingSettings::class)->assignTeam($this->superAdmin(), $this->category['id'], null, $this->grant());
        $case = app(WellbeingSettings::class)->openCase($this->superAdmin(), $this->operation(['learner_id' => $this->schoolA->ids['asha']]));
        app(WellbeingSettings::class)->assignCase($this->superAdmin(), $case['id'], null, $this->grant());
        $this->submit();
        $report = IncidentReport::firstOrFail();
        $this->api($this->professional)->getJson('/api/v2/wellbeing/access')->assertOk()->assertJsonPath('data.can_triage', true)->assertJsonPath('data.can_casework', true);
        $this->getJson('/api/v2/wellbeing/reports')->assertOk()->assertJsonPath('data.items.0.category_name', 'Safety concern');
        $this->getJson('/api/v2/wellbeing/reports/'.$report->id)->assertOk()->assertJsonPath('data.body', 'Synthetic anonymous concern.');
        $this->postJson('/api/v2/wellbeing/reports/'.$report->id.'/replies', $this->operation(['revision' => 1, 'body' => 'Private internal triage', 'internal' => true, 'status' => 'triaged']))->assertOk();
        $entry = $this->operation(['revision' => 1, 'kind' => 'note', 'body' => 'Synthetic private case entry', 'happened_at' => now()->subMinute()->toISOString()]);
        $one = $this->postJson('/api/v2/wellbeing/cases/'.$case['id'].'/entries', $entry)->assertOk()->json('data');
        $this->assertSame($one, $this->postJson('/api/v2/wellbeing/cases/'.$case['id'].'/entries', $entry)->assertOk()->json('data'));
        $this->getJson('/api/v2/wellbeing/cases')->assertOk()->assertJsonStructure(['data' => ['items' => [['learner_name']]]]);
        $this->getJson('/api/v2/wellbeing/cases/'.$case['id'])->assertOk()->assertJsonPath('data.entries.0.body', 'Synthetic private case entry');
        $this->postJson('/api/v2/wellbeing/cases/'.$case['id'].'/appointments', $this->operation(['revision' => 0, 'professional_user_id' => $this->professional->id, 'starts_at' => '2026-02-11T08:00:00Z', 'ends_at' => '2026-02-11T08:30:00Z', 'location' => 'Private room', 'notes' => '', 'status' => 'scheduled']))->assertOk();
        $this->getJson('/api/v2/wellbeing/cases/'.$case['id'].'/appointments?from=2026-02-01&to=2026-02-28')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_professional_expiry_platform_registration_password_setup_and_expected_account_refuse_before_private_records(): void
    {
        $case = app(WellbeingSettings::class)->openCase($this->superAdmin(), $this->operation(['learner_id' => $this->schoolA->ids['asha']]));
        app(WellbeingSettings::class)->assignCase($this->superAdmin(), $case['id'], null, $this->grant());
        $this->api($this->professional)->withHeader('X-Account-Id', '999999')->getJson('/api/v2/wellbeing/cases/'.$case['id'])->assertForbidden();
        $this->withHeader('X-Account-Id', (string) $this->professional->id)->getJson('/api/v2/wellbeing/cases/'.$case['id'])->assertOk();
        $this->professional->forceFill(['password_change_required' => true])->save();
        // A retained teacher-profile token must not bypass clinical readiness.
        $this->app['auth']->forgetGuards();
        $this->withToken($this->professional->createToken(AppTokens::TEACHER)->plainTextToken);
        $this->getJson('/api/v2/wellbeing/cases/'.$case['id'])->assertForbidden();
        $this->professional->forceFill(['password_change_required' => false])->save();
        PlatformStaffIdentity::create(['user_id' => $this->professional->id, 'active' => true]);
        $this->getJson('/api/v2/wellbeing/cases/'.$case['id'])->assertForbidden();
        PlatformStaffIdentity::where('user_id', $this->professional->id)->delete();
        CaseAssignment::where('user_id', $this->professional->id)->update(['ends_on' => '2026-02-09']);
        $this->getJson('/api/v2/wellbeing/cases/'.$case['id'])->assertNotFound();
    }

    public function test_identification_is_explicit_and_uses_bound_account_after_the_public_code_stops_working(): void
    {
        $this->submit();
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->api($parent)->postJson('/api/v2/wellbeing/own/identify', ['secret' => $this->secret, 'revision' => 1, 'identify_consent' => true, 'learner_id' => $this->schoolA->ids['asha']])->assertOk()->assertJsonPath('data.anonymous', false);
        $this->flushHeaders()->postJson('/api/v2/wellbeing/public/'.$this->slug.'/follow-up', ['secret' => $this->secret])->assertForbidden();
        $this->api($parent)->postJson('/api/v2/wellbeing/own/follow-up', ['secret' => $this->secret])->assertOk()->assertJsonPath('data.anonymous', false);
        $this->api($this->professional)->postJson('/api/v2/wellbeing/own/follow-up', ['secret' => $this->secret])->assertForbidden();
    }
}
