<?php

declare(strict_types=1);

namespace Tests\Feature\Activities;

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Activities\ActivityStaff;
use App\Models\Activities\Chat\GroupChatMessage;
use App\Models\Activities\Chat\GroupChatThread;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\Students;
use App\Models\User;
use App\Services\Activities\Chat\GroupChatRules;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class GroupChatApiTest extends TestCase
{
    use UsesTwoSchools;
    use ValidatesOpenApiResponses;

    private User $admin;

    private User $parent;

    private User $teacher;

    private int $learner;

    private ActivityGroup $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        Queue::fake();
        $this->travelTo('2026-02-10 12:00:00 UTC');
        SchoolMembership::query()->update(['starts_on' => '2026-01-01', 'ends_on' => null]);
        config(['modules.family_forms' => ['always_on' => true], 'modules.groups' => ['always_on' => true], 'modules.chat' => ['always_on' => true]]);
        $this->admin = $this->superAdmin();
        $this->parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->parent->forceFill(['email_verified_at' => now()])->save();
        $this->teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->learner = $this->schoolA->ids['asha'];
        $year = $this->schoolA->ids['session_year'];
        Enrolment::create(['learner_id' => $this->learner, 'session_year_id' => $year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
        GuardianLink::updateOrCreate(['learner_id' => $this->learner, 'guardian_user_id' => $this->parent->id], ['status' => 'active', 'relationship' => 'father', 'can_approve' => true]);
        app(ApprovalService::class)->decide($this->parent, Students::findOrFail($this->learner), 'approve', config('identity.consent_version'));
        $this->group = ActivityGroup::create(['session_year_id' => $year, 'name' => 'Synthetic consent group', 'discipline' => 'Culture', 'season' => '2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true]);
        ActivityStaff::create(['activity_group_id' => $this->group->id, 'user_id' => $this->teacher->id, 'role' => 'facilitator', 'cascade' => false, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic allocation', 'revision' => 1]);
        ActivityMembership::create(['activity_group_id' => $this->group->id, 'learner_id' => $this->learner, 'role' => 'member', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic group', 'revision' => 1]);
    }

    private function api(User $actor, string $profile): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($actor->createToken($profile)->plainTextToken)
            ->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $actor->id]);
    }

    private function base(): string
    {
        return '/api/v2/activities/groups/'.$this->group->id.'/chat';
    }

    private function create(array $changes = []): TestResponse
    {
        return $this->postJson($this->base().'/threads', $changes + ['operation_id' => (string) Str::uuid(), 'persona' => 'staff',
            'kind' => 'announcement', 'audience' => 'learners', 'title' => 'Synthetic group conversation', 'body' => 'Synthetic staff announcement', 'language' => 'en']);
    }

    public function test_staff_student_private_reply_round_trip_and_metadata_only_outcome_recovery(): void
    {
        $operation = (string) Str::uuid();
        $created = $this->api($this->teacher, AppTokens::TEACHER)->create(['operation_id' => $operation])->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data');
        $thread = $created['thread_id'];
        $this->assertSame('thread_created', $created['operation_type']);
        $recovery = $this->getJson($this->base().'/operations/'.$operation.'?persona=staff')->assertOk()->assertJsonPath('data.recorded', true);
        $recovery->assertDontSee('Synthetic staff announcement')->assertDontSee('Synthetic group conversation');
        $student = User::findOrFail($this->schoolA->ids['asha_user']);
        $this->api($student, AppTokens::STUDENT)->getJson($this->base().'/threads?persona=student')->assertOk()->assertJsonPath('data.items.0.id', $thread);
        $this->getJson($this->base().'/threads/'.$thread.'?persona=student')->assertOk()->assertJsonPath('data.items.0.body', 'Synthetic staff announcement')->assertJsonPath('data.can_post', true);
        $input = ['operation_id' => (string) Str::uuid(), 'persona' => 'student', 'visibility' => 'staff', 'body' => 'Synthetic private reply', 'language' => 'en'];
        $reply = $this->postJson($this->base().'/threads/'.$thread.'/messages', $input)->assertOk()->json('data');
        $this->postJson($this->base().'/threads/'.$thread.'/messages', $input)->assertOk()->assertJsonPath('data.message_id', $reply['message_id']);
        $this->postJson($this->base().'/threads/'.$thread.'/read', ['persona' => 'student', 'last_message_id' => $reply['message_id']])->assertOk();
        $this->getJson($this->base().'/operations/'.$operation.'?persona=student')->assertNotFound();
        $this->api($this->teacher, AppTokens::TEACHER)->getJson($this->base().'/threads/'.$thread.'?persona=staff')->assertOk()->assertJsonCount(2, 'data.items')->assertJsonPath('data.items.1.private', true);
        $this->assertSame(2, GroupChatMessage::count());
    }

    public function test_known_app_profiles_do_not_borrow_a_dual_roles_other_persona(): void
    {
        $thread = $this->api($this->teacher, AppTokens::TEACHER)->create(['audience' => 'guardians'])->assertOk()->json('data.thread_id');
        $this->parent->assignRole('Teacher');
        ActivityStaff::create(['activity_group_id' => $this->group->id, 'user_id' => $this->parent->id, 'role' => 'facilitator', 'cascade' => false, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic dual-role grant', 'revision' => 1]);
        $path = $this->base().'/threads/'.$thread;
        $this->api($this->parent, AppTokens::TEACHER)->getJson($path.'?persona=staff')->assertOk();
        $this->getJson($path.'?persona=parent')->assertForbidden()->assertDontSee('Synthetic staff announcement');
        $this->api($this->parent, AppTokens::PARENT)->getJson($path.'?persona=parent')->assertOk();
        $this->getJson($path.'?persona=staff')->assertForbidden();
        $this->api($this->parent, 'synthetic-legacy-account')->getJson($path.'?persona=parent')->assertOk();
        $own = $this->create(['persona' => 'parent', 'kind' => 'question', 'operation_id' => $operation = (string) Str::uuid()])->assertOk();
        $this->getJson($this->base().'/operations/'.$operation.'?persona=staff')->assertNotFound();
        $this->getJson($this->base().'/operations/'.$operation.'?persona=parent')->assertOk()->assertJsonPath('data.receipt.thread_id', $own->json('data.thread_id'));
        $this->withHeader('X-Account-Id', (string) $this->teacher->id)->getJson($path.'?persona=parent')->assertForbidden();
        $this->api($this->teacher, AppTokens::TEACHER)->withHeader('X-School-Id', '2')->getJson($path.'?persona=staff')->assertForbidden()->assertDontSee('Synthetic staff announcement');
    }

    public function test_cookie_profile_cannot_borrow_another_dual_role_or_retired_account(): void
    {
        config(['sanctum.stateful' => ['app.navigateed.co.za']]);
        $this->parent->assignRole('Teacher');
        ActivityStaff::create(['activity_group_id' => $this->group->id, 'user_id' => $this->parent->id, 'role' => 'facilitator', 'cascade' => false, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic dual-role grant', 'revision' => 1]);
        $this->actingAs($this->parent, 'web')->withSession(['portal_app' => AppTokens::PARENT])
            ->withHeaders(['Origin' => 'https://app.navigateed.co.za', 'X-School-Id' => '1', 'X-Account-Id' => (string) $this->parent->id]);
        $this->getJson($this->base().'/threads?persona=parent')->assertOk();
        $this->getJson($this->base().'/threads?persona=staff')->assertForbidden();
        $this->withHeader('X-Account-Id', (string) $this->teacher->id)->getJson($this->base().'/threads?persona=parent')
            ->assertForbidden()->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_group_detail_chat_capability_is_an_explicit_persona_bound_opt_in(): void
    {
        $path = '/api/v2/activities/groups/'.$this->group->id;
        $this->api($this->teacher, AppTokens::TEACHER)->getJson($path)->assertOk()->assertJsonMissingPath('data.chat_available');
        $this->getJson($path.'?chat_persona=staff')->assertOk()->assertJsonPath('data.chat_available', true);
        $this->getJson($path.'?chat_persona=parent')->assertForbidden();
        $this->api($this->parent, AppTokens::PARENT)->getJson($path.'?chat_persona=parent')->assertOk()->assertJsonPath('data.chat_available', true);
        // Management can inspect the group but is not an allocated conversation participant.
        $this->api($this->admin, AppTokens::TEACHER)->getJson($path.'?chat_persona=staff')->assertOk()->assertJsonPath('data.chat_available', false);
        config(['modules.chat' => ['default' => false, 'legacy' => false]]);
        SchoolModule::updateOrCreate(['school_id' => 1, 'module' => 'chat'], ['enabled' => false, 'read_only' => false, 'in_package' => true]);
        app(ModuleGate::class)->forget();
        $this->api($this->parent, AppTokens::PARENT)->getJson($path.'?chat_persona=parent')->assertOk()->assertJsonPath('data.chat_available', false);
    }

    public function test_unauthenticated_refusals_and_html_validation_are_private_without_session_drafts(): void
    {
        foreach (['GET', 'POST'] as $method) {
            $this->call($method, $this->base().'/threads', ['persona' => 'staff'], [], [], ['HTTP_ACCEPT' => 'text/html'])
                ->assertUnauthorized()->assertHeader('Cache-Control', 'no-store, private')->assertHeader('Referrer-Policy', 'no-referrer');
        }
        $this->api($this->teacher, AppTokens::TEACHER)->withHeader('Accept', 'text/html')->post($this->base().'/threads',
            ['persona' => 'staff', 'kind' => 'announcement', 'title' => 'Synthetic secret draft'])->assertUnprocessable()
            ->assertJsonPath('error', true)->assertHeader('Cache-Control', 'no-store, private')->assertSessionMissing('_old_input')->assertSessionMissing('errors');
        $this->post($this->base().'/threads?operation_id='.Str::uuid(), ['persona' => 'staff', 'body' => 'Synthetic private body'])
            ->assertUnprocessable()->assertSessionMissing('_old_input');
        $this->assertSame(0, GroupChatThread::count());
    }

    public function test_private_family_threads_are_filtered_before_other_parent_counts_or_text(): void
    {
        $created = $this->api($this->parent, AppTokens::PARENT)->create(['persona' => 'parent', 'kind' => 'question', 'title' => 'Synthetic private family title'])->assertOk()->json('data');
        $mother = User::findOrFail($this->schoolA->ids['mona_user']);
        GuardianLink::updateOrCreate(['learner_id' => $this->learner, 'guardian_user_id' => $mother->id], ['status' => 'active', 'can_approve' => true]);
        $this->api($mother, AppTokens::PARENT)->getJson($this->base().'/threads?persona=parent')->assertOk()->assertJsonCount(0, 'data.items')->assertDontSee('Synthetic private family title');
        $this->getJson($this->base().'/threads/'.$created['thread_id'].'?persona=parent')->assertNotFound()->assertDontSee('Synthetic private family title');
        $this->getJson($this->base().'/operations/'.Str::uuid().'?persona=parent')->assertNotFound();
        $this->api($this->teacher, AppTokens::FACILITATOR)->getJson($this->base().'/threads/'.$created['thread_id'].'?persona=staff')->assertOk();
    }

    public function test_policy_state_and_readonly_removal_do_not_authorize_new_messages(): void
    {
        $this->api($this->admin, AppTokens::TEACHER)->getJson('/api/v2/activities/chat-policy')->assertOk()->assertJsonPath('data.guardian_shared_replies', false);
        $this->putJson('/api/v2/activities/chat-policy', ['operation_id' => (string) Str::uuid(), 'revision' => 0] + GroupChatRules::DEFAULTS)->assertOk()->assertJsonPath('data.revision', 1);
        $created = $this->api($this->teacher, AppTokens::TEACHER)->create()->assertOk()->json('data');
        $path = $this->base().'/threads/'.$created['thread_id'];
        $state = ['operation_id' => (string) Str::uuid(), 'persona' => 'staff', 'revision' => 1, 'closed' => true];
        $this->postJson($path.'/state', $state)->assertOk()->assertJsonPath('data.closed', true);
        $this->postJson($path.'/messages', ['operation_id' => (string) Str::uuid(), 'persona' => 'staff', 'body' => 'Synthetic closed reply', 'language' => 'en', 'visibility' => 'thread'])->assertConflict();
        config(['modules.groups' => ['default' => true, 'legacy' => true]]);
        SchoolModule::updateOrCreate(['school_id' => 1, 'module' => 'groups'], ['enabled' => true, 'read_only' => true, 'in_package' => true]);
        app(ModuleGate::class)->forget();
        $this->getJson($path.'?persona=staff')->assertOk()->assertJsonPath('data.can_post', false)->assertJsonPath('data.can_reopen', false);
        $this->postJson($path.'/messages/'.$created['message_id'].'/remove', ['operation_id' => (string) Str::uuid(), 'persona' => 'staff', 'revision' => 1, 'reason' => 'Synthetic private removal reason'])
            ->assertOk()->assertJsonPath('data.removed', true)->assertHeader('Cache-Control', 'no-store, private');
        $this->assertNull(DB::table('activity_chat_messages')->value('body'));
    }
}
