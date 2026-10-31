<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Http\Middleware\PrivateDomainResponse;
use App\Models\Notification;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\Platform\SchoolModule;
use App\Models\Platform\SupportAccessGrant;
use App\Models\Settings;
use App\Models\User;
use App\Policies\Growth\LearnerNotesPolicy;
use App\Services\Discipline\DisciplineAuthority;
use App\Services\FamilyForms\HealthForms;
use App\Services\Growth\RiskAuthority;
use App\Services\Pastoral\SensitiveRecordsAccess;
use App\Services\Pastoral\SupportAuthority;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Saas\SupportAccess;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Wellbeing\WellbeingAccess;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use LogicException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * Audited support access (Action Plan SA.9): a Navigate Ed staff member asks a
 * school for read-only, time-limited access; the school's Super Admin approves
 * (or an Owner records an emergency override); every page viewed is logged
 * for the school and HQ; writes, restricted records and other schools are
 * refused. engineering/specs/platform/support-access.md.
 */
class SupportAccessTest extends TestCase
{
    use UsesTwoSchools;

    private const REASON = 'Ticket 4411: the school says new staff cannot sign in after their invitation.';

    private User $adminB;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed dated memberships under the same clock used by the requests.
        // Freezing after seeding can leave every membership in the future.
        $this->travelTo(CarbonImmutable::parse('2026-10-30 08:00', SupportAccess::TIMEZONE));
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->forget();
        $this->adminB = app(CurrentSchool::class)->run(self::SCHOOL_B_ID, function (): User {
            $user = User::query()->create(['first_name' => 'Baobab', 'last_name' => 'Principal', 'email' => 'principal@baobab.example', 'mobile' => '', 'password' => 'Synthetic-support-password-731!', 'status' => 1]);
            $user->assignRole('Super Admin');

            return $user;
        });
        app(CurrentSchool::class)->forget();
    }

    private function staff(string $role, string $name = ''): User
    {
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => ucfirst($role).$name, 'last_name' => 'Operator', 'email' => $role.$name.'@platform.example', 'mobile' => '', 'password' => 'Synthetic-support-password-582!', 'status' => 1]));
        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => $role, 'expires_at' => now()->addDays(7)]);

        return $user;
    }

    private function ask(int $school = 1, array $changes = []): int
    {
        $this->post('/saas/support', $changes + ['school_id' => $school, 'reason' => self::REASON, 'minutes' => 60, 'scope' => 'read'])
            ->assertRedirect()->assertSessionHasNoErrors();

        return (int) SupportAccessGrant::acrossSchools()->max('id');
    }

    private function grant(int $id): SupportAccessGrant
    {
        return SupportAccessGrant::acrossSchools()->findOrFail($id);
    }

    /** A grant row in the given state, written directly (for refusal tests). */
    private function forced(User $staff, string $status, array $changes = []): int
    {
        $grant = new SupportAccessGrant;
        $grant->forceFill($changes + [
            'school_id' => 1, 'requested_by' => $staff->id, 'requester_role' => 'support', 'reason' => self::REASON, 'scope' => 'read', 'minutes' => 60,
            'status' => $status, 'requested_at' => now(),
            'starts_at' => $status === 'active' ? now() : null, 'expires_at' => $status === 'active' ? now()->addHour() : null,
            'decided_at' => $status === 'declined' ? now() : null,
        ])->save();

        return (int) $grant->id;
    }

    private function views(): int
    {
        return DB::table('support_access_views')->count();
    }

    /** Everything a refusal must leave as it was. */
    private function state(): array
    {
        return [
            'grants' => DB::table('support_access_grants')->orderBy('id')->get(['id', 'status', 'decided_by', 'starts_at', 'expires_at', 'ended_at', 'emergency'])->map(fn ($r) => (array) $r)->all(),
            'views' => $this->views(),
            'audit' => DB::table('audit_log')->where('action', 'like', 'platform\_support%')->count(),
            'notices' => DB::table('notifications')->where('type', 'like', 'support_access.%')->count(),
        ];
    }

    /** @return list<int> the recipients of the newest notice of this type, from its outbox event */
    private function recipients(string $type): array
    {
        $notice = Notification::query()->withoutGlobalScopes()->where('type', $type)->latest('id')->firstOrFail();
        $event = DB::table('domain_events')->where('type', 'notification.requested')->get()
            ->first(fn ($row) => (int) (json_decode((string) $row->payload, true)['notification_id'] ?? 0) === (int) $notice->id);
        $this->assertNotNull($event, 'the notice was queued for delivery');
        $this->assertSame(1, (int) $notice->school_id);

        return array_map('intval', json_decode((string) $event->payload, true)['user_ids']);
    }

    public function test_support_asks_the_school_approves_every_page_viewed_is_logged_and_the_session_expires(): void
    {
        $support = $this->staff('support');
        $admin = $this->superAdmin();
        $tara = $this->schoolA->ids['tara_user'];
        $this->actingAs($support);
        $this->get('/saas/support')->assertOk()->assertSee('Ask a school for access');

        $id = $this->ask();
        $this->assertSame(['pending', 1, 60, 'read', 'support'], [$this->grant($id)->state(), $this->grant($id)->school_id, $this->grant($id)->minutes, $this->grant($id)->scope, $this->grant($id)->requester_role]);
        // The school's Super Admins are told, and only they.
        $this->assertSame([(int) $admin->id], $this->recipients(SupportAccess::REQUESTED));
        $notice = Notification::query()->withoutGlobalScopes()->where('type', SupportAccess::REQUESTED)->sole();
        $this->assertSame('/support-access', $notice->delivery_payload['link']);
        $this->assertStringNotContainsString('4411', $notice->title.$notice->message);
        $sent = app(CurrentSchool::class)->run(1, fn () => app(Notifier::class)->send($admin, $notice->type, $notice->title, $notice->message, SupportAccess::LINK, $notice, ['in_app']));
        // Every channel is mandatory for this notice; the in-app copy is the one the panel shows.
        $this->assertSame(['email', 'in_app'], $sent);
        $this->assertDatabaseHas('user_notifications', ['notification_id' => $notice->id, 'user_id' => $admin->id]);

        // Not open before the school decides.
        $this->get('/support-session/'.$id)->assertForbidden();
        $this->assertSame(0, $this->views());

        $this->actingAs($admin);
        $this->get('/support-access')->assertOk()->assertSee('Waiting for your decision')->assertSee('Support Operator')->assertSee('Ticket 4411')
            ->assertSee('class="m3-card__actions m3-card__actions--start"', false)->assertDontSee('class="d-inline"', false)
            ->assertDontSee('support@platform.example');
        $this->post('/support-access/'.$id.'/approve')->assertRedirect('/support-access')->assertSessionHas('success');
        $grant = $this->grant($id);
        $this->assertSame(['active', (int) $admin->id], [$grant->state(), $grant->decided_by]);
        $this->assertSame(now()->utc()->addHour()->format('Y-m-d H:i'), $grant->expires_at?->format('Y-m-d H:i'));
        // The staff member gains no membership and no role in the school.
        $this->assertSame(0, DB::table('school_memberships')->where('user_id', $support->id)->count());
        $this->assertSame(0, DB::table('model_has_roles')->where('model_id', $support->id)->count());

        $this->actingAs($support);
        $this->get('/support-session/'.$id)->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('Read-only support session')->assertSee((string) DB::table('schools')->where('id', 1)->value('name'))->assertDontSee(self::SCHOOL_B_MARKER);
        $this->get('/support-session/'.$id.'/people')->assertOk()->assertSee('Tara')->assertDontSee(self::SCHOOL_B_MARKER);
        $faridEmail = User::acrossSchools()->findOrFail($this->schoolA->ids['farid_user'])->email;
        $this->post('/support-session/'.$id.'/people', ['q' => $faridEmail])->assertOk()->assertSee('Farid');
        // The school comes from the grant alone: neither a header nor a chosen school changes it.
        $this->withHeader('X-School-Id', '2')->withSession([(string) config('tenancy.session_key') => 2])
            ->get('/support-session/'.$id.'/people/'.$tara)->assertOk()->assertSee('Roles at this school')->assertDontSee(self::SCHOOL_B_MARKER);
        $this->flushHeaders();
        $this->assertSame(0, DB::table('school_memberships')->where('user_id', $support->id)->count());

        $views = DB::table('support_access_views')->where('grant_id', $id)->orderBy('id')->get();
        $this->assertSame(['support-session.show', 'support-session.people', 'support-session.people.search', 'support-session.person'], $views->pluck('route_name')->all());
        $this->assertSame([null, null, null, 'person'], $views->pluck('record_type')->all());
        $this->assertSame([(int) $this->schoolA->ids['farid_user']], json_decode((string) $views[2]->listed, true));
        $this->assertSame((int) $tara, (int) $views->last()->record_id);
        $this->assertSame([1], $views->pluck('school_id')->unique()->map(fn ($v) => (int) $v)->values()->all());
        // Never content: the search words are stored nowhere.
        $this->assertSame(0, DB::table('support_access_views')->where('route_name', 'like', '%Farid%')->count());
        $this->assertSame(0, DB::table('audit_log')->where('action', 'platform_support_page_viewed')->where('after', 'like', '%Farid%')->count());
        $this->assertSame(4, DB::table('audit_log')->where('action', 'platform_support_page_viewed')->where('school_id', 1)->count());

        // HQ sees the same pages, in the grant and in its audit log.
        $this->get('/saas/support/'.$id)->assertOk()->assertSee('support-session.person')->assertSee('person #'.$tara);
        $this->get('/saas/audit?school=1')->assertOk()->assertSee('Page viewed in a support session')->assertSee('Support access approved by the school');

        // The school sees its history and the viewed pages.
        $this->actingAs($admin);
        $this->get('/support-access')->assertOk()->assertSee('Open now')->assertSee('See the 4 pages viewed');
        $this->get('/support-access/'.$id)->assertOk()->assertSee('One account')->assertSee('person #'.$tara)->assertSee('People search')
            ->assertSee('1 account listed')->assertDontSee('Farid');

        // It expires by itself: refused afterwards, and nothing more is logged.
        $this->travel(61)->minutes();
        $this->actingAs($support);
        $before = $this->state();
        $this->get('/support-session/'.$id)->assertForbidden();
        $this->get('/support-session/'.$id.'/people/'.$tara)->assertForbidden();
        $this->assertSame($before, $this->state());
        $this->assertSame('expired', $this->grant($id)->state());
        $this->actingAs($admin);
        $this->get('/support-access')->assertOk()->assertSee('No support session is open')->assertSee('Expired');
    }

    public function test_an_owner_emergency_override_opens_at_once_and_tells_the_school_immediately(): void
    {
        $support = $this->staff('support');
        $owner = $this->staff('owner');
        $admin = $this->superAdmin();
        $this->actingAs($support);
        $id = $this->ask();

        // Support cannot override, and an Owner must give a reason.
        $before = $this->state();
        $this->post('/saas/support/'.$id.'/emergency', ['emergency_reason' => 'The school is offline and exams start in an hour.'])->assertForbidden();
        $this->actingAs($owner);
        $this->post('/saas/support/'.$id.'/emergency', [])->assertSessionHasErrors('emergency_reason');
        $this->post('/saas/support/'.$id.'/emergency', ['emergency_reason' => 'Too short'])->assertSessionHasErrors('emergency_reason');
        $this->assertSame($before, $this->state());

        $this->get('/saas/support/'.$id)->assertOk()->assertSee('Emergency override');
        $this->travel(16)->minutes();
        $this->post('/saas/support/'.$id.'/emergency', ['emergency_reason' => 'The school office cannot be reached and report cards fail to send.'])
            ->assertRedirect('/saas/support/'.$id)->assertSessionHas('success');
        $grant = $this->grant($id);
        $this->assertSame(['active', true, (int) $owner->id], [$grant->state(), $grant->emergency, $grant->decided_by]);
        $this->assertSame([(int) $admin->id], $this->recipients(SupportAccess::EMERGENCY));
        $notice = Notification::query()->withoutGlobalScopes()->where('type', SupportAccess::EMERGENCY)->sole();
        $this->assertSame(['email', 'in_app'], app(CurrentSchool::class)->run(1, fn () => app(Notifier::class)->send($admin, $notice->type, $notice->title, $notice->message, SupportAccess::LINK, $notice, ['in_app'])));
        $audit = DB::table('audit_log')->where('action', 'platform_support_access_emergency')->sole();
        $this->assertSame([1, (int) $owner->id], [(int) $audit->school_id, (int) $audit->actor_id]);
        $this->assertStringContainsString('cannot be reached', (string) $audit->after);

        // The requester opens it; the Owner who overrode it cannot use someone else's grant.
        $this->get('/support-session/'.$id)->assertNotFound();
        $this->actingAs($support);
        $this->get('/support-session/'.$id)->assertOk()->assertSee('Opened by emergency override.');

        $this->actingAs($admin);
        $this->get('/support-access')->assertOk()->assertSee('Opened by a Navigate Ed emergency override, without your approval.')
            ->assertSee('Emergency reason: The school office cannot be reached');
        $this->get('/support-access/'.$id)->assertOk()->assertSee('Emergency reason: The school office cannot be reached');

        // A request the school declined cannot be overridden.
        $this->actingAs($support);
        $second = $this->ask(self::SCHOOL_B_ID, ['reason' => 'Ticket 4412: the timetable page shows the wrong week for Grade 5.']);
        $this->post('/saas/support/'.$second.'/end')->assertRedirect();
        $declined = $this->forced($support, 'declined');
        $this->actingAs($owner);
        $before = $this->state();
        $this->post('/saas/support/'.$declined.'/emergency', ['emergency_reason' => 'The school office cannot be reached and report cards fail to send.'])->assertStatus(409);
        $this->post('/saas/support/'.$second.'/emergency', ['emergency_reason' => 'The school office cannot be reached and report cards fail to send.'])->assertStatus(409);
        $this->assertSame($before, $this->state());
    }

    public function test_the_school_or_the_staff_member_can_end_it_early(): void
    {
        $support = $this->staff('support');
        $admin = $this->superAdmin();
        $this->actingAs($support);
        $id = $this->ask();
        $this->actingAs($admin)->post('/support-access/'.$id.'/approve')->assertRedirect();
        $this->actingAs($support)->get('/support-session/'.$id)->assertOk();

        $this->actingAs($admin)->post('/support-access/'.$id.'/end')->assertRedirect('/support-access');
        $this->assertSame(['ended', 'school', (int) $admin->id], [$this->grant($id)->state(), $this->grant($id)->ended_side, $this->grant($id)->ended_by]);
        $this->actingAs($support)->get('/support-session/'.$id)->assertForbidden();
        $this->actingAs($admin)->post('/support-access/'.$id.'/end')->assertStatus(409);

        // Ending it starts the school's cool-down: no new request for a day.
        $this->actingAs($support);
        $before = $this->state();
        $this->post('/saas/support', ['school_id' => 1, 'reason' => self::REASON, 'minutes' => 60, 'scope' => 'read'])->assertStatus(409);
        $this->assertSame($before, $this->state());
        $this->travel(25)->hours();
        $second = $this->ask();
        $this->actingAs($admin)->post('/support-access/'.$second.'/approve')->assertRedirect();
        $this->actingAs($support)->post('/saas/support/'.$second.'/end')->assertRedirect('/saas/support/'.$second);
        $this->assertSame(['ended', 'staff'], [$this->grant($second)->state(), $this->grant($second)->ended_side]);
        $this->get('/support-session/'.$second)->assertForbidden();

        // A pending request is withdrawn by its requester, and can no longer be approved.
        $third = $this->ask();
        $this->post('/saas/support/'.$third.'/end')->assertRedirect();
        $this->assertSame('withdrawn', $this->grant($third)->state());
        $this->actingAs($admin)->post('/support-access/'.$third.'/approve')->assertStatus(409);
        $this->assertSame('withdrawn', $this->grant($third)->state());
        $this->assertSame(1, DB::table('audit_log')->where('action', 'platform_support_access_ended')->where('after->side', 'school')->count());
    }

    public function test_without_an_open_grant_of_ones_own_nothing_opens_and_nothing_is_written(): void
    {
        $support = $this->staff('support');
        $other = $this->staff('support', 'two');
        $pending = $this->forced($support, 'pending');
        $declined = $this->forced($support, 'declined');
        $expired = $this->forced($support, 'active', ['starts_at' => now()->subHours(2), 'expires_at' => now()->subHour()]);
        $ended = $this->forced($support, 'active', ['status' => 'ended', 'ended_at' => now()]);
        $lapsed = $this->forced($support, 'pending', ['requested_at' => now()->subHours(25)]);
        $othersOpen = $this->forced($other, 'active');
        $this->actingAs($support);

        $before = $this->state();
        $this->get('/support-session/999999')->assertNotFound();
        foreach ([$pending, $declined, $expired, $ended, $lapsed] as $id) {
            $this->get('/support-session/'.$id)->assertForbidden();
            $this->get('/support-session/'.$id.'/people')->assertForbidden();
            $this->get('/support-session/'.$id.'/people/'.$this->schoolA->ids['tara_user'])->assertForbidden();
        }
        // Someone else's open grant is the same as none.
        $this->get('/support-session/'.$othersOpen)->assertNotFound();
        $this->assertSame($before, $this->state());

        // A lapsed request cannot be approved any more.
        $this->actingAs($this->superAdmin())->post('/support-access/'.$lapsed.'/approve')->assertStatus(409);
        $this->assertSame($before, $this->state());
        // Signed out: the sign-in page, and nothing logged.
        auth()->logout();
        $this->get('/support-session/'.$othersOpen)->assertRedirect();
        $this->assertSame($before, $this->state());
    }

    public function test_a_session_never_reaches_another_school(): void
    {
        $support = $this->staff('support');
        $id = $this->forced($support, 'active');
        $this->actingAs($support);
        $baobabFarid = $this->schoolB->ids['farid_user'];

        $before = $this->state();
        $this->get('/support-session/'.$id.'/people/'.$baobabFarid)->assertNotFound();
        $this->get('/support-session/'.$id.'/people/'.$this->adminB->id)->assertNotFound();
        // The staff member's own account (no school role) is not a school record either.
        $this->get('/support-session/'.$id.'/people/'.$support->id)->assertNotFound();
        $this->assertSame($before, $this->state());

        $this->post('/support-session/'.$id.'/people', ['q' => User::acrossSchools()->findOrFail($this->schoolA->ids['farid_user'])->email])->assertOk()
            ->assertSee(User::acrossSchools()->findOrFail($this->schoolA->ids['farid_user'])->email);
        $this->post('/support-session/'.$id.'/people', ['q' => User::acrossSchools()->findOrFail($baobabFarid)->email])->assertOk()
            ->assertSee('No account at this school matches.')->assertDontSee(self::SCHOOL_B_MARKER);
        $this->post('/support-session/'.$id.'/people', ['q' => 'principal@baobab'])->assertOk()->assertSee('No account at this school matches.');

        // School B's own grant to another staff member gives this one nothing in either school.
        $grantB = $this->forced($this->staff('support', 'b'), 'active', ['school_id' => 2]);
        $before = $this->state();
        $this->get('/support-session/'.$grantB)->assertNotFound();
        $this->assertSame($before, $this->state());

        // Inside a session the request cannot switch school or read across schools.
        $school = app(CurrentSchool::class);
        $school->supportSession(School::findOrFail(1), $id);
        try {
            $this->assertTrue($school->isImpersonating());
            $school->run(1, fn () => $this->assertTrue($school->isSupportSession() && $school->isImpersonating()));
            try {
                $school->set(2);
                $this->fail('A support session moved to another school.');
            } catch (LogicException) {
            }
            try {
                $school->acrossSchools(fn () => null);
                $this->fail('A support session read across schools.');
            } catch (LogicException) {
            }
            $this->assertSame(1, $school->id());
        } finally {
            $school->reset();
        }
    }

    public function test_every_write_in_a_read_only_session_is_refused_server_side(): void
    {
        $support = $this->staff('support');
        $id = $this->forced($support, 'active');
        // A route that would write if the session let it through.
        Route::middleware(['web', PrivateDomainResponse::class, 'auth', 'support.session'])
            ->match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], 'support-session/{grant}/probe-write', function () {
                DB::table('school_modules')->where('school_id', 1)->update(['enabled' => false]);

                return response('written');
            })->whereNumber('grant')->name('support-session.probe-write');
        app('router')->getRoutes()->refreshNameLookups();
        $modules = DB::table('school_modules')->where('school_id', 1)->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
        $this->actingAs($support);

        $before = $this->state();
        foreach (['post', 'put', 'patch', 'delete'] as $method) {
            $this->{$method}('/support-session/'.$id.'/probe-write', ['enabled' => 0])->assertForbidden();
            $this->{$method}('/support-session/'.$id, ['q' => 'x'])->assertStatus(405);
        }
        $this->assertSame($before, $this->state());
        $this->assertSame($modules, DB::table('school_modules')->where('school_id', 1)->orderBy('id')->get()->map(fn ($r) => (array) $r)->all());

        // A school panel write never runs in the session: the staff member has no school there.
        $this->put('/school-modules/gallery', ['enabled' => 1]);
        $this->assertSame($modules, DB::table('school_modules')->where('school_id', 1)->orderBy('id')->get()->map(fn ($r) => (array) $r)->all());
        $this->assertSame($before, $this->state());
    }

    public function test_restricted_records_are_refused_by_their_own_authorities_inside_a_session(): void
    {
        $support = $this->staff('support');
        $id = $this->forced($support, 'active');
        // Switch the restricted modules on, so only the session can be the reason for a refusal.
        foreach (['wellbeing', 'early_warning', 'support_plans', 'discipline', 'family_forms'] as $module) {
            app(CurrentSchool::class)->run(1, fn () => SchoolModule::query()->updateOrCreate(['module' => $module], ['enabled' => true, 'in_package' => true, 'read_only' => false]));
        }
        app(CurrentSchool::class)->forget();
        $learner = (int) $this->schoolA->ids['asha'];
        Route::middleware(['web', PrivateDomainResponse::class, 'auth', 'support.session'])
            ->get('support-session/{grant}/probe/{kind}', function (int $grant, string $kind) use ($learner) {
                $actor = auth()->user();
                match ($kind) {
                    'wellbeing' => app(WellbeingAccess::class)->environment(),
                    'risk' => app(RiskAuthority::class)->enabled(),
                    'risk-actor' => app(RiskAuthority::class)->actor($actor),
                    'support-plans' => app(SupportAuthority::class)->actor($actor),
                    'counselling' => app(SensitiveRecordsAccess::class)->actor($actor),
                    'discipline' => app(DisciplineAuthority::class)->enabled(),
                    'health' => app(HealthForms::class)->person($actor, $learner),
                    'learner-notes' => app(LearnerNotesPolicy::class)->enabled(),
                };

                return response('reached '.$kind);
            })->whereNumber('grant')->name('support-session.probe');
        app('router')->getRoutes()->refreshNameLookups();
        $this->actingAs($support);

        $before = $this->state();
        foreach (['wellbeing', 'risk', 'risk-actor', 'support-plans', 'counselling', 'discipline', 'health', 'learner-notes'] as $kind) {
            $this->get('/support-session/'.$id.'/probe/'.$kind)->assertForbidden()->assertDontSee('reached');
        }
        // The school's own pages for them are never reachable by the staff member either.
        foreach (['/wellbeing', '/risk-interventions', '/support-plans', '/school-discipline'] as $page) {
            $this->assertNotSame(200, $this->actingAs($support)->get($page)->getStatusCode(), $page);
        }
        $this->assertSame($before, $this->state());

        // The same authorities refuse a support session even when called directly.
        $school = app(CurrentSchool::class);
        $school->supportSession(School::findOrFail(1), $id);
        try {
            foreach ([fn () => app(WellbeingAccess::class)->environment(), fn () => app(RiskAuthority::class)->enabled(), fn () => app(SupportAuthority::class)->actor($support),
                fn () => app(SensitiveRecordsAccess::class)->actor($support), fn () => app(DisciplineAuthority::class)->enabled(), fn () => app(HealthForms::class)->person($support, $learner)] as $call) {
                try {
                    $call();
                    $this->fail('A restricted authority let a support session through.');
                } catch (HttpException $e) {
                    $this->assertSame(403, $e->getStatusCode());
                }
            }
        } finally {
            $school->reset();
        }
    }

    public function test_sales_finance_content_and_school_accounts_cannot_request_or_open_support_access(): void
    {
        foreach (['sales', 'finance', 'content'] as $role) {
            $staff = $this->staff($role);
            $id = $this->forced($staff, 'active');
            $this->actingAs($staff);
            $before = $this->state();
            $this->get('/saas/support')->assertForbidden();
            $this->get('/saas/support/'.$id)->assertForbidden();
            $this->post('/saas/support', ['school_id' => 1, 'reason' => self::REASON, 'minutes' => 60, 'scope' => 'read'])->assertForbidden();
            $this->post('/saas/support/'.$id.'/end')->assertForbidden();
            $this->post('/saas/support/'.$id.'/emergency', ['emergency_reason' => 'The school office cannot be reached and report cards fail to send.'])->assertForbidden();
            // Even an open grant (from before a role change) does not open for a role without support.access.
            $this->get('/support-session/'.$id)->assertForbidden();
            $this->assertSame($before, $this->state(), $role);
        }

        // A Support member moved to Sales mid-session is refused at the next page.
        $support = $this->staff('support');
        $id = $this->forced($support, 'active');
        $this->actingAs($support)->get('/support-session/'.$id)->assertOk();
        PlatformAdminGrant::query()->where('user_id', $support->id)->update(['role' => 'sales']);
        $before = $this->state();
        $this->get('/support-session/'.$id)->assertForbidden();
        $this->assertSame($before, $this->state());

        // School accounts never reach HQ's side or a session.
        foreach ([$this->superAdmin(), User::acrossSchools()->findOrFail($this->schoolA->ids['tara_user'])] as $schoolUser) {
            $this->actingAs($schoolUser);
            $this->get('/saas/support')->assertForbidden();
            $this->post('/saas/support', ['school_id' => 1, 'reason' => self::REASON, 'minutes' => 60, 'scope' => 'read'])->assertForbidden();
            $this->get('/support-session/'.$id)->assertForbidden();
            $this->assertSame($before, $this->state());
        }
    }

    public function test_a_school_cannot_see_or_decide_another_schools_requests_and_only_its_super_admin_decides(): void
    {
        $support = $this->staff('support');
        $this->actingAs($support);
        $id = $this->ask(1);
        $before = $this->state();

        // School B's Super Admin: not listed, not found, not decided.
        $this->actingAs($this->adminB);
        $this->get('/support-access')->assertOk()->assertSee('No request is waiting')->assertDontSee('Ticket 4411')->assertDontSee('Support Operator');
        $this->get('/support-access/'.$id)->assertNotFound();
        foreach (['approve', 'decline', 'end'] as $action) {
            $this->post('/support-access/'.$id.'/'.$action)->assertNotFound();
        }
        $this->assertSame($before, $this->state());
        // ...even when School A's request is offered through School B's own context switch.
        $this->withHeader('X-School-Id', '2')->post('/support-access/'.$id.'/approve')->assertNotFound();
        $this->flushHeaders();
        $this->assertSame($before, $this->state());

        // In School A, a teacher cannot decide or see it.
        $this->actingAs(User::acrossSchools()->findOrFail($this->schoolA->ids['tara_user']));
        $this->get('/support-access')->assertForbidden();
        $this->get('/support-access/'.$id)->assertForbidden();
        $this->post('/support-access/'.$id.'/approve')->assertForbidden();
        $this->assertSame($before, $this->state());

        // Nor can a platform staff account that also holds School A's Super Admin role approve its own request.
        app(CurrentSchool::class)->run(1, fn () => $support->assignRole('Super Admin'));
        app(CurrentSchool::class)->forget();
        $this->actingAs($support)->post('/support-access/'.$id.'/approve')->assertForbidden();
        $this->assertSame('pending', $this->grant($id)->state());

        // School A's Super Admin decides, and School B's history stays empty.
        $this->actingAs($this->superAdmin())->post('/support-access/'.$id.'/decline')->assertRedirect('/support-access');
        $this->assertSame('declined', $this->grant($id)->state());
        $this->actingAs($this->adminB)->get('/support-access')->assertOk()->assertSee('No history yet');
    }

    public function test_requests_are_validated_and_one_open_request_per_school(): void
    {
        $support = $this->staff('support');
        $this->actingAs($support);
        $before = $this->state();
        $valid = ['school_id' => 1, 'reason' => self::REASON, 'minutes' => 60, 'scope' => 'read'];
        $this->post('/saas/support', ['reason' => 'Too short'] + $valid)->assertSessionHasErrors('reason');
        $this->post('/saas/support', ['minutes' => 480] + $valid)->assertSessionHasErrors('minutes');
        $this->post('/saas/support', ['scope' => 'write'] + $valid)->assertSessionHasErrors('scope');
        $this->post('/saas/support', ['school_id' => 999] + $valid)->assertSessionHasErrors('school_id');
        DB::table('schools')->where('id', 2)->update(['status' => 'suspended']);
        $this->post('/saas/support', ['school_id' => 2] + $valid)->assertSessionHasErrors('school_id');
        $this->assertSame($before, $this->state());
        $this->assertSame([30, 60, 120, 240], SupportAccess::durations());

        $this->ask();
        $state = $this->state();
        $this->post('/saas/support', $valid)->assertStatus(409);
        $this->assertSame($state, $this->state());
    }

    public function test_a_school_decline_cannot_be_defeated_by_a_new_request_or_an_override(): void
    {
        $support = $this->staff('support');
        $other = $this->staff('support', 'two');
        $owner = $this->staff('owner');
        $admin = $this->superAdmin();
        $valid = ['school_id' => 1, 'reason' => self::REASON, 'minutes' => 60, 'scope' => 'read'];
        $override = ['emergency_reason' => 'The school office cannot be reached and report cards fail to send.'];
        $this->actingAs($support);
        $first = $this->ask();
        $this->actingAs($admin)->post('/support-access/'.$first.'/decline')->assertRedirect();

        // (a) Nobody may ask the school again during the cool-down.
        $before = $this->state();
        foreach ([$other, $owner, $support] as $staff) {
            $this->actingAs($staff)->post('/saas/support', $valid)->assertStatus(409);
        }
        $this->assertSame($before, $this->state());

        $this->travel((int) config('support_access.refusal_cooldown_hours') + 1)->hours();
        $this->actingAs($other);
        $second = $this->ask();
        $this->actingAs($owner);
        $own = $this->ask();

        // (b) An override needs the request to have waited for the school first.
        $before = $this->state();
        $this->post('/saas/support/'.$second.'/emergency', $override)->assertStatus(409);
        $this->assertSame($before, $this->state());
        $this->travel((int) config('support_access.override_after_minutes') + 1)->minutes();

        // (c) An Owner cannot override their own request.
        $this->post('/saas/support/'.$own.'/emergency', $override)->assertForbidden();
        $this->assertSame($before, $this->state());

        // (d) The earlier decline is shown on the override form and recorded with the override.
        $this->get('/saas/support/'.$second)->assertOk()->assertSee('This school declined or ended a support request on');
        $this->post('/saas/support/'.$second.'/emergency', $override)->assertRedirect('/saas/support/'.$second);
        $this->assertSame('active', $this->grant($second)->state());
        $audit = json_decode((string) DB::table('audit_log')->where('action', 'platform_support_access_emergency')->sole()->after, true);
        $this->assertSame($first, $audit['earlier_refusal']['grant_id']);

        // (a) An override is refused too while the school's refusal is recent.
        $this->actingAs($support);
        $third = $this->ask();
        $this->actingAs($admin)->post('/support-access/'.$own.'/decline')->assertRedirect();
        $this->travel((int) config('support_access.override_after_minutes') + 1)->minutes();
        $before = $this->state();
        $this->actingAs($owner)->post('/saas/support/'.$third.'/emergency', $override)->assertStatus(409);
        $this->assertSame($before, $this->state());
    }

    public function test_people_search_logs_who_was_listed_and_finds_families_only_by_exact_email(): void
    {
        $support = $this->staff('support');
        $id = $this->forced($support, 'active');
        $farid = User::acrossSchools()->findOrFail($this->schoolA->ids['farid_user']);
        $asha = User::acrossSchools()->findOrFail($this->schoolA->ids['asha_user']);
        $this->actingAs($support);

        // Staff by part of a name; learners and parents never by a fragment.
        $this->post('/support-session/'.$id.'/people', ['q' => 'Tar'])->assertOk()->assertSee('Tara');
        $this->post('/support-session/'.$id.'/people', ['q' => 'Far'])->assertOk()->assertDontSee($farid->email)->assertSee('No account at this school matches.');
        $this->post('/support-session/'.$id.'/people', ['q' => substr($asha->email, 0, 6)])->assertOk()->assertDontSee($asha->email);
        $this->post('/support-session/'.$id.'/people', ['q' => strtoupper($farid->email)])->assertOk()->assertSee($farid->email);
        // A query string never searches.
        $this->get('/support-session/'.$id.'/people?q='.urlencode($farid->email))->assertOk()->assertDontSee($farid->email);

        $views = DB::table('support_access_views')->where('grant_id', $id)->orderBy('id')->get();
        $this->assertSame(['support-session.people.search', 'support-session.people.search', 'support-session.people.search', 'support-session.people.search', 'support-session.people'], $views->pluck('route_name')->all());
        $this->assertSame([(int) $this->schoolA->ids['tara_user']], json_decode((string) $views[0]->listed, true));
        $this->assertSame([], json_decode((string) $views[1]->listed, true));
        $this->assertSame([(int) $farid->id], json_decode((string) $views[3]->listed, true));
        // The staff list logs every account it showed.
        $this->assertContains((int) $this->schoolA->ids['tara_user'], json_decode((string) $views[4]->listed, true));
        $this->assertNotContains((int) $farid->id, json_decode((string) $views[4]->listed, true));
        // Never the search words.
        $stored = DB::table('support_access_views')->where('grant_id', $id)->get()->map(fn ($row) => json_encode($row))->implode(' ')
            .DB::table('audit_log')->where('action', 'platform_support_page_viewed')->pluck('after')->implode(' ');
        $this->assertStringNotContainsString(strtolower($farid->email), strtolower($stored));
        $this->assertStringNotContainsString('"Tar"', $stored);
        $this->assertStringNotContainsString(substr($asha->email, 0, 6), $stored);
        $this->assertStringContainsString((string) $farid->id, (string) DB::table('audit_log')->where('action', 'platform_support_page_viewed')->orderBy('id')->skip(3)->first()->after);

        $this->actingAs($this->superAdmin())->get('/support-access/'.$id)->assertOk()->assertSee('1 account listed')->assertSee('No accounts listed');
    }

    public function test_refused_session_attempts_are_logged_and_cross_site_requests_refused(): void
    {
        $support = $this->staff('support');
        $pending = $this->forced($support, 'pending');
        $open = $this->forced($support, 'active');
        $this->actingAs($support);
        Log::spy();

        $before = $this->state();
        $this->get('/support-session/'.$pending)->assertForbidden();
        $this->get('/support-session/999999')->assertNotFound();
        // A link followed from another site cannot plant a page view.
        $this->withHeader('Sec-Fetch-Site', 'cross-site')->get('/support-session/'.$open)->assertForbidden();
        $this->flushHeaders();
        $this->assertSame($before, $this->state());
        $this->withHeader('Sec-Fetch-Site', 'same-origin')->get('/support-session/'.$open)->assertOk();
        $this->flushHeaders();

        $logged = fn (string $reason, ?int $grant) => Log::shouldHaveReceived('notice')->withArgs(fn ($message, $context = []) => $message === 'support_session_refused'
            && ($context['reason'] ?? null) === $reason && ($context['grant_id'] ?? null) === $grant && ($context['user_id'] ?? null) === (int) $support->id);
        $logged('not_open', $pending);
        $logged('not_found', 999999);
        $logged('cross_site', $open);
    }

    public function test_an_override_is_refused_when_nobody_at_the_school_can_be_told(): void
    {
        DB::table('school_memberships')->where('school_id', self::SCHOOL_B_ID)->where('role', 'Super Admin')->update(['status' => 'ended']);
        $support = $this->staff('support');
        $owner = $this->staff('owner');
        $this->actingAs($support);
        $id = $this->ask(self::SCHOOL_B_ID);
        $this->get('/saas/support/'.$id)->assertOk()->assertSee('Nobody at this school can approve it');
        $this->get('/saas/support')->assertOk()->assertSee('Nobody at this school can approve it');

        $this->travel((int) config('support_access.override_after_minutes') + 1)->minutes();
        $before = $this->state();
        $this->actingAs($owner)->post('/saas/support/'.$id.'/emergency', ['emergency_reason' => 'The school office cannot be reached and report cards fail to send.'])->assertStatus(409);
        $this->assertSame($before, $this->state());
        $this->assertSame('pending', $this->grant($id)->state());
    }

    public function test_the_session_lock_is_lifted_only_by_the_request_reset(): void
    {
        $support = $this->staff('support');
        $id = $this->forced($support, 'active');
        $school = app(CurrentSchool::class);
        $school->supportSession(School::findOrFail(1), $id);
        try {
            $school->forget();
            $this->fail('forget() lifted a support session.');
        } catch (LogicException) {
        }
        $this->assertTrue($school->isSupportSession());
        $this->assertSame(1, $school->id());
        $school->reset();
        $this->assertFalse($school->isSupportSession());
        $this->assertFalse($school->isSet());
        // Without a session, forget() works as before (jobs, the console).
        $school->set(1);
        $school->forget();
        $this->assertFalse($school->isSet());

        // A request after a session starts clean.
        $this->actingAs($support)->get('/support-session/'.$id)->assertOk();
        $this->assertFalse(app(CurrentSchool::class)->isSupportSession());
    }

    public function test_the_requester_can_never_decide_their_own_request(): void
    {
        $support = $this->staff('support');
        $this->actingAs($support);
        $id = $this->ask();
        // No longer platform staff, and now a Super Admin of the school.
        PlatformStaffIdentity::query()->where('user_id', $support->id)->update(['active' => false]);
        app(CurrentSchool::class)->run(1, fn () => $support->assignRole('Super Admin'));
        app(CurrentSchool::class)->forget();

        $before = $this->state();
        $this->actingAs($support)->post('/support-access/'.$id.'/approve')->assertForbidden();
        $this->post('/support-access/'.$id.'/decline')->assertForbidden();
        $this->assertSame($before, $this->state());
        $this->assertSame('pending', $this->grant($id)->state());
    }

    public function test_restricted_authorities_refuse_a_support_session_without_the_impersonation_flag(): void
    {
        $support = $this->staff('support');
        $id = $this->forced($support, 'active');
        foreach (['wellbeing', 'early_warning', 'support_plans', 'discipline', 'family_forms'] as $module) {
            app(CurrentSchool::class)->run(1, fn () => SchoolModule::query()->updateOrCreate(['module' => $module], ['enabled' => true, 'in_package' => true, 'read_only' => false]));
        }
        $counsellor = app(CurrentSchool::class)->run(1, function (): User {
            $user = User::query()->create(['first_name' => 'Cleo', 'last_name' => 'Counsellor', 'email' => 'cleo@jacaranda.example', 'mobile' => '', 'password' => 'Synthetic-support-password-913!', 'status' => 1]);
            $user->assignRole('Counsellor');

            return $user;
        });
        app(CurrentSchool::class)->forget();
        $tara = User::acrossSchools()->findOrFail($this->schoolA->ids['tara_user']);
        $admin = $this->superAdmin();
        $calls = [
            'wellbeing' => fn () => app(WellbeingAccess::class)->environment(),
            'risk' => fn () => app(RiskAuthority::class)->enabled(),
            'risk-actor' => fn () => app(RiskAuthority::class)->actor($tara),
            'support-plans' => fn () => app(SupportAuthority::class)->actor($tara),
            'counselling' => fn () => app(SensitiveRecordsAccess::class)->actor($counsellor),
            'discipline' => fn () => app(DisciplineAuthority::class)->enabled(),
            'learner-notes' => fn () => app(LearnerNotesPolicy::class)->enabled(),
            'health' => fn () => app(HealthForms::class)->person($admin, (int) $this->schoolA->ids['asha']),
        ];
        $school = app(CurrentSchool::class);

        // Control: in the school itself, each of these lets these people through.
        $school->set(1);
        foreach ($calls as $kind => $call) {
            if ($kind !== 'health') {
                $call();
            }
        }

        // A support session with the impersonation flag forced off: the support check alone refuses.
        $school->supportSession(School::findOrFail(1), $id);
        (new \ReflectionProperty(CurrentSchool::class, 'impersonating'))->setValue($school, false);
        try {
            $this->assertFalse($school->isImpersonating());
            foreach ($calls as $kind => $call) {
                try {
                    $call();
                    $this->fail($kind.' let a support session through.');
                } catch (HttpException $e) {
                    $this->assertSame(403, $e->getStatusCode(), $kind);
                }
            }
        } finally {
            $school->reset();
        }
    }

    public function test_a_later_school_termination_controls_the_cooldown_and_override_audit_not_its_earlier_approval(): void
    {
        // Keep a request issued before either refusal pending for the override checks.
        config(['support_access.pending_hours' => 48]);
        $support = $this->staff('support', 'ended');
        $other = $this->staff('support', 'declined');
        $waiting = $this->staff('support', 'waiting');
        $owner = $this->staff('owner');
        $admin = $this->superAdmin();
        $this->travelTo(CarbonImmutable::parse('2026-10-30T06:00:00Z'));
        $this->actingAs($support);
        $ended = $this->ask(1, ['minutes' => 240]);
        $this->actingAs($other);
        $declined = $this->ask();
        $this->actingAs($waiting);
        $pending = $this->ask();
        $this->actingAs($admin)->post('/support-access/'.$ended.'/approve')->assertRedirect();

        $this->travelTo(CarbonImmutable::parse('2026-10-30T07:00:00Z'));
        $this->post('/support-access/'.$declined.'/decline')->assertRedirect();
        $this->travelTo(CarbonImmutable::parse('2026-10-30T09:00:00Z'));
        $this->post('/support-access/'.$ended.'/end')->assertRedirect();
        $this->assertSame('ended', $this->grant($ended)->state());

        // The decline's cooldown has expired, but the later termination still stands.
        $this->travelTo(CarbonImmutable::parse('2026-10-31T07:30:00Z'));
        $input = ['school_id' => 1, 'reason' => self::REASON, 'minutes' => 60, 'scope' => 'read'];
        $override = ['emergency_reason' => 'The school office cannot be reached and report cards fail to send.'];
        $before = $this->state();
        $this->actingAs($support)->post('/saas/support', $input)->assertStatus(409);
        $this->actingAs($owner)->post('/saas/support/'.$pending.'/emergency', $override)->assertStatus(409);
        $this->assertSame($before, $this->state());
        $earlier = app(SupportAccess::class)->hqShow($owner, $pending)['grant']['earlier_refusal'];
        $this->assertSame($ended, $earlier['id']);
        $this->assertSame('2026-10-30 11:00', $earlier['at']);

        // At exactly 24 hours after the termination both actions are permitted.
        $this->travelTo(CarbonImmutable::parse('2026-10-31T09:00:00Z'));
        $this->actingAs($support)->post('/saas/support', $input)->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($owner)->post('/saas/support/'.$pending.'/emergency', $override)->assertRedirect('/saas/support/'.$pending);
        $this->assertSame('active', $this->grant($pending)->state());
        $audit = json_decode((string) DB::table('audit_log')->where('action', 'platform_support_access_emergency')->sole()->after, true);
        $this->assertSame($ended, $audit['earlier_refusal']['grant_id']);
        $this->assertSame('2026-10-30T09:00:00+00:00', $audit['earlier_refusal']['at']);
    }

    private function schoolInAuckland(): void
    {
        // Keep the default school (and HQ) in Johannesburg; only School B changes.
        app(CurrentSchool::class)->run(1, fn () => Settings::query()->updateOrCreate(['type' => 'time_zone'], ['message' => SupportAccess::TIMEZONE]));
        DB::table('schools')->where('id', self::SCHOOL_B_ID)->update(['timezone' => 'Pacific/Auckland']);
        app(CurrentSchool::class)->run(self::SCHOOL_B_ID, fn () => Settings::query()->updateOrCreate(['type' => 'time_zone'], ['message' => 'Pacific/Auckland']));
        app(CurrentSchool::class)->forget();
        $this->travelTo(CarbonImmutable::parse('2026-10-30T06:00:00Z'));
    }

    public function test_approval_and_session_expiry_use_the_same_instant_in_hq_and_a_different_school_timezone(): void
    {
        $this->schoolInAuckland();
        $support = $this->staff('support');
        $admin = $this->adminB;
        $this->actingAs($support);
        $id = $this->ask(self::SCHOOL_B_ID);
        $this->assertSame(SupportAccess::TIMEZONE, config('app.timezone'), 'HQ retains its own clock');
        $this->assertSame('2026-10-30 06:00:00', DB::table('support_access_grants')->where('id', $id)->value('requested_at'));

        $this->actingAs($admin)->post('/support-access/'.$id.'/approve')->assertRedirect('/support-access');
        $row = DB::table('support_access_grants')->where('id', $id)->first();
        $this->assertSame('2026-10-30 06:00:00', $row->starts_at);
        $this->assertSame('2026-10-30 07:00:00', $row->expires_at);
        $this->get('/support-access/'.$id)->assertOk()->assertSee('2026-10-30 20:00');

        // HQ checks the grant before entering the school's timezone: it opens immediately, never hours later.
        $this->actingAs($support)->get('/support-session/'.$id)->assertOk();
        $this->assertSame('2026-10-30 06:00:00', DB::table('support_access_views')->where('grant_id', $id)->value('viewed_at'));
        $this->assertSame([$id], array_column(app(SupportAccess::class)->hqList($support)['open'], 'id'));
        $this->travelTo(CarbonImmutable::parse('2026-10-30T06:59:59Z'));
        $this->get('/support-session/'.$id)->assertOk();
        $this->travelTo(CarbonImmutable::parse('2026-10-30T07:00:00Z'));
        $before = $this->state();
        $this->get('/support-session/'.$id)->assertForbidden();
        $this->assertSame($before, $this->state());
        $this->assertSame([], app(SupportAccess::class)->hqList($support)['open']);
    }

    public function test_request_lifetime_and_refusal_cooldown_do_not_change_with_the_schools_timezone(): void
    {
        $this->schoolInAuckland();
        $support = $this->staff('support');
        $admin = $this->adminB;
        $this->actingAs($support);
        $id = $this->ask(self::SCHOOL_B_ID);
        $this->travelTo(CarbonImmutable::parse('2026-10-31T05:59:59Z'));
        // The school's clock is already thirteen hours ahead, but this request has not waited 24 real hours.
        $this->actingAs($admin)->post('/support-access/'.$id.'/decline')->assertRedirect('/support-access');
        $this->assertSame('2026-10-31 05:59:59', DB::table('support_access_grants')->where('id', $id)->value('decided_at'));
        $this->travelTo(CarbonImmutable::parse('2026-11-01T05:59:58Z'));
        $input = ['school_id' => self::SCHOOL_B_ID, 'reason' => self::REASON, 'minutes' => 60, 'scope' => 'read'];
        $this->actingAs($support)->post('/saas/support', $input)->assertStatus(409);
        $this->travelTo(CarbonImmutable::parse('2026-11-01T05:59:59Z'));
        $this->post('/saas/support', $input)->assertRedirect()->assertSessionHasNoErrors();
    }

    public function test_open_requests_stay_listed_however_many_old_grants_exist(): void
    {
        $support = $this->staff('support');
        $other = $this->staff('support', 'two');
        $old = now()->subDays(3);
        $rows = [];
        for ($i = 0; $i < 510; $i++) {
            $rows[] = ['school_id' => 1, 'requested_by' => $support->id, 'requester_role' => 'support', 'reason' => self::REASON, 'scope' => 'read', 'minutes' => 60,
                'status' => $i % 2 === 0 ? 'active' : 'pending', 'requested_at' => $old, 'starts_at' => $i % 2 === 0 ? $old : null,
                'expires_at' => $i % 2 === 0 ? $old->copy()->addHour() : null, 'created_at' => $old, 'updated_at' => $old];
        }
        DB::table('support_access_grants')->insert($rows);
        $this->actingAs($other);
        $id = $this->ask();

        $open = app(SupportAccess::class)->hqList($other)['open'];
        $this->assertSame([$id], array_column($open, 'id'));
    }
}
