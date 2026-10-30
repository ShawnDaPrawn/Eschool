<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Platform\School;
use App\Models\SessionYear;
use App\Models\Settings;
use App\Models\Term;
use App\Models\User;
use App\Services\Platform\Context\ContextContributor;
use App\Services\Platform\Context\ContextScope;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

/**
 * GET /api/v2/me/context for someone at two schools: the answer is for the
 * school the request works in, lists both schools with the roles there,
 * and is composed by the contributors in config/context.php. Every answer
 * is checked against openapi.yaml.
 */
class ContextSchoolsTest extends TestCase
{
    use RefreshDatabase, ValidatesOpenApiResponses;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
        School::query()->forceCreate(['id' => 2, 'name' => 'Baobab High', 'slug' => 'baobab-high', 'timezone' => 'Africa/Johannesburg']);
        School::query()->forceCreate(['id' => 3, 'name' => 'Marula College', 'slug' => 'marula-college', 'timezone' => 'Africa/Johannesburg']);
        app(SchoolRoles::class)->seed(2);
        Settings::query()->updateOrCreate(['type' => 'school_name'], ['message' => 'Jacaranda Primary']);

        $user = User::query()->forceCreate([
            'first_name' => 'Pieter', 'last_name' => 'van Wyk', 'email' => 'pieter@example.com',
            'password' => bcrypt('Secret-Pass-2026'), 'gender' => 'Male', 'mobile' => '0820000000', 'status' => 1,
        ]);
        $user->assignRole('Teacher');
        app(CurrentSchool::class)->run(2, function () use ($user) {
            $user->assignRole('Super Admin');
            Settings::query()->create(['type' => 'school_name', 'message' => 'Baobab High School']);
            Settings::query()->create(['type' => 'time_zone', 'message' => 'Africa/Harare']);
        });
        $this->token = $user->createToken('test')->plainTextToken;
    }

    public function test_the_answer_is_for_the_first_school_and_lists_every_school_with_its_roles(): void
    {
        $this->withToken($this->token)->getJson('/api/v2/me/context')->assertOk()
            ->assertJsonPath('data.current_school.id', 1)
            ->assertJsonPath('data.current_school.name', 'Jacaranda Primary')
            ->assertJsonPath('data.user.roles', ['Teacher'])
            ->assertJsonPath('data.schools.0.school.id', 1)
            ->assertJsonPath('data.schools.0.roles', ['Teacher'])
            ->assertJsonPath('data.schools.1.school', ['id' => 2, 'name' => 'Baobab High School', 'short_name' => null, 'crest_url' => null])
            ->assertJsonPath('data.schools.1.roles', ['Super Admin'])
            ->assertJsonMissingPath('data.roles');
    }

    public function test_x_school_id_chooses_the_school(): void
    {
        $this->withToken($this->token)->getJson('/api/v2/me/context', ['X-School-Id' => '2'])->assertOk()
            ->assertJsonPath('data.current_school.id', 2)
            ->assertJsonPath('data.current_school.name', 'Baobab High School')
            ->assertJsonPath('data.current_school.timezone', 'Africa/Harare')
            ->assertJsonPath('data.current_school.session_year', null)
            ->assertJsonPath('data.user.roles', ['Super Admin']);

        // Not one of the user's schools.
        $this->withToken($this->token)->getJson('/api/v2/me/context', ['X-School-Id' => '3'])
            ->assertForbidden()->assertJsonPath('error', true);
    }

    public function test_a_contributor_adds_keys_and_fields_without_removing_any(): void
    {
        $before = $this->withToken($this->token)->getJson('/api/v2/me/context')->json('data');
        config(['context.contributors' => [...config('context.contributors'), AddsToContext::class]]);

        $this->withToken($this->token)->getJson('/api/v2/me/context')->assertOk()
            ->assertJsonPath('data.current_school.name', 'Jacaranda Primary')
            ->assertJsonPath('data.current_school.branding.theme_color', '#000001')
            ->assertJsonPath('data.current_school.branding.secondary_color', $before['current_school']['branding']['secondary_color'])
            ->assertJsonPath('data.feature_flags', ['new_switch' => true]);
    }

    public function test_calendar_terms_remain_ordered_and_deleted_terms_do_not_hide_an_empty_year(): void
    {
        $year = SessionYear::query()->create(['name' => 'Context calendar', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31']);
        $common = ['session_year_id' => $year->id, 'grade_from' => 0, 'grade_to' => 12];
        $later = Term::query()->create(['name' => 'Later term', 'start_date' => '2027-04-01', 'end_date' => '2027-06-30'] + $common);
        $first = Term::query()->create(['name' => 'First term', 'start_date' => '2027-01-01', 'end_date' => '2027-03-31', 'marks_lock_on' => '2027-04-02', 'reports_on' => '2027-04-05'] + $common);
        $tie = Term::query()->create(['name' => 'Same start', 'start_date' => '2027-01-01', 'end_date' => '2027-03-31'] + $common);
        $deleted = Term::query()->create(['name' => 'Deleted term', 'start_date' => '2027-07-01', 'end_date' => '2027-09-30'] + $common);
        $deleted->delete();
        Settings::query()->updateOrCreate(['type' => 'session_year'], ['message' => (string) $year->id]);

        $terms = $this->withToken($this->token)->getJson('/api/v2/me/context')->assertOk()->json('data.current_school.session_year.terms');
        $this->assertSame([$first->id, $tie->id, $later->id], array_column($terms, 'id'));
        $this->assertSame(['2027-01-01', '2027-04-02', '2027-04-05', 0, 12], [$terms[0]['start_date'], $terms[0]['marks_lock_on'], $terms[0]['reports_on'], $terms[0]['grade_from'], $terms[0]['grade_to']]);
        $first->delete();
        $tie->delete();
        $later->delete();
        $this->withToken($this->token)->getJson('/api/v2/me/context')->assertOk()
            ->assertJsonPath('data.current_school.session_year.id', $year->id)
            ->assertJsonPath('data.current_school.session_year.terms', []);
    }

    public function test_a_foreign_configured_calendar_never_exposes_another_schools_year_or_terms(): void
    {
        $year = app(CurrentSchool::class)->run(2, function (): SessionYear {
            $year = SessionYear::query()->create(['name' => 'Baobab calendar', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31']);
            Term::query()->create(['session_year_id' => $year->id, 'name' => 'Baobab term', 'start_date' => '2027-01-01', 'end_date' => '2027-03-31', 'grade_from' => 0, 'grade_to' => 12]);
            Settings::query()->updateOrCreate(['type' => 'session_year'], ['message' => (string) $year->id]);

            return $year;
        });
        Settings::query()->updateOrCreate(['type' => 'session_year'], ['message' => (string) $year->id]);
        $this->withToken($this->token)->getJson('/api/v2/me/context')->assertOk()
            ->assertJsonPath('data.current_school.id', 1)
            ->assertJsonPath('data.current_school.session_year', null);
        $this->withToken($this->token)->getJson('/api/v2/me/context', ['X-School-Id' => '2'])->assertOk()
            ->assertJsonPath('data.current_school.id', 2)
            ->assertJsonPath('data.current_school.session_year.id', $year->id)
            ->assertJsonPath('data.current_school.session_year.terms.0.name', 'Baobab term');
    }
}

/** A later stream's contributor, for the composition test. */
final class AddsToContext implements ContextContributor
{
    public function contribute(ContextScope $scope): array
    {
        return [
            'current_school' => ['branding' => ['theme_color' => '#000001']],
            'feature_flags' => ['new_switch' => true],
        ];
    }
}
