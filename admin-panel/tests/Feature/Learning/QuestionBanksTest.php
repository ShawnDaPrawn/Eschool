<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Actions\Transfers\TransferPracticeBanks;
use App\Actions\Transfers\TransferTeachingGroups;
use App\Models\Academics\ContentItemGroup;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Learning\PracticeItem;
use App\Models\Learning\PracticeItemGroup;
use App\Models\Learning\PracticeItemVersion;
use App\Models\Platform\SchoolModule;
use App\Models\SessionYear;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Academics\NewYearService;
use App\Services\Academics\TeachingGroups;
use App\Services\Learning\PracticeBank;
use App\Services\Learning\QuestionBanks;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPracticeScenario;
use Tests\TestCase;

/**
 * Teachers share their own questions (Action Plan 3.6): a practice question
 * belongs to its subject and grade, is reused across classes and survives the
 * year change. Specification: engineering/specs/learning/question-banks.md.
 */
class QuestionBanksTest extends TestCase
{
    use UsesPracticeScenario;

    /** Theo's parallel class: same subject and grade as Tara's group. */
    private int $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        $this->actor('theo')->forceFill(['password_change_required' => false])->save();
        $group = TeachingGroup::findOrFail($this->group);
        $this->other = (int) app(TeachingGroups::class)->save($this->superAdmin(), ['name' => '7B Maths', 'subject_id' => $this->subject, 'grade_level_id' => $group->grade_level_id,
            'session_year_id' => $this->year, 'class_section_id' => null, 'active' => true])->id;
        app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', ['teacher_id' => Teacher::where('user_id', $this->schoolA->ids['theo_user'])->value('id'),
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Allocated subject'], $this->other);
        // Tara's material is published to both classes, so a shared question can reach either.
        ContentItemGroup::create(['content_item_id' => $this->content->id, 'teaching_group_id' => $this->other]);
    }

    private function banks(): QuestionBanks
    {
        return app(QuestionBanks::class);
    }

    private function share(string $visibility, int $item, string $who = 'tara', bool $active = true): void
    {
        $this->banks()->update($this->actor($who), $item, ['visibility' => $visibility, 'active' => $active]);
    }

    /** @return list<int> */
    private function listed(string $who): array
    {
        return array_column($this->banks()->bank($this->actor($who), $this->subject, (int) TeachingGroup::findOrFail($this->group)->grade_level_id)['items'], 'id');
    }

    private function scope(): string
    {
        return $this->subject.'-'.TeachingGroup::findOrFail($this->group)->grade_level_id;
    }

    public function test_a_new_question_belongs_to_its_subject_and_grade_author_and_group(): void
    {
        $id = $this->approve()['id'];

        $item = PracticeItem::findOrFail($id);
        $group = TeachingGroup::findOrFail($this->group);
        $this->assertSame([$this->subject, (int) $group->grade_level_id, (int) $this->actor()->id, 'groups'], [$item->subject_id, $item->grade_level_id, $item->author_id, $item->visibility]);
        $this->assertSame([$this->group], PracticeItemGroup::where('practice_item_id', $id)->pluck('teaching_group_id')->all());
        $row = $this->banks()->bank($this->actor(), $this->subject, (int) $group->grade_level_id)['items'][0];
        $this->assertSame('One half as a decimal?', $row['prompt']);
        $this->assertTrue($row['mine']);
        $this->assertTrue($row['can_edit']);
        $this->assertSame([['group_id' => $this->group, 'name' => 'Maths learning group', 'mine' => true, 'waiting' => null]], $row['uses']);
        $this->assertSame($id, $this->issue()['items'][0]['item_id'], 'learners of the group still get it');
    }

    public function test_a_question_shared_with_the_subject_is_reused_by_a_colleague_in_another_class(): void
    {
        $id = $this->approve()['id'];
        $this->assertSame([], $this->listed('theo'), 'my groups only: not in a colleague\'s bank yet');

        $this->share('school', $id);
        $this->assertSame([$id], $this->listed('theo'));
        $this->banks()->use($this->actor('theo'), $id, $this->other);
        $this->banks()->use($this->actor('theo'), $id, $this->other);

        $this->assertSame(2, PracticeItemGroup::where('practice_item_id', $id)->count(), 'used once in each class');
        $this->assertSame([$id], array_column(app(PracticeBank::class)->catalogue($this->actor('theo'), $this->other)['items'], 'id'));
        $row = $this->banks()->bank($this->actor('theo'), $this->subject, (int) TeachingGroup::findOrFail($this->group)->grade_level_id)['items'][0];
        $this->assertFalse($row['can_edit'], 'a colleague may use it, not change it');
        $this->assertNull(collect($row['uses'])->firstWhere('group_id', $this->other)['waiting'], 'it reaches 7B learners: material published, topic in the plan');

        $this->banks()->stop($this->actor('theo'), $id, $this->other);
        $this->assertSame([$this->group], PracticeItemGroup::where('practice_item_id', $id)->pluck('teaching_group_id')->all(), 'Tara\'s class keeps it');
    }

    public function test_a_question_used_only_where_its_material_is_not_published_waits_for_material(): void
    {
        $id = $this->approve()['id'];
        $this->share('school', $id);
        ContentItemGroup::where('teaching_group_id', $this->other)->delete();
        $this->banks()->use($this->actor('theo'), $id, $this->other);

        $row = $this->banks()->bank($this->actor('theo'), $this->subject, (int) TeachingGroup::findOrFail($this->group)->grade_level_id)['items'][0];
        $this->assertSame('source', collect($row['uses'])->firstWhere('group_id', $this->other)['waiting']);
    }

    public function test_private_and_my_groups_questions_stay_out_of_colleagues_reach(): void
    {
        $id = $this->approve()['id'];
        $this->share('private', $id);
        $this->assertSame([], $this->listed('theo'));
        $this->refuses(fn () => $this->banks()->use($this->actor('theo'), $id, $this->other), 404);
        $this->assertSame(1, PracticeItemGroup::count());

        // Theo co-teaches Tara's group: he sees what its learners get, but may not take a private question elsewhere.
        app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', ['teacher_id' => Teacher::where('user_id', $this->schoolA->ids['theo_user'])->value('id'),
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Co-teacher'], $this->group);
        $this->assertSame([$id], $this->listed('theo'));
        $this->refuses(fn () => $this->banks()->use($this->actor('theo'), $id, $this->other), 403);
        $this->assertSame(1, PracticeItemGroup::count());

        $this->share('groups', $id);
        $this->banks()->use($this->actor('theo'), $id, $this->other);
        $this->assertSame(2, PracticeItemGroup::count(), 'the teachers of the groups using it may reuse it');
    }

    public function test_only_the_author_or_a_subject_lead_changes_a_question(): void
    {
        $id = $this->approve()['id'];
        $this->share('school', $id);
        $this->banks()->use($this->actor('theo'), $id, $this->other);
        $versions = PracticeItemVersion::count();

        $this->refuses(fn () => $this->share('private', $id, 'theo'), 403);
        // Even with material of his own published to 7B, a colleague may not approve new wording for Tara's question.
        $this->content->forceFill(['author_id' => $this->actor('theo')->id])->save();
        $this->refuses(fn () => app(PracticeBank::class)->approve($this->actor('theo'), $this->other, $id, ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1,
            'topic_id' => $this->nodes[$this->keys[1]], 'content_version_id' => $this->source, 'estimated_seconds' => 120, 'active' => true,
            'definition' => ['kind' => 'number', 'prompt' => 'Changed by a colleague', 'explanation' => 'No.', 'options' => [], 'answers' => ['1']]]), 403);
        $this->assertSame(['school', true, 1], [PracticeItem::findOrFail($id)->visibility, PracticeItem::findOrFail($id)->active, PracticeItem::findOrFail($id)->revision]);
        $this->assertSame($versions, PracticeItemVersion::count());

        // The grade's head is a subject lead for this step.
        app(TeachingGroups::class)->dated($this->superAdmin(), 'leadership', ['user_id' => $this->actor('theo')->id, 'session_year_id' => $this->year, 'kind' => 'grade_head',
            'grade_level_id' => TeachingGroup::findOrFail($this->group)->grade_level_id, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic grade head']);
        $this->share('school', $id, 'theo', false);
        $this->assertFalse(PracticeItem::findOrFail($id)->active);
        $this->assertSame(1, PracticeItem::findOrFail($id)->revision, 'archiving never changes the approved revision');
        $this->refuses(fn () => $this->issue(), 409);
    }

    public function test_wrong_role_and_wrong_group_are_refused_and_nothing_is_written(): void
    {
        $id = $this->approve()['id'];
        $this->share('school', $id);
        $before = [PracticeItemGroup::count(), PracticeItem::findOrFail($id)->visibility];

        $this->refuses(fn () => $this->banks()->use($this->actor('theo'), $id, $this->group), 403);
        $this->refuses(fn () => $this->banks()->stop($this->actor('theo'), $id, $this->group), 403);
        $this->refuses(fn () => $this->banks()->update($this->actor('asha'), $id, ['visibility' => 'private', 'active' => true]), 403);
        $this->refuses(fn () => $this->banks()->use($this->actor('farid'), $id, $this->other), 403);
        $this->assertSame($before, [PracticeItemGroup::count(), PracticeItem::findOrFail($id)->visibility]);

        // The panel turns a parent away before the page ("not enough permissions").
        $this->actingAs($this->actor('farid'))->get(route('question-banks.index'))->assertRedirect()->assertDontSee('One half as a decimal?');
        $this->actingAs($this->actor('theo'))->post(route('question-banks.update', ['item' => $id]), ['visibility' => 'private', 'active' => 1])->assertForbidden();
        $this->assertSame('school', PracticeItem::findOrFail($id)->visibility);
    }

    public function test_another_schools_question_and_group_are_not_found_and_neither_school_changes(): void
    {
        $id = $this->approve()['id'];
        $this->share('school', $id);
        $schoolB = fn () => [DB::table('practice_items')->where('school_id', 2)->count(), DB::table('practice_item_groups')->where('school_id', 2)->count()];
        $before = [$schoolB(), PracticeItemGroup::count(), PracticeItem::findOrFail($id)->only(['visibility', 'active'])];

        app(CurrentSchool::class)->run(2, function () use ($id) {
            $baobab = User::findOrFail($this->schoolB->ids['tara_user']);
            $baobab->forceFill(['password_change_required' => false])->save();
            SchoolModule::updateOrCreate(['module' => 'daily_practice'], ['in_package' => true, 'enabled' => true]);
            app(ModuleGate::class)->forget();
            $this->refuses(fn () => $this->banks()->use($baobab, $id, $this->other), 404);
            $this->refuses(fn () => $this->banks()->update($baobab, $id, ['visibility' => 'private', 'active' => false]), 404);
            $this->assertSame([], $this->banks()->scopes($baobab));
        });
        app(ModuleGate::class)->forget();

        $this->assertSame($before, [$schoolB(), PracticeItemGroup::count(), PracticeItem::findOrFail($id)->only(['visibility', 'active'])]);
        $this->actingAs($this->actor())->get(route('question-banks.index', ['scope' => '999999-'.TeachingGroup::findOrFail($this->group)->grade_level_id]))->assertNotFound();
    }

    public function test_the_page_lists_the_bank_and_a_colleague_reuses_a_shared_question(): void
    {
        $id = $this->approve()['id'];
        $this->actingAs($this->actor())->get(route('question-banks.index'))->assertOk()
            ->assertSee('One half as a decimal?')->assertSee('Maths learning group')->assertSee(__('Save sharing'));
        $this->actingAs($this->actor())->post(route('question-banks.update', ['item' => $id]), ['visibility' => 'school', 'active' => 1])->assertRedirect();
        $this->assertSame('school', PracticeItem::findOrFail($id)->visibility);

        $this->actingAs($this->actor('theo'))->get(route('question-banks.index', ['scope' => $this->scope()]))->assertOk()
            ->assertSee('One half as a decimal?')->assertSee(__('Use in this group'))->assertDontSee(__('Save sharing'));
        $this->actingAs($this->actor('theo'))->post(route('question-banks.use', ['item' => $id]), ['group_id' => $this->other])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue(PracticeItemGroup::where('practice_item_id', $id)->where('teaching_group_id', $this->other)->exists());
        $this->actingAs($this->actor('theo'))->post(route('question-banks.update', ['item' => $id]), ['visibility' => 'nobody', 'active' => 1])->assertSessionHasErrors('visibility');
    }

    public function test_a_bank_outlives_its_groups(): void
    {
        $id = $this->approve()['id'];
        TeachingAllocation::where('teaching_group_id', $this->group)->update(['ends_on' => '2026-02-09']);

        $this->assertSame([$this->subject], array_column($this->banks()->scopes($this->actor()), 'subject_id'), 'the author keeps her bank without a class');
        $this->assertSame([$id], $this->listed('tara'));
    }

    public function test_the_year_change_keeps_questions_in_use_in_each_groups_copy_once(): void
    {
        $kept = $this->approve()['id'];
        $archived = $this->approve()['id'];
        PracticeItem::whereKey($archived)->update(['active' => false]);
        $next = (int) SchoolTable::query('session_years')->insertGetId(['name' => '2027', 'start_date' => '2027-01-13', 'end_date' => '2027-12-08',
            'free_app_use_date' => '2027-01-13', 'fee_due_date' => '2027-02-13', 'fee_due_charges' => 5, 'default' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($this->superAdmin());

        $options = collect(app(NewYearService::class)->carryOptions(SessionYear::findOrFail($this->year), SessionYear::findOrFail($next), ['practice_banks']))->keyBy('key');
        $this->assertSame(1, $options['practice_banks']['source']);
        $this->assertTrue($options['practice_banks']['copies']);
        $this->assertTrue($options['teaching_groups']['chosen'], 'questions need their group\'s copy');

        app(TransferTeachingGroups::class)->execute($this->year, $next);
        app(TransferPracticeBanks::class)->execute($this->year, $next);
        app(TransferPracticeBanks::class)->execute($this->year, $next);

        $copy = TeachingGroup::where('session_year_id', $next)->where('name', 'Maths learning group')->firstOrFail();
        $this->assertSame([$kept], PracticeItemGroup::where('teaching_group_id', $copy->id)->pluck('practice_item_id')->all(), 'once, and archived questions stay behind');
        $this->assertSame(2, PracticeItemGroup::where('teaching_group_id', $this->group)->count(), 'last year keeps its history');
        $this->assertSame(1, PracticeItem::whereKey($kept)->value('revision'), 'the question itself never moves or changes');

        SchoolModule::where('module', 'daily_practice')->update(['enabled' => false]);
        app(ModuleGate::class)->forget();
        $options = collect(app(NewYearService::class)->carryOptions(SessionYear::findOrFail($this->year), SessionYear::findOrFail($next), ['practice_banks']))->keyBy('key');
        $this->assertSame(0, $options['practice_banks']['source'], 'nothing to carry with daily practice off');
    }

    public function test_a_teacher_cannot_carry_question_banks_into_the_new_year(): void
    {
        $this->approve();
        $next = (int) SchoolTable::query('session_years')->insertGetId(['name' => '2027', 'start_date' => '2027-01-13', 'end_date' => '2027-12-08',
            'free_app_use_date' => '2027-01-13', 'fee_due_date' => '2027-02-13', 'fee_due_charges' => 5, 'default' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $copy = (int) app(TeachingGroups::class)->save($this->superAdmin(), ['name' => 'Maths learning group', 'subject_id' => $this->subject,
            'grade_level_id' => TeachingGroup::findOrFail($this->group)->grade_level_id, 'session_year_id' => $next, 'active' => true])->id;
        $before = PracticeItemGroup::count();
        $this->actingAs($this->actor());

        $this->refuses(fn () => app(TransferPracticeBanks::class)->execute($this->year, $next), 403);
        $this->assertSame($before, PracticeItemGroup::count());
        $this->assertSame(0, PracticeItemGroup::where('teaching_group_id', $copy)->count());
    }
}
