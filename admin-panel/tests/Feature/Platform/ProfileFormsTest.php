<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\GuardianLink;
use App\Models\Platform\ProfileFieldDefinition;
use App\Models\Platform\ProfileFieldValue;
use App\Models\Students;
use App\Models\User;
use App\Services\People\Profiles\ProfileDefinitions;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class ProfileFormsTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(GuardianService::class)->syncLegacy(Students::findOrFail($this->schoolA->ids['asha']));
    }

    private function fieldData(int $category, array $extra = []): array
    {
        return $extra + ['category_id' => $category, 'key' => 'transport_route', 'label' => 'Transport route', 'type' => 'text',
            'visible_to' => ['owner', 'guardian', 'school_admin', 'teacher'], 'editable_by' => ['guardian', 'school_admin']];
    }

    private function customField(array $extra = []): ProfileFieldDefinition
    {
        $service = app(ProfileDefinitions::class);
        $category = $service->category($this->superAdmin(), ['profile_type' => 'learner', 'key' => 'transport', 'label' => 'Transport']);

        return $service->field($this->superAdmin(), $this->fieldData($category->id, $extra));
    }

    private function ownProfile(string $who = 'asha')
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($this->schoolA->tokens[$who])->getJson('/api/v2/me/profile');
    }

    public function test_a_new_transport_field_immediately_appears_in_the_authorised_form(): void
    {
        $this->customField();
        $answer = $this->ownProfile()->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $fields = collect($answer->json('data.categories'))->pluck('fields')->flatten(1);
        $this->assertSame('Transport route', $fields->firstWhere('key', 'transport_route')['label']);
        $this->assertNull($fields->firstWhere('key', 'transport_route')['value']);
    }

    public function test_verified_guardian_writes_and_the_learner_reads_an_encrypted_value(): void
    {
        $field = $this->customField();
        User::findOrFail($this->schoolA->ids['farid_user'])->forceFill(['email_verified_at' => now()])->save();
        $this->withToken($this->schoolA->tokens['farid'])->putJson('/api/v2/people/'.$this->schoolA->ids['asha_user'].'/profile',
            ['profile_type' => 'learner', 'values' => ['transport_route' => ['text' => 'Route Seven']]])->assertOk()->assertJsonPath('data', null);
        $raw = DB::table('profile_field_values')->where('definition_id', $field->id)->value('value');
        $this->assertStringNotContainsString('Route Seven', $raw);
        $this->assertArrayNotHasKey('value', ProfileFieldValue::query()->where('definition_id', $field->id)->firstOrFail()->toArray());
        $form = $this->ownProfile()->assertOk()->json('data.categories');
        $this->assertSame('Route Seven', collect($form)->pluck('fields')->flatten(1)->firstWhere('key', 'transport_route')['value']['text']);
        $this->assertStringNotContainsString('Route Seven', json_encode(DB::table('audit_log')->get()));
    }

    public function test_profile_write_response_cache_does_not_hold_decrypted_medical_values(): void
    {
        $learner = User::findOrFail($this->schoolA->ids['asha_user']);
        $admin = $this->superAdmin();
        $token = $admin->createToken('profile-admin')->plainTextToken;
        $this->withToken($token)->withHeader('Idempotency-Key', 'profile-private-2026')->putJson('/api/v2/people/'.$learner->id.'/profile',
            ['profile_type' => 'learner', 'values' => ['medical_alerts' => ['text' => 'PRIVATE MEDICAL VALUE']]])->assertOk()->assertJsonPath('data', null);
        $this->assertStringNotContainsString('PRIVATE MEDICAL VALUE', json_encode(DB::table('idempotency_keys')->get()));
        $this->assertStringNotContainsString('PRIVATE MEDICAL VALUE', json_encode(DB::table('profile_field_values')->get()));
        $this->assertStringNotContainsString('PRIVATE MEDICAL VALUE', json_encode(DB::table('audit_log')->get()));
    }

    public function test_learner_cannot_change_the_age_used_for_guardian_approval(): void
    {
        $before = User::findOrFail($this->schoolA->ids['asha_user'])->dob;
        $this->withToken($this->schoolA->tokens['asha'])->putJson('/api/v2/me/profile', ['values' => ['dob' => ['text' => '2000-01-01']]])->assertUnprocessable();
        $this->assertSame($before, User::findOrFail($this->schoolA->ids['asha_user'])->dob);
    }

    public function test_unverified_or_ended_guardian_cannot_write_a_child_profile(): void
    {
        $this->customField();
        $path = '/api/v2/people/'.$this->schoolA->ids['asha_user'].'/profile';
        $this->withToken($this->schoolA->tokens['farid'])->putJson($path, ['profile_type' => 'learner', 'values' => ['transport_route' => ['text' => 'No']]])->assertForbidden();
        GuardianLink::query()->where('learner_id', $this->schoolA->ids['asha'])->where('guardian_user_id', $this->schoolA->ids['farid_user'])->update(['status' => 'ended']);
        $this->getJson($path.'?profile_type=learner')->assertNotFound();
        $this->assertSame(0, ProfileFieldValue::query()->count());
    }

    public function test_unrelated_guardian_wrong_school_and_teacher_configuration_are_refused(): void
    {
        $this->withToken($this->schoolA->tokens['gina'])->getJson('/api/v2/people/'.$this->schoolA->ids['asha_user'].'/profile?profile_type=learner')->assertNotFound();
        $this->app['auth']->forgetGuards();
        $this->withToken($this->schoolA->tokens['farid'])->withHeader('X-School-Id', '2')->getJson('/api/v2/me/profile')->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->defaultHeaders = [];
        $this->withToken($this->schoolA->tokens['tara'])->getJson('/api/v2/profile-fields')->assertForbidden();
    }

    public function test_assigned_teacher_sees_only_offered_fields_and_cannot_edit_official_names(): void
    {
        $answer = $this->withToken($this->schoolA->tokens['tara'])->getJson('/api/v2/people/'.$this->schoolA->ids['asha_user'].'/profile?profile_type=learner')->assertOk();
        $keys = collect($answer->json('data.categories'))->pluck('fields')->flatten(1)->pluck('key')->all();
        $this->assertContains('first_name', $keys);
        $this->assertNotContains('medical_alerts', $keys);
        $this->assertNotContains('identity_number', $keys);
        $this->assertNotContains('religion', $keys);
        $this->putJson('/api/v2/people/'.$this->schoolA->ids['asha_user'].'/profile', ['profile_type' => 'learner', 'values' => ['first_name' => ['text' => 'Changed']]])->assertUnprocessable();
    }

    public function test_teacher_does_not_see_a_learner_in_another_teachers_class(): void
    {
        $this->withToken($this->schoolA->tokens['tara'])->getJson('/api/v2/people/'.$this->schoolA->ids['cara_user'].'/profile?profile_type=learner')->assertNotFound();
    }

    public function test_role_objects_cached_at_one_school_cannot_manage_another_schools_fields(): void
    {
        $admin = $this->superAdmin();
        $admin->load('roles');
        app(CurrentSchool::class)->run(2, function () use ($admin) {
            $admin->assignRole('Teacher');
            $this->expectException(HttpException::class);
            app(ProfileDefinitions::class)->category($admin, ['profile_type' => 'learner', 'key' => 'foreign', 'label' => 'No']);
        });
    }

    public function test_an_expired_admin_role_cannot_grant_profile_access_through_an_active_teacher_membership(): void
    {
        $admin = $this->superAdmin();
        $admin->assignRole('Teacher');
        DB::table('school_memberships')->where('school_id', 1)->where('user_id', $admin->id)->where('role', 'Super Admin')
            ->update(['ends_on' => now()->subDay()->toDateString()]);
        $this->actingAs($admin)->getJson('/api/v2/profile-fields')->assertForbidden();
        $this->getJson('/api/v2/people/'.$this->schoolA->ids['asha_user'].'/profile?profile_type=learner')->assertNotFound();
        $this->postJson('/api/v2/profile-fields/categories', ['profile_type' => 'learner', 'key' => 'expired_admin', 'label' => 'No'])
            ->assertForbidden();
    }
}
