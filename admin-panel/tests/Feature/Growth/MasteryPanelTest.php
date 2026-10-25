<?php

declare(strict_types=1);

namespace Tests\Feature\Growth;

use App\Jobs\Growth\RecomputeMastery;
use App\Models\Growth\TopicLink;
use App\Models\Platform\SchoolModule;
use App\Services\Growth\MasteryRecompute;
use App\Services\Growth\TopicGraph;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\UsesMasteryScenario;
use Tests\TestCase;

class MasteryPanelTest extends TestCase
{
    use UsesMasteryScenario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
    }

    public function test_allocated_teacher_reads_guidance_and_queues_owned_repair_without_editor(): void
    {
        $this->capture();
        app(MasteryRecompute::class)->group($this->group);
        $this->actingAs($this->actor())->get('/growth-mastery')->assertOk()->assertViewIs('growth.mastery')
            ->assertSee('Maths learning group')->assertSee('Decimals')->assertDontSee('data-mastery-graph', false)->assertHeader('Cache-Control', 'no-store, private');
        Queue::fake();
        $this->post('/growth-mastery/groups/'.$this->group.'/rebuild')->assertRedirect();
        Queue::assertPushed(RecomputeMastery::class, fn ($job) => $job->groupId === $this->group && $job->schoolId === 1);
    }

    public function test_graph_manager_can_create_empty_add_sourced_edge_and_clear_with_revision_conflict(): void
    {
        $this->actingAs($this->superAdmin())->get('/growth-mastery')->assertOk()->assertSee('data-mastery-graph', false)->assertSee('Share mastery from released marks across this school');
        $url = '/growth-mastery/subjects/'.$this->subject;
        $this->post($url, ['graph_present' => 1, 'expected_revision' => 0, 'family_marks_enabled' => 1])->assertRedirect();
        $edge = ['topic_id' => $this->nodes[$this->keys[1]], 'prerequisite_id' => $this->nodes[$this->keys[0]], 'source' => 'Synthetic published reference'];
        $input = ['graph_present' => 1, 'expected_revision' => 1, 'family_marks_enabled' => 1, 'links' => [$edge]];
        $this->post($url, $input)->assertRedirect();
        $this->assertSame(1, TopicLink::count());
        $this->post($url, $input)->assertStatus(409)->assertHeader('Cache-Control', 'no-store, private')->assertViewIs('growth.mastery-conflict')->assertSee('Synthetic published reference');
        $this->post($url, ['graph_present' => 1, 'expected_revision' => 2, 'family_marks_enabled' => 0])->assertRedirect();
        $this->assertSame(0, TopicLink::count());
        $this->assertFalse(app(TopicGraph::class)->settings()['family_marks_enabled']);
    }

    public function test_wrong_role_owner_school_and_revoked_module_cannot_read_or_write_panel(): void
    {
        $this->actingAs($this->actor('farid'))->get('/growth-mastery')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->actingAs($this->actor('farid'))->post('/growth-mastery/subjects/'.$this->subject, [])->assertRedirect(route('login'));
        $this->assertGuest();
        $this->actingAs($this->actor('theo'))->get('/growth-mastery?group_id='.$this->group)->assertForbidden();
        $this->actingAs($this->actor())->get('/growth-mastery?group_id='.$this->group.'&learner_id='.$this->schoolB->ids['asha'])->assertNotFound();
        $this->post('/growth-mastery/subjects/'.$this->subject.'/refresh')->assertForbidden();
        $this->post('/growth-mastery/subjects/'.$this->schoolB->ids['maths'], [])->assertForbidden();
        $this->actingAs($this->superAdmin())->post('/growth-mastery/subjects/'.$this->schoolB->ids['maths'], [])->assertNotFound();
        SchoolModule::where('module', 'mastery')->update(['enabled' => false]);
        app(ModuleGate::class)->forget();
        $this->get('/growth-mastery')->assertNotFound();
    }

    public function test_invalid_sourced_graph_retains_escape_safe_text_without_changing_history(): void
    {
        $url = '/growth-mastery/subjects/'.$this->subject;
        $id = $this->nodes[$this->keys[1]];
        $text = '</template><script>window.invalid=true</script> Synthetic source';
        $this->actingAs($this->superAdmin())->from('/growth-mastery')->post($url, ['graph_present' => 1, 'expected_revision' => 0, 'family_marks_enabled' => 1,
            'links' => [['topic_id' => $id, 'prerequisite_id' => $id, 'source' => $text]]])->assertSessionHasErrors('links')->assertRedirect('/growth-mastery');
        $this->get('/growth-mastery')->assertOk()->assertSee($text)->assertDontSee($text, false);
        $this->assertSame(0, TopicLink::count());
        $this->assertSame(0, app(TopicGraph::class)->settings()['revision']);
    }
}
