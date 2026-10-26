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
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Identity\ApprovalService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class GroupChatPanelTest extends TestCase
{
    use UsesTwoSchools;

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

    private function path(string $suffix = ''): string
    {
        return '/group-conversations/groups/'.$this->group->id.$suffix;
    }

    private function input(array $values = []): array
    {
        return $values + ['operation_id' => (string) Str::uuid(), 'expected_account_id' => (int) $this->teacher->id,
            'expected_school_id' => 1, 'title' => 'Synthetic panel conversation <script>bad()</script>', 'body' => 'Synthetic panel staff text <script>bad()</script>', 'language' => 'en', 'kind' => 'announcement', 'audience' => 'guardians'];
    }

    public function test_allocated_teacher_creates_replays_replies_closes_and_removes_with_private_html(): void
    {
        $this->actingAs($this->teacher, 'web')->withSession(['school_id' => 1]);
        $this->get('/group-conversations')->assertOk()->assertSee($this->group->name)->assertHeader('Cache-Control', 'no-store, private');
        $input = $this->input();
        $this->post($this->path('/create'), $input)->assertRedirect();
        $thread = GroupChatThread::sole();
        $this->post($this->path('/create'), $input)->assertRedirect($this->path('/threads/'.$thread->id));
        $this->assertSame(1, GroupChatMessage::count());
        $this->get($this->path('/threads/'.$thread->id))->assertOk()->assertSee('Synthetic panel staff text &lt;script&gt;bad()&lt;/script&gt;', false)
            ->assertDontSee('<script>bad()</script>', false)->assertHeader('Referrer-Policy', 'no-referrer');
        $this->post($this->path('/threads/'.$thread->id.'/send'), $this->input(['body' => 'Synthetic second message', 'visibility' => 'thread']))->assertRedirect();
        $this->assertSame(2, GroupChatMessage::count());
        $this->post($this->path('/threads/'.$thread->id.'/state'), $this->input(['revision' => 1, 'closed' => true]))->assertRedirect();
        $this->get($this->path('/threads/'.$thread->id))->assertOk()->assertDontSee('Send reply');
        $message = GroupChatMessage::firstOrFail();
        $this->post($this->path('/threads/'.$thread->id.'/messages/'.$message->id.'/remove'), $this->input(['revision' => 1, 'reason' => 'Synthetic private correction']))->assertRedirect();
        $this->assertNull($message->fresh()->body);
        $this->get($this->path('/threads/'.$thread->id))->assertOk()->assertSee('Message removed')->assertDontSee('Synthetic panel staff text');
    }

    public function test_panel_scope_has_no_manager_platform_or_cross_school_shortcut(): void
    {
        $this->actingAs($this->admin, 'web')->withSession(['school_id' => 1]);
        $this->get($this->path())->assertForbidden()->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs($this->parent, 'web');
        $this->get('/group-conversations')->assertForbidden();
        $this->actingAs($this->teacher, 'web');
        $this->withSession(['school_id' => 2])->get($this->path())->assertRedirect(route('login'))->assertDontSee($this->group->name);
        $this->actingAs($this->teacher, 'web');
        $this->withSession(['school_id' => 1]);
        PlatformStaffIdentity::create(['user_id' => $this->teacher->id, 'active' => true]);
        $this->get($this->path())->assertForbidden();
        $this->post($this->path('/create'), $this->input())->assertForbidden()->assertSessionMissing('_old_input');
    }

    public function test_private_validation_never_flashes_drafts_and_cookie_replacement_cannot_write(): void
    {
        $this->actingAs($this->teacher, 'web')->withSession(['school_id' => 1]);
        $input = $this->input(['body' => 'Synthetic bounded rejected draft', 'title' => '']);
        $this->post($this->path('/create'), $input)->assertUnprocessable()->assertSee('Synthetic bounded rejected draft')
            ->assertHeader('Cache-Control', 'no-store, private')->assertSessionMissing('_old_input')->assertSessionMissing('errors');
        $this->post($this->path('/create'), $this->input(['expected_account_id' => (int) $this->parent->id]))->assertForbidden()->assertSessionMissing('_old_input');
        $this->post($this->path('/create'), $this->input(['expected_school_id' => 2]))->assertForbidden();
        $this->post($this->path('/create').'?body=Synthetic-query', $this->input())->assertUnprocessable()->assertSessionMissing('_old_input');
        $this->assertSame(0, GroupChatMessage::count());
    }

    public function test_selected_historical_year_cannot_post_or_flash_private_text(): void
    {
        $historical = SessionYear::create(['name' => 'Synthetic historic year', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31', 'default' => 0]);
        $this->actingAs($this->teacher, 'web')->withSession(['school_id' => 1, 'session_year' => (int) $historical->id]);
        $this->get($this->path())->assertOk()->assertDontSee('Create conversation');
        $this->post($this->path('/create'), $this->input())->assertStatus(423)->assertSessionMissing('_old_input')->assertDontSee('Synthetic panel staff text');
        $this->assertSame(0, GroupChatMessage::count());
    }

    public function test_metadata_lease_rechecks_account_and_retired_allocation_without_message_body(): void
    {
        $this->actingAs($this->teacher, 'web')->withSession(['school_id' => 1]);
        $lease = $this->path('/lease').'?expected_account_id='.$this->teacher->id.'&expected_school_id=1';
        $this->getJson($lease)->assertOk()->assertExactJson(['available' => true]);
        $this->getJson($this->path('/lease').'?expected_account_id='.$this->parent->id.'&expected_school_id=1')->assertForbidden();
        ActivityStaff::query()->update(['active' => false]);
        $this->getJson($lease)->assertNotFound()->assertHeader('Cache-Control', 'no-store, private');
        $this->get($this->path())->assertNotFound()->assertDontSee($this->group->name);
    }
}
