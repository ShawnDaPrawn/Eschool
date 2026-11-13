<?php

declare(strict_types=1);

namespace Tests\Feature\People;

use App\Models\People\Pools\PoolMember;
use App\Models\Platform\Role;
use App\Models\Platform\SchoolJoinInvitation;
use App\Models\Platform\SchoolMembership;
use App\Models\Staff;
use App\Models\User;
use App\Services\People\Pools\PoolAccess;
use App\Services\People\Pools\PoolMembers;
use App\Services\Platform\Identity\SchoolJoinInvitations;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class PeopleWorkspacesTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(ModuleGate::class)->change('groups', true, $this->superAdmin());
        app(ModuleGate::class)->change('people_pool', true, $this->superAdmin());
    }

    private function invitation(array $overrides = []): array
    {
        return $overrides + ['operation_id' => (string) Str::uuid(), 'kind' => 'volunteer', 'first_name' => 'Nomsa', 'last_name' => 'Reviewer', 'email' => 'pool.panel@example.test', 'initial_password' => 'TemporaryPanel2026!x', 'offers_career_talk' => 1, 'offers_job_shadow' => 0];
    }

    public function test_invitation_uses_existing_pool_rules_and_opens_the_saved_member(): void
    {
        $this->actingAs($this->superAdmin())->get('/people/alumni')->assertOk()->assertSee('No alumni or volunteers invited yet');
        $this->get('/people/alumni/invite')->assertOk()->assertSee('name="initial_password"', false)->assertSee('name="learner_id"', false);
        $this->post('/people/alumni/members', $this->invitation())->assertRedirect();
        $member = PoolMember::firstOrFail();
        $this->get('/people/alumni/members/'.$member->id)->assertOk()->assertSee('Nomsa Reviewer')->assertSee('Record a vetting decision')->assertSee('Invited')->assertDontSee('TemporaryPanel2026!x');
        $this->assertSame('invited', $member->consent_status);
    }

    public function test_invalid_invitation_does_not_flash_the_initial_password(): void
    {
        $this->actingAs($this->superAdmin())->from('/people/alumni/invite')->post('/people/alumni/members', $this->invitation(['email' => 'invalid']))
            ->assertRedirect('/people/alumni/invite')->assertSessionHasErrors('email')->assertSessionMissing('_old_input.initial_password');
    }

    public function test_business_refusal_keeps_the_invitation_draft_without_a_password(): void
    {
        $email = User::findOrFail($this->schoolA->ids['tara_user'])->email;
        $this->actingAs($this->superAdmin())->from('/people/alumni/invite')->post('/people/alumni/members', $this->invitation(['email' => $email]))
            ->assertRedirect('/people/alumni/invite')->assertSessionHasErrors('record')->assertSessionHas('_old_input.first_name', 'Nomsa')->assertSessionMissing('_old_input.initial_password');
    }

    public function test_wrong_role_cannot_open_or_write_pool_or_facilitator_directories(): void
    {
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->actingAs($teacher)->get('/people/alumni')->assertForbidden();
        $this->post('/people/alumni/members', $this->invitation())->assertForbidden();
        $this->get('/people/facilitators')->assertForbidden();
    }

    public function test_foreign_member_is_not_visible_to_school_manager(): void
    {
        $member = app(CurrentSchool::class)->run(2, fn () => PoolMember::create(['user_id' => $this->schoolB->ids['tara_user'], 'invited_by' => $this->schoolB->ids['tara_user'], 'kind' => 'volunteer', 'consent_status' => 'invited', 'vetting_status' => 'pending', 'revision' => 1]));
        $this->actingAs($this->superAdmin())->get('/people/alumni')->assertOk()->assertDontSee(self::SCHOOL_B_MARKER);
        $this->get('/people/alumni/members/'.$member->id)->assertNotFound();
        $this->post('/people/alumni/members/'.$member->id.'/vetting', ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'outcome' => 'refused', 'checks' => [], 'checked_on' => now()->toDateString()])->assertNotFound();
    }

    public function test_vetting_is_not_granted_by_pool_management_and_notes_remain_private(): void
    {
        $member = app(PoolMembers::class)->invite($this->superAdmin(), $this->invitation());
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $teacher->givePermissionTo(PoolAccess::MANAGE);
        $this->actingAs($teacher)->get('/people/alumni/members/'.$member['id'])->assertOk()->assertDontSee('Record a vetting decision');
        $this->post('/people/alumni/members/'.$member['id'].'/vetting', ['operation_id' => (string) Str::uuid(), 'revision' => $member['revision'], 'outcome' => 'refused', 'checks' => [], 'checked_on' => now()->toDateString()])->assertForbidden();
    }

    public function test_booking_entry_obeys_the_pool_module(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin)->get('/people/alumni/bookings')->assertOk()->assertSee('name="pool_member_id"', false);
        app(ModuleGate::class)->change('people_pool', false, $admin);
        $this->get('/people/alumni/bookings')->assertNotFound();
    }

    public function test_facilitator_creation_uses_the_dedicated_system_role_and_staff_invitation(): void
    {
        Queue::fake();
        $role = Role::where('name', 'Facilitator')->firstOrFail();
        $this->actingAs($this->superAdmin())->get('/staff?view=create&role=Facilitator')->assertOk()->assertSee('Add facilitator')->assertSee('Facilitator');
        $body = ['role_id' => $role->id, 'first_name' => 'Nomsa', 'last_name' => 'Facilitator', 'email' => 'facilitator.panel@example.test', 'gender' => 'Female', 'mobile' => '0821234567', 'dob' => '1990-04-12'];
        $this->post(route('staff.store'), $body)->assertOk()->assertJsonPath('error', false);
        $person = User::where('email', $body['email'])->firstOrFail();
        $this->assertTrue($person->hasRole('Facilitator'));
        $this->assertTrue(Staff::where('user_id', $person->id)->exists());
        $this->assertDatabaseHas('staff_invitations', ['school_id' => 1, 'user_id' => $person->id]);
        $this->get('/people/facilitators')->assertOk()->assertSee('Nomsa Facilitator');
        foreach ([Role::where('name', 'Super Admin')->value('id'), Role::where('name', 'Teacher')->value('id'), app(CurrentSchool::class)->run(2, fn () => Role::where('name', 'Facilitator')->value('id'))] as $forbiddenRole) {
            $this->post(route('staff.store'), array_replace($body, ['role_id' => $forbiddenRole, 'email' => 'forbidden-facilitator@example.test']))->assertJsonPath('error', true);
        }
        $this->assertFalse(User::where('email', 'forbidden-facilitator@example.test')->exists());
    }

    public function test_existing_account_facilitator_invitation_requires_the_invited_owner_to_accept(): void
    {
        Queue::fake();
        $role = Role::where('name', 'Facilitator')->firstOrFail();
        $existing = app(CurrentSchool::class)->run(2, fn () => User::findOrFail($this->schoolB->ids['tara_user']));
        $this->actingAs($this->superAdmin())->post(route('staff.store'), ['role_id' => $role->id, 'first_name' => 'Invited', 'last_name' => 'Facilitator', 'email' => $existing->email, 'gender' => 'Female', 'mobile' => '0821234567', 'dob' => '1990-04-12'])->assertOk()->assertJsonPath('error', false);
        $invitation = SchoolJoinInvitation::where('user_id', $existing->id)->firstOrFail();
        $this->assertFalse(SchoolMembership::where('school_id', 1)->where('user_id', $existing->id)->where('role', 'Facilitator')->exists());
        try {
            app(SchoolJoinInvitations::class)->accept((string) $invitation->encrypted_token, $this->superAdmin());
            $this->fail('Another account must not accept the facilitator invitation.');
        } catch (HttpExceptionInterface $error) {
            $this->assertSame(403, $error->getStatusCode());
        }
        app(SchoolJoinInvitations::class)->accept((string) $invitation->encrypted_token, $existing);
        $this->assertTrue(SchoolMembership::where('school_id', 1)->where('user_id', $existing->id)->where('role', 'Facilitator')->exists());
    }

    public function test_facilitator_directory_is_real_and_school_scoped(): void
    {
        $this->actingAs($this->superAdmin())->get('/people/facilitators')->assertOk()->assertSee('Search facilitators')->assertDontSee(self::SCHOOL_B_MARKER);
    }
}
