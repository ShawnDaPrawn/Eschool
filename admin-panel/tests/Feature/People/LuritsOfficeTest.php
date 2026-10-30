<?php

declare(strict_types=1);

namespace Tests\Feature\People;

use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Identity\LuritsNumbers;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * LURITS numbers in the school office (1.0e) and claims for Navigate Ed
 * support in HQ (1.0f). engineering/specs/platform/lurits.md.
 */
class LuritsOfficeTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
    }

    private function hq(string $role): User
    {
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => ucfirst($role), 'last_name' => 'Operator', 'email' => $role.'@platform.example', 'mobile' => '', 'password' => 'Synthetic-lurits-password-582!', 'status' => 1]));
        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => $role, 'expires_at' => now()->addHour()]);

        return $user;
    }

    public function test_the_office_records_missing_numbers_and_never_learns_who_holds_one(): void
    {
        $admin = $this->superAdmin();
        // Ben at School B holds 8800000002.
        app(CurrentSchool::class)->run(2, fn () => app(LuritsNumbers::class)->record(Students::query()->findOrFail($this->schoolB->ids['ben']), '8800000002', 'sasams_import'));

        $page = $this->actingAs($admin)->get('/learners/lurits')->assertOk()->assertSee('0 of 3 learners have a LURITS number.')->assertSee('GR-ASHA');
        $page->assertDontSee(self::SCHOOL_B_MARKER);

        $this->post('/learners/lurits', ['admission_no' => 'GR-ASHA', 'lurits' => '8800 000 001'])->assertRedirect('/learners/lurits')->assertSessionHas('success');
        $this->assertSame('8800000001', app(LuritsNumbers::class)->forLearner(Students::query()->findOrFail($this->schoolA->ids['asha'])));
        $this->post('/learners/lurits', ['admission_no' => 'GR-ASHA', 'lurits' => '8800000001'])->assertSessionHas('success', 'GR-ASHA already has this LURITS number.');

        // Ben's number, claimed for Cara: not written, and School B is never named.
        $this->post('/learners/lurits', ['admission_no' => 'GR-CARA', 'lurits' => '8800000002'])->assertSessionHas('warning');
        $this->assertNull(app(LuritsNumbers::class)->forLearner(Students::query()->findOrFail($this->schoolA->ids['cara'])));
        $this->get('/learners/lurits')->assertSee('1 of 3 learners have a LURITS number.')->assertSee('Numbers waiting for Navigate Ed support')
            ->assertSee('GR-CARA')->assertDontSee(self::SCHOOL_B_MARKER);

        $this->post('/learners/lurits', ['admission_no' => 'NOPE', 'lurits' => '8800000003'])->assertSessionHasErrors('admission_no');
        $this->post('/learners/lurits', ['admission_no' => 'GR-BEN', 'lurits' => '12'])->assertSessionHasErrors('lurits');
        // School B's learner is not found from School A.
        $this->post('/learners/lurits', ['admission_no' => 'GR-BAOBAB-ASHA', 'lurits' => '8800000004'])->assertSessionHasErrors('admission_no');
    }

    public function test_staff_without_learner_editing_cannot_use_it(): void
    {
        $this->actingAs(User::query()->findOrFail($this->schoolA->ids['tara_user']));

        $this->get('/learners/lurits')->assertForbidden();
        $this->post('/learners/lurits', ['admission_no' => 'GR-ASHA', 'lurits' => '8800000001'])->assertForbidden();
        $this->assertSame(0, DB::table('learner_identifiers')->whereNotNull('lurits_hash')->count());
    }

    public function test_an_archived_claimant_does_not_break_the_offices_open_claims_page(): void
    {
        app(CurrentSchool::class)->run(2, fn () => app(LuritsNumbers::class)->record(Students::query()->findOrFail($this->schoolB->ids['ben']), '8800000002', 'sasams_import'));
        app(LuritsNumbers::class)->record(Students::query()->findOrFail($this->schoolA->ids['cara']), '8800000002', 'school_entry');
        DB::table('students')->where('id', $this->schoolA->ids['cara'])->update(['deleted_at' => now()]);

        $this->actingAs($this->superAdmin())->get('/learners/lurits')->assertOk()->assertSee('Numbers waiting for Navigate Ed support')
            ->assertDontSee(self::SCHOOL_B_MARKER)->assertDontSee('GR-CARA');
    }

    public function test_support_sees_claims_by_school_only_and_closes_them(): void
    {
        app(CurrentSchool::class)->run(2, fn () => app(LuritsNumbers::class)->record(Students::query()->findOrFail($this->schoolB->ids['ben']), '8800000002', 'sasams_import'));
        app(CurrentSchool::class)->run(1, fn () => app(LuritsNumbers::class)->record(Students::query()->findOrFail($this->schoolA->ids['cara']), '8800000002', 'school_entry'));
        app(CurrentSchool::class)->forget();
        $claim = (int) DB::table('lurits_conflicts')->value('id');

        $page = $this->actingAs($this->hq('support'))->get('/saas/lurits')->assertOk()->assertSee('Jacaranda')->assertSee('Baobab High')->assertSee('school_entry');
        foreach (['Cara', 'Ben', '8800000002', 'GR-CARA'] as $private) {
            $page->assertDontSee($private);
        }
        $this->post('/saas/lurits/'.$claim.'/close', ['outcome' => 'corrected'])->assertRedirect('/saas/lurits');
        $this->assertSame(['resolved', 'corrected'], [DB::table('lurits_conflicts')->value('status'), DB::table('lurits_conflicts')->value('resolution')]);
        $this->post('/saas/lurits/'.$claim.'/close', ['outcome' => 'rejected'])->assertStatus(409);
        $this->assertTrue(DB::table('audit_log')->where('action', 'platform_lurits_claim_closed')->exists());
        $this->assertSame(0, app(CurrentSchool::class)->run(1, fn () => SchoolTable::query('lurits_conflicts')->where('status', 'open')->count()));

        $this->actingAs($this->hq('sales'))->get('/saas/lurits')->assertForbidden();
    }
}
