<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Academics\ContentItem;
use App\Models\Academics\ContentItemGroup;
use App\Models\Academics\ContentSource;
use App\Models\Academics\ContentVersion;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingGroup;
use App\Models\ClassSchool;
use App\Models\Growth\MasteryEvidence;
use App\Models\Growth\MasteryTopic;
use App\Models\Learning\PracticeItem;
use App\Models\Learning\PracticeItemVersion;
use App\Models\Learning\PracticeSetEntry;
use App\Models\Library\ContributionGate;
use App\Models\Library\LibraryContribution;
use App\Models\Library\LibraryItem;
use App\Models\Library\LibraryItemVersion;
use App\Models\Library\LibraryRating;
use App\Models\Library\LibraryReview;
use App\Models\Library\LibraryTopic;
use App\Models\Platform\SchoolModule;
use App\Models\SessionYear;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use App\Services\Academics\CurriculumPlans;
use App\Services\Academics\TeachingGroups;
use App\Services\Growth\TopicGraph;
use App\Services\Learning\DailyPractice;
use App\Services\Learning\LearningPathSources;
use App\Services\Learning\Library\LibraryItems;
use App\Services\Learning\Library\SharedLibrary;
use App\Services\Learning\Library\SharedLibraryBrowse;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolOwnedTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\Concerns\PassesPlatformTwoFactor;
use Tests\Concerns\UsesContentLibrary;
use Tests\Concerns\UsesPracticeScenario;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

/**
 * The shared content library, teacher to teacher (Action Plan 5.4): School A
 * (Jacaranda) teaches CAPS MATH-SP Grade 7 and its teacher Tara shares her
 * approved question; School B (Baobab) browses, copies and rates it. The
 * library has a Fractions topic with a Maths reviewer.
 * engineering/specs/learning/shared-content-library.md.
 */
class SharedContentLibraryTest extends TestCase
{
    use PassesPlatformTwoFactor;
    use UsesContentLibrary, UsesPracticeScenario;
    use ValidatesOpenApiResponses;

    private const API = '/api/v2/learning/shared-library';

    private LibraryTopic $fractions;

    private int $item;

    /** @var array{group: int, topic: int, source: int, admin: User}|null School B's own practice world, built when a test needs it */
    private ?array $b = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        $standard = $this->standard('MATH-SP');
        DB::table('curriculum_plans')->where('school_id', 1)->update(['standard_id' => $standard->id,
            'standard_snapshot' => json_encode(['id' => $standard->id, 'framework' => 'CAPS', 'code' => 'MATH-SP', 'name' => $standard->name])]);
        $this->fractions = $this->libraryTopic('Fractions');
        $this->assignReviewer('MATH-SP');
        $this->item = $this->approve()['id'];
    }

    public function test_a_shared_question_waits_for_a_leader_and_a_reviewer_before_another_school_sees_it(): void
    {
        $practice = $this->practiceSnapshot();
        $this->sharing(1, true);
        $this->assertTrue(app(SharedLibrary::class)->settings()['leader_approval'], 'a leader approves by default');

        $contribution = $this->share();
        $this->assertSame('awaiting_approval', $contribution->state);
        $this->assertNull($contribution->library_item_id);
        $this->assertSame(0, LibraryItem::query()->count(), 'nothing left the school yet');
        // The teacher cannot approve her own; the Super Admin (a school leader) can.
        $this->refuses(fn () => app(SharedLibrary::class)->approve($this->actor(), $contribution->id), 403);
        $approved = app(SharedLibrary::class)->approve($this->superAdmin(), $contribution->id);
        $this->assertSame('shared', $approved->state);

        $item = LibraryItem::query()->with('source')->findOrFail($approved->library_item_id);
        $version = LibraryItemVersion::query()->where('library_item_id', $item->id)->sole();
        $this->assertSame(['in_review', 'contributed', null], [$version->state, $version->origin, $item->published_version_id]);
        // Only the question's teaching content was copied, nothing about learners, groups or the school's material.
        $this->assertSame(['definition'], array_keys($version->content));
        $this->assertSame('One half as a decimal?', $version->content['definition']['prompt']);
        $this->assertSame(['contributed', 1, (int) $this->actor()->id, 'named', 'CC BY 4.0'],
            [$item->source->kind, $item->source->contributor_school_id, $item->source->contributor_user_id, $item->source->credit, $item->source->licence]);
        $this->assertSame($practice, $this->practiceSnapshot(), 'sharing never changes the teacher\'s question');

        // Waiting for review: School B (library on) finds nothing, not even by id.
        $this->switchLibrary(2, true);
        $this->enableModulesInB();
        $this->asB(fn () => $this->assertSame([], app(SharedLibraryBrowse::class)->catalogue($this->bTeacher(), [])['items']));
        $this->asB(fn () => $this->refuses(fn () => app(SharedLibraryBrowse::class)->item($this->bTeacher(), $item->id), 404));
        $this->assertSame('in_review', app(SharedLibrary::class)->mine($this->actor())[0]['status']);

        // HQ's queue shows it as shared by a school; the subject reviewer approves and Content publishes.
        $this->signInAs($this->libraryStaff('reviewer'));
        $this->get('/saas/library/reviews?filter=contributed')->assertOk()->assertSee('Shared by a school')->assertSee('Tara Demo')->assertSee('One half as a decimal?');
        $this->publish($item);
        $this->assertSame('published', app(SharedLibrary::class)->mine($this->actor())[0]['status']);

        // Now School B finds it, credited as Tara chose, under the licence.
        $found = $this->asB(fn () => app(SharedLibraryBrowse::class)->catalogue($this->bTeacher(), ['framework' => 'CAPS', 'subject_code' => 'MATH-SP',
            'grade_level_id' => $this->grade('7'), 'library_topic_id' => $this->fractions->id]));
        $this->assertSame([$item->id], array_column($found['items'], 'id'));
        $this->assertSame('Shared by Tara Demo, Jacaranda Demo School', $found['items'][0]['attribution']);
        $this->assertSame('CC BY 4.0', $found['items'][0]['licence']['code']);
        $this->assertNull($found['items'][0]['rating']);
        $this->asB(fn () => $this->assertSame([], app(SharedLibraryBrowse::class)->catalogue($this->bTeacher(), ['grade_level_id' => $this->grade('8')])['items']));
        $this->assertSame($practice, $this->practiceSnapshot());
    }

    public function test_sharing_is_off_until_a_leader_switches_it_on_and_only_the_author_shares_without_approval_when_the_school_chose_so(): void
    {
        $this->refuses(fn () => $this->share(), 403);
        $this->assertSame(0, LibraryContribution::query()->count());
        // Only a leader changes the settings.
        $this->refuses(fn () => app(SharedLibrary::class)->saveSettings($this->actor(), ['sharing_enabled' => true, 'leader_approval' => false]), 403);
        $this->sharing(1, true, false);

        // A colleague cannot share Tara's question; another school's teacher does not find it.
        $this->refuses(fn () => $this->share([], $this->actor('theo')), 403);
        $this->enableModulesInB();
        $this->asB(fn () => $this->refuses(fn () => $this->share([], $this->bTeacher()), 404));
        // Licence and rights must be confirmed; the topic must be of the question's framework, subject and grade.
        $this->invalid(fn () => $this->share(['licence_accepted' => false]), 'licence_accepted');
        $this->invalid(fn () => $this->share(['rights_confirmed' => false]), 'rights_confirmed');
        $this->standard('ENG-HL');
        $english = $this->libraryTopic('Nouns', 'ENG-HL');
        $this->invalid(fn () => $this->share(['library_topic_id' => $english->id]), 'library_topic_id');
        $this->assertSame(0, LibraryContribution::query()->count());

        $contribution = $this->share(['credit' => 'anonymous']);
        $this->assertSame('shared', $contribution->state, 'no leader approval when the school chose so');
        $this->assertNotNull($contribution->library_item_id);
        // Twice is refused; so is sharing while it is live.
        $this->refuses(fn () => $this->share(), 409);
        $this->assertSame(1, LibraryContribution::query()->count());
        $this->assertSame(1, LibraryItem::query()->count());

        // Withdrawing is for the author or a leader: a colleague and another school cannot.
        $this->refuses(fn () => app(SharedLibrary::class)->withdraw($this->actor('theo'), $contribution->id), 403);
        $this->asB(fn () => $this->refuses(fn () => app(SharedLibrary::class)->withdraw($this->bTeacher(), $contribution->id), 404));
        $this->assertSame('shared', $contribution->fresh()->state);
        $this->assertSame('withdrawn', app(SharedLibrary::class)->withdraw($this->actor(), $contribution->id)->state);
        $this->assertNotNull(LibraryItem::query()->findOrFail($contribution->library_item_id)->withdrawn_at);
    }

    public function test_identifying_data_is_refused_in_a_shared_question_and_a_rating_comment(): void
    {
        $this->sharing(1, true, false);
        foreach ([
            'email' => ['prompt' => 'Mail your answer to tara@school.example: one half as a decimal?'],
            'id_number' => ['explanation' => 'Learner 8001015009087 found 0.5 by dividing.'],
            'phone' => ['prompt' => 'Call 082 123 4567 if stuck. One half as a decimal?'],
            'learner' => ['prompt' => 'Asha shares one pizza with a friend. What decimal does each get?'],
        ] as $kind => $change) {
            $id = $this->approve(['definition' => $change + ['kind' => 'number', 'prompt' => 'One half as a decimal?', 'explanation' => 'Divide one by two to get 0.5.',
                'options' => [], 'answers' => ['0.5']]])['id'];
            $this->invalid(fn () => $this->share(['practice_item_id' => $id]), 'question', $kind);
        }
        // A learner's surname in the title is refused too.
        $this->invalid(fn () => $this->share(['title' => 'The Demo family pizza']), 'question');
        $this->assertSame(0, LibraryContribution::query()->count());
        $this->assertSame(0, LibraryItem::query()->count());

        // A rating comment naming a learner of the rater's own school is refused.
        $item = $this->publishedContribution();
        $this->buildSchoolB();
        $this->copyIntoB($item->id);
        $this->asB(fn () => $this->invalid(fn () => app(SharedLibraryBrowse::class)->rate($this->bTeacher(), $item->id, ['stars' => 4, 'comment' => 'Asha Baobab loved this one']), 'comment'));
        $this->asB(fn () => $this->invalid(fn () => app(SharedLibraryBrowse::class)->rate($this->bTeacher(), $item->id, ['stars' => 4, 'comment' => 'Ask me at 011 555 0101']), 'comment'));
        $this->assertSame(0, LibraryRating::acrossSchools()->count());
    }

    public function test_wrong_roles_are_refused_and_nothing_is_written(): void
    {
        $this->sharing(1, true, false);
        $item = $this->publishedContribution();
        $this->switchLibrary(1, true);
        $contribution = LibraryContribution::query()->sole();
        $staff = $this->schoolStaff();
        $before = [LibraryContribution::query()->count(), LibraryRating::query()->count(), PracticeItem::query()->count(), LibraryItem::query()->count()];

        foreach (['asha' => $this->actor('asha'), 'farid' => $this->actor('farid'), 'office staff' => $staff] as $who => $person) {
            $this->refuses(fn () => app(SharedLibraryBrowse::class)->catalogue($person, []), 403);
            $this->refuses(fn () => app(SharedLibraryBrowse::class)->item($person, $item->id), 403);
            $this->refuses(fn () => app(SharedLibraryBrowse::class)->rate($person, $item->id, ['stars' => 5]), 403);
            $this->refuses(fn () => app(SharedLibraryBrowse::class)->copy($person, $item->id, $this->copyInput()), 403);
            $this->refuses(fn () => $this->share([], $person), 403);
            $this->refuses(fn () => app(SharedLibrary::class)->withdraw($person, $contribution->id), 403);
            $this->refuses(fn () => app(SharedLibrary::class)->saveSettings($person, ['sharing_enabled' => false, 'leader_approval' => true]), 403);
        }
        // Over HTTP: office staff are refused the panel page.
        $this->actingAs($staff)->get(route('shared-library.index'))->assertForbidden();
        $this->assertContains($this->actingAs($this->actor('farid'))->get(route('shared-library.index'))->getStatusCode(), [302, 403]);

        // HQ roles without library abilities, and Content (manage only), reach neither the ratings page nor unpublishing.
        foreach (['support', 'finance', 'content'] as $role) {
            $this->signInAs($this->libraryStaff($role));
            $this->get('/saas/library/ratings')->assertForbidden();
            $this->post('/saas/library/items/'.$item->id.'/unpublish', ['revision' => $item->fresh()->revision, 'reason' => 'Should never be taken down'])->assertForbidden();
        }
        // Platform staff working inside a school cannot share or rate.
        $this->refuses(fn () => app(SharedLibraryBrowse::class)->rate($this->libraryStaff('content'), $item->id, ['stars' => 5]), 403);
        // A learner and a parent are refused by the API's staff guard.
        foreach (['asha', 'farid'] as $who) {
            $this->api($who, 1)->getJson(self::API.'/items')->assertForbidden();
            $this->api($who, 1)->putJson(self::API.'/items/'.$item->id.'/rating', ['stars' => 5])->assertForbidden();
        }
        $this->assertSame($before, [LibraryContribution::query()->count(), LibraryRating::query()->count(), PracticeItem::query()->count(), LibraryItem::query()->count()]);
        $this->assertNotNull($item->fresh()->published_version_id);
    }

    public function test_school_b_never_sees_school_a_contributions_or_who_rated_and_cannot_act_for_a(): void
    {
        $this->sharing(1, true);
        $waiting = $this->share();
        $item = $this->publishedContribution();
        $this->switchLibrary(1, true);
        $shared = LibraryContribution::query()->where('library_item_id', $item->id)->sole();
        $this->buildSchoolB();
        $this->asB(function () use ($waiting, $shared, $item) {
            // B's own lists hold nothing of A's; A's ids are not found.
            $this->assertSame([], app(SharedLibrary::class)->mine($this->bTeacher()));
            $this->assertSame([], app(SharedLibrary::class)->school($this->b['admin']));
            $this->refuses(fn () => app(SharedLibrary::class)->approve($this->b['admin'], $waiting->id), 404);
            $this->refuses(fn () => app(SharedLibrary::class)->withdraw($this->b['admin'], $shared->id), 404);
            $this->refuses(fn () => app(SharedLibrary::class)->options($this->bTeacher(), $this->item), 404);
            // Not used yet at B: no rating.
            $this->refuses(fn () => app(SharedLibraryBrowse::class)->rate($this->bTeacher(), $item->id, ['stars' => 5]), 403);
        });
        $this->assertSame('awaiting_approval', $waiting->fresh()->state);
        $this->assertSame('shared', $shared->fresh()->state);

        // Over the API with B's token: the item reads, nothing of A's contributions does.
        $this->copyIntoB($item->id);
        $this->api('tara', 2)->putJson(self::API.'/items/'.$item->id.'/rating', ['stars' => 4, 'comment' => 'Clear and quick'])->assertOk()->assertJsonPath('data.counted', true);
        $page = $this->api('tara', 2)->getJson(self::API.'/items/'.$item->id)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $page->assertJsonPath('data.my_rating.stars', 4)->assertJsonPath('data.rating', null)->assertJsonPath('data.comments', []);
        $this->assertStringNotContainsString('contributor', $page->getContent());
        $this->api('tara', 2)->getJson(self::API.'/contributions')->assertOk()->assertJsonPath('data.contributions', []);
        $this->api('tara', 2)->postJson(self::API.'/contributions/'.$shared->id.'/withdraw')->assertNotFound();
        $this->api('tara', 2)->getJson(self::API.'/practice-items/'.$this->item.'/share-options')->assertNotFound();
        // School A cannot read or change B's rating, and never learns who rated.
        $this->assertSame(0, LibraryRating::query()->count(), 'A sees none of B\'s rating rows');
        $this->assertSame(1, LibraryRating::acrossSchools()->where('school_id', 2)->count());
        $this->asB(fn () => $this->assertSame(1, LibraryRating::query()->count()));
        $mine = $this->api('tara', 1)->getJson(self::API.'/items/'.$item->id)->assertOk()->json('data');
        $this->assertNull($mine['my_rating']);
        $this->assertNull($mine['rating']);
        $this->assertStringNotContainsString('Baobab', json_encode($mine));
    }

    public function test_an_average_shows_only_from_three_counted_ratings_and_the_sharing_schools_ratings_never_count(): void
    {
        $this->sharing(1, true, false);
        $item = $this->publishedContribution();
        $this->switchLibrary(1, true);
        // Tara and Theo at the sharing school may rate it; their ratings are kept but never counted.
        $this->assertFalse(app(SharedLibraryBrowse::class)->rate($this->actor(), $item->id, ['stars' => 5, 'comment' => 'Mine, of course'])['counted']);
        app(SharedLibraryBrowse::class)->rate($this->actor('theo'), $item->id, ['stars' => 5]);
        $this->buildSchoolB();
        $this->copyIntoB($item->id);
        $third = $this->extraBTeacher();
        $rate = fn (User $teacher, int $stars, ?string $comment = null) => $this->asB(fn () => app(SharedLibraryBrowse::class)->rate($teacher, $item->id, ['stars' => $stars, 'comment' => $comment]));
        $rate($this->bTeacher(), 2, 'Too easy for Grade 7');
        $rate($this->bTeacher('theo'), 3);
        $shown = fn () => $this->asB(fn () => app(SharedLibraryBrowse::class)->item($this->bTeacher(), $item->id));
        $this->assertNull($shown()['rating'], 'two counted ratings: nothing shown');
        $this->assertSame([], $shown()['comments']);
        $rate($third, 4, 'Good for a warm-up');
        $this->assertSame(['count' => 3, 'average' => 3.0], $shown()['rating'], 'three counted ratings; the sharing school\'s two fives are not among them');
        $this->assertSame(['Good for a warm-up', 'Too easy for Grade 7'], array_column($shown()['comments'], 'comment'));
        $this->assertStringNotContainsString('Mine, of course', json_encode($shown()));
        // Changing a rating replaces it: once per item per teacher.
        $rate($this->bTeacher(), 5, 'Better than I thought');
        $this->assertSame(['count' => 3, 'average' => 4.0], $shown()['rating']);
        $this->assertSame(1, LibraryRating::acrossSchools()->where('user_id', $this->bTeacher()->id)->count());
        $this->assertSame(5, LibraryRating::acrossSchools()->count());
        // School A's own page shows the same counted average.
        $this->assertSame(['count' => 3, 'average' => 4.0], app(SharedLibrary::class)->mine($this->actor())[0]['rating']);

        // A reviewer sees the counts and comments, without a teacher's or school's name, and takes it down.
        $this->signInAs($this->libraryStaff('reviewer'));
        $page = $this->get('/saas/library/ratings')->assertOk()->assertSee('4.0 out of 5 from 3 counted ratings.')->assertSee('2 from the sharing school, not counted.')
            ->assertSee('Better than I thought')->assertSee('Mine, of course');
        foreach (['Baobab', 'Tara', 'Theo', 'Jacaranda'] as $name) {
            $page->assertDontSee($name);
        }
        $this->post('/saas/library/items/'.$item->id.'/unpublish', ['revision' => $item->fresh()->revision])->assertSessionHasErrors('reason');
        $this->post('/saas/library/items/'.$item->id.'/unpublish', ['revision' => $item->fresh()->revision, 'reason' => 'Teachers report it is too easy for Grade 7'])
            ->assertRedirect(route('saas.library.ratings'));
        $this->assertNull($item->fresh()->published_version_id);
        $this->assertSame('published', LibraryItemVersion::query()->where('library_item_id', $item->id)->sole()->state, 'the version itself never changes');
        $this->asB(fn () => $this->refuses(fn () => app(SharedLibraryBrowse::class)->item($this->bTeacher(), $item->id), 404));
        $this->assertSame('unpublished', app(SharedLibrary::class)->mine($this->actor())[0]['status']);
        $this->assertSame(1, DB::table('audit_log')->where('action', 'library_unpublished')->whereNull('school_id')->count());
    }

    public function test_a_copy_is_the_schools_own_question_approved_like_any_other(): void
    {
        $this->sharing(1, true, false);
        $item = $this->publishedContribution();
        $this->buildSchoolB();
        $version = (int) $item->published_version_id;
        $input = $this->copyInput();
        // A group the teacher is not allocated to is refused, as for any question.
        $this->asB(fn () => $this->refuses(fn () => app(SharedLibraryBrowse::class)->copy($this->bTeacher('theo'), $item->id, $input), 403));
        $copied = $this->api('tara', 2)->postJson(self::API.'/items/'.$item->id.'/copy', $input)->assertOk()->json('data');
        $this->api('tara', 2)->postJson(self::API.'/items/'.$item->id.'/copy', $input)->assertOk()->assertJsonPath('data', $copied);
        $copy = $this->asB(fn () => PracticeItem::query()->findOrFail($copied['id']));
        $this->assertSame([$version, (int) $this->bTeacher()->id, 1], [(int) $copy->library_item_version_id, (int) $copy->author_id, (int) $copy->revision]);
        $this->assertSame('One half as a decimal?', $this->asB(fn () => PracticeItemVersion::query()->where('practice_item_id', $copy->id)->sole()->definition['prompt']));
        $this->assertSame(2, (int) DB::table('practice_items')->where('id', $copy->id)->value('school_id'));
        $this->asB(fn () => $this->assertTrue(app(SharedLibraryBrowse::class)->item($this->bTeacher(), $item->id)['copied']));

        // Withdrawing the shared item leaves B's copy exactly as it is.
        app(SharedLibrary::class)->withdraw($this->actor(), LibraryContribution::query()->sole()->id);
        $this->asB(fn () => $this->assertSame($copy->updated_at?->toIso8601String(), PracticeItem::query()->findOrFail($copy->id)->updated_at?->toIso8601String()));
        $this->asB(fn () => $this->refuses(fn () => app(SharedLibraryBrowse::class)->copy($this->bTeacher(), $item->id, ['operation_id' => (string) Str::uuid()] + $input), 404));
    }

    public function test_published_contributions_reach_daily_practice_and_learning_paths_and_withdrawing_keeps_the_evidence(): void
    {
        $this->sharing(1, true, false);
        $item = $this->publishedContribution();
        $this->switchLibrary(1, true);
        $version = (int) $item->published_version_id;

        // The daily practice fallback offers it as an ordinary published library version.
        $set = $this->issue();
        $this->assertContains($version, PracticeSetEntry::query()->pluck('library_item_version_id')->filter()->map(fn ($id) => (int) $id)->all());
        $entry = collect($set['items'])->firstWhere('source_version_id', $version);
        $this->assertNotNull($entry);
        app(DailyPractice::class)->review($this->actor('asha'), $this->schoolA->ids['asha'], $set['id'], $entry['id'], ['operation_id' => (string) Str::uuid(), 'answer' => '0.5', 'rating' => 3]);
        $evidence = MasteryEvidence::query()->where('content_source', 'library')->sole();
        $this->assertSame($version, (int) $evidence->library_item_version_id);

        // The guided learning path, which hands its teach step to the tutor, offers it under the same-named topic.
        $group = TeachingGroup::query()->findOrFail($this->group);
        $topic = MasteryTopic::query()->findOrFail($this->nodes[$this->keys[1]]);
        $keys = array_column(app(LearningPathSources::class)->candidates($this->actor('asha'), $this->schoolA->ids['asha'], $group, $topic), 'key');
        $this->assertContains('l:'.$item->id, $keys);

        // Withdrawn: no longer offered anywhere, the version unchanged, the evidence still pointing at it.
        app(SharedLibrary::class)->withdraw($this->superAdmin(), LibraryContribution::query()->sole()->id);
        $this->assertNull($item->fresh()->published_version_id);
        $this->assertSame('published', LibraryItemVersion::query()->findOrFail($version)->state);
        $this->refuses(fn () => app(DailyPractice::class)->read($this->actor('asha'), $this->schoolA->ids['asha'], $set['id']), 404);
        $this->assertSame($version, (int) $evidence->fresh()->library_item_version_id);
        $this->assertNotContains('l:'.$item->id, array_column(app(LearningPathSources::class)->candidates($this->actor('asha'), $this->schoolA->ids['asha'], $group, $topic), 'key'));
        // HQ can no longer change or publish it, and it left the review queue.
        $this->refuses(fn () => $this->asHq(fn () => app(LibraryItems::class)->publish($this->libraryStaff('content'), $item->id, ['version_id' => $version, 'revision' => $item->fresh()->revision])), 409);
        $this->refuses(fn () => $this->asHq(fn () => app(LibraryItems::class)->redraft($this->libraryStaff('content'), $item->id, ['revision' => $item->fresh()->revision])), 409);
    }

    public function test_the_contribution_gate_lets_only_sources_items_and_versions_through_and_the_tables_are_school_owned(): void
    {
        $this->sharing(1, true, false);
        $item = $this->publishedContribution();
        $version = LibraryItemVersion::query()->where('library_item_id', $item->id)->sole();
        $before = $this->libraryCounts();
        // Outside the gate a school still writes nothing into the library; inside it, never a review or a delete.
        $this->refuses(fn () => LibraryItem::query()->findOrFail($item->id)->update(['title' => 'Changed by a school']), 403);
        $this->refuses(fn () => ContributionGate::run(fn () => LibraryReview::query()->create(['library_item_version_id' => $version->id, 'reviewer_id' => $this->actor()->id,
            'decision' => 'approved'])), 403);
        $this->refuses(fn () => ContributionGate::run(fn () => LibraryItem::query()->findOrFail($item->id)->delete()), 403);
        $this->assertSame($before, $this->libraryCounts());
        $this->assertSame($item->title, $item->fresh()->title);

        foreach (['library_contributions', 'library_ratings'] as $table) {
            $this->assertContains($table, SchoolOwnedTables::REQUIRED);
            $this->assertContains('school_id', Schema::getColumnListing($table));
        }
        foreach (self::LIBRARY_TABLES as $table) {
            $this->assertNotContains('school_id', Schema::getColumnListing($table), $table);
        }
    }

    public function test_a_content_lead_without_the_teacher_role_or_settings_permission_previews_and_decides_only_their_grade(): void
    {
        $this->sharing(1, true);
        $contribution = $this->share();
        $lead = $this->schoolStaff('gita');
        $other = $this->schoolStaff('omar');
        foreach ([[$lead, '7'], [$other, '8']] as [$person, $grade]) {
            app(TeachingGroups::class)->dated($this->superAdmin(), 'leadership', ['user_id' => $person->id, 'session_year_id' => $this->year, 'kind' => 'grade_head',
                'grade_level_id' => $this->grade($grade), 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic grade head']);
        }
        $this->assertFalse($lead->hasRole('Teacher'));
        $this->assertFalse($lead->can('setting-create'));
        // Tara changes her question after sharing it: the preview still shows exactly what she submitted.
        $this->approve(['expected_revision' => 1, 'definition' => ['kind' => 'number', 'prompt' => 'Changed after sharing: one quarter as a decimal?',
            'explanation' => 'Divide one by four to get 0.25.', 'options' => [], 'answers' => ['0.25']]], $this->item);

        $this->actingAs($lead)->get(route('shared-library.index'))->assertOk()->assertSee('Half as a decimal')->assertSee(__('Approve'))
            ->assertDontSee(__('How your school shares'));
        $this->get(route('shared-library.contributions.show', $contribution->id))->assertOk()->assertSee('One half as a decimal?')
            ->assertSee('Divide one by two to get 0.5.')->assertSee('0.5')->assertDontSee('Changed after sharing');
        $preview = app(SharedLibrary::class)->preview($lead, $contribution->id);
        $this->assertSame(['One half as a decimal?', ['0.5'], 1], [$preview['question']['prompt'], $preview['question']['answers'], $preview['revision']]);

        // Another grade's lead opens the page but sees nothing of grade 7 and cannot preview or decide it.
        $this->actingAs($other)->get(route('shared-library.index'))->assertOk()->assertDontSee('Half as a decimal');
        $this->get(route('shared-library.contributions.show', $contribution->id))->assertForbidden();
        $this->refuses(fn () => app(SharedLibrary::class)->approve($other, $contribution->id), 403);
        // The author cannot preview it as a decider, and another school's teacher does not find it.
        $this->refuses(fn () => app(SharedLibrary::class)->preview($this->actor(), $contribution->id), 403);
        $this->enableModulesInB();
        $this->asB(fn () => $this->refuses(fn () => app(SharedLibrary::class)->preview($this->bTeacher(), $contribution->id), 404));
        $this->actingAs($this->bTeacher())->get(route('shared-library.contributions.show', $contribution->id))->assertNotFound();
        $this->assertSame('awaiting_approval', $contribution->fresh()->state);

        // The grade's lead approves; what leaves is the submitted version, not the later revision.
        $this->actingAs($lead)->post(route('shared-library.contributions.approve', $contribution->id))->assertRedirect()->assertSessionHasNoErrors();
        $item = LibraryItem::query()->findOrFail($contribution->fresh()->library_item_id);
        $this->assertSame('One half as a decimal?', LibraryItemVersion::query()->where('library_item_id', $item->id)->sole()->content['definition']['prompt']);
    }

    public function test_the_panel_pages_and_api_let_a_school_share_and_another_browse(): void
    {
        // A leader switches sharing on with leader approval; a teacher shares from the page.
        $this->actingAs($this->superAdmin())->get(route('shared-library.index'))->assertOk()->assertSee(__('How your school shares'));
        $this->put(route('shared-library.settings'), ['sharing_enabled' => '1', 'leader_approval' => '1'])->assertRedirect(route('shared-library.index'));
        $this->assertTrue(app(SharedLibrary::class)->settings()['sharing_enabled']);
        $this->actingAs($this->actor())->get(route('question-banks.index'))->assertOk()->assertSee(__('Share with other schools'));
        $this->get(route('shared-library.share', $this->item))->assertOk()->assertSee('One half as a decimal?')->assertSee('Fractions')->assertSee('CC BY 4.0');
        $this->put(route('shared-library.settings'), ['sharing_enabled' => '0', 'leader_approval' => '0'])->assertForbidden();
        $this->post(route('shared-library.contributions.store'), ['practice_item_id' => $this->item, 'library_topic_id' => $this->fractions->id, 'title' => 'Half as a decimal',
            'credit' => 'school', 'rights_confirmed' => '1', 'licence_accepted' => '1'])->assertRedirect(route('shared-library.index'));
        $contribution = LibraryContribution::query()->sole();
        $this->get(route('shared-library.index'))->assertOk()->assertSee(__('Waiting for a school leader to approve'));
        // The leader approves from the page; another attempt is refused.
        $this->actingAs($this->superAdmin())->get(route('shared-library.index'))->assertOk()->assertSee('Half as a decimal')->assertSee(__('Approve'));
        $this->post(route('shared-library.contributions.approve', $contribution->id))->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('shared-library.contributions.approve', $contribution->id))->assertRedirect()->assertSessionHasErrors('shared');
        $item = LibraryItem::query()->findOrFail($contribution->fresh()->library_item_id);
        $this->publish($item);

        // School B's teacher browses, opens and copies it in the panel, then rates it.
        $this->buildSchoolB();
        $this->app['auth']->forgetGuards();
        $b = $this->bTeacher();
        $this->actingAs($b);
        $this->asBRequest(fn () => $this->get(route('shared-library.index'))->assertOk()->assertSee('Half as a decimal')->assertSee('Shared by Jacaranda Demo School')->assertDontSee('Shared by Tara'));
        $this->asBRequest(fn () => $this->get(route('shared-library.items.show', ['item' => $item->id, 'group' => $this->b['group']]))->assertOk()->assertSee(__('Copy into your question bank')));
        $this->asBRequest(fn () => $this->post(route('shared-library.items.copy', $item->id), $this->copyInput())->assertRedirect()->assertSessionHasNoErrors());
        $this->asBRequest(fn () => $this->post(route('shared-library.items.rate', $item->id), ['stars' => 5, 'comment' => 'Works well'])->assertRedirect()->assertSessionHasNoErrors());
        $this->assertSame(1, LibraryRating::acrossSchools()->where('school_id', 2)->count());

        // The app's API: browse with filters, the share options and a contribution list for School A.
        $this->api('tara', 2)->getJson(self::API.'/items?grade_level_id='.$this->grade('7'))->assertOk()->assertJsonPath('data.items.0.id', $item->id)
            ->assertJsonPath('data.items.0.attribution', 'Shared by Jacaranda Demo School');
        $this->api('tara', 1)->getJson(self::API.'/contributions')->assertOk()->assertJsonPath('data.contributions.0.status', 'published')->assertJsonPath('data.sharing.sharing_enabled', true);
        $this->api('tara', 1)->getJson(self::API.'/practice-items/'.$this->item.'/share-options')->assertOk()->assertJsonPath('data.topics.0.id', $this->fractions->id)
            ->assertJsonPath('data.current_contribution_id', $contribution->id);
        $this->api('tara', 1)->postJson(self::API.'/contributions/'.$contribution->id.'/withdraw')->assertOk()->assertJsonPath('data.state', 'withdrawn');
        $this->api('tara', 2)->getJson(self::API.'/items/'.$item->id)->assertNotFound();
    }

    // ---- helpers ----

    private function share(array $changes = [], ?User $actor = null): LibraryContribution
    {
        return app(SharedLibrary::class)->contribute($actor ?? $this->actor(), $changes + ['practice_item_id' => $this->item, 'library_topic_id' => $this->fractions->id,
            'title' => 'Half as a decimal', 'credit' => 'named', 'rights_confirmed' => true, 'licence_accepted' => true]);
    }

    private function sharing(int $school, bool $on, bool $approval = true): void
    {
        if ($school === 1) {
            app(SharedLibrary::class)->saveSettings($this->superAdmin(), ['sharing_enabled' => $on, 'leader_approval' => $approval]);
        }
    }

    /** Tara shares her question without leader approval; the reviewer approves and Content publishes. */
    private function publishedContribution(): LibraryItem
    {
        if (! app(SharedLibrary::class)->settings()['sharing_enabled'] || app(SharedLibrary::class)->settings()['leader_approval']) {
            $this->sharing(1, true, false);
        }
        $contribution = $this->share(['practice_item_id' => $this->approve()['id']]);

        return $this->publish(LibraryItem::query()->findOrFail($contribution->library_item_id));
    }

    private function publish(LibraryItem $item): LibraryItem
    {
        return $this->asHq(function () use ($item) {
            $version = LibraryItemVersion::query()->where('library_item_id', $item->id)->orderByDesc('number')->firstOrFail();
            app(LibraryItems::class)->review($this->libraryStaff('reviewer'), $version->id, ['decision' => 'approved']);
            app(LibraryItems::class)->publish($this->libraryStaff('content'), $item->id, ['version_id' => $version->id, 'revision' => $item->fresh()->revision]);

            return $item->fresh();
        });
    }

    /** @return array<string, mixed> */
    private function practiceSnapshot(): array
    {
        return [PracticeItem::query()->orderBy('id')->get()->map(fn ($i) => $i->only(['id', 'revision', 'active', 'visibility', 'library_item_version_id']) + ['updated' => (string) $i->updated_at])->all(),
            PracticeItemVersion::query()->count()];
    }

    private function asB(callable $callback): mixed
    {
        return app(CurrentSchool::class)->run(self::SCHOOL_B_ID, $callback);
    }

    /** A panel request made as a School B member: the session picks the school. */
    private function asBRequest(callable $callback): mixed
    {
        return $callback();
    }

    private function bTeacher(string $name = 'tara'): User
    {
        return User::acrossSchools()->findOrFail($this->schoolB->ids[$name.'_user']);
    }

    private function enableModulesInB(): void
    {
        $this->asB(function () {
            SchoolModule::updateOrCreate(['module' => 'daily_practice'], ['in_package' => true, 'enabled' => true]);
            SchoolModule::updateOrCreate(['module' => 'mastery'], ['in_package' => true, 'enabled' => true]);
            foreach (['tara', 'theo'] as $name) {
                $this->bTeacher($name)->forceFill(['password_change_required' => false])->save();
            }
            app(ModuleGate::class)->forget();
        });
    }

    /** School B's own practice world: a Grade 7 maths group Tara teaches, with a published plan and lesson material. */
    private function buildSchoolB(): void
    {
        if ($this->b !== null) {
            return;
        }
        $this->switchLibrary(2, true);
        $this->enableModulesInB();
        $this->b = $this->asB(function (): array {
            $admin = User::query()->create(['first_name' => 'Baobab', 'last_name' => 'Principal', 'email' => 'principal.baobab@shared.example', 'mobile' => '',
                'password' => bcrypt('SyntheticOnly2026'), 'status' => 1]);
            $admin->assignRole('Super Admin');
            app(MembershipService::class)->grant($admin, self::SCHOOL_B_ID, 'Super Admin');
            DB::table('school_memberships')->where('user_id', $admin->id)->update(['starts_on' => '2026-01-01']);
            $ids = $this->schoolB->ids;
            $grade = (int) GradeLevel::where('code', '7')->value('id');
            SessionYear::whereKey($ids['session_year'])->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
            Term::whereKey($ids['term'])->update(['start_date' => '2026-01-01', 'end_date' => '2026-03-31']);
            ClassSchool::whereKey($ids['class_a'])->update(['grade_level_id' => $grade]);
            $key = (string) Str::uuid();
            $plan = app(CurriculumPlans::class)->save($admin, ['subject_id' => $ids['maths'], 'grade_level_id' => $grade, 'session_year_id' => $ids['session_year'],
                'title' => 'Baobab maths', 'annual_components' => ['sba' => 10000], 'rounding' => 'half_up', 'absent_policy' => 'incomplete',
                'topics' => [['key' => $key, 'name' => 'Baobab fractions', 'term_id' => $ids['term'], 'sort_order' => 0]],
                'assessments' => [['term_id' => $ids['term'], 'components' => ['sba' => 10000], 'tasks' => [['name' => 'Baobab task', 'type' => 'test', 'component' => 'sba',
                    'annual_component' => 'sba', 'annual_weight_bp' => 10000, 'weight_bp' => 10000, 'max_mark' => '10.00', 'due_on' => '2026-02-10', 'topic_keys' => [$key],
                    'questions' => [['number' => '1', 'max_mark' => '10.00', 'topic_keys' => [$key]]]]]]]]);
            app(CurriculumPlans::class)->publish($admin, $plan->id, $plan->assessments->sole()->id, 1);
            $group = app(TeachingGroups::class)->save($admin, ['name' => 'Baobab maths group', 'subject_id' => $ids['maths'], 'grade_level_id' => $grade,
                'session_year_id' => $ids['session_year'], 'class_section_id' => $ids['section_a'], 'active' => true]);
            app(TeachingGroups::class)->dated($admin, 'teachers', ['teacher_id' => Teacher::where('user_id', $ids['tara_user'])->value('id'),
                'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Allocated subject'], (int) $group->id);
            app(TopicGraph::class)->sync($ids['maths']);
            $topic = (int) MasteryTopic::query()->where('subject_id', $ids['maths'])->where('topic_key', $key)->value('id');
            $content = ContentItem::create(['author_id' => $ids['tara_user'], 'subject_id' => $ids['maths'], 'grade_level_id' => $grade, 'session_year_id' => $ids['session_year'],
                'operation_id' => (string) Str::uuid(), 'request_hash' => str_repeat('c', 64), 'active' => true]);
            $source = ContentSource::create(['content_item_id' => $content->id, 'uploaded_by' => $ids['tara_user'], 'name' => 'Baobab fractions.md', 'path' => 'synthetic/no-file.md',
                'mime' => 'text/markdown', 'extension' => 'md', 'bytes' => 1, 'sha256' => str_repeat('d', 64), 'scan_state' => 'clean']);
            $version = ContentVersion::create(['content_item_id' => $content->id, 'content_source_id' => $source->id, 'created_by' => $ids['tara_user'], 'number' => 1,
                'state' => 'published', 'metadata' => ['group_ids' => [(int) $group->id], 'title' => 'Baobab fractions'], 'markdown' => '# Fractions', 'generation_id' => (string) Str::uuid(),
                'published_at' => now()]);
            $content->update(['published_version_id' => $version->id]);
            ContentItemGroup::create(['content_item_id' => $content->id, 'teaching_group_id' => $group->id]);

            return ['group' => (int) $group->id, 'topic' => $topic, 'source' => (int) $version->id, 'admin' => $admin];
        });
    }

    /** @return array<string, mixed> */
    private function copyInput(): array
    {
        return ['operation_id' => (string) Str::uuid(), 'group_id' => $this->b['group'] ?? $this->group, 'topic_id' => $this->b['topic'] ?? $this->nodes[$this->keys[1]],
            'content_version_id' => $this->b['source'] ?? $this->source];
    }

    private function copyIntoB(int $itemId): void
    {
        $this->asB(fn () => app(SharedLibraryBrowse::class)->copy($this->bTeacher(), $itemId, $this->copyInput()));
    }

    private function extraBTeacher(): User
    {
        return $this->asB(function (): User {
            $user = User::query()->create(['first_name' => 'Tumi', 'last_name' => 'Baobab', 'email' => 'tumi.baobab@shared.example', 'mobile' => '',
                'password' => bcrypt('SyntheticOnly2026'), 'status' => 1]);
            $user->assignRole('Teacher');
            app(MembershipService::class)->grant($user, self::SCHOOL_B_ID, 'Teacher');
            DB::table('school_memberships')->where('user_id', $user->id)->update(['starts_on' => '2026-01-01']);

            return $user;
        });
    }

    /** A school-created staff role that does not teach and does not manage settings. */
    private function schoolStaff(string $name = 'robin'): User
    {
        $user = User::query()->create(['first_name' => ucfirst($name), 'last_name' => 'Office', 'email' => $name.'.office@shared.example', 'mobile' => '',
            'password' => bcrypt('SyntheticOnly2026'), 'status' => 1]);
        $user->assignRole(Role::firstOrCreate(['name' => 'Office staff', 'guard_name' => 'web']));
        app(MembershipService::class)->grant($user, 1, 'Office staff');
        DB::table('school_memberships')->where('user_id', $user->id)->update(['starts_on' => '2026-01-01']);

        return $user;
    }

    private function api(string $who, int $school): static
    {
        $this->app['auth']->forgetGuards();
        $tokens = $school === 1 ? $this->schoolA->tokens : $this->schoolB->tokens;
        $ids = $school === 1 ? $this->schoolA->ids : $this->schoolB->ids;

        return $this->flushHeaders()->withToken($tokens[$who])->withHeaders(['X-School-Id' => (string) $school, 'X-Account-Id' => (string) $ids[$who.'_user']]);
    }

    private function invalid(callable $call, string $field, string $why = ''): void
    {
        try {
            $call();
            $this->fail('Expected a validation error on '.$field.' '.$why);
        } catch (ValidationException $error) {
            $this->assertArrayHasKey($field, $error->errors(), $why);
        }
    }
}
