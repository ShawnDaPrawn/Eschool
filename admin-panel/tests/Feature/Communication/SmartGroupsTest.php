<?php

declare(strict_types=1);

namespace Tests\Feature\Communication;

use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\ClassSchool;
use App\Models\Communication\Audience;
use App\Models\Communication\Message;
use App\Models\Communication\MessageRecipient;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Academics\TeachingGroups;
use App\Services\Communication\CommunicationAudiences;
use App\Services\Communication\MessageDelivery;
use App\Services\Communication\MessageFlow;
use App\Services\Communication\MessageQueries;
use App\Services\People\EnrolmentService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

/**
 * Smart groups, and private or shared saved audiences (Action Plan 2.5).
 * engineering/specs/communication/custom-groups.md.
 */
class SmartGroupsTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private int $year;

    private int $grade;

    private int $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10)->setTime(9, 0));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->year = $this->schoolA->ids['session_year'];
        $this->grade = (int) GradeLevel::where('code', '7')->value('id');
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => $this->grade]);
        foreach (['asha', 'ben'] as $key) {
            app(EnrolmentService::class)->place($this->schoolA->ids[$key], $this->schoolA->ids['section_a'], $this->year, '2026-01-01', 'Synthetic message enrolment');
        }
        $g = app(TeachingGroups::class)->save($this->superAdmin(), ['name' => 'Message fractions group', 'subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => $this->grade, 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'active' => true]);
        $this->group = (int) $g->id;
        app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', ['teacher_id' => Teacher::where('user_id', $this->teacher()->id)->value('id'), 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic message author'], $this->group);
        foreach (['asha', 'ben'] as $key) {
            app(TeachingGroups::class)->dated($this->superAdmin(), 'memberships', ['learner_id' => $this->schoolA->ids[$key], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'mark_rule' => 'catch_up', 'reason' => 'Synthetic audience member'], $this->group);
        }
        Queue::fake();
    }

    private function teacher(): User
    {
        return User::findOrFail($this->schoolA->ids['tara_user']);
    }

    private function person(string $key): User
    {
        return User::findOrFail($this->schoolA->ids[$key.'_user']);
    }

    private function data(array $changes = []): array
    {
        return $changes + ['revision' => 0, 'operation_id' => (string) Str::uuid(), 'title' => 'Private school message', 'markdown' => "# Fractions\n\nPrivate family details.",
            'channels' => ['in_app'], 'scheduled_local' => null, 'audience' => ['kind' => 'group', 'session_year_id' => $this->year, 'target_id' => $this->group, 'parents' => true]];
    }

    private function draft(?User $actor = null, array $changes = []): Message
    {
        $r = app(MessageFlow::class)->save($actor ?? $this->teacher(), null, $this->data($changes));

        return Message::findOrFail($r['id']);
    }

    private function submit(Message $m, ?User $actor = null): Message
    {
        $actor ??= $this->teacher();
        $preview = app(MessageFlow::class)->preview($actor, (int) $m->id);
        app(MessageFlow::class)->submit($actor, (int) $m->id, ['operation_id' => (string) Str::uuid(), 'revision' => $m->revision, 'fingerprint' => $preview['fingerprint']]);

        return $m->refresh();
    }

    private function approved(?Message $m = null): Message
    {
        $m = $this->submit($m ?? $this->draft());
        $preview = app(MessageFlow::class)->preview($this->superAdmin(), (int) $m->id);
        app(MessageFlow::class)->decide($this->superAdmin(), (int) $m->id, ['operation_id' => (string) Str::uuid(), 'revision' => $m->revision, 'fingerprint' => $preview['fingerprint'], 'decision' => 'approve', 'reason' => 'Reviewed synthetic audience and message']);

        return $m->refresh();
    }

    private function denied(callable $fn, int $code): void
    {
        try {
            $fn();
            $this->fail('Expected refusal');
        } catch (HttpExceptionInterface $e) {
            $this->assertSame($code, $e->getStatusCode());
        } catch (ModelNotFoundException $e) {
            $this->assertSame(404, $code);
        }
    }

    private function headers(string $person = 'tara'): array
    {
        $this->app['auth']->forgetGuards();

        return ['Authorization' => 'Bearer '.$this->schoolA->tokens[$person], 'X-School-Id' => '1'];
    }

    private function smart(?User $actor = null, array $changes = []): Audience
    {
        $receipt = app(CommunicationAudiences::class)->save($actor ?? $this->teacher(), null, $changes + ['operation_id' => (string) Str::uuid(), 'revision' => 0,
            'session_year_id' => $this->year, 'name' => 'Fractions families', 'active' => true, 'kind' => 'smart', 'shared' => false,
            'match' => 'any', 'parents' => true, 'rules' => [['kind' => 'group', 'target_id' => $this->group]]]);

        return Audience::findOrFail($receipt['id']);
    }

    private function toGroup(Audience $group, ?User $actor = null): Message
    {
        return $this->draft($actor, ['audience' => ['kind' => 'custom', 'session_year_id' => $this->year, 'target_id' => (int) $group->id, 'parents' => false]]);
    }

    private function recipients(Message $m): array
    {
        return MessageRecipient::where('message_id', $m->id)->orderBy('user_id')->pluck('user_id')->map(fn ($id) => (int) $id)->all();
    }

    private function ids(string ...$people): array
    {
        $ids = array_map(fn ($p) => (int) $this->person($p)->id, $people);
        sort($ids);

        return $ids;
    }

    public function test_a_smart_group_is_resolved_again_when_the_message_is_sent(): void
    {
        $group = $this->smart(null, ['parents' => false]);
        $this->assertSame('smart', $group->kind);
        $this->assertSame(2, app(CommunicationAudiences::class)->count($group));
        $m = $this->toGroup($group);
        $this->assertSame(2, app(MessageFlow::class)->preview($this->teacher(), (int) $m->id)['recipient_count']);
        $this->assertSame(true, $m->audience['smart']['rules'] === [['kind' => 'group', 'target_id' => $this->group]]);
        $this->assertArrayNotHasKey('user_ids', $m->audience);

        // Cara joins the homeroom and the group after the group and the message were saved.
        app(EnrolmentService::class)->place($this->schoolA->ids['cara'], $this->schoolA->ids['section_a'], $this->year, '2026-01-01', 'Synthetic later member');
        app(TeachingGroups::class)->dated($this->superAdmin(), 'memberships', ['learner_id' => $this->schoolA->ids['cara'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'mark_rule' => 'catch_up', 'reason' => 'Synthetic later member'], $this->group);
        $m = $this->approved($m);
        app(MessageDelivery::class)->process((int) $m->id);
        $this->assertSame($this->ids('asha', 'ben', 'cara'), $this->recipients($m));
    }

    public function test_guardians_and_all_of_the_rules(): void
    {
        $guardians = $this->smart();
        $m = $this->approved($this->toGroup($guardians));
        app(MessageDelivery::class)->process((int) $m->id);
        $this->assertSame($this->ids('farid', 'mona', 'gina'), $this->recipients($m));

        $both = $this->smart($this->superAdmin(), ['parents' => false, 'match' => 'all', 'rules' => [['kind' => 'grade', 'target_id' => $this->grade], ['kind' => 'group', 'target_id' => $this->group]]]);
        $this->assertSame(2, app(CommunicationAudiences::class)->count($both));
        $none = $this->smart($this->superAdmin(), ['parents' => false, 'match' => 'all', 'rules' => [['kind' => 'homeroom', 'target_id' => $this->schoolA->ids['section_c']], ['kind' => 'group', 'target_id' => $this->group]]]);
        $this->assertSame(0, app(CommunicationAudiences::class)->count($none));
        $either = $this->smart($this->superAdmin(), ['parents' => false, 'match' => 'any', 'rules' => [['kind' => 'homeroom', 'target_id' => $this->schoolA->ids['section_c']], ['kind' => 'group', 'target_id' => $this->group]]]);
        $this->assertSame(2, app(CommunicationAudiences::class)->count($either));
    }

    public function test_rules_need_the_authors_authority_and_a_shared_group_needs_it_for_its_user_too(): void
    {
        // Wrong role: a parent may not save audiences.
        $this->denied(fn () => $this->smart($this->person('farid')), 403);
        // Theo has no allocation to the group or grade: he may not make a rule of them.
        $this->denied(fn () => $this->smart($this->person('theo')), 403);
        $this->denied(fn () => $this->smart(null, ['rules' => [['kind' => 'grade', 'target_id' => $this->grade]]]), 403);

        // Wrong owner: a private group is Tara's alone.
        $private = $this->smart();
        $this->assertSame([], array_column(app(CommunicationAudiences::class)->listing($this->person('theo'), $this->year), 'id'));
        $this->denied(fn () => $this->toGroup($private, $this->person('theo')), 404);
        $this->denied(fn () => app(CommunicationAudiences::class)->owned($this->person('theo'), (int) $private->id), 404);

        // Shared, it is offered only to staff who may address every rule; anyone else is refused.
        $shared = $this->smart(null, ['shared' => true, 'name' => 'Shared fractions families']);
        $this->assertNotContains((int) $shared->id, array_column(app(CommunicationAudiences::class)->listing($this->person('theo'), $this->year), 'id'));
        $this->denied(fn () => $this->toGroup($shared, $this->person('theo')), 403);
        $this->assertContains((int) $shared->id, array_column(app(CommunicationAudiences::class)->listing($this->superAdmin(), $this->year), 'id'));

        // An authority lost after saving stops the message at sending.
        $m = $this->approved($this->toGroup($shared));
        TeachingAllocation::where('teaching_group_id', $this->group)->update(['ends_on' => '2026-02-01']);
        app(MessageDelivery::class)->process((int) $m->id);
        $this->assertSame('blocked', $m->refresh()->state);
        $this->assertSame([], $this->recipients($m));
    }

    public function test_another_schools_groups_and_audiences_are_out_of_reach(): void
    {
        $group = $this->smart();
        app(CurrentSchool::class)->run(2, function () use ($group) {
            $tara = User::findOrFail($this->schoolB->ids['tara_user']);
            $this->assertNull(Audience::find($group->id));
            $this->denied(fn () => app(CommunicationAudiences::class)->owned($tara, (int) $group->id), 404);
        });
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v2/communication/audiences?session_year_id='.$this->schoolB->ids['session_year'], ['Authorization' => 'Bearer '.$this->schoolB->tokens['tara'], 'X-School-Id' => '2'])
            ->assertOk()->assertDontSee('Fractions families');
        // A rule naming School B's homeroom is not found at School A.
        $this->denied(fn () => $this->smart($this->superAdmin(), ['rules' => [['kind' => 'homeroom', 'target_id' => $this->schoolB->ids['section_a'] + 100000]]]), 404);
    }

    public function test_kind_is_fixed_once_saved_and_rules_are_validated(): void
    {
        $group = $this->smart();
        $this->denied(fn () => app(CommunicationAudiences::class)->save($this->teacher(), (int) $group->id, ['operation_id' => (string) Str::uuid(), 'revision' => $group->revision,
            'session_year_id' => $this->year, 'name' => 'Now a list', 'active' => true, 'kind' => 'fixed', 'user_ids' => [$this->person('farid')->id]]), 422);
        $this->postJson('/api/v2/communication/audiences', ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $this->year, 'name' => 'Too many',
            'active' => true, 'kind' => 'smart', 'match' => 'any', 'parents' => true, 'rules' => array_fill(0, 11, ['kind' => 'group', 'target_id' => $this->group])], $this->headers())->assertUnprocessable();
        $this->postJson('/api/v2/communication/audiences', ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $this->year, 'name' => 'Fixed with rules',
            'active' => true, 'user_ids' => [$this->person('farid')->id], 'rules' => [['kind' => 'group', 'target_id' => $this->group]]], $this->headers())->assertUnprocessable();
        $this->postJson('/api/v2/communication/audiences', ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $this->year, 'name' => 'Whole school',
            'active' => true, 'kind' => 'smart', 'match' => 'any', 'parents' => true, 'rules' => [['kind' => 'school', 'target_id' => 1]]], $this->headers())->assertUnprocessable();
    }

    public function test_the_app_and_panel_create_list_and_use_smart_groups(): void
    {
        $created = $this->postJson('/api/v2/communication/audiences', ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $this->year, 'name' => 'App fractions families',
            'active' => true, 'kind' => 'smart', 'shared' => true, 'match' => 'any', 'parents' => true, 'rules' => [['kind' => 'group', 'target_id' => $this->group]]], $this->headers())->assertOk();
        $id = (int) $created->json('data.id');
        $list = $this->getJson('/api/v2/communication/audiences?session_year_id='.$this->year, $this->headers())->assertOk();
        $row = collect($list->json('data.items'))->firstWhere('id', $id);
        $this->assertSame(['kind' => 'smart', 'shared' => true, 'user_ids' => []], ['kind' => $row['kind'], 'shared' => $row['shared'], 'user_ids' => $row['user_ids']]);
        $this->assertSame([['kind' => 'group', 'target_id' => $this->group]], $row['smart']['rules']);
        // A fixed list written as before stays fixed and shared.
        $fixed = $this->postJson('/api/v2/communication/audiences', ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $this->year, 'name' => 'Old style list',
            'active' => true, 'user_ids' => [$this->person('farid')->id]], $this->headers())->assertOk();
        $this->assertSame(['fixed', true], [Audience::findOrFail($fixed->json('data.id'))->kind, Audience::findOrFail($fixed->json('data.id'))->shared]);

        $this->actingAs($this->teacher(), 'web')->post('/communication-centre/audiences', ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $this->year,
            'kind' => 'smart', 'name' => 'Panel fractions learners', 'active' => '1', 'shared' => '0', 'match' => 'any', 'parents' => '0', 'rule_selectors' => ['group:'.$this->group]])->assertRedirect();
        $panel = Audience::where('kind', 'smart')->orderByDesc('id')->firstOrFail();
        $this->assertSame([['kind' => 'group', 'target_id' => $this->group]], $panel->definition['rules']);
        $this->assertFalse($panel->shared);
        $this->get('/communication-centre?audience_id='.$panel->id)->assertOk()->assertSee('Save smart group')->assertSee('People this group reaches today: 2', false);
        $this->get('/communication-centre')->assertOk()->assertSee('custom:'.$panel->id, false);
        $this->actingAs($this->person('theo'), 'web')->get('/communication-centre?audience_id='.$panel->id)->assertNotFound();
        $this->post('/communication-centre/audiences', ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $this->year,
            'kind' => 'smart', 'name' => 'Bad', 'active' => '1', 'match' => 'any', 'parents' => '0', 'rule_selectors' => ['school:0']])->assertStatus(422);
    }

    public function test_a_group_whose_rule_was_deactivated_stays_editable_but_cannot_be_sent(): void
    {
        $group = $this->smart(null, ['parents' => false]);
        TeachingGroup::whereKey($this->group)->update(['active' => false]);
        $this->assertNull(app(CommunicationAudiences::class)->count($group));
        $this->actingAs($this->teacher(), 'web')->get('/communication-centre?audience_id='.$group->id)->assertOk()
            ->assertSee('A rule names a group that is no longer active.')->assertSee('Save smart group');
        $this->denied(fn () => $this->toGroup($group), 404);
    }

    private function staleSmartAudience(): Audience
    {
        $group = $this->smart(null, ['parents' => false]);
        TeachingGroup::whereKey($this->group)->update(['active' => false]);
        $this->assertSame([], app(MessageQueries::class)->configuration($this->teacher(), $this->year, true)['scopes']);

        return $group;
    }

    private function archiveData(Audience $group): array
    {
        return ['operation_id' => (string) Str::uuid(), 'revision' => $group->revision, 'session_year_id' => $this->year,
            'name' => $group->name, 'kind' => 'smart', 'active' => false, 'shared' => $group->shared,
            'match' => $group->definition['match'], 'parents' => $group->definition['parents'], 'rules' => $group->definition['rules']];
    }

    public function test_panel_can_archive_existing_stale_smart_audience_without_any_current_scope(): void
    {
        $group = $this->staleSmartAudience();
        $definition = $group->definition;
        $this->actingAs($this->teacher(), 'web')->get('/communication-centre?audience_id='.$group->id)
            ->assertOk()->assertSee('Remove that rule or archive the group');
        $this->post('/communication-centre/audiences/'.$group->id, $this->archiveData($group) + ['rule_selectors' => []])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse($group->fresh()->active);
        $this->assertSame($definition, $group->fresh()->definition);
        $this->denied(fn () => $this->toGroup($group->fresh()), 404);
    }

    public function test_api_can_archive_existing_stale_smart_audience_with_its_retained_rule(): void
    {
        $group = $this->staleSmartAudience();
        $definition = $group->definition;
        $this->putJson('/api/v2/communication/audiences/'.$group->id, $this->archiveData($group), $this->headers())->assertOk();
        $this->assertFalse($group->fresh()->active);
        $this->assertSame($definition, $group->fresh()->definition);
        $this->denied(fn () => $this->toGroup($group->fresh()), 404);
    }

    public function test_a_manager_can_archive_a_stale_smart_audience_but_other_people_and_schools_cannot(): void
    {
        $group = $this->staleSmartAudience();
        $input = $this->archiveData($group);
        $this->putJson('/api/v2/communication/audiences/'.$group->id, $input, $this->headers('theo'))->assertNotFound();
        $this->actingAs($this->person('theo'), 'web')->post('/communication-centre/audiences/'.$group->id, $input + ['rule_selectors' => []])->assertNotFound();
        $this->app['auth']->forgetGuards();
        $this->putJson('/api/v2/communication/audiences/'.$group->id, $input, ['Authorization' => 'Bearer '.$this->schoolB->tokens['tara'], 'X-School-Id' => '2'])->assertNotFound();
        $this->app['auth']->forgetGuards();
        $this->putJson('/api/v2/communication/audiences/'.$group->id, $input, ['Authorization' => 'Bearer '.$this->schoolA->tokens['farid'], 'X-School-Id' => '1'])->assertForbidden();
        $this->assertTrue($group->fresh()->active);
        $this->assertSame(1, $group->fresh()->revision);

        $definition = $group->definition;
        $this->actingAs($this->superAdmin(), 'web')->post('/communication-centre/audiences/'.$group->id, $input + ['rule_selectors' => []])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse($group->fresh()->active);
        $this->assertSame($definition, $group->fresh()->definition);
    }

    public function test_archival_does_not_accept_new_empty_audiences_invalid_rule_schema_or_changed_stale_rules(): void
    {
        $group = $this->staleSmartAudience();
        $url = '/api/v2/communication/audiences/'.$group->id;
        $input = $this->archiveData($group);
        $this->putJson($url, array_replace($input, ['rules' => [['kind' => 'school', 'target_id' => 1]]]), $this->headers())->assertUnprocessable();
        $this->putJson($url, array_replace($input, ['rules' => [['kind' => 'group', 'target_id' => 999999]]]), $this->headers())->assertNotFound();
        $this->putJson($url, array_replace($input, ['active' => true, 'rules' => []]), $this->headers())->assertUnprocessable();
        $this->putJson($url, array_replace($input, ['active' => true]), $this->headers())->assertNotFound();
        // The API keeps its documented nonempty rule schema; it can retain the old rule to archive.
        $this->putJson($url, array_replace($input, ['rules' => []]), $this->headers())->assertUnprocessable();
        $this->postJson('/api/v2/communication/audiences', array_replace($input, ['revision' => 0, 'rules' => []]), $this->headers())->assertUnprocessable();
        $this->assertTrue($group->fresh()->active);
        $this->assertSame(1, $group->fresh()->revision);
        $this->assertSame(1, Audience::count());
    }

    public function test_valid_inactive_rule_edits_still_work_and_an_archived_group_cannot_be_reactivated_with_stale_rules(): void
    {
        $group = $this->smart(null, ['parents' => false]);
        $input = array_replace($this->archiveData($group), ['parents' => true]);
        $this->putJson('/api/v2/communication/audiences/'.$group->id, $input, $this->headers())->assertOk();
        $this->assertFalse($group->fresh()->active);
        $this->assertTrue($group->fresh()->definition['parents']);

        TeachingGroup::whereKey($this->group)->update(['active' => false]);
        $this->putJson('/api/v2/communication/audiences/'.$group->id,
            array_replace($this->archiveData($group->fresh()), ['active' => true]), $this->headers())->assertNotFound();
        $this->assertFalse($group->fresh()->active);
        $this->denied(fn () => $this->toGroup($group->fresh()), 404);
    }
}
