<?php

declare(strict_types=1);

namespace Tests\Feature\Activities;

use App\Models\Activities\ActivityAttendance;
use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Activities\ActivityOperation;
use App\Models\Activities\ActivitySession;
use App\Models\Activities\ActivityStaff;
use App\Models\Communication\CommunicationRoleRule;
use App\Models\Communication\Message;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\Role;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Policies\Communication\MessagePolicy;
use App\Services\Activities\ActivityGroups;
use App\Services\Activities\ActivityOperations;
use App\Services\Activities\CommunityAuthority;
use App\Services\Communication\CommunicationRecipients;
use App\Services\Communication\CommunicationRules;
use App\Services\Communication\MessageDelivery;
use App\Services\Communication\MessageFlow;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class ActivityGroupsTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private int $year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->year = $this->schoolA->ids['session_year'];
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $this->superAdmin()->givePermissionTo('activity-groups-manage');
        app(ModuleGate::class)->change('groups', true, $this->superAdmin());
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic dated group enrolment']);
    }

    private function data(array $extra = []): array
    {
        return $extra + ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $this->year, 'parent_id' => null, 'name' => 'Hockey', 'discipline' => 'Sport', 'season' => '2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'gender' => 'any', 'minimum_grade' => null, 'maximum_grade' => null, 'minimum_age' => null, 'maximum_age' => null, 'active' => true];
    }

    private function dates(array $extra = []): array
    {
        return $extra + ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic allocation'];
    }

    private function group(array $extra = []): array
    {
        return app(ActivityGroups::class)->save($this->superAdmin(), null, $this->data($extra));
    }

    private function coach(int $group): User
    {
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        app(ActivityGroups::class)->allocation($this->superAdmin(), $group, null, true, $this->dates(['user_id' => $teacher->id, 'role' => 'facilitator', 'cascade' => false]));

        return $teacher;
    }

    private function member(int $group): array
    {
        return app(ActivityGroups::class)->allocation($this->superAdmin(), $group, null, false, $this->dates(['learner_id' => $this->schoolA->ids['asha'], 'role' => 'member']));
    }

    private function api(User $actor): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($actor->createToken('activity-test')->plainTextToken);
    }

    public function test_manager_creates_tree_and_idempotent_replay_is_freshly_authorized(): void
    {
        $data = $this->data();
        $one = app(ActivityGroups::class)->save($this->superAdmin(), null, $data);
        $this->assertSame($one, app(ActivityGroups::class)->save($this->superAdmin(), null, $data));
        $this->assertSame(1, ActivityGroup::count());
        $this->assertSame(1, ActivityOperation::count());
        $this->group(['parent_id' => $one['id'], 'name' => 'Under 13']);
        $this->assertCount(2, app(CommunityAuthority::class)->descendants(ActivityGroup::findOrFail($one['id'])));
        $this->superAdmin()->revokePermissionTo('activity-groups-manage');
        Role::where('name', 'Super Admin')->firstOrFail()->revokePermissionTo('activity-groups-manage');
        $this->expectException(HttpException::class);
        app(ActivityGroups::class)->save($this->superAdmin(), null, $data);
    }

    public function test_api_denies_unauthenticated_other_role_other_group_and_school(): void
    {
        $base = '/api/v2/activities/groups';
        $this->getJson($base)->assertUnauthorized();
        $this->api($this->superAdmin());
        $group = $this->postJson($base, $this->data())->assertOk()->json('data');
        $this->getJson($base)->assertOk()->assertJsonPath('data.items.0.id', $group['id']);
        $this->api(User::findOrFail($this->schoolA->ids['tara_user']))->getJson("$base/{$group['id']}")->assertNotFound();
        $this->postJson($base, $this->data())->assertForbidden();
        $this->api(User::findOrFail($this->schoolA->ids['farid_user']))->getJson("$base/{$group['id']}/roster")->assertForbidden();
        $this->api(User::findOrFail($this->schoolA->ids['asha_user']))->postJson($base, $this->data())->assertForbidden();
        $this->api($this->superAdmin())->withHeader('X-School-Id', '2')->getJson("$base/{$group['id']}")->assertForbidden();
    }

    public function test_allocated_coach_can_capture_only_dated_roster_and_materials_are_sanitized_encrypted(): void
    {
        $group = $this->group();
        $teacher = $this->coach($group['id']);
        $this->member($group['id']);
        $session = app(ActivityGroups::class)->session($teacher, $group['id'], null, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'on_date' => '2026-02-10', 'name' => 'Training', 'venue' => 'Field', 'status' => 'scheduled']);
        $result = app(ActivityGroups::class)->attendance($teacher, $session['id'], ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'reason' => 'Coach captured register', 'entries' => [['learner_id' => $this->schoolA->ids['asha'], 'status' => 'late', 'revision' => 0]]]);
        $this->assertSame(0, $result['unrecorded_count']);
        $this->assertSame('late', $result['items'][0]['status']);
        $material = app(ActivityGroups::class)->material($teacher, $group['id'], null, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'title' => 'Fixture', 'markdown' => '# Fixture <script>alert(1)</script> [unsafe](javascript:alert(1))', 'published' => true]);
        $this->assertStringNotContainsString('<script>', $material['markdown']);
        $this->assertNotSame($material['markdown'], DB::table('activity_materials')->where('id', $material['id'])->value('markdown'));
        $this->assertCount(1, app(ActivityGroups::class)->roster($teacher, $group['id'])['memberships']);
    }

    public function test_guardian_relationship_revocation_and_expired_staff_remove_current_reads(): void
    {
        $group = $this->group();
        $teacher = $this->coach($group['id']);
        $this->member($group['id']);
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->assertSame($group['id'], app(ActivityGroups::class)->detail($parent, $group['id'])['group']['id']);
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $parent->id], ['relationship' => 'guardian', 'status' => 'ended']);
        $this->api($parent)->getJson('/api/v2/activities/groups/'.$group['id'])->assertNotFound();
        ActivityStaff::where('user_id', $teacher->id)->update(['ends_on' => '2026-02-09']);
        $this->api($teacher)->getJson('/api/v2/activities/groups/'.$group['id'])->assertNotFound();
    }

    public function test_cross_school_people_and_hierarchy_cycles_are_refused_without_partial_writes(): void
    {
        $root = $this->group();
        $child = $this->group(['parent_id' => $root['id'], 'name' => 'Child']);
        $this->api($this->superAdmin());
        $this->putJson('/api/v2/activities/groups/'.$root['id'], $this->data(['revision' => 1, 'parent_id' => $child['id']]))->assertUnprocessable();
        $this->postJson('/api/v2/activities/groups/'.$root['id'].'/memberships', $this->dates(['learner_id' => $this->schoolB->ids['asha'], 'role' => 'member']))->assertNotFound();
        $this->assertSame(2, ActivityGroup::count());
        $this->assertSame(2, ActivityOperation::count());
    }

    public function test_version_conflicts_and_module_revocation_refuse_retry(): void
    {
        $data = $this->data();
        $group = app(ActivityGroups::class)->save($this->superAdmin(), null, $data);
        $this->api($this->superAdmin())->putJson('/api/v2/activities/groups/'.$group['id'], $this->data(['revision' => 0]))->assertConflict();
        app(ModuleGate::class)->change('groups', false, $this->superAdmin());
        $this->expectException(HttpException::class);
        app(ActivityGroups::class)->save($this->superAdmin(), null, $data);
    }

    public function test_facilitator_has_only_owned_activity_audiences_even_when_broad_role_rule_is_set(): void
    {
        $root = $this->group();
        $child = $this->group(['parent_id' => $root['id'], 'name' => 'Subteam']);
        $other = $this->group(['name' => 'Other activity']);
        $actor = $this->coach($root['id']);
        $this->member($child['id']);
        SchoolMembership::where('user_id', $actor->id)->where('role', 'Teacher')->update(['status' => 'ended']);
        SchoolMembership::create(['school_id' => 1, 'user_id' => $actor->id, 'role' => 'Facilitator', 'status' => 'active', 'starts_on' => '2026-01-01']);
        $actor->assignRole('Facilitator');
        $role = Role::where('name', 'Facilitator')->firstOrFail();
        CommunicationRoleRule::create(['role_id' => $role->id, 'allow_school' => true, 'requires_approval' => true, 'revision' => 1]);
        $this->assertFalse(app(CommunicationRules::class)->effective($actor)['allow_school']);
        $this->api($actor)->getJson('/api/v2/activities/groups/'.$other['id'])->assertNotFound();
        $this->expectException(HttpException::class);
        app(MessagePolicy::class)->scope($actor, ['kind' => 'school', 'session_year_id' => $this->year, 'parents' => false], '2026-02-10');
    }

    public function test_parent_group_audience_cascades_only_with_explicit_subtree_authority(): void
    {
        $root = $this->group();
        $child = $this->group(['parent_id' => $root['id'], 'name' => 'Junior team']);
        $this->member($child['id']);
        $coach = $this->coach($root['id']);
        $definition = ['kind' => 'activity_group', 'session_year_id' => $this->year, 'target_id' => $root['id'], 'parents' => true];
        $policy = app(MessagePolicy::class);
        $resolved = $policy->recipients($this->superAdmin(), $definition, '2026-02-10');
        $this->assertArrayHasKey($this->schoolA->ids['farid_user'], $resolved);
        try {
            $policy->recipients($coach, $definition, '2026-02-10');
            $this->fail('A direct-only parent-group grant addressed unowned descendants.');
        } catch (HttpException $e) {
            $this->assertSame(404, $e->getStatusCode());
        }
        ActivityStaff::where('user_id', $coach->id)->where('activity_group_id', $root['id'])->update(['role' => 'admin', 'cascade' => true]);
        $this->assertArrayHasKey($this->schoolA->ids['farid_user'], $policy->recipients($coach, $definition, '2026-02-10'));
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->schoolA->ids['farid_user']], ['relationship' => 'guardian', 'status' => 'ended']);
        $this->assertArrayNotHasKey($this->schoolA->ids['farid_user'], $policy->recipients($coach, $definition, '2026-02-10'));
    }

    public function test_capture_rejects_outside_roster_atomically_and_never_fills_missing_entries(): void
    {
        $group = $this->group();
        $coach = $this->coach($group['id']);
        $this->member($group['id']);
        $session = app(ActivityGroups::class)->session($coach, $group['id'], null, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'on_date' => '2026-02-10', 'name' => 'Training', 'venue' => 'Field', 'status' => 'scheduled']);
        $this->assertSame(0, ActivityAttendance::count());
        $body = ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'reason' => 'Synthetic session capture', 'entries' => [['learner_id' => $this->schoolA->ids['asha'], 'status' => 'present', 'revision' => 0], ['learner_id' => $this->schoolB->ids['asha'], 'status' => 'absent', 'revision' => 0]]];
        $this->api($coach)->putJson('/api/v2/activities/sessions/'.$session['id'].'/attendance', $body)->assertUnprocessable();
        $this->assertSame(0, ActivityAttendance::count());
        $this->assertSame(1, ActivitySession::findOrFail($session['id'])->revision);
    }

    public function test_new_role_without_teacher_or_facilitator_does_not_keep_an_old_coaching_allocation(): void
    {
        $group = $this->group();
        $actor = $this->coach($group['id']);
        SchoolMembership::where('user_id', $actor->id)->where('role', 'Teacher')->update(['status' => 'ended']);
        SchoolMembership::create(['school_id' => 1, 'user_id' => $actor->id, 'role' => 'Finance', 'status' => 'active', 'starts_on' => '2026-01-01']);
        $this->api($actor)->getJson('/api/v2/activities/groups/'.$group['id'])->assertForbidden();
    }

    public function test_overlapping_membership_and_wrong_group_allocation_updates_are_refused(): void
    {
        $group = $this->group();
        $other = $this->group(['name' => 'Other team']);
        $member = $this->member($group['id']);
        $this->api($this->superAdmin())->postJson('/api/v2/activities/groups/'.$group['id'].'/memberships', $this->dates(['learner_id' => $this->schoolA->ids['asha'], 'role' => 'member']))->assertConflict();
        $this->putJson('/api/v2/activities/groups/'.$other['id'].'/memberships/'.$member['id'], $this->dates(['learner_id' => $this->schoolA->ids['asha'], 'role' => 'member', 'revision' => 1]))->assertNotFound();
        $this->assertSame(1, ActivityMembership::count());
    }

    public function test_eligibility_is_checked_without_guessing_grade_and_cannot_silently_change_after_admission(): void
    {
        $group = $this->group();
        $this->member($group['id']);
        $this->api($this->superAdmin())->putJson('/api/v2/activities/groups/'.$group['id'], $this->data(['revision' => 1, 'minimum_grade' => 7]))->assertConflict();
        $age = $this->group(['name' => 'Under fives', 'maximum_age' => 5]);
        User::findOrFail($this->schoolA->ids['asha_user'])->update(['dob' => '2010-01-01']);
        $this->postJson('/api/v2/activities/groups/'.$age['id'].'/memberships', $this->dates(['learner_id' => $this->schoolA->ids['asha'], 'role' => 'member']))->assertUnprocessable();
        $this->assertSame(1, ActivityMembership::count());
    }

    public function test_every_activity_api_operation_returns_its_declared_contract(): void
    {
        $this->api($this->superAdmin());
        $path = '/api/v2/activities/groups';
        $group = $this->postJson($path, $this->data())->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data');
        $id = $group['id'];
        $this->putJson("$path/$id", $this->data(['revision' => 1, 'name' => 'Hockey updated']))->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('data.revision', 2);
        $this->getJson($path)->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('data.total', 1);
        $member = $this->postJson("$path/$id/memberships", $this->dates(['learner_id' => $this->schoolA->ids['asha'], 'role' => 'member']))->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data');
        $this->putJson("$path/$id/memberships/{$member['id']}", $this->dates(['revision' => 1, 'learner_id' => $this->schoolA->ids['asha'], 'role' => 'captain']))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $staff = $this->postJson("$path/$id/staff", $this->dates(['user_id' => $this->schoolA->ids['tara_user'], 'role' => 'facilitator', 'cascade' => false]))->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data');
        $this->putJson("$path/$id/staff/{$staff['id']}", $this->dates(['revision' => 1, 'user_id' => $this->schoolA->ids['tara_user'], 'role' => 'admin', 'cascade' => true]))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $body = ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'on_date' => '2026-02-10', 'name' => 'Session', 'venue' => 'Field', 'status' => 'scheduled'];
        $session = $this->postJson("$path/$id/sessions", $body)->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data');
        $body['revision'] = 1;
        $body['operation_id'] = (string) Str::uuid();
        $body['name'] = 'Training updated';
        $this->putJson("$path/$id/sessions/{$session['id']}", $body)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->putJson('/api/v2/activities/sessions/'.$session['id'].'/attendance', ['operation_id' => (string) Str::uuid(), 'revision' => 2, 'reason' => 'Explicit register capture', 'entries' => [['learner_id' => $this->schoolA->ids['asha'], 'status' => 'present', 'revision' => 0]]])->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('data.session_revision', 3);
        $body = ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'title' => 'Fixture', 'markdown' => '# Fixtures', 'published' => false];
        $material = $this->postJson("$path/$id/materials", $body)->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data');
        $body['revision'] = 1;
        $body['operation_id'] = (string) Str::uuid();
        $body['published'] = true;
        $this->putJson("$path/$id/materials/{$material['id']}", $body)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->getJson("$path/$id/roster")->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('data.memberships.0.role', 'captain');
        $this->getJson("$path/$id")->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('data.materials.0.published', true);
    }

    public function test_legacy_compose_is_not_exposed_to_new_audience_enums_but_published_reader_stays_compatible(): void
    {
        Queue::fake();
        $group = $this->group();
        $this->member($group['id']);
        $actor = $this->superAdmin();
        $path = '/api/v2/communication';
        $this->api($actor);
        $old = $this->getJson("$path/configuration?session_year_id={$this->year}")->assertOk()->json('data.scopes');
        $this->assertNotContains('activity_group', array_column($old, 'kind'));
        $new = $this->getJson("$path/configuration?session_year_id={$this->year}&activity_groups=1")->assertOk()->json('data.scopes');
        $this->assertContains('activity_group', array_column($new, 'kind'));
        $body = ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'title' => 'Group update', 'markdown' => '# School fixture', 'channels' => ['in_app'], 'scheduled_local' => null, 'audience' => ['kind' => 'activity_group', 'session_year_id' => $this->year, 'target_id' => $group['id'], 'parents' => true]];
        $this->postJson("$path/drafts", $body)->assertConflict();
        $saved = $this->postJson("$path/drafts?activity_groups=1", $body)->assertOk()->json('data');
        $id = $saved['id'];
        $old = $this->getJson("$path/drafts")->assertOk()->json('data.items');
        $this->assertNotContains($id, array_column($old, 'id'));
        $new = $this->getJson("$path/drafts?activity_groups=1")->assertOk()->json('data.items');
        $this->assertContains($id, array_column($new, 'id'));
        $this->getJson("$path/drafts/$id")->assertConflict()->assertJsonPath('message', 'Update your app to work with activity group messages.');
        $this->getJson("$path/drafts/$id?activity_groups=1")->assertOk()->assertJsonPath('data.audience.kind', 'activity_group');
        $preview = app(MessageFlow::class)->preview($actor, $id);
        app(MessageFlow::class)->submit($actor, $id, ['operation_id' => (string) Str::uuid(), 'revision' => $saved['revision'], 'fingerprint' => $preview['fingerprint']]);
        app(MessageDelivery::class)->process($id);
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $read = $this->api($parent)->getJson("$path/messages/$id")->assertOk()->json('data');
        $this->assertArrayNotHasKey('audience', $read);
        $this->assertSame($id, $read['id']);
    }

    public function test_parent_who_is_also_facilitator_can_read_own_child_group_without_roster_or_message_authority(): void
    {
        $group = $this->group();
        $this->member($group['id']);
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        SchoolMembership::create(['school_id' => 1, 'user_id' => $parent->id, 'role' => 'Facilitator', 'status' => 'active', 'starts_on' => '2026-01-01']);
        $parent->assignRole('Facilitator');
        app(ActivityGroups::class)->material($this->superAdmin(), $group['id'], null, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'title' => 'Private draft', 'markdown' => '# Draft', 'published' => false]);
        $details = app(ActivityGroups::class)->detail($parent, $group['id']);
        $this->assertFalse($details['can_capture']);
        $this->assertSame([], $details['materials']);
        $this->assertSame(1, app(ActivityGroups::class)->listing($parent, $this->year)['total']);
        $this->api($parent)->getJson('/api/v2/activities/groups/'.$group['id'].'/roster')->assertNotFound();
        $contacts = app(CommunicationRecipients::class)->allowed($parent, $this->year, '2026-02-10');
        $this->assertSame([], $contacts);
    }

    public function test_optional_eligibility_can_be_cleared_by_full_put_when_sdk_omits_null_properties(): void
    {
        $group = $this->group(['minimum_age' => 7]);
        $body = $this->data(['revision' => 1]);
        foreach (['minimum_age', 'maximum_age', 'minimum_grade', 'maximum_grade', 'parent_id'] as $field) {
            unset($body[$field]);
        }
        $this->api($this->superAdmin())->putJson('/api/v2/activities/groups/'.$group['id'], $body)->assertOk()->assertJsonPath('data.minimum_age', null)->assertJsonPath('data.parent_id', null);
    }

    public function test_every_group_endpoint_refuses_guest_wrong_current_role_and_foreign_school_before_validation(): void
    {
        $group = $this->group();
        $id = $group['id'];
        $member = $this->member($id);
        $coach = $this->coach($id);
        $staff = ActivityStaff::where('activity_group_id', $id)->firstOrFail();
        $session = app(ActivityGroups::class)->session($this->superAdmin(), $id, null, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'on_date' => '2026-02-10', 'name' => 'Matrix', 'venue' => 'Field', 'status' => 'scheduled']);
        $material = app(ActivityGroups::class)->material($this->superAdmin(), $id, null, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'title' => 'Matrix', 'markdown' => '# Fixture', 'published' => false]);
        $base = '/api/v2/activities';
        $requests = [['GET', "$base/groups"], ['POST', "$base/groups"], ['GET', "$base/groups/$id"], ['PUT', "$base/groups/$id"], ['GET', "$base/groups/$id/roster"], ['POST', "$base/groups/$id/memberships"], ['PUT', "$base/groups/$id/memberships/{$member['id']}"], ['POST', "$base/groups/$id/staff"], ['PUT', "$base/groups/$id/staff/{$staff->id}"], ['POST', "$base/groups/$id/sessions"], ['PUT', "$base/groups/$id/sessions/{$session['id']}"], ['POST', "$base/groups/$id/materials"], ['PUT', "$base/groups/$id/materials/{$material['id']}"], ['PUT', "$base/sessions/{$session['id']}/attendance"]];
        $before = ActivityOperation::count();
        foreach ($requests as [$method,$path]) {
            $this->app['auth']->forgetGuards();
            $this->flushHeaders()->json($method, $path)->assertUnauthorized();
        }
        $wrong = User::findOrFail($this->schoolA->ids['farid_user']);
        SchoolMembership::where('user_id', $wrong->id)->where('role', 'Parent')->update(['status' => 'ended']);
        SchoolMembership::create(['school_id' => 1, 'user_id' => $wrong->id, 'role' => 'Finance', 'status' => 'active', 'starts_on' => '2026-01-01']);
        foreach ($requests as [$method,$path]) {
            $this->api($wrong)->json($method, $path)->assertForbidden();
        }
        foreach ($requests as [$method,$path]) {
            $this->api($this->superAdmin())->withHeader('X-School-Id', '2')->json($method, $path)->assertForbidden();
        }
        $this->assertSame($before, ActivityOperation::count());
    }

    public function test_existing_message_panel_opts_in_to_activity_scopes_and_saves_the_exact_scope(): void
    {
        $group = $this->group();
        $this->member($group['id']);
        $this->actingAs($this->superAdmin());
        $page = $this->get(route('communication-centre.index', ['session_year_id' => $this->year]))->assertOk();
        $page->assertSee('activity_group:'.$group['id'], false)->assertSee('Activity group');
        $this->post(route('communication-centre.create'), ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $this->year, 'scope_selector' => 'activity_group:'.$group['id'], 'parents' => '1', 'title' => 'Coach fixture update', 'markdown' => '# Saturday fixture', 'channels' => ['in_app'], 'scheduled_local' => null])->assertRedirect()->assertSessionHasNoErrors();
        $saved = Message::where('author_id', $this->superAdmin()->id)->firstOrFail();
        $this->assertSame('activity_group', $saved->audience['kind']);
        $this->assertSame($group['id'], $saved->audience['target_id']);
    }

    public function test_pending_allocations_can_be_cancelled_and_cancelled_members_leave_no_future_audience(): void
    {
        $group = $this->group();
        $coach = $this->coach($group['id']);
        $member = $this->member($group['id']);
        $row = ActivityStaff::where('activity_group_id', $group['id'])->where('user_id', $coach->id)->firstOrFail();
        app(ActivityGroups::class)->allocation($this->superAdmin(), $group['id'], $row->id, true, $this->dates(['revision' => 1, 'user_id' => $coach->id, 'role' => 'facilitator', 'cascade' => false, 'active' => false]));
        $this->api($coach)->getJson('/api/v2/activities/groups/'.$group['id'])->assertNotFound();
        app(ActivityGroups::class)->allocation($this->superAdmin(), $group['id'], $member['id'], false, $this->dates(['revision' => 1, 'learner_id' => $this->schoolA->ids['asha'], 'role' => 'member', 'active' => false]));
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->assertSame([], app(CommunityAuthority::class)->visible($parent, $this->year));
        $this->api($parent)->getJson('/api/v2/activities/groups')->assertOk()->assertJsonCount(0, 'data.items');
        $this->getJson('/api/v2/activities/groups/'.$group['id'])->assertNotFound();
        $definition = ['kind' => 'activity_group', 'session_year_id' => $this->year, 'target_id' => $group['id'], 'parents' => true];
        $this->assertSame([], app(MessagePolicy::class)->recipients($this->superAdmin(), $definition, '2026-02-10'));
        $future = app(ActivityGroups::class)->allocation($this->superAdmin(), $group['id'], null, true, $this->dates(['starts_on' => '2026-05-01', 'user_id' => $coach->id, 'role' => 'facilitator', 'cascade' => false]));
        app(ActivityGroups::class)->allocation($this->superAdmin(), $group['id'], $future['id'], true, $this->dates(['starts_on' => '2026-05-01', 'revision' => 1, 'user_id' => $coach->id, 'role' => 'facilitator', 'cascade' => false, 'active' => false]));
        $this->assertFalse(ActivityStaff::findOrFail($future['id'])->active);
    }

    public function test_future_session_can_be_planned_but_attendance_cannot_be_invented_early(): void
    {
        $group = $this->group();
        $coach = $this->coach($group['id']);
        $this->member($group['id']);
        $session = app(ActivityGroups::class)->session($coach, $group['id'], null, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'on_date' => '2026-02-11', 'name' => 'Tomorrow', 'venue' => 'Field', 'status' => 'scheduled']);
        $this->api($coach)->putJson('/api/v2/activities/sessions/'.$session['id'].'/attendance', ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'reason' => 'Future capture', 'entries' => [['learner_id' => $this->schoolA->ids['asha'], 'status' => 'present', 'revision' => 0]]])->assertUnprocessable()->assertJsonPath('data.errors.on_date.0', 'Session attendance opens on the session date.');
        $this->assertSame(0, ActivityAttendance::count());
    }

    public function test_session_date_authority_is_reloaded_inside_the_write_transaction(): void
    {
        $group = $this->group();
        $coach = $this->coach($group['id']);
        $this->member($group['id']);
        ActivityStaff::where('user_id', $coach->id)->update(['starts_on' => '2026-02-01']);
        $session = app(ActivityGroups::class)->session($coach, $group['id'], null, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'on_date' => '2026-02-05', 'name' => 'Training', 'venue' => 'Field', 'status' => 'scheduled']);
        $change = function () use ($session) {
            ActivitySession::whereKey($session['id'])->update(['on_date' => '2026-01-15', 'revision' => 2]);
        };
        $this->app->instance(ActivityOperations::class, new class($change) extends ActivityOperations
        {
            public function __construct(private \Closure $change) {}

            public function once(User $actor, string $kind, array $data, callable $authority, callable $write): array
            {
                if (str_starts_with($kind, 'attendance:')) {
                    ($this->change)();
                }

                return parent::once($actor, $kind, $data, $authority, $write);
            }
        });
        try {
            app(ActivityGroups::class)->attendance($coach, $session['id'], ['operation_id' => (string) Str::uuid(), 'revision' => 2, 'reason' => 'Concurrent date edit', 'entries' => [['learner_id' => $this->schoolA->ids['asha'], 'status' => 'present', 'revision' => 0]]]);
            $this->fail('A stale session date bypassed dated staff authority.');
        } catch (HttpException $e) {
            $this->assertSame(404, $e->getStatusCode());
        }
        $this->assertSame(0, ActivityAttendance::count());
    }

    public function test_activity_panel_renders_named_scoped_choices_and_saves_definitions_allocations_sessions_and_materials(): void
    {
        $this->actingAs($this->superAdmin());
        $this->get(route('activity-groups.index'))->assertOk()->assertSee('New activity group')->assertHeader('cache-control', 'no-store, private');
        $this->post(route('activity-groups.create'), $this->data(['name' => 'Panel Hockey']))->assertRedirect()->assertHeader('Cache-Control', 'no-store, private');
        $group = ActivityGroup::where('name', 'Panel Hockey')->firstOrFail();
        $this->get(route('activity-groups.index', ['group_id' => $group->id]))->assertOk()->assertSee('Asha')->assertDontSee('Bea');
        $this->post(route('activity-groups.membership-save', ['group' => $group->id]), $this->dates(['learner_id' => $this->schoolA->ids['asha'], 'role' => 'member']))->assertRedirect();
        $this->post(route('activity-groups.staff-save', ['group' => $group->id]), $this->dates(['user_id' => $this->schoolA->ids['tara_user'], 'role' => 'facilitator', 'cascade' => false]))->assertRedirect();
        $this->post(route('activity-groups.session-save', ['group' => $group->id]), ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'on_date' => '2026-02-10', 'name' => 'Practice', 'venue' => 'Field', 'status' => 'scheduled'])->assertRedirect();
        $session = ActivitySession::where('activity_group_id', $group->id)->firstOrFail();
        $this->get(route('activity-groups.index', ['group_id' => $group->id, 'session_id' => $session->id]))->assertOk()->assertSee('Unrecorded')->assertSee('Asha');
        $this->post(route('activity-groups.attendance-save', ['group' => $group->id, 'session' => $session->id]), ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'reason' => 'Observed attendance', 'entries' => [['learner_id' => $this->schoolA->ids['asha'], 'revision' => 0, 'status' => 'late']]])->assertRedirect();
        $this->post(route('activity-groups.material-save', ['group' => $group->id]), ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'title' => 'Safe fixture', 'markdown' => '# Practice <script>alert(1)</script>', 'published' => true])->assertRedirect();
        $this->get(route('activity-groups.index', ['group_id' => $group->id]))->assertOk()->assertSee('Safe fixture')->assertDontSee('<script>alert(1)</script>', false);
        $this->assertSame('late', ActivityAttendance::firstOrFail()->status);
    }

    public function test_activity_panel_refuses_wrong_role_foreign_ids_and_cross_group_records_before_rendering(): void
    {
        $group = $this->group();
        $member = $this->member($group['id']);
        $other = $this->group(['name' => 'Other']);
        $this->actingAs(User::findOrFail($this->schoolA->ids['farid_user']));
        $this->get(route('activity-groups.index'))->assertRedirect()->assertSessionHasErrors();
        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']));
        $this->post(route('activity-groups.create'), $this->data())->assertForbidden();
        $this->actingAs($this->superAdmin());
        $this->get(route('activity-groups.index', ['group_id' => $other['id'], 'edit' => 'membership', 'record_id' => $member['id']]))->assertNotFound();
        $this->withHeader('X-School-Id', '2')->get(route('activity-groups.index', ['group_id' => $group['id']]))->assertForbidden();
    }

    public function test_activity_panel_keeps_validation_and_conflict_drafts_in_response_without_plain_session_flash(): void
    {
        $group = $this->group();
        $this->actingAs($this->superAdmin());
        $invalid = $this->data(['name' => 'Keep this draft', 'starts_on' => '2026-12-31', 'ends_on' => '2026-01-01']);
        $this->post(route('activity-groups.create'), $invalid)->assertUnprocessable()->assertHeader('Cache-Control', 'no-store, private')->assertSee('Keep this draft')->assertSessionMissing('_old_input');
        $this->post(route('activity-groups.create'), $this->data(['name' => ['malformed']]))->assertUnprocessable()->assertSessionMissing('_old_input');
        $this->post(route('activity-groups.save', ['group' => $group['id']]), $this->data(['name' => 'Conflicting draft']))->assertConflict()->assertSee('Conflicting draft')->assertSee('Hockey')->assertSee('reviewed_conflict', false)->assertSessionMissing('_old_input');
        $this->assertSame('Hockey', ActivityGroup::findOrFail($group['id'])->name);
    }

    public function test_activity_panel_refuses_explicit_historical_year_writes_even_with_current_header(): void
    {
        $year = SessionYear::create(['name' => 'Historical activity year', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31', 'default' => 0]);
        $group = $this->group(['session_year_id' => $year->id, 'starts_on' => '2025-01-01', 'ends_on' => '2025-12-31']);
        $this->actingAs($this->superAdmin());
        $this->get(route('activity-groups.index', ['group_id' => $group['id']]))->assertOk()->assertSee('This school year is read-only.')->assertSee('disabled', false);
        $this->post(route('activity-groups.save', ['group' => $group['id']]), $this->data(['session_year_id' => $year->id, 'starts_on' => '2025-01-01', 'ends_on' => '2025-12-31', 'revision' => 1]))->assertStatus(423);
        $this->assertSame(1, ActivityGroup::findOrFail($group['id'])->revision);
    }

    public function test_reparenting_keeps_the_entire_retained_subtree_within_twelve_levels(): void
    {
        $parent = null;
        for ($depth = 1; $depth <= 11; $depth++) {
            $parent = $this->group(['name' => 'Level '.$depth, 'parent_id' => $parent['id'] ?? null]);
        }
        $moving = $this->group(['name' => 'Moving root']);
        $child = $this->group(['name' => 'Retained child', 'parent_id' => $moving['id']]);
        $before = ActivityOperation::count();
        $this->api($this->superAdmin())->putJson('/api/v2/activities/groups/'.$moving['id'], $this->data(['name' => 'Moving root', 'revision' => 1, 'parent_id' => $parent['id']]))->assertUnprocessable();
        $this->assertNull(ActivityGroup::findOrFail($moving['id'])->parent_id);
        $this->assertSame([$child['id'], $moving['id']], app(CommunityAuthority::class)->lineage(ActivityGroup::findOrFail($child['id'])));
        $this->assertSame($before, ActivityOperation::count());
        $this->assertCount(13, app(CommunityAuthority::class)->visible($this->superAdmin(), $this->year));
        $this->putJson('/api/v2/activities/groups/'.$child['id'], $this->data(['name' => 'Retained child', 'revision' => 1, 'parent_id' => $moving['id'], 'active' => false]))->assertOk();
        $this->putJson('/api/v2/activities/groups/'.$moving['id'], $this->data(['name' => 'Moving root', 'revision' => 1, 'parent_id' => $parent['id']]))->assertUnprocessable();
        $this->putJson('/api/v2/activities/groups/'.$moving['id'], $this->data(['name' => 'Moving root', 'revision' => 1, 'parent_id' => $parent['parent_id']]))->assertOk();
        $this->assertCount(12, app(CommunityAuthority::class)->lineage(ActivityGroup::findOrFail($child['id'])));

    }

    public function test_panel_attendance_binds_the_actual_session_group_and_its_historical_year(): void
    {
        $current = $this->group();
        $year = SessionYear::create(['name' => 'Historical attendance year', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31', 'default' => 0]);
        $historical = $this->group(['session_year_id' => $year->id, 'starts_on' => '2025-01-01', 'ends_on' => '2025-12-31']);
        $session = ActivitySession::create(['activity_group_id' => $historical['id'], 'on_date' => '2025-02-10', 'name' => 'Retained session', 'venue' => 'Field', 'status' => 'scheduled', 'revision' => 1]);
        ActivityAttendance::create(['activity_session_id' => $session->id, 'learner_id' => $this->schoolA->ids['asha'], 'status' => 'present', 'recorded_by' => $this->superAdmin()->id, 'reason' => 'Original attendance', 'revision' => 1]);
        $input = ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'reason' => 'Forged current group correction', 'entries' => [['learner_id' => $this->schoolA->ids['asha'], 'status' => 'absent', 'revision' => 1]]];
        $receipts = ActivityOperation::count();
        $this->actingAs($this->superAdmin());
        $this->post(route('activity-groups.attendance-save', ['group' => $current['id'], 'session' => $session->id]), $input)->assertNotFound();
        $this->post(route('activity-groups.attendance-save', ['group' => $historical['id'], 'session' => $session->id]), $input)->assertStatus(423);
        $this->assertSame('present', ActivityAttendance::where('activity_session_id', $session->id)->firstOrFail()->status);
        $this->assertSame(1, $session->fresh()->revision);
        $this->assertSame($receipts, ActivityOperation::count());
    }

    public function test_large_attendance_receipt_survives_mysql_encryption_and_exact_replay(): void
    {
        // Exercise an already installed TEXT schema, then the forward upgrade.
        Schema::table('activity_operations', fn ($t) => $t->text('receipt')->change());
        $upgrade = require database_path('migrations/2026_10_25_040000_widen_activity_operation_receipts.php');
        $upgrade->up();
        $group = $this->group();
        $service = app(ActivityGroups::class);
        $session = $service->session($this->superAdmin(), $group['id'], null, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'on_date' => '2026-02-10', 'name' => 'Large register', 'venue' => 'Field', 'status' => 'scheduled']);
        $prototype = Students::findOrFail($this->schoolA->ids['asha']);
        $entries = [];
        for ($i = 0; $i < 40; $i++) {
            $user = User::create(['first_name' => 'Synthetic', 'last_name' => 'Register '.$i, 'email' => 'large-register-'.$i.'@example.test', 'password' => Hash::make('SyntheticRegister2026'), 'status' => 1]);
            $learner = $prototype->replicate();
            $learner->user_id = $user->id;
            $learner->admission_no = 'LARGE-REGISTER-'.$i;
            $learner->roll_number = 100 + $i;
            $learner->save();
            Enrolment::create(['learner_id' => $learner->id, 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic large register']);
            ActivityMembership::create(['activity_group_id' => $group['id'], 'learner_id' => $learner->id, 'role' => 'member', 'active' => true, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic large roster', 'revision' => 1]);
            $entries[] = ['learner_id' => $learner->id, 'status' => 'present', 'revision' => 0];
        }
        $input = ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'entries' => $entries, 'reason' => str_repeat('R', 2000)];
        $result = $service->attendance($this->superAdmin(), $session['id'], $input);
        $this->assertCount(40, $result['items']);
        $stored = DB::table('activity_operations')->where('operation_id', $input['operation_id'])->value('receipt');
        $this->assertGreaterThan(65535, strlen($stored));
        $this->assertSame($result, $service->attendance($this->superAdmin(), $session['id'], $input));
        $this->assertSame(40, ActivityAttendance::where('activity_session_id', $session['id'])->count());
        $this->assertSame(2, ActivitySession::findOrFail($session['id'])->revision);
        $this->assertSame(1, ActivityOperation::where('operation_id', $input['operation_id'])->count());
        try {
            $upgrade->down();
            $this->fail('Narrowing erased a retained encrypted register receipt.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('exceed TEXT capacity', $error->getMessage());
        }
    }
}
