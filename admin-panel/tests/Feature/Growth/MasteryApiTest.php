<?php

declare(strict_types=1);

namespace Tests\Feature\Growth;

use App\Jobs\Growth\RecomputeMastery;
use App\Models\Growth\TopicLink;
use App\Models\Platform\SchoolMembership;
use App\Services\Growth\MasteryRecompute;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\UsesMasteryScenario;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class MasteryApiTest extends TestCase
{
    use UsesMasteryScenario, ValidatesOpenApiResponses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
        config(['cache.default' => 'array']);
    }

    public function test_staff_configuration_roster_and_mastery_are_scoped_and_contract_typed(): void
    {
        $this->capture();
        app(MasteryRecompute::class)->group($this->group);
        $this->actingAs($this->actor())->getJson('/api/v2/growth/mastery/configuration')->assertOk()
            ->assertJsonPath('data.groups.0.id', $this->group)->assertJsonPath('data.can_manage', false);
        $this->getJson('/api/v2/growth/mastery/groups/'.$this->group.'/learners')->assertOk()
            ->assertJsonPath('data.learners.0.learner_id', $this->schoolA->ids['asha'])->assertJsonPath('data.total', 1);
        $this->getJson('/api/v2/growth/mastery/groups/'.$this->group.'/learners/'.$this->schoolA->ids['asha'])->assertOk()
            ->assertJsonPath('data.audience', 'staff')->assertHeader('Cache-Control', 'no-store, private');
        $this->getJson('/api/v2/growth/mastery/subjects/'.$this->subject.'/graph?group_id='.$this->group)->assertOk();
    }

    public function test_parent_reads_only_own_frozen_released_evidence(): void
    {
        $this->capture();
        $this->graph();
        $this->release();
        app(MasteryRecompute::class)->group($this->group);
        $this->actingAs($this->actor('farid'))->getJson('/api/v2/growth/mastery/learners/'.$this->schoolA->ids['asha'].'/subjects')->assertOk()->assertJsonPath('data.subjects.0.id', $this->subject);
        $url = '/api/v2/growth/mastery/learners/'.$this->schoolA->ids['asha'].'?subject_id='.$this->subject;
        $response = $this->actingAs($this->actor('farid'))->getJson($url)->assertOk()->assertJsonPath('data.audience', 'family')->assertJsonPath('data.marks_visible', true);
        $levels = array_column($response->json('data.topics'), 'level', 'topic_key');
        $this->assertSame(4, $levels[$this->keys[2]]);
        $this->actingAs($this->actor('gina'))->getJson($url)->assertNotFound();
        $this->actingAs($this->actor('farid'))->getJson('/api/v2/growth/mastery/learners/'.$this->schoolB->ids['asha'].'?subject_id='.$this->subject)->assertNotFound();
    }

    public function test_wrong_profiles_and_other_group_owners_are_refused_before_replay_or_validation(): void
    {
        foreach (['configuration', 'groups/'.$this->group.'/learners', 'subjects/'.$this->subject.'/graph?group_id='.$this->group] as $path) {
            $this->actingAs($this->actor('farid'))->getJson('/api/v2/growth/mastery/'.$path)->assertForbidden();
        }
        $this->actingAs($this->actor('farid'))->postJson('/api/v2/growth/mastery/groups/'.$this->group.'/rebuild')->assertForbidden();
        $this->actingAs($this->actor('theo'))->getJson('/api/v2/growth/mastery/groups/'.$this->group.'/learners')->assertForbidden();
        $this->actingAs($this->actor())->getJson('/api/v2/growth/mastery/groups/'.$this->group.'/learners/'.$this->schoolA->ids['ben'])->assertNotFound();
        $this->getJson('/api/v2/growth/mastery/groups/'.$this->group.'/learners/'.$this->schoolB->ids['asha'])->assertNotFound();
    }

    public static function nonTeachingTransitions(): array
    {
        return [['Counsellor'], ['Safeguarding'], ['Facilitator']];
    }

    #[DataProvider('nonTeachingTransitions')]
    public function test_retained_teacher_profile_cannot_read_mastery_after_role_transition(string $role): void
    {
        $actor = $this->actor();
        $this->actingAs($actor)->getJson('/api/v2/growth/mastery/groups/'.$this->group.'/learners')->assertOk();
        $actor->load('roles');
        $actor->syncRoles([$role]);
        $this->getJson('/api/v2/growth/mastery/configuration')->assertForbidden();
        $this->getJson('/api/v2/growth/mastery/groups/'.$this->group.'/learners')->assertForbidden();
        $this->getJson('/api/v2/growth/mastery/groups/'.$this->group.'/learners/'.$this->schoolA->ids['asha'])->assertForbidden();
        $this->getJson('/api/v2/growth/mastery/subjects/'.$this->subject.'/graph?group_id='.$this->group)->assertForbidden();
    }

    public function test_graph_can_start_empty_then_add_and_clear_edges_with_exact_revision(): void
    {
        $url = '/api/v2/growth/mastery/subjects/'.$this->subject.'/graph';
        $this->actingAs($this->superAdmin())->putJson($url, ['expected_revision' => 0, 'family_marks_enabled' => true, 'links' => []], ['Idempotency-Key' => (string) Str::uuid()])
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('data.settings.revision', 1)->assertJsonPath('data.links', []);
        $edge = ['topic_id' => $this->nodes[$this->keys[1]], 'prerequisite_id' => $this->nodes[$this->keys[0]], 'source' => 'Synthetic source reference'];
        $this->putJson($url, ['expected_revision' => 1, 'family_marks_enabled' => true, 'links' => [$edge]], ['Idempotency-Key' => (string) Str::uuid()])->assertOk();
        $this->assertSame(1, TopicLink::count());
        $this->putJson($url, ['expected_revision' => 2, 'family_marks_enabled' => true, 'links' => []], ['Idempotency-Key' => (string) Str::uuid()])->assertOk()->assertJsonPath('data.settings.revision', 3);
        $this->assertSame(0, TopicLink::count());
        $this->putJson($url, ['expected_revision' => 2, 'family_marks_enabled' => true, 'links' => []], ['Idempotency-Key' => (string) Str::uuid()])->assertConflict()->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_graph_receipt_replay_is_reauthorized_after_membership_revocation(): void
    {
        $actor = $this->superAdmin();
        $url = '/api/v2/growth/mastery/subjects/'.$this->subject.'/graph';
        $input = ['expected_revision' => 0, 'family_marks_enabled' => true, 'links' => []];
        $headers = ['Idempotency-Key' => (string) Str::uuid()];
        $this->actingAs($actor)->putJson($url, $input, $headers)->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('data.settings.revision', 1);
        $this->putJson($url, $input, $headers)->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('data.settings.revision', 1);
        SchoolMembership::query()->where('school_id', 1)->where('user_id', $actor->id)->update(['status' => 'ended']);
        $this->putJson($url, $input, $headers)->assertForbidden();
    }

    public function test_rebuild_is_queued_and_an_unapproved_learner_is_refused(): void
    {
        Queue::fake();
        $this->actingAs($this->actor())->postJson('/api/v2/growth/mastery/groups/'.$this->group.'/rebuild', [], ['Idempotency-Key' => (string) Str::uuid()])
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('data.queued', true);
        Queue::assertPushed(RecomputeMastery::class, fn ($job) => $job->schoolId === 1 && $job->groupId === $this->group);
        $this->actingAs($this->actor('asha'))->getJson('/api/v2/growth/mastery/learners/'.$this->schoolA->ids['asha'].'?subject_id='.$this->subject)->assertForbidden();
    }
}
