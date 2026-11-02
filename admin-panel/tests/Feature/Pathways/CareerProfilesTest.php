<?php

declare(strict_types=1);

namespace Tests\Feature\Pathways;

use App\Models\ClassSchool;
use App\Models\Growth\MasteryRecord;
use App\Models\Pathways\CareerProfile;
use App\Models\Pathways\CareerProfileVersion;
use App\Models\Pathways\PathwayOperation;
use App\Models\Platform\SchoolModule;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Academics\TeachingGroups;
use App\Services\Growth\MasteryRecompute;
use App\Services\Pathways\CareerGuidance;
use App\Services\Pathways\CareerProfiles;
use App\Services\Pathways\PathwayReferences;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesMasteryScenario;
use Tests\TestCase;

/** Action Plan 4.9: career profiles and the staff "what would it take" view. */
class CareerProfilesTest extends TestCase
{
    use UsesMasteryScenario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
        SchoolModule::updateOrCreate(['module' => 'pathway'], ['in_package' => true, 'enabled' => true]);
        app(ModuleGate::class)->forget();
        foreach (['tara', 'theo', 'farid', 'asha'] as $name) {
            $this->actor($name)->forceFill(['password_change_required' => false])->save();
        }
        Http::preventStrayRequests();
    }

    private function input(array $changes = []): array
    {
        return $changes + ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'status' => 'published', 'title' => 'Synthetic electrician',
            'summary' => 'Synthetic source fixture: installs and maintains electrical systems.', 'source_title' => 'Synthetic 2026 career guide',
            'source_url' => 'https://careers.example.test/electrician', 'source_year' => 2026, 'reviewed_on' => '2026-02-10', 'expires_on' => '2026-12-31',
            'details' => ['subjects' => [['subject_ids' => [$this->subject], 'minimum_bp' => 5000, 'requirement' => 'required'],
                ['subject_ids' => [$this->schoolA->ids['science']], 'minimum_bp' => null, 'requirement' => 'recommended']],
                'topic_ids' => [$this->nodes[$this->keys[1]], $this->nodes[$this->keys[2]]],
                'routes' => [
                    ['type' => 'tvet', 'title' => 'Synthetic NC(V) Electrical Infrastructure', 'provider' => 'Synthetic TVET college', 'entry_note' => 'Grade 9 pass, as the source states.',
                        'source_title' => 'Synthetic college prospectus', 'source_url' => 'https://college.example.test/ncv', 'programme_id' => null],
                    ['type' => 'learnership', 'title' => 'Synthetic electrical learnership', 'provider' => 'Synthetic SETA', 'entry_note' => 'Grade 12 with Mathematics, as the source states.',
                        'source_title' => 'Synthetic SETA listing', 'source_url' => 'https://seta.example.test/electrical', 'programme_id' => null],
                ]]];
    }

    private function publish(array $changes = []): array
    {
        return app(CareerProfiles::class)->save($this->superAdmin(), null, $this->input($changes));
    }

    private function refused(callable $call, array $codes): void
    {
        try {
            $call();
            $this->fail('An unauthorized or invalid career request succeeded.');
        } catch (HttpExceptionInterface|ModelNotFoundException $e) {
            $this->assertContains($e instanceof HttpExceptionInterface ? $e->getStatusCode() : 404, $codes);
        }
    }

    private function counts(): array
    {
        return [CareerProfile::count(), CareerProfileVersion::count(), PathwayOperation::count()];
    }

    private function evidence(): void
    {
        $this->capture();
        $this->graph();
        app(MasteryRecompute::class)->group($this->group);
    }

    /** Theo teaches a science group without Asha in it. */
    private function otherTeacher(): User
    {
        $group = app(TeachingGroups::class)->save($this->superAdmin(), ['name' => 'Science learning group', 'subject_id' => $this->schoolA->ids['science'],
            'grade_level_id' => ClassSchool::findOrFail($this->schoolA->ids['class_a'])->grade_level_id, 'session_year_id' => $this->year,
            'class_section_id' => $this->schoolA->ids['section_a'], 'active' => true]);
        app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', ['teacher_id' => Teacher::where('user_id', $this->schoolA->ids['theo_user'])->value('id'),
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Allocated subject'], $group->id);

        return $this->actor('theo');
    }

    public function test_manager_publishes_a_sourced_profile_with_tvet_and_learnership_routes_and_history_is_immutable(): void
    {
        $service = app(CareerProfiles::class);
        $input = $this->input();
        $first = $service->save($this->superAdmin(), null, $input);
        $this->assertSame($first, $service->save($this->superAdmin(), null, $input));
        $this->assertSame(['tvet', 'learnership'], array_column($first['details']['routes'], 'type'));
        $this->assertSame([$this->nodes[$this->keys[1]], $this->nodes[$this->keys[2]]], $first['details']['topic_ids']);
        $this->assertFalse($first['stale']);
        $second = $service->save($this->superAdmin(), $first['id'], $this->input(['revision' => 1, 'title' => 'Synthetic electrician, reviewed']));
        $this->assertSame(2, $second['revision']);
        $this->assertSame([1, 2, 2], $this->counts());
        $this->refused(fn () => $service->save($this->superAdmin(), $first['id'], $this->input(['revision' => 1])), [409]);
        $this->refused(fn () => $service->save($this->superAdmin(), null, array_replace($input, ['title' => 'Changed retry'])), [409]);
        $this->expectException(\LogicException::class);
        CareerProfileVersion::firstOrFail()->update(['summary' => 'Rewrite retained history']);
    }

    public function test_university_route_links_a_programme_and_every_route_needs_a_public_source(): void
    {
        $programme = app(PathwayReferences::class)->save($this->superAdmin(), null, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'kind' => 'programme', 'status' => 'published',
            'title' => 'Synthetic BEng Electrical', 'institution' => 'Synthetic university', 'source_title' => 'Synthetic prospectus', 'source_url' => 'https://uni.example.test/beng',
            'source_year' => 2026, 'reviewed_on' => '2026-02-10', 'expires_on' => '2026-12-31', 'guidance' => 'Synthetic source fixture.',
            'rules' => ['subjects' => [['subject_id' => $this->subject, 'minimum_bp' => 6000, 'counts_for_score' => true]], 'bands' => [['minimum_bp' => 0, 'points' => 0], ['minimum_bp' => 6000, 'points' => 5]], 'best_n' => 1, 'minimum_score' => 5]]);
        $university = ['type' => 'university', 'title' => 'Synthetic BEng', 'provider' => 'Synthetic university', 'entry_note' => 'See the programme source.',
            'source_title' => 'Synthetic prospectus', 'source_url' => 'https://uni.example.test/beng', 'programme_id' => $programme['id']];
        $input = $this->input();
        $input['details']['routes'][] = $university;
        $profile = app(CareerProfiles::class)->save($this->superAdmin(), null, $input);
        $view = app(CareerGuidance::class)->view($this->superAdmin(), $this->schoolA->ids['asha'], $profile['id']);
        $this->assertSame('Synthetic BEng Electrical', $view['routes'][2]['programme']['title']);
        $before = $this->counts();
        foreach ([['source_url' => 'http://college.example.test/ncv'], ['source_url' => 'https://127.0.0.1/x'], ['type' => 'tvet', 'programme_id' => $programme['id']]] as $change) {
            $bad = $this->input();
            $bad['details']['routes'][0] = array_replace($bad['details']['routes'][0], $change);
            try {
                app(CareerProfiles::class)->save($this->superAdmin(), null, $bad);
                $this->fail('An unsourced or mislinked route was published.');
            } catch (HttpExceptionInterface|ValidationException) {
                $this->assertSame($before, $this->counts());
            }
        }
    }

    public function test_what_would_it_take_compares_subjects_and_shows_mastery_gaps_with_unknown_never_zero(): void
    {
        $profile = $this->publish();
        $this->evidence();
        $view = app(CareerGuidance::class)->view($this->actor('tara'), $this->schoolA->ids['asha'], $profile['id']);
        $this->assertSame([true, false], array_column($view['subjects'], 'taking'));
        $this->assertSame([$this->subject], array_column($view['current_subjects'], 'id'));
        $maths = $view['topics'][0];
        $this->assertSame('shared', $maths['evidence']);
        $levels = array_column($maths['topics'], 'level', 'id');
        $this->assertSame(1, $levels[$this->nodes[$this->keys[1]]]);
        $this->assertSame(4, $levels[$this->nodes[$this->keys[2]]]);
        $this->assertSame($this->nodes[$this->keys[0]], $maths['starting_points'][0]['topic_id']);
        $this->assertNull($maths['starting_points'][0]['level']);
        $this->assertSame('not_yet_assessed', $maths['starting_points'][0]['reason']);
        $before = [MasteryRecord::count(), PathwayOperation::count()];
        $this->actingAs($this->actor('tara'))->get('/pathways/guidance?learner_id='.$this->schoolA->ids['asha'].'&profile_id='.$profile['id'])->assertOk()
            ->assertSee('Synthetic electrician')->assertSee('Not yet assessed')->assertSee('TVET college')->assertSee('Learnership')
            ->assertSee('Not in current subjects yet.')->assertDontSee('not suitable')->assertDontSee(self::SCHOOL_B_MARKER);
        $this->get('/pathways/guidance?q=Asha')->assertOk()->assertSee('learner_id='.$this->schoolA->ids['asha'], false);
        $this->assertSame($before, [MasteryRecord::count(), PathwayOperation::count()]);
    }

    public function test_panel_publishes_a_profile_and_flags_it_once_its_review_date_passes(): void
    {
        $body = ['expected_account_id' => $this->superAdmin()->id, 'expected_school_id' => 1, 'operation_id' => (string) Str::uuid(), 'revision' => 0, 'status' => 'published',
            'title' => 'Synthetic electrician', 'summary' => 'Synthetic source fixture.', 'source_title' => 'Synthetic guide', 'source_url' => 'https://careers.example.test/electrician',
            'source_year' => 2026, 'reviewed_on' => '2026-02-10', 'expires_on' => '2026-02-10',
            'career_subjects' => [['subject_ids' => [$this->subject], 'minimum_percent' => '50', 'requirement' => 'required']], 'topic_ids' => [$this->nodes[$this->keys[1]]],
            'career_routes' => [['type' => 'learnership', 'title' => 'Synthetic learnership', 'provider' => 'Synthetic SETA', 'entry_note' => 'As the source states.',
                'source_title' => 'Synthetic SETA listing', 'source_url' => 'https://seta.example.test/l', 'programme_id' => '']]];
        $this->actingAs($this->superAdmin())->get('/pathways/careers/new')->assertOk()->assertSee('pathway-route-0-type', false)->assertSee('TVET college');
        $this->post('/pathways/careers', $body)->assertRedirect('/pathways');
        $profile = CareerProfile::sole();
        $this->assertSame(5000, CareerProfileVersion::sole()->details['subjects'][0]['minimum_bp']);
        $this->get('/pathways/careers/'.$profile->id.'/edit')->assertOk()->assertSee('Synthetic electrician');
        $this->get('/pathways')->assertOk()->assertSee('Synthetic electrician')->assertDontSee('This career profile needs review.');
        $this->travelTo(now()->setDate(2026, 2, 11));
        $this->assertTrue(app(CareerProfiles::class)->read($this->superAdmin(), $profile->id, true)['stale']);
        $this->get('/pathways')->assertOk()->assertSee('This career profile needs review.');
        $this->get('/pathways/guidance?learner_id='.$this->schoolA->ids['asha'].'&profile_id='.$profile->id)->assertOk()
            ->assertSee('This career profile is past its review date. Check the source before sharing it.');
        app(CareerProfiles::class)->save($this->superAdmin(), $profile->id, $this->input(['revision' => 1, 'status' => 'withdrawn', 'reviewed_on' => '2026-02-11']));
        $this->refused(fn () => app(CareerGuidance::class)->view($this->superAdmin(), $this->schoolA->ids['asha'], $profile->id), [409]);
    }

    public function test_wrong_role_cannot_publish_profiles_or_open_the_guidance_view(): void
    {
        $profile = $this->publish();
        $before = $this->counts();
        foreach (['tara', 'farid', 'asha'] as $name) {
            $this->refused(fn () => app(CareerProfiles::class)->save($this->actor($name), null, $this->input()), [403]);
        }
        foreach (['farid', 'asha'] as $name) {
            $this->refused(fn () => app(CareerGuidance::class)->view($this->actor($name), $this->schoolA->ids['asha'], $profile['id']), [403]);
            $this->actingAs($this->actor($name))->get('/pathways/guidance?learner_id='.$this->schoolA->ids['asha'].'&profile_id='.$profile['id'])->assertForbidden();
        }
        // Theo teaches no group yet: no learners of his own, no pathways-manage.
        $this->refused(fn () => app(CareerGuidance::class)->actor($this->actor('theo')), [403]);
        $this->actingAs($this->actor('tara'))->post('/pathways/careers', ['expected_account_id' => $this->actor('tara')->id, 'expected_school_id' => 1] + $this->input())->assertForbidden();
        $this->get('/pathways/careers/new')->assertForbidden();
        $this->assertSame($before, $this->counts());
    }

    public function test_wrong_school_ids_are_refused_and_school_b_rows_are_unchanged(): void
    {
        $foreign = app(CurrentSchool::class)->run(self::SCHOOL_B_ID, function () {
            $profile = CareerProfile::create(['status' => 'published', 'revision' => 1, 'expires_on' => '2026-12-31']);
            $version = CareerProfileVersion::create(['profile_id' => $profile->id, 'revision' => 1, 'status' => 'published', 'title' => 'Baobab private career', 'summary' => 'Baobab',
                'source_title' => 'Baobab source', 'source_url' => 'https://baobab.example.test', 'source_year' => 2026, 'reviewed_on' => '2026-02-10', 'expires_on' => '2026-12-31',
                'details' => ['subjects' => [['subject_ids' => [$this->schoolB->ids['maths']], 'minimum_bp' => null, 'requirement' => 'required']], 'topic_ids' => [], 'routes' => []],
                'reviewed_by' => $this->schoolB->ids['tara_user']]);
            $profile->update(['current_version_id' => $version->id]);

            return $profile->id;
        });
        $foreignRows = app(CurrentSchool::class)->run(self::SCHOOL_B_ID, fn () => [CareerProfile::count(), CareerProfileVersion::count(), CareerProfile::findOrFail($foreign)->revision]);
        $profile = $this->publish();
        $before = $this->counts();
        $bad = $this->input();
        $bad['details']['subjects'][0]['subject_ids'] = [$this->schoolB->ids['maths']];
        $this->refused(fn () => app(CareerProfiles::class)->save($this->superAdmin(), null, $bad), [422]);
        $this->refused(fn () => app(CareerProfiles::class)->save($this->superAdmin(), $foreign, $this->input(['revision' => 1])), [404]);
        $this->refused(fn () => app(CareerGuidance::class)->view($this->superAdmin(), $this->schoolA->ids['asha'], $foreign), [404]);
        $this->refused(fn () => app(CareerGuidance::class)->view($this->superAdmin(), $this->schoolB->ids['asha'], $profile['id']), [404]);
        $this->actingAs($this->superAdmin())->get('/pathways/guidance?learner_id='.$this->schoolB->ids['asha'])->assertNotFound();
        $this->get('/pathways/careers/'.$foreign.'/edit')->assertNotFound();
        $this->get('/pathways/guidance?q=Baobab')->assertOk()->assertDontSee('learner_id='.$this->schoolB->ids['asha'], false);
        $this->assertSame($before, $this->counts());
        $this->assertSame($foreignRows, app(CurrentSchool::class)->run(self::SCHOOL_B_ID, fn () => [CareerProfile::count(), CareerProfileVersion::count(), CareerProfile::findOrFail($foreign)->revision]));
    }

    public function test_wrong_owner_teacher_cannot_open_a_learner_they_do_not_teach_and_unshared_evidence_stays_unknown(): void
    {
        $profile = $this->publish();
        $this->evidence();
        $theo = $this->otherTeacher();
        $this->assertSame([], app(CareerGuidance::class)->learners($theo, 'Asha'));
        $this->refused(fn () => app(CareerGuidance::class)->view($theo, $this->schoolA->ids['asha'], $profile['id']), [404]);
        $this->actingAs($theo)->get('/pathways/guidance?learner_id='.$this->schoolA->ids['asha'].'&profile_id='.$profile['id'])->assertNotFound();
        // A catalogue manager who does not teach Asha may open her profile view, but not her maths evidence.
        $theo->givePermissionTo('pathways-manage');
        $view = app(CareerGuidance::class)->view($theo->fresh(), $this->schoolA->ids['asha'], $profile['id']);
        $this->assertSame('not_shared', $view['topics'][0]['evidence']);
        $this->assertSame([null, null], array_column($view['topics'][0]['topics'], 'level'));
        $this->assertSame([], $view['topics'][0]['starting_points']);
    }
}
