<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\AuditLog;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformInvoice;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolGroup;
use App\Models\Platform\SchoolGroupAdmin;
use App\Models\Platform\SchoolGroupInvoice;
use App\Models\Platform\SchoolGroupInvoiceLine;
use App\Models\Platform\SchoolGroupMember;
use App\Models\Platform\SchoolGroupPayment;
use App\Models\User;
use App\Services\Platform\Groups\SchoolGroupAccess;
use App\Services\Platform\Groups\SchoolGroupBilling;
use App\Services\Platform\Groups\SchoolGroupDashboard;
use App\Services\Platform\Groups\SchoolGroups;
use App\Services\Platform\Groups\SmallNumbers;
use App\Services\Platform\Saas\PlatformBilling;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\PassesPlatformTwoFactor;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * School groups (Action Plan 5.7, SA.18): HQ creates a group and invites
 * schools, each school agrees in its own panel, group administrators see
 * counts and shares across the group's current schools only, with small
 * numbers hidden, and a group invoice rolls up the schools' own monthly
 * lines without ever billing a school's month twice.
 * engineering/specs/platform/school-groups.md.
 */
class SchoolGroupsTest extends TestCase
{
    use PassesPlatformTwoFactor;
    use UsesTwoSchools;

    /** The demo schools' people: none may appear on a group or HQ group page. */
    private const PEOPLE = ['Asha', 'Ben', 'Cara', 'Farid', 'Mona', 'Gina', 'Theo', 'GR-', '@example.com'];

    private int $staffCount = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->forget();
    }

    private function staff(string $role): User
    {
        $this->staffCount++;
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => ucfirst($role), 'last_name' => 'Operator', 'email' => $role.$this->staffCount.'@groups.example',
            'mobile' => '', 'password' => 'Synthetic-groups-password-482!', 'status' => 1]));
        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => $role, 'expires_at' => now()->addYear()]);

        return $user;
    }

    /** An account with no school role at all, for a group's owner. */
    private function person(string $name): User
    {
        return app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => $name, 'last_name' => 'Owner', 'email' => strtolower($name).'@owner.example',
            'mobile' => '', 'password' => 'Synthetic-owner-password-913!', 'status' => 1]));
    }

    private function schoolBAdmin(): User
    {
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => 'Baobab', 'last_name' => 'Principal', 'email' => 'baobab-principal@groups.example',
            'mobile' => '', 'password' => 'Synthetic-groups-password-482!', 'status' => 1]));
        app(CurrentSchool::class)->run(2, fn () => $user->assignRole('Super Admin'));

        return $user;
    }

    private function teacherA(): User
    {
        return User::query()->withoutGlobalScopes()->findOrFail($this->schoolA->ids['tara_user']);
    }

    /** A group with School A (and School B when asked) in it, agreed through the services. */
    private function group(User $ops, bool $withB = false, string $name = 'Synthetic Schools Trust'): SchoolGroup
    {
        $groups = app(SchoolGroups::class);
        $group = $groups->create($ops, ['name' => $name]);
        $a = $groups->invite($ops, $group->id, ['school_id' => 1]);
        app(CurrentSchool::class)->run(1, fn () => $groups->agree($this->superAdmin(), $a->id));
        if ($withB) {
            $admin = $this->schoolBAdmin();
            $b = $groups->invite($ops, $group->id, ['school_id' => 2]);
            app(CurrentSchool::class)->run(2, fn () => $groups->agree($admin, $b->id));
        }

        return $group;
    }

    private function member(int $school): SchoolGroupMember
    {
        return SchoolGroupMember::acrossSchools()->where('school_id', $school)->latest('id')->firstOrFail();
    }

    private function grantAdmin(User $ops, SchoolGroup $group, User $person): SchoolGroupAdmin
    {
        return app(SchoolGroups::class)->addAdmin($ops, $group->id, ['email' => $person->email, 'reason' => 'Synthetic owner of the trust']);
    }

    private function month(): string
    {
        return substr(PlatformBilling::today(), 0, 7);
    }

    private function price(int $cents): void
    {
        DB::table('platform_prices')->insert(['package_id' => DB::table('platform_packages')->where('package_key', 'legacy')->value('id'), 'kind' => 'base',
            'product_module' => null, 'amount_cents' => $cents, 'effective_from' => '2026-01-01', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function ownInvoice(int $school): PlatformInvoice
    {
        return PlatformInvoice::acrossSchools()->where('school_id', $school)->where('slot', $this->month())->sole();
    }

    private function assertNoPerson(string $html): void
    {
        foreach (self::PEOPLE as $marker) {
            $this->assertStringNotContainsString($marker, $html, $marker.' must not appear');
        }
    }

    public function test_hq_creates_a_group_and_a_school_joins_only_when_its_super_admin_agrees(): void
    {
        $ops = $this->staff('operations');
        $this->actingAsHq($ops)->post('/saas/groups', ['name' => 'Synthetic Schools Trust'])->assertRedirect();
        $group = SchoolGroup::query()->sole();
        $this->post('/saas/groups/'.$group->id.'/schools', ['school_id' => 1, 'note' => 'Your owner asked us to set this up.'])->assertRedirect();
        $this->post('/saas/groups/'.$group->id.'/schools', ['school_id' => 2])->assertRedirect();
        // One open membership per school: inviting again, here or to another group, is refused.
        $this->post('/saas/groups/'.$group->id.'/schools', ['school_id' => 1])->assertSessionHasErrors('school_id');
        $this->assertSame(2, SchoolGroupMember::acrossSchools()->count());
        $this->get('/saas/groups/'.$group->id)->assertOk()->assertSee('Invited: waiting for the school')->assertSee('No school is in the group yet.');

        // Nothing is shared while the invitation waits.
        $this->assertSame([], app(SchoolGroupDashboard::class)->read($group->id)['schools']);

        // School A's Super Admin reads exactly what the group will see, then agrees.
        $admin = $this->superAdmin();
        $memberA = $this->member(1);
        $this->signInAs($admin)->get('/school-groups')->assertOk()->assertSee('Join Synthetic Schools Trust?')
            ->assertSee('never see a learner', false)->assertSee('Your owner asked us to set this up.');
        $this->post('/school-groups/'.$memberA->id.'/agree')->assertRedirect('/school-groups');
        $memberA->refresh();
        $this->assertSame([SchoolGroupMember::ACTIVE, 1, $admin->id, '2026-11'], [$memberA->status, $memberA->open_key, (int) $memberA->decided_by, $memberA->agreement_version]);
        $this->assertSame(SchoolGroups::agreementHash('Synthetic Schools Trust'), $memberA->agreement_hash);
        $this->assertTrue(AuditLog::query()->where('action', 'platform_school_group_joined')->where('school_id', 1)->exists());
        // Agreeing twice is refused.
        $this->post('/school-groups/'.$memberA->id.'/agree')->assertStatus(409);

        // School B's Super Admin declines: nothing about School B is shared, and it may be invited again later.
        $bAdmin = $this->schoolBAdmin();
        $memberB = $this->member(2);
        $this->signInAs($bAdmin)->post('/school-groups/'.$memberB->id.'/decline')->assertRedirect('/school-groups');
        $this->assertSame([SchoolGroupMember::DECLINED, null], [$memberB->refresh()->status, $memberB->open_key]);

        $dashboard = app(SchoolGroupDashboard::class)->read($group->id);
        $this->assertSame([1], array_column($dashboard['schools'], 'id'));
        $this->signInAs($ops)->get('/saas/groups/'.$group->id)->assertOk()->assertSee('In the group')->assertSee('Declined by the school');
        $this->post('/saas/groups/'.$group->id.'/schools', ['school_id' => 2])->assertRedirect()->assertSessionHasNoErrors();
    }

    public function test_a_group_administrator_sees_counts_across_the_current_schools_and_never_a_person(): void
    {
        config(['school_groups.min_count' => 1]);
        $ops = $this->staff('operations');
        $group = $this->group($ops, true);
        $owner = $this->person('Olwethu');
        $this->grantAdmin($ops, $group, $owner);
        // School A: one learner active in the app this week.
        DB::table('learner_activity_days')->insert(['school_id' => 1, 'learner_id' => $this->schoolA->ids['asha'], 'grade_level_id' => DB::table('grade_levels')->value('id'),
            'session_year_id' => $this->schoolA->ids['session_year'], 'on_date' => now('Africa/Johannesburg')->toDateString()]);

        $dashboard = app(SchoolGroupDashboard::class)->read($group->id);
        // Listed by name: Baobab High, then the demo school.
        $this->assertSame([2, 1], array_column($dashboard['schools'], 'id'));
        $a = $dashboard['schools'][1];
        $this->assertSame(3, $a['learners']['value']);
        $this->assertSame(['count' => 1, 'of' => 3, 'percent_bp' => 3333, 'hidden' => false], $a['active_learners']);
        $this->assertSame(0, $dashboard['schools'][0]['active_learners']['count']);
        $this->assertSame(['schools' => 2, 'learners' => 6], ['schools' => $dashboard['totals']['schools'], 'learners' => $dashboard['totals']['learners']['value']]);
        $this->assertSame(1, $dashboard['totals']['active_learners']['count']);

        // The owner has no school role: signing in takes them to the group, and the page shows the two schools' figures only.
        $this->post('/logout');
        $this->post('/login', ['email' => $owner->email, 'password' => 'Synthetic-owner-password-913!'])->assertRedirect(route('group.index'));
        $this->get('/group')->assertRedirect(route('group.show', $group->id));
        $page = $this->get('/group/'.$group->id)->assertOk()->assertSee('Synthetic Schools Trust')->assertSee('Baobab High')->assertSee('33.3% (1 of 3)')->getContent();
        $this->assertNoPerson($page);
        $this->assertStringNotContainsString('people-profiles', $page);
        $this->assertStringNotContainsString('/students', $page);
    }

    public function test_a_school_outside_the_group_is_never_visible(): void
    {
        config(['school_groups.min_count' => 1]);
        $ops = $this->staff('operations');
        $group = $this->group($ops);
        $other = app(SchoolGroups::class)->create($ops, ['name' => 'Synthetic Other Trust']);
        $owner = $this->person('Olwethu');
        $this->grantAdmin($ops, $group, $owner);
        // School B is invited to the group but has not answered: still invisible.
        app(SchoolGroups::class)->invite($ops, $group->id, ['school_id' => 2]);

        $page = $this->signInAs($owner)->get('/group/'.$group->id)->assertOk()->getContent();
        $this->assertStringNotContainsString(self::SCHOOL_B_MARKER, $page);
        $this->assertSame([1], array_column(app(SchoolGroupDashboard::class)->read($group->id)['schools'], 'id'));
        // Another group, or one that does not exist, is refused with the same answer.
        $this->get('/group/'.$other->id)->assertForbidden();
        $this->get('/group/999999')->assertForbidden();
        // A suspended school drops out of the figures.
        DB::table('schools')->where('id', 1)->update(['status' => 'suspended']);
        $this->assertSame([], app(SchoolGroupDashboard::class)->read($group->id)['schools']);
    }

    public function test_wrong_roles_are_refused_and_nothing_is_written(): void
    {
        $ops = $this->staff('operations');
        $group = $this->group($ops);
        $member = $this->member(1);
        $before = [SchoolGroup::query()->count(), SchoolGroupMember::acrossSchools()->count(), SchoolGroupAdmin::query()->count(), SchoolGroupInvoice::query()->count()];

        foreach (['sales', 'content', 'support'] as $role) {
            $staff = $this->staff($role);
            $this->signInAs($staff)->get('/saas/groups')->assertForbidden();
            $this->get('/saas/groups/'.$group->id)->assertForbidden();
            $this->post('/saas/groups', ['name' => 'Synthetic '.$role])->assertForbidden();
            $this->post('/saas/groups/'.$group->id.'/schools', ['school_id' => 2])->assertForbidden();
            $this->post('/saas/groups/'.$group->id.'/invoices', ['period' => $this->month()])->assertForbidden();
        }
        // Finance sees groups and bills them, but does not manage them; Operations manages them but does not bill.
        $finance = $this->staff('finance');
        $this->signInAs($finance)->get('/saas/groups/'.$group->id)->assertOk()->assertDontSee('Invite a school');
        $this->post('/saas/groups/'.$group->id.'/schools', ['school_id' => 2])->assertForbidden();
        $this->post('/saas/groups/'.$group->id.'/admins', ['email' => 'olwethu@owner.example', 'reason' => 'Synthetic owner of the trust'])->assertForbidden();
        $this->signInAs($ops)->post('/saas/groups/'.$group->id.'/invoices', ['period' => $this->month()])->assertForbidden();
        $this->put('/saas/groups/'.$group->id.'/schools/'.$member->id.'/billing', ['billing' => 'group'])->assertForbidden();
        $this->expectsForbidden(fn () => app(SchoolGroupBilling::class)->compose($ops, $group->id, ['period' => $this->month()]));
        $this->expectsForbidden(fn () => app(SchoolGroups::class)->create($finance, ['name' => 'Synthetic finance group']));

        // School accounts never reach /saas.
        foreach ([$this->superAdmin(), $this->teacherA()] as $schoolUser) {
            $this->signInAs($schoolUser)->get('/saas/groups')->assertStatus(403);
            $this->post('/saas/groups', ['name' => 'Synthetic school group'])->assertStatus(403);
        }
        // A teacher is not the school's Super Admin: no answer, no leaving.
        $this->signInAs($this->teacherA())->get('/school-groups')->assertForbidden();
        $this->post('/school-groups/'.$member->id.'/leave')->assertForbidden();
        // Someone who is not a group administrator sees no group page.
        $this->get('/group/'.$group->id)->assertForbidden();
        $this->signInAs($this->person('Nobody'))->get('/group')->assertForbidden();

        $this->assertSame($before, [SchoolGroup::query()->count(), SchoolGroupMember::acrossSchools()->count(), SchoolGroupAdmin::query()->count(), SchoolGroupInvoice::query()->count()]);
        $this->assertSame(SchoolGroupMember::ACTIVE, $member->refresh()->status);
    }

    public function test_one_school_cannot_answer_or_leave_for_another(): void
    {
        $ops = $this->staff('operations');
        $group = $this->group($ops);
        $memberA = $this->member(1);
        $bAdmin = $this->schoolBAdmin();
        app(SchoolGroups::class)->invite($ops, $group->id, ['school_id' => 2]);
        // School B's Super Admin uses School A's ids: not found, and School A stays in the group.
        $this->signInAs($bAdmin)->get('/school-groups')->assertOk()->assertDontSee('Joined');
        $this->post('/school-groups/'.$memberA->id.'/leave')->assertNotFound();
        $this->post('/school-groups/'.$memberA->id.'/agree')->assertNotFound();
        $this->assertSame(SchoolGroupMember::ACTIVE, $memberA->refresh()->status);
        // HQ through another group's URL: not found.
        $other = app(SchoolGroups::class)->create($ops, ['name' => 'Synthetic Other Trust']);
        $this->signInAs($ops)->post('/saas/groups/'.$other->id.'/schools/'.$memberA->id.'/end', ['reason' => 'Synthetic wrong group'])->assertNotFound();
        $this->assertSame(SchoolGroupMember::ACTIVE, $memberA->refresh()->status);
    }

    public function test_a_group_administrator_never_opens_a_learner_record(): void
    {
        config(['school_groups.min_count' => 1]);
        $ops = $this->staff('operations');
        $group = $this->group($ops, true);
        $owner = $this->person('Olwethu');
        $this->grantAdmin($ops, $group, $owner);
        $learner = $this->schoolA->ids['asha_user'];

        // A group administrator who teaches at School A sees the group page; it links to no record.
        $tara = $this->teacherA();
        $this->grantAdmin($ops, $group, $tara);
        $this->assertSame([$group->id => ['group.dashboard']], app(SchoolGroupAccess::class)->groups($tara));
        $this->signInAs($tara)->get('/group/'.$group->id)->assertOk()->assertDontSee('people-profiles');

        // In the panel: no school role, so no school, so no record.
        $this->signInAs($owner);
        $this->assertNotSame(200, $this->get('/people-profiles/'.$learner)->getStatusCode());
        $this->assertNotSame(200, $this->get('/students')->getStatusCode());
        // In the app API: refused, with or without a school chosen.
        $token = $owner->createToken('group-app')->plainTextToken;
        $this->newBrowserSession();
        $this->withToken($token)->getJson('/api/v2/academics/timetable/week?learner_id='.$this->schoolA->ids['asha'])->assertForbidden();
        $this->withToken($token)->withHeader('X-School-Id', '1')->getJson('/api/v2/academics/timetable/week?learner_id='.$this->schoolA->ids['asha'])->assertForbidden();

        // Tara reaches School A through her teaching role only, never School B.
        $this->withToken($this->schoolA->tokens['tara'])->withHeader('X-School-Id', '2')->getJson('/api/v2/academics/timetable/week?learner_id='.$this->schoolB->ids['cara'])->assertForbidden();
        // The grant gave Tara nothing at School B: no membership, role or permission there.
        $this->assertSame(0, DB::table('school_memberships')->where('school_id', 2)->where('user_id', $tara->id)->count());
        $this->assertSame(0, DB::table('model_has_roles')->where('school_id', 2)->where('model_id', $tara->id)->count());

        // Platform staff are never group administrators.
        $this->expectsValidation(fn () => app(SchoolGroups::class)->addAdmin($ops, $group->id, ['email' => $ops->email, 'reason' => 'Synthetic staff try']), 'email');
    }

    public function test_a_school_that_leaves_drops_out_of_the_groups_view_at_once(): void
    {
        config(['school_groups.min_count' => 1]);
        $ops = $this->staff('operations');
        $group = $this->group($ops, true);
        $owner = $this->person('Olwethu');
        $grant = $this->grantAdmin($ops, $group, $owner);
        $this->signInAs($owner)->get('/group/'.$group->id)->assertOk()->assertSee('Baobab High');

        // School B's Super Admin leaves: the very next request no longer shows it.
        $bAdmin = User::query()->withoutGlobalScopes()->where('email', 'baobab-principal@groups.example')->sole();
        $this->signInAs($bAdmin)->post('/school-groups/'.$this->member(2)->id.'/leave', ['reason' => 'Synthetic change of owner'])->assertRedirect('/school-groups');
        $this->assertSame([SchoolGroupMember::LEFT, 'school', null], [$this->member(2)->status, $this->member(2)->ended_side, $this->member(2)->open_key]);
        $this->signInAs($owner)->get('/group/'.$group->id)->assertOk()->assertDontSee(self::SCHOOL_B_MARKER);

        // HQ removes School A: nothing left to see.
        $this->signInAs($ops)->post('/saas/groups/'.$group->id.'/schools/'.$this->member(1)->id.'/end', ['reason' => 'Synthetic end of the contract'])->assertRedirect();
        $this->assertSame([SchoolGroupMember::REMOVED, 'hq'], [$this->member(1)->status, $this->member(1)->ended_side]);
        $this->assertSame([], app(SchoolGroupDashboard::class)->read($group->id)['schools']);
        $this->signInAs($owner)->get('/group/'.$group->id)->assertOk()->assertSee('No school is in the group yet');
        $this->assertTrue(AuditLog::query()->where('action', 'platform_school_group_left')->where('school_id', 2)->exists());

        // A revoked administrator is refused on the next request.
        app(SchoolGroups::class)->revokeAdmin($ops, $group->id, $grant->id, ['reason' => 'Synthetic owner sold the trust']);
        $this->get('/group/'.$group->id)->assertForbidden();
        $this->assertSame([], app(SchoolGroupAccess::class)->groups($owner));
    }

    public function test_small_numbers_are_hidden_per_school_and_in_the_groups_totals(): void
    {
        $k = new SmallNumbers(10);
        $this->assertSame(['value' => 0, 'hidden' => false], $k->count(0));
        $this->assertSame(['value' => null, 'hidden' => true], $k->count(9));
        $this->assertSame(['value' => 10, 'hidden' => false], $k->count(10));
        $this->assertSame(['count' => 200, 'of' => 400, 'percent_bp' => 5000, 'hidden' => false], $k->share(200, 400));
        // "All but five" is hidden as well as "five"; so is any share of a base under k.
        $this->assertTrue($k->share(395, 400)['hidden']);
        $this->assertTrue($k->share(5, 400)['hidden']);
        $this->assertSame(['count' => null, 'of' => null, 'percent_bp' => null, 'hidden' => true], $k->share(4, 8));
        $this->assertSame(['count' => 0, 'of' => 400, 'percent_bp' => 0, 'hidden' => false], $k->share(0, 400));
        $this->assertSame(['count' => 400, 'of' => 400, 'percent_bp' => 10000, 'hidden' => false], $k->share(400, 400));

        $ops = $this->staff('operations');
        $group = $this->group($ops, true);
        $owner = $this->person('Olwethu');
        $this->grantAdmin($ops, $group, $owner);
        // Each demo school has three learners. At the default of 10 every figure is hidden, and so is the group's six.
        $dashboard = app(SchoolGroupDashboard::class)->read($group->id);
        $this->assertSame(10, $dashboard['k']);
        foreach ($dashboard['schools'] as $row) {
            $this->assertSame(['value' => null, 'hidden' => true], $row['learners']);
            $this->assertTrue($row['guardian_reach']['hidden']);
            $this->assertTrue($row['attendance']['hidden']);
        }
        $this->assertTrue($dashboard['totals']['learners']['hidden']);
        $page = $this->signInAs($owner)->get('/group/'.$group->id)->assertOk()->assertSee('Fewer than 10')->assertDontSee('>3<', false)->getContent();
        $this->assertNoPerson($page);

        // At 4 each school's 3 is hidden; the group's 6 would reveal them by subtraction, so it is hidden too.
        config(['school_groups.min_count' => 4]);
        $dashboard = app(SchoolGroupDashboard::class)->read($group->id);
        $this->assertSame([true, true], array_map(fn (array $r): bool => $r['learners']['hidden'], $dashboard['schools']));
        $this->assertSame(['value' => null, 'hidden' => true], $dashboard['totals']['learners']);
        // At 3 nothing about the counts is small: each 3 and the total 6 are shown.
        config(['school_groups.min_count' => 3]);
        $dashboard = app(SchoolGroupDashboard::class)->read($group->id);
        $this->assertSame([3, 3, 6], [$dashboard['schools'][0]['learners']['value'], $dashboard['schools'][1]['learners']['value'], $dashboard['totals']['learners']['value']]);
        // HQ sees the same suppressed figures, never more.
        config(['school_groups.min_count' => 10]);
        $hq = $this->signInAs($ops)->get('/saas/groups/'.$group->id)->assertOk()->assertSee('Fewer than 10')->getContent();
        $this->assertNoPerson($hq);
    }

    public function test_a_group_invoice_rolls_up_the_schools_lines_and_no_month_is_billed_twice(): void
    {
        $ops = $this->staff('operations');
        $finance = $this->staff('finance');
        $group = $this->group($ops, true);
        $this->price(5000);
        $groups = app(SchoolGroups::class);
        foreach ([1, 2] as $school) {
            $groups->setBilling($finance, $group->id, $this->member($school)->id, ['billing' => 'group']);
        }
        $billing = app(PlatformBilling::class);
        // The month's run makes both schools' own drafts; issuing the month leaves them, as they are billed through the group.
        $billing->run(['period' => $this->month()], $finance);
        $this->assertSame(['issued' => 0, 'left' => 2, 'skipped' => 0, 'changed' => 0], $billing->issueMonth(['period' => $this->month()], $finance));
        $this->signInAs($finance)->post('/saas/billing/schools/1/invoices/'.$this->ownInvoice(1)->id.'/issue')->assertSessionHasErrors('invoice');
        $this->assertSame(['draft', 'draft'], [$this->ownInvoice(1)->status, $this->ownInvoice(2)->status]);

        // The group's draft copies each school's own lines.
        $this->post('/saas/groups/'.$group->id.'/invoices', ['period' => $this->month()])->assertRedirect();
        $invoice = SchoolGroupInvoice::query()->sole();
        $own = [$this->ownInvoice(1), $this->ownInvoice(2)];
        $this->assertSame([2, $own[0]->learners + $own[1]->learners], [$invoice->schools, $invoice->learners]);
        $this->assertSame($own[0]->total_cents + $own[1]->total_cents, $invoice->total_cents);
        $this->assertSame($own[0]->vat_cents + $own[1]->vat_cents, $invoice->vat_cents);
        $this->assertSame(2, SchoolGroupInvoiceLine::acrossSchools()->where('group_invoice_id', $invoice->id)->count());
        $this->get('/saas/groups/'.$group->id.'/invoices/'.$invoice->id)->assertOk()->assertSee('Baobab High')->assertSee('Draft group invoice');
        // A second draft for the month is refused; the school's own draft cannot be issued while the group draft holds it.
        $this->post('/saas/groups/'.$group->id.'/invoices', ['period' => $this->month()])->assertSessionHasErrors('period');
        $this->post('/saas/billing/schools/2/invoices/'.$own[1]->id.'/issue')->assertSessionHasErrors('invoice');

        // Issue: one reference for the group, and each school's month becomes "grouped".
        $this->post('/saas/groups/'.$group->id.'/invoices/'.$invoice->id.'/issue')->assertRedirect();
        $invoice->refresh();
        $this->assertSame([SchoolGroupInvoice::ISSUED, 'NE-'.substr($this->month(), 0, 4).'-0001'], [$invoice->status, $invoice->reference]);
        $this->assertSame([PlatformInvoice::GROUPED, PlatformInvoice::GROUPED], [$this->ownInvoice(1)->status, $this->ownInvoice(2)->status]);
        $this->assertNull($this->ownInvoice(1)->reference);

        // Never both: the run makes nothing new, issuing refuses, and the school's statement shows no invoice for the month.
        $this->assertSame(['created' => 0, 'existing' => 2, 'skipped' => 0], $billing->run(['period' => $this->month()], $finance));
        $this->assertSame(['issued' => 0, 'left' => 0, 'skipped' => 0, 'changed' => 0], $billing->issueMonth(['period' => $this->month()], $finance));
        $this->post('/saas/billing/schools/1/invoices/'.$own[0]->id.'/issue')->assertSessionHasErrors('invoice');
        $this->post('/saas/billing/schools/1/invoices/'.$own[0]->id.'/recompute')->assertSessionHasErrors('invoice');
        $this->assertSame(0, PlatformInvoice::acrossSchools()->where('status', PlatformInvoice::ISSUED)->count());
        $this->get('/saas/billing?month='.$this->month())->assertOk()->assertSee('Billed through a school group this month:');
        $this->get('/saas/billing/schools/1/invoices/'.$own[0]->id)->assertOk()->assertSee('Month billed through the school group');

        // The issued group invoice and the grouped school months never change, through the models or the database.
        $this->expectsLogic(fn () => $invoice->forceFill(['total_cents' => 1])->save());
        $this->expectsLogic(fn () => $this->ownInvoice(1)->forceFill(['status' => PlatformInvoice::DRAFT])->save());
        $this->expectsQuery(fn () => DB::table('school_group_invoices')->where('id', $invoice->id)->update(['total_cents' => 1]));
        $this->expectsQuery(fn () => DB::table('school_group_invoices')->where('id', $invoice->id)->delete());
        $this->expectsQuery(fn () => DB::table('platform_invoices')->where('id', $own[0]->id)->update(['status' => 'draft']));
        $this->expectsQuery(fn () => DB::table('school_group_invoice_lines')->where('group_invoice_id', $invoice->id)->delete());
        $this->expectsQuery(fn () => DB::table('school_group_invoice_schools')->where('group_invoice_id', $invoice->id)->update(['open_key' => null]));

        // EFT payments: part, a repeated bank reference and an over-payment refused, a reversal kept.
        $total = $invoice->total_cents;
        $this->post('/saas/groups/'.$group->id.'/invoices/'.$invoice->id.'/payments', ['amount' => '100.00', 'received_on' => PlatformBilling::today(), 'bank_reference' => 'SYN-TRUST-1'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($total - 10000, app(SchoolGroupBilling::class)->outstanding($invoice));
        $this->post('/saas/groups/'.$group->id.'/invoices/'.$invoice->id.'/payments', ['amount' => '1.00', 'received_on' => PlatformBilling::today(), 'bank_reference' => 'SYN-TRUST-1'])->assertSessionHasErrors('bank_reference');
        $this->post('/saas/groups/'.$group->id.'/invoices/'.$invoice->id.'/payments', ['amount' => '99999999.00', 'received_on' => PlatformBilling::today(), 'bank_reference' => 'SYN-TRUST-2'])->assertSessionHasErrors('amount');
        $payment = SchoolGroupPayment::query()->sole();
        $this->post('/saas/groups/'.$group->id.'/invoices/'.$invoice->id.'/payments/'.$payment->id.'/reverse', ['reason' => 'Synthetic wrong account'])->assertRedirect();
        $this->assertSame($total, app(SchoolGroupBilling::class)->outstanding($invoice));
        $this->expectsLogic(fn () => $payment->refresh()->forceFill(['amount_cents' => 1])->save());
        $this->expectsQuery(fn () => DB::table('school_group_payments')->where('id', $payment->id)->delete());
        $this->assertTrue(AuditLog::query()->where('action', 'platform_group_invoice_issued')->exists());
    }

    public function test_a_school_billed_alone_or_leaving_is_never_on_the_group_invoice_as_well(): void
    {
        $ops = $this->staff('operations');
        $finance = $this->staff('finance');
        $group = $this->group($ops, true);
        $this->price(5000);
        // School A is billed through the group; School B stays billed on its own and is issued alone.
        app(SchoolGroups::class)->setBilling($finance, $group->id, $this->member(1)->id, ['billing' => 'group']);
        $billing = app(PlatformBilling::class);
        $billing->run(['period' => $this->month()], $finance);
        $billing->issueMonth(['period' => $this->month()], $finance);
        $this->assertSame([PlatformInvoice::DRAFT, PlatformInvoice::ISSUED], [$this->ownInvoice(1)->status, $this->ownInvoice(2)->status]);
        // Changing School B to the group now does not pull its issued month onto the group invoice.
        app(SchoolGroups::class)->setBilling($finance, $group->id, $this->member(2)->id, ['billing' => 'group']);
        $invoice = app(SchoolGroupBilling::class)->compose($finance, $group->id, ['period' => $this->month()]);
        $this->assertSame([1], $invoice->schoolRows()->pluck('school_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame($this->ownInvoice(1)->total_cents, $invoice->total_cents);

        // School A leaves before the group invoice is issued: it comes off the draft, and its own month is billed alone.
        app(CurrentSchool::class)->run(1, fn () => app(SchoolGroups::class)->leave($this->superAdmin(), $this->member(1)->id, []));
        $invoice->refresh();
        $this->assertSame([0, 0], [$invoice->schools, $invoice->total_cents]);
        $this->assertNull($billing->issueProblem($this->ownInvoice(1)));
        $this->signInAs($finance)->post('/saas/billing/schools/1/invoices/'.$this->ownInvoice(1)->id.'/issue')->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(PlatformInvoice::ISSUED, $this->ownInvoice(1)->status);
        // The empty group draft cannot be issued; discarding it leaves nothing claimed.
        $this->expectsValidation(fn () => app(SchoolGroupBilling::class)->issue($finance, $group->id, $invoice->id), 'invoice');
        app(SchoolGroupBilling::class)->discard($finance, $group->id, $invoice->id);
        $this->assertSame(0, DB::table('school_group_invoice_schools')->whereNotNull('open_key')->count());

        // A school's draft changed after the group copied it: issuing asks for a recompute first.
        $this->travel(1)->months();
        $next = $this->month();
        $draft = app(SchoolGroupBilling::class)->compose($finance, $group->id, ['period' => $next]);
        $this->assertSame([2], $draft->schoolRows()->pluck('school_id')->map(fn ($id) => (int) $id)->all());
        $own = PlatformInvoice::acrossSchools()->where('school_id', 2)->where('slot', $next)->sole();
        $this->travel(2)->seconds();
        $billing->recompute(2, $own->id, $finance);
        $this->expectsValidation(fn () => app(SchoolGroupBilling::class)->issue($finance, $group->id, $draft->id), 'invoice');
        app(SchoolGroupBilling::class)->recompute($finance, $group->id, $draft->id);
        app(SchoolGroupBilling::class)->issue($finance, $group->id, $draft->id);
        $this->assertSame(PlatformInvoice::GROUPED, $own->refresh()->status);
    }

    public function test_hq_pages_use_two_factor_and_show_counts_only(): void
    {
        $ops = $this->staff('operations');
        $group = $this->group($ops, true);
        $this->grantAdmin($ops, $group, $this->person('Olwethu'));
        // Without the two-factor challenge passed, HQ pages send staff to set it up.
        $this->actingAs($ops)->get('/saas/groups')->assertRedirect();
        $list = $this->actingAsHq($ops)->get('/saas/groups')->assertOk()->assertSee('Synthetic Schools Trust')->getContent();
        $show = $this->get('/saas/groups/'.$group->id)->assertOk()->assertSee('Group administrators')->assertSee('olwethu@owner.example')->getContent();
        foreach ([$list, $show] as $page) {
            $this->assertNoPerson($page);
        }
        $this->get('/saas/groups/999999')->assertNotFound();
    }

    private function expectsForbidden(callable $call): void
    {
        try {
            $call();
            $this->fail('Expected a refusal.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    private function expectsValidation(callable $call, string $field): void
    {
        try {
            $call();
            $this->fail('Expected a validation error.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($field, $e->errors());
        }
    }

    private function expectsLogic(callable $call): void
    {
        try {
            $call();
            $this->fail('Expected the model to refuse.');
        } catch (LogicException) {
            $this->addToAssertionCount(1);
        }
    }

    private function expectsQuery(callable $call): void
    {
        try {
            DB::transaction(fn () => $call());
            $this->fail('Expected the database to refuse.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }
    }
}
