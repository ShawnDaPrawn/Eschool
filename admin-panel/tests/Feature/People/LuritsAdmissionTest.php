<?php

declare(strict_types=1);

namespace Tests\Feature\People;

use App\Models\Platform\AuditLog;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Identity\LuritsNumbers;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * Matching on LURITS when the office adds a learner (Action Plan 1.0c).
 * engineering/specs/platform/lurits.md, "Matching at admission".
 */
class LuritsAdmissionTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        // Ben at School B holds 8800000002; Asha at School A holds 8800000001.
        app(CurrentSchool::class)->run(2, fn () => app(LuritsNumbers::class)->record(Students::query()->findOrFail($this->schoolB->ids['ben']), '8800000002', 'sasams_import'));
        app(CurrentSchool::class)->run(1, fn () => app(LuritsNumbers::class)->record(Students::query()->findOrFail($this->schoolA->ids['asha']), '8800000001', 'school_entry'));
        app(CurrentSchool::class)->forget();
    }

    /** The panel's Add learner form, with an existing parent of School A. */
    private function add(User $actor, array $extra = [])
    {
        $parents = app(CurrentSchool::class)->run(1, fn () => SchoolTable::query('parents')->orderBy('id')->limit(2)->pluck('id')->map(fn ($id) => (int) $id)->all());
        $classSection = app(CurrentSchool::class)->run(1, fn () => (int) SchoolTable::query('class_sections')->orderBy('id')->value('id'));
        $category = app(CurrentSchool::class)->run(1, fn () => (int) SchoolTable::query('categories')->orderBy('id')->value('id'));
        app(CurrentSchool::class)->forget();

        return $this->actingAs($actor)->postJson('/students', $extra + [
            'first_name' => 'Nia', 'last_name' => 'New', 'gender' => 'female', 'dob' => '15-03-2016',
            'class_section_id' => $classSection, 'category_id' => $category,
            'admission_date' => now()->format('d-m-Y'), 'current_address' => '1 Road', 'permanent_address' => '1 Road',
            'height' => 120, 'weight' => 25, 'parent_guardian_type' => 'parent',
            'father_email' => $parents[0], 'mother_email' => $parents[1], 'father_dob' => '03-02-1985', 'mother_dob' => '04-05-1986',
        ]);
    }

    private function nias(): int
    {
        return app(CurrentSchool::class)->acrossSchools(fn () => User::query()->where('first_name', 'Nia')->where('last_name', 'New')->count());
    }

    public function test_a_new_number_is_recorded_on_the_new_learner(): void
    {
        $this->add($this->superAdmin(), ['lurits' => '8800 000 009'])->assertOk()->assertJson(['error' => false]);

        $nia = app(CurrentSchool::class)->run(1, fn () => Students::query()->whereHas('user', fn ($q) => $q->where('first_name', 'Nia'))->firstOrFail());
        $this->assertSame('8800000009', app(CurrentSchool::class)->run(1, fn () => app(LuritsNumbers::class)->forLearner($nia)));
    }

    public function test_a_learner_added_without_a_number_is_added_as_before(): void
    {
        $this->add($this->superAdmin())->assertOk()->assertJson(['error' => false]);
        $this->assertSame(1, $this->nias());
    }

    public function test_a_number_held_at_another_school_asks_for_a_transfer_and_never_says_where(): void
    {
        $conflicts = DB::table('lurits_conflicts')->count();
        $response = $this->add($this->superAdmin(), ['lurits' => '8800000002'])->assertUnprocessable()
            ->assertJsonValidationErrors(['lurits' => 'Start a transfer request']);
        $this->assertStringNotContainsString(self::SCHOOL_B_MARKER, (string) $response->getContent());
        $this->assertStringNotContainsString('GR-BAOBAB', (string) $response->getContent());
        $this->assertSame(0, $this->nias());
        $this->assertSame($conflicts, DB::table('lurits_conflicts')->count());

        $audit = AuditLog::query()->withoutGlobalScopes()->where('action', 'identity.lurits_admission_matched')->sole();
        $this->assertSame(1, (int) $audit->school_id);
        $this->assertStringNotContainsString('8800000002', json_encode($audit->toArray()));
    }

    public function test_a_number_held_by_one_of_the_schools_own_learners_names_that_learner(): void
    {
        $this->add($this->superAdmin(), ['lurits' => '8800000001'])->assertUnprocessable()
            ->assertJsonValidationErrors(['lurits' => 'GR-ASHA']);
        $this->assertSame(0, $this->nias());

        DB::table('students')->where('id', $this->schoolA->ids['asha'])->update(['deleted_at' => now()]);
        $this->add($this->superAdmin(), ['lurits' => '8800000001'])->assertUnprocessable()
            ->assertJsonValidationErrors(['lurits' => 'former learner']);
        $this->assertSame(0, $this->nias());
    }

    public function test_an_invalid_number_is_refused(): void
    {
        $this->add($this->superAdmin(), ['lurits' => '12'])->assertUnprocessable()->assertJsonValidationErrors('lurits');
        $this->add($this->superAdmin(), ['lurits' => str_repeat('9', 40)])->assertUnprocessable()->assertJsonValidationErrors('lurits');
        $this->assertSame(0, $this->nias());
    }

    public function test_staff_who_cannot_add_learners_learn_nothing_and_add_no_one(): void
    {
        $teacher = User::query()->findOrFail($this->schoolA->ids['tara_user']);
        $response = $this->add($teacher, ['lurits' => '8800000002']);
        $this->assertStringNotContainsString('transfer', (string) $response->getContent());
        $this->assertSame(0, $this->nias());
        $this->assertSame(0, AuditLog::query()->withoutGlobalScopes()->where('action', 'identity.lurits_admission_matched')->count());
    }
}
