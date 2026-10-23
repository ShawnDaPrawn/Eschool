<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\ProfileFieldCategory;
use App\Models\Platform\ProfileFieldDefinition;
use App\Models\Platform\ProfileFieldValue;
use App\Models\Students;
use App\Models\User;
use App\Services\People\Profiles\ProfileForms;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class ProfilePanelTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(GuardianService::class)->syncLegacy(Students::findOrFail($this->schoolA->ids['asha']));
    }

    private function learner(): User
    {
        return User::findOrFail($this->schoolA->ids['asha_user']);
    }

    public function test_learner_list_can_open_profiles_before_a_school_sets_its_date_format(): void
    {
        DB::table('settings')->where('school_id', 1)->where('type', 'date_formate')->delete();
        $response = $this->actingAs($this->superAdmin())->getJson('/students-list-data?session_year_id='.$this->schoolA->ids['session_year'])->assertOk();
        $this->assertGreaterThan(0, $response->json('total'));
        $this->assertStringContainsString('people-profiles', $response->json('rows.0.operate'));
    }

    public function test_school_admin_adds_a_transport_field_and_it_appears_in_the_panel_form_and_export(): void
    {
        $this->actingAs($this->superAdmin())->get('/profile-configuration')->assertOk()->assertSee('Profile fields');
        $this->post('/profile-configuration/categories', ['profile_type' => 'learner', 'key' => 'transport', 'label' => 'Transport'])
            ->assertRedirect('/profile-configuration?profile_type=learner');
        $category = ProfileFieldCategory::query()->where('key', 'transport')->firstOrFail();
        $this->post('/profile-configuration/definitions', ['category_id' => $category->id, 'key' => 'transport_route',
            'label' => 'Transport route', 'type' => 'text', 'visible_to' => ['owner', 'guardian', 'school_admin'],
            'editable_by' => ['school_admin'], 'options_text' => '', 'validation' => ['min' => '', 'max' => '']])->assertRedirect();
        $this->get('/people-profiles/'.$this->learner()->id.'?profile_type=learner')->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')->assertSee('Transport route')->assertDontSee('data-draft="person-profile"', false);
        $this->put('/people-profiles/'.$this->learner()->id, ['profile_type' => 'learner', 'values' => ['transport_route' => ['text' => 'Route 12']]])->assertOk();
        $csv = $this->get('/people-profiles/'.$this->learner()->id.'/export?profile_type=learner')->assertOk()->streamedContent();
        $this->assertStringContainsString('Transport route', $csv);
        $this->assertStringContainsString('Route 12', $csv);
        $this->assertSame(1, (int) DB::table('audit_log')->where('action', 'profile.exported')->sole()->school_id);
        $this->assertSame(1, (int) DB::table('audit_log')->where('action', 'profile.updated')->sole()->school_id);
    }

    public function test_exports_of_a_global_person_are_audited_in_the_school_that_was_exported(): void
    {
        $admin = $this->superAdmin();
        app(CurrentSchool::class)->run(2, fn () => $admin->assignRole('Super Admin'));
        $this->actingAs($admin)->get('/people-profiles/'.$admin->id.'/export?profile_type=staff')->assertOk();
        $this->withHeader('X-School-Id', '2')->get('/people-profiles/'.$admin->id.'/export?profile_type=staff')->assertOk();

        $rows = DB::table('audit_log')->where('action', 'profile.exported')->orderBy('id')->get();
        $this->assertSame([1, 2], $rows->pluck('school_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame([$admin->id, $admin->id], $rows->pluck('subject_id')->all());
    }

    public function test_panel_errors_never_flash_private_values_into_the_session(): void
    {
        $this->actingAs($this->superAdmin())->put('/people-profiles/'.$this->learner()->id, [
            'profile_type' => 'learner', 'values' => ['medical_alerts' => ['text' => str_repeat('PRIVATE ', 2000)]],
        ])->assertUnprocessable();
        $this->assertStringNotContainsString('PRIVATE', json_encode(session()->all()));
        $this->put('/people-profiles/'.$this->learner()->id, ['profile_type' => 'learner',
            'values' => ['medical_alerts' => ['text' => 'PRIVATE'], 'first_name' => ['text' => str_repeat('A', 129)]]])->assertUnprocessable();
        $this->assertStringNotContainsString('PRIVATE', json_encode(session()->all()));
        $this->assertSame(0, ProfileFieldValue::query()->count());
    }

    public function test_export_without_sensitive_export_permission_does_not_load_or_decrypt_health_values(): void
    {
        $field = ProfileFieldDefinition::query()->where('profile_type', 'learner')->where('key', 'medical_alerts')->firstOrFail();
        ProfileFieldValue::query()->create(['user_id' => $this->learner()->id, 'definition_id' => $field->id, 'value' => 'not-valid-ciphertext', 'encrypted' => true]);
        $response = $this->actingAs(User::findOrFail($this->schoolA->ids['farid_user']))
            ->get('/people-profiles/'.$this->learner()->id.'/export?profile_type=learner')->assertOk();
        $this->assertStringNotContainsString('medical_alerts', $response->streamedContent());
        $this->assertStringNotContainsString('not-valid-ciphertext', $response->streamedContent());
    }

    public function test_an_authorised_export_includes_sensitive_values_and_neutralises_spreadsheet_formulas(): void
    {
        app(ProfileForms::class)->save($this->superAdmin(), $this->learner(), 'learner', ['medical_alerts' => '=1+1']);
        $response = $this->actingAs($this->superAdmin())->get('/people-profiles/'.$this->learner()->id.'/export?profile_type=learner')->assertOk();
        $this->assertStringContainsString("'=1+1", $response->streamedContent());
        $this->assertStringNotContainsString('=1+1', json_encode(DB::table('audit_log')->get()));
    }

    public function test_expired_admin_export_rights_cannot_decrypt_sensitive_values_on_an_active_teacher_profile(): void
    {
        $admin = $this->superAdmin();
        $admin->assignRole('Teacher');
        $field = ProfileFieldDefinition::query()->where('profile_type', 'teacher')->where('key', 'identity_number')->firstOrFail();
        ProfileFieldValue::query()->create(['user_id' => $admin->id, 'definition_id' => $field->id, 'value' => 'not-valid-ciphertext', 'encrypted' => true]);
        DB::table('school_memberships')->where('school_id', 1)->where('user_id', $admin->id)->where('role', 'Super Admin')
            ->update(['ends_on' => now()->subDay()->toDateString()]);

        $response = $this->actingAs($admin)->get('/people-profiles/'.$admin->id.'/export?profile_type=teacher')->assertOk();
        $this->assertStringNotContainsString('identity_number', $response->streamedContent());
    }

    public function test_wrong_school_owner_and_role_cannot_use_panel_configuration_or_forms(): void
    {
        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']))->get('/profile-configuration')->assertForbidden();
        $this->get('/profile-configuration?field='.ProfileFieldDefinition::withoutGlobalScopes()->where('school_id', 2)->value('id'))->assertForbidden();
        $this->actingAs($this->superAdmin())->get('/people-profiles/'.$this->schoolB->ids['asha_user'].'?profile_type=learner')->assertNotFound();
        $this->actingAs(User::findOrFail($this->schoolA->ids['gina_user']))->get('/people-profiles/'.$this->learner()->id.'/export?profile_type=learner')->assertNotFound();
    }

    public function test_context_has_metadata_but_never_queries_or_contains_profile_values(): void
    {
        app(ProfileForms::class)->save($this->superAdmin(), $this->learner(), 'learner', ['medical_alerts' => 'PRIVATE CONTEXT VALUE']);
        DB::flushQueryLog();
        DB::enableQueryLog();
        $answer = $this->withToken($this->schoolA->tokens['asha'])->getJson('/api/v2/me/context')->assertOk();
        DB::disableQueryLog();
        $this->assertNotEmpty($answer->json('data.profile_fields'));
        $this->assertSame(['learner'], collect($answer->json('data.profile_fields'))->pluck('profile_type')->unique()->all());
        $this->assertStringNotContainsString('PRIVATE CONTEXT VALUE', $answer->getContent());
        $this->assertStringNotContainsString('profile_field_values', json_encode(DB::getQueryLog()));
    }
}
