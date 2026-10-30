<?php

declare(strict_types=1);

namespace Tests\Feature\Activities;

use App\Models\Activities\ActivityMembership;
use App\Models\People\Enrolment;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Activities\ActivityGroups;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * Re-assignment suggestions (Action Plan 1.20): last year's members of the
 * same group, still at the school and eligible, offered, never added.
 */
class ActivitySuggestionsTest extends TestCase
{
    use UsesTwoSchools;

    private int $year;

    private int $last;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10));
        SchoolMembership::query()->update(['starts_on' => '2025-01-01']);
        $this->year = $this->schoolA->ids['session_year'];
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $this->last = (int) SchoolTable::query('session_years')->insertGetId(['name' => '2025', 'start_date' => '2025-01-01', 'end_date' => '2025-12-10',
            'free_app_use_date' => '2025-01-01', 'fee_due_date' => '2025-02-01', 'fee_due_charges' => 5, 'default' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $this->superAdmin()->givePermissionTo('activity-groups-manage');
        app(ModuleGate::class)->change('groups', true, $this->superAdmin());
        $ids = $this->schoolA->ids;
        foreach (['asha', 'ben', 'cara'] as $learner) {
            Enrolment::create(['learner_id' => $ids[$learner], 'session_year_id' => $this->last, 'class_section_id' => $ids['section_a'], 'starts_on' => '2025-01-01', 'ends_on' => '2025-12-10', 'reason' => 'Synthetic']);
        }
        // Asha and Ben are back this year; Cara left.
        foreach (['asha', 'ben'] as $learner) {
            Enrolment::create(['learner_id' => $ids[$learner], 'session_year_id' => $this->year, 'class_section_id' => $ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic']);
        }
        $this->actingAs($this->superAdmin());
    }

    private function group(int $year, string $name, array $extra = []): array
    {
        [$start, $end] = $year === $this->year ? ['2026-01-20', '2026-06-30'] : ['2025-01-20', '2025-06-30'];

        return app(ActivityGroups::class)->save($this->superAdmin(), null, $extra + [
            'operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $year, 'parent_id' => null, 'name' => $name, 'discipline' => 'Sport',
            'season' => 'Summer', 'starts_on' => $start, 'ends_on' => $end, 'gender' => 'any', 'minimum_grade' => null, 'maximum_grade' => null,
            'minimum_age' => null, 'maximum_age' => null, 'active' => true,
        ]);
    }

    private function member(array $group, string $learner, string $ends): void
    {
        $this->travelTo(now()->setDate(2025, 1, 25));
        app(ActivityGroups::class)->allocation($this->superAdmin(), $group['id'], null, false, ['operation_id' => (string) Str::uuid(), 'revision' => 0,
            'starts_on' => '2025-01-25', 'ends_on' => $ends, 'reason' => 'Synthetic member', 'learner_id' => $this->schoolA->ids[$learner], 'role' => 'member']);
        $this->travelTo(now()->setDate(2026, 2, 10));
    }

    public function test_last_years_members_who_are_back_and_eligible_are_suggested(): void
    {
        $old = $this->group($this->last, 'Netball');
        $this->member($old, 'asha', '2025-06-30');
        $this->member($old, 'ben', '2025-03-31');
        $this->member($old, 'cara', '2025-06-30');
        $new = $this->group($this->year, 'Netball');

        $ids = $this->schoolA->ids;
        $this->assertSame([$ids['asha']], array_keys(app(ActivityGroups::class)->lastYearSuggestions($this->superAdmin(), $new['id'])),
            'Ben left the group before its end; Cara left the school');
        $this->assertSame(0, ActivityMembership::where('activity_group_id', $new['id'])->count(), 'nothing is added by itself');

        // A rule Asha no longer meets, or membership already, removes the suggestion.
        User::whereKey($ids['asha_user'])->update(['gender' => 'female']);
        $boys = $this->group($this->year, 'Netball boys', ['gender' => 'male']);
        $this->assertSame([], app(ActivityGroups::class)->lastYearSuggestions($this->superAdmin(), $boys['id']));

        $this->get(route('activity-groups.index', ['group_id' => $new['id']]))->assertOk()->assertSee('In this group last year')->assertSee('Add as member');
        $this->post(route('activity-groups.membership-save', ['group' => $new['id']]), ['operation_id' => (string) Str::uuid(), 'revision' => 0,
            'learner_id' => $ids['asha'], 'role' => 'member', 'starts_on' => '2026-02-10', 'ends_on' => '2026-06-30', 'reason' => 'In this group last year'])->assertRedirect();
        $this->assertSame([], app(ActivityGroups::class)->lastYearSuggestions($this->superAdmin(), $new['id']));
    }

    public function test_a_teacher_gets_no_suggestions(): void
    {
        $new = $this->group($this->year, 'Netball');
        $this->expectException(HttpException::class);
        app(ActivityGroups::class)->lastYearSuggestions(User::findOrFail($this->schoolA->ids['tara_user']), $new['id']);
    }

    public function test_an_ended_season_does_not_offer_allocations_with_an_inverted_date_range(): void
    {
        $old = $this->group($this->last, 'Netball');
        $this->member($old, 'asha', '2025-06-30');
        $new = $this->group($this->year, 'Netball');
        $this->travelTo(now()->setDate(2026, 7, 1));

        $this->assertSame([], app(ActivityGroups::class)->lastYearSuggestions($this->superAdmin(), $new['id']));
        $this->assertSame(0, ActivityMembership::where('activity_group_id', $new['id'])->count());
    }
}
