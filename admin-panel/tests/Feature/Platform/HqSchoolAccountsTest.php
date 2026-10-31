<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\AuditLog;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolAccount;
use App\Models\Platform\SchoolAccountEvent;
use App\Models\Platform\SchoolContact;
use App\Models\Platform\SchoolContract;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * HQ's record of each customer school: profile and stage, contacts, contracts
 * and a timeline (Action Plan SA.4, SA.5).
 * Specification: engineering/specs/platform/hq-school-accounts.md.
 */
class HqSchoolAccountsTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->forget();
    }

    private function staff(): User
    {
        $user = $this->superAdmin();
        PlatformStaffIdentity::query()->updateOrCreate(['user_id' => $user->id], ['active' => true]);
        PlatformAdminGrant::query()->updateOrCreate(['user_id' => $user->id], ['active' => true, 'expires_at' => now()->addHour()]);

        return $user;
    }

    private function profile(array $changes = []): array
    {
        return $changes + ['stage' => 'live', 'school_type' => 'independent', 'phase' => 'combined', 'curriculum' => 'ieb', 'province' => 'gauteng',
            'district' => 'Johannesburg North', 'emis_number' => '700123456', 'address' => '1 Jacaranda Lane', 'expected_learners' => 620, 'revision' => 0];
    }

    private function contract(array $changes = []): array
    {
        return $changes + ['starts_on' => '2027-01-01', 'ends_on' => '2027-12-31', 'renewal_on' => '2027-10-01', 'contracted_learners' => 600,
            'price_per_learner' => '24.5', 'signatory' => 'Principal N. Khumalo', 'operator_agreement_version' => 'OA-2026.1',
            'operator_agreement_signed_on' => now('Africa/Johannesburg')->toDateString(), 'data_processing_signed_on' => null, 'notes' => 'Pilot pricing'];
    }

    private function inSchool(int $school, callable $read): mixed
    {
        return app(CurrentSchool::class)->run($school, $read);
    }

    public function test_staff_record_the_profile_stage_contacts_contract_and_notes_of_a_school(): void
    {
        $this->actingAs($this->staff());
        $this->get('/saas/schools/1/account')->assertOk()->assertSee('Onboarding')->assertSee('No contacts recorded yet.')->assertDontSee('Asha');

        $this->put('/saas/schools/1/account', $this->profile())->assertRedirect('/saas/schools/1/account');
        $this->post('/saas/schools/1/contacts', ['role' => 'principal', 'name' => 'Nomsa Khumalo', 'email' => 'principal@jacaranda.example', 'phone' => '011 555 0100'])->assertRedirect();
        $this->post('/saas/schools/1/contracts', $this->contract())->assertRedirect();
        $this->post('/saas/schools/1/notes', ['body' => 'Demo went well; asked for the Afrikaans parent app.'])->assertRedirect();

        $this->inSchool(1, function () {
            $account = SchoolAccount::query()->sole();
            $this->assertSame(['live', 'gauteng', 620, 1], [$account->stage, $account->province, $account->expected_learners, $account->revision]);
            $this->assertSame(2450, SchoolContract::query()->sole()->price_per_learner_cents, 'R24.50 is stored as integer cents');
            $this->assertSame(['contact', 'contract', 'note', 'stage'], SchoolAccountEvent::query()->orderBy('kind')->pluck('kind')->all());
        });
        $this->get('/saas/schools/1/account')->assertOk()->assertSee('Nomsa Khumalo')->assertSee('R 24.50')->assertSee('OA-2026.1')
            ->assertSee('Stage changed from Onboarding to Live')->assertSee('asked for the Afrikaans parent app')
            ->assertSee('2027-10-01')->assertSee('Pilot pricing')->assertSee('Not signed');
        $this->get('/saas')->assertOk()->assertSee('Live');
        $this->assertSame(1, AuditLog::query()->where('action', 'platform_school_contract_recorded')->where('school_id', 1)->count());
        $this->assertSame(0, $this->inSchool(2, fn () => SchoolContact::query()->count() + SchoolAccountEvent::query()->count()), 'nothing reached the other school');

        // A later contract with the addendum signed shows that date.
        $this->post('/saas/schools/1/contracts', $this->contract(['starts_on' => '2028-01-01', 'ends_on' => null, 'renewal_on' => null, 'notes' => null,
            'data_processing_signed_on' => now('Africa/Johannesburg')->toDateString()]))->assertRedirect();
        $this->get('/saas/schools/1/account')->assertOk()->assertSee('Signed '.now('Africa/Johannesburg')->toDateString());
    }

    public function test_the_timeline_names_staff_who_are_not_members_of_the_school(): void
    {
        $staff = $this->staff();
        $this->assertSame(0, DB::table('school_memberships')->where('school_id', 2)->where('user_id', $staff->id)->count(), 'the operator belongs to another school');
        $this->actingAs($staff);

        $this->post('/saas/schools/2/notes', ['body' => 'Called the Baobab bursar.'])->assertRedirect();

        $name = trim($staff->first_name.' '.$staff->last_name);
        $this->get('/saas/schools/2/account')->assertOk()->assertSee($name.': Called the Baobab bursar.')->assertDontSee('System: Called the Baobab bursar.');
    }

    public function test_stale_edits_bad_values_and_one_way_contracts_are_refused(): void
    {
        $this->actingAs($this->staff());
        $this->put('/saas/schools/1/account', $this->profile())->assertRedirect();
        $this->put('/saas/schools/1/account', $this->profile(['stage' => 'at_risk']))->assertStatus(409);
        $this->put('/saas/schools/1/account', $this->profile(['stage' => 'sold', 'revision' => 1]))->assertSessionHasErrors('stage');
        $this->put('/saas/schools/1/account', $this->profile(['province' => 'atlantis', 'revision' => 1]))->assertSessionHasErrors('province');
        $this->post('/saas/schools/1/contacts', ['role' => 'principal', 'name' => 'No way to reach'])->assertSessionHasErrors('email');
        $this->post('/saas/schools/1/contracts', $this->contract(['price_per_learner' => '24.505']))->assertSessionHasErrors('price_per_learner');
        $this->post('/saas/schools/1/contracts', $this->contract(['ends_on' => '2026-12-31']))->assertSessionHasErrors('ends_on');
        $this->post('/saas/schools/1/contracts', $this->contract(['operator_agreement_signed_on' => now()->addWeek()->toDateString()]))->assertSessionHasErrors('operator_agreement_signed_on');
        $this->assertSame([1, 0, 0], $this->inSchool(1, fn () => [SchoolAccount::query()->sole()->revision, SchoolContact::query()->count(), SchoolContract::query()->count()]));
        foreach (['put' => '/saas/schools/1/contracts/1', 'delete' => '/saas/schools/1/contracts/1'] as $method => $path) {
            $this->assertContains($this->{$method}($path, $this->contract())->getStatusCode(), [404, 405], 'a recorded contract has no edit or delete route');
        }
    }

    public function test_a_contact_belongs_to_its_school_and_ids_from_another_school_are_not_found(): void
    {
        $this->actingAs($this->staff());
        $this->post('/saas/schools/2/contacts', ['role' => 'bursar', 'name' => 'Baobab Bursar', 'email' => 'bursar@baobab.example'])->assertRedirect();
        $foreign = $this->inSchool(2, fn () => (int) SchoolContact::query()->sole()->id);

        $this->put('/saas/schools/1/contacts/'.$foreign, ['role' => 'bursar', 'name' => 'Hijacked', 'email' => 'x@example.org'])->assertNotFound();
        $this->delete('/saas/schools/1/contacts/'.$foreign)->assertNotFound();
        $this->get('/saas/schools/1/account')->assertOk()->assertDontSee('Baobab Bursar');
        $this->get('/saas/schools/999999/account')->assertNotFound();
        $this->post('/saas/schools/999999/notes', ['body' => 'Nowhere'])->assertNotFound();

        $this->put('/saas/schools/2/contacts/'.$foreign, ['role' => 'bursar', 'name' => 'Baobab Finance', 'email' => 'finance@baobab.example'])->assertRedirect();
        $this->delete('/saas/schools/2/contacts/'.$foreign)->assertRedirect();
        $this->assertSame(0, $this->inSchool(2, fn () => SchoolContact::query()->count()));
    }

    public function test_school_people_and_ungranted_accounts_cannot_reach_the_record(): void
    {
        $paths = ['get' => '/saas/schools/1/account', 'put' => '/saas/schools/1/account', 'post' => '/saas/schools/1/notes'];
        // A school Super Admin without the platform identity and grant.
        $this->actingAs($this->superAdmin());
        foreach ($paths as $method => $path) {
            $this->{$method}($path, ['body' => 'Not allowed', 'stage' => 'live', 'revision' => 0])->assertForbidden();
        }
        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']));
        $this->get('/saas/schools/1/account')->assertForbidden();
        auth()->forgetGuards();
        $this->app['auth']->logout();
        $this->get('/saas/schools/1/account')->assertRedirect('/login');
        $this->assertSame(0, DB::table('school_account_events')->count());
    }
}
