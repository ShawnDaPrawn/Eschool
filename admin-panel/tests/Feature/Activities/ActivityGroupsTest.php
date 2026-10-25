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
use Illuminate\Support\Facades\Queue;
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
        $group = $this->postJson($path, $this->data())->assertOk()->json('data');
        $id = $group['id'];
        $this->putJson("$path/$id", $this->data(['revision' => 1, 'name' => 'Hockey updated']))->assertOk()->assertJsonPath('data.revision', 2);
        $this->getJson($path)->assertOk()->assertJsonPath('data.total', 1);
        $member = $this->postJson("$path/$id/memberships", $this->dates(['learner_id' => $this->schoolA->ids['asha'], 'role' => 'member']))->assertOk()->json('data');
        $this->putJson("$path/$id/memberships/{$member['id']}", $this->dates(['revision' => 1, 'learner_id' => $this->schoolA->ids['asha'], 'role' => 'captain']))->assertOk();
        $staff = $this->postJson("$path/$id/staff", $this->dates(['user_id' => $this->schoolA->ids['tara_user'], 'role' => 'facilitator', 'cascade' => false]))->assertOk()->json('data');
        $this->putJson("$path/$id/staff/{$staff['id']}", $this->dates(['revision' => 1, 'user_id' => $this->schoolA->ids['tara_user'], 'role' => 'admin', 'cascade' => true]))->assertOk();
        $body = ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'on_date' => '2026-02-10', 'name' => 'Session', 'venue' => 'Field', 'status' => 'scheduled'];
        $session = $this->postJson("$path/$id/sessions", $body)->assertOk()->json('data');
        $body['revision'] = 1;
        $body['operation_id'] = (string) Str::uuid();
        $body['name'] = 'Training updated';
        $this->putJson("$path/$id/sessions/{$session['id']}", $body)->assertOk();
        $this->putJson('/api/v2/activities/sessions/'.$session['id'].'/attendance', ['operation_id' => (string) Str::uuid(), 'revision' => 2, 'reason' => 'Explicit register capture', 'entries' => [['learner_id' => $this->schoolA->ids['asha'], 'status' => 'present', 'revision' => 0]]])->assertOk()->assertJsonPath('data.session_revision', 3);
        $body = ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'title' => 'Fixture', 'markdown' => '# Fixtures', 'published' => false];
        $material = $this->postJson("$path/$id/materials", $body)->assertOk()->json('data');
        $body['revision'] = 1;
        $body['operation_id'] = (string) Str::uuid();
        $body['published'] = true;
        $this->putJson("$path/$id/materials/{$material['id']}", $body)->assertOk();
        $this->getJson("$path/$id/roster")->assertOk()->assertJsonPath('data.memberships.0.role', 'captain');
        $this->getJson("$path/$id")->assertOk()->assertJsonPath('data.materials.0.published', true);
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
        $this->assertSame(0,ActivityAttendance::count());
    }
}
