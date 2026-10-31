<?php

declare(strict_types=1);

namespace Tests\Feature\Activities;

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\People\Enrolment;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Activities\ActivityGroups;
use App\Services\Activities\ActivitySeasons;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * Season rollover (Action Plan 1.22): summer groups become winter groups,
 * empty. engineering/specs/activities/season-rollover.md.
 */
class ActivitySeasonsTest extends TestCase
{
    use UsesTwoSchools;

    private int $year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->year = $this->schoolA->ids['session_year'];
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $this->superAdmin()->givePermissionTo('activity-groups-manage');
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic enrolment']);
        app(ModuleGate::class)->change('groups', true, $this->superAdmin());
        $this->actingAs($this->superAdmin());
    }

    private function group(array $extra = []): array
    {
        return app(ActivityGroups::class)->save($this->superAdmin(), null, $extra + [
            'operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $this->year, 'parent_id' => null,
            'name' => 'Soccer', 'discipline' => 'Sport', 'season' => 'Summer', 'starts_on' => '2026-01-20', 'ends_on' => '2026-06-30',
            'gender' => 'any', 'minimum_grade' => null, 'maximum_grade' => null, 'minimum_age' => null, 'maximum_age' => null, 'active' => true,
        ]);
    }

    private function rollover(array $input = []): array
    {
        return app(ActivitySeasons::class)->rollover($this->superAdmin(), $input + [
            'session_year_id' => $this->year, 'from' => 'Summer', 'to' => 'Winter', 'starts_on' => '2026-07-15', 'ends_on' => '2026-11-30',
        ]);
    }

    public function test_the_seasons_groups_are_copied_empty_with_their_tree_rules_and_new_dates(): void
    {
        $club = $this->group(['name' => 'Sports club', 'season' => 'All year', 'starts_on' => '2026-01-10', 'ends_on' => '2026-12-10']);
        $soccer = $this->group(['parent_id' => $club['id']]);
        $this->group(['name' => 'U14 Boys', 'parent_id' => $soccer['id'], 'gender' => 'male', 'minimum_grade' => 7, 'maximum_grade' => 8, 'starts_on' => '2026-02-01', 'ends_on' => '2026-06-15']);
        $this->group(['name' => 'Chess', 'season' => 'All year', 'starts_on' => '2026-01-10', 'ends_on' => '2026-12-10']);
        app(ActivityGroups::class)->allocation($this->superAdmin(), $soccer['id'], null, false, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'starts_on' => '2026-02-01', 'ends_on' => '2026-06-30', 'reason' => 'Synthetic member', 'learner_id' => $this->schoolA->ids['asha'], 'role' => 'member']);

        $this->assertSame(['created' => 2, 'skipped' => 0], $this->rollover());

        $winter = ActivityGroup::where('season', 'Winter')->where('name', 'Soccer')->firstOrFail();
        $this->assertSame($club['id'], (int) $winter->parent_id, 'stays under the whole-year club');
        $this->assertSame(['2026-07-15', '2026-11-30'], [$winter->starts_on->toDateString(), $winter->ends_on->toDateString()]);
        $u14 = ActivityGroup::where('season', 'Winter')->where('name', 'U14 Boys')->firstOrFail();
        $this->assertSame([$winter->id, 'male', 7, 8], [(int) $u14->parent_id, $u14->gender, (int) $u14->minimum_grade, (int) $u14->maximum_grade]);
        $this->assertSame(0, ActivityMembership::where('activity_group_id', $winter->id)->count(), 'nobody is carried');
        $this->assertSame(1, ActivityMembership::where('activity_group_id', $soccer['id'])->count(), 'summer keeps its history');
        $this->assertSame(1, ActivityGroup::where('name', 'Chess')->count(), 'other seasons are untouched');

        // Again: nothing new.
        $this->assertSame(['created' => 0, 'skipped' => 2], $this->rollover());
        $this->assertSame(['All year' => 2, 'Summer' => 2, 'Winter' => 2], app(ActivitySeasons::class)->seasons($this->superAdmin(), $this->year));
    }

    public function test_a_group_is_kept_inside_its_parents_dates_or_skipped(): void
    {
        $club = $this->group(['name' => 'Athletics club', 'season' => 'All year', 'starts_on' => '2026-01-10', 'ends_on' => '2026-08-31']);
        $this->group(['name' => 'Cross country', 'parent_id' => $club['id']]);
        $short = $this->group(['name' => 'Spring meet', 'season' => 'All year', 'starts_on' => '2026-01-10', 'ends_on' => '2026-06-30']);
        $this->group(['name' => 'Relay', 'parent_id' => $short['id']]);

        $this->assertSame(['created' => 1, 'skipped' => 1], $this->rollover());

        $cross = ActivityGroup::where('season', 'Winter')->where('name', 'Cross country')->firstOrFail();
        $this->assertSame(['2026-07-15', '2026-08-31'], [$cross->starts_on->toDateString(), $cross->ends_on->toDateString()]);
        $this->assertFalse(ActivityGroup::where('season', 'Winter')->where('name', 'Relay')->exists());
    }

    public function test_bad_requests_are_refused(): void
    {
        $this->group();
        foreach ([['to' => 'summer'], ['starts_on' => '2026-12-01', 'ends_on' => '2026-11-01'], ['ends_on' => '2027-01-31'], ['from' => 'Spring']] as $bad) {
            try {
                $this->rollover($bad);
                $this->fail('Accepted '.json_encode($bad));
            } catch (ValidationException) {
            }
        }
        $this->assertSame(0, ActivityGroup::where('season', 'Winter')->count());
    }

    public function test_existing_long_season_labels_can_be_rolled_over_from_the_panel(): void
    {
        $label = str_repeat('S', 90);
        $this->group(['season' => $label]);

        $this->post('/activity-seasons', ['from' => $label, 'to' => 'Winter', 'starts_on' => '2026-07-15', 'ends_on' => '2026-11-30'])
            ->assertRedirect('/activity-seasons')->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame(1, ActivityGroup::where('season', 'Winter')->count());
    }

    public function test_the_panel_starts_a_season_and_others_cannot(): void
    {
        $this->group();
        $this->get('/activity-seasons')->assertOk()->assertSee('Summer: 1 active group');
        $this->post('/activity-seasons', ['from' => 'Summer', 'to' => 'Winter', 'starts_on' => '2026-07-15', 'ends_on' => '2026-11-30'])
            ->assertRedirect('/activity-seasons')->assertSessionHas('success');
        $this->assertSame(1, ActivityGroup::where('season', 'Winter')->count());

        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']));
        $this->get('/activity-seasons')->assertForbidden();
        $this->post('/activity-seasons', ['from' => 'Summer', 'to' => 'Spring', 'starts_on' => '2026-07-15', 'ends_on' => '2026-11-30'])->assertForbidden();
        try {
            app(ActivitySeasons::class)->rollover(User::findOrFail($this->schoolA->ids['tara_user']), ['session_year_id' => $this->year, 'from' => 'Summer', 'to' => 'Spring', 'starts_on' => '2026-07-15', 'ends_on' => '2026-11-30']);
            $this->fail('A teacher started a season');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        // School B's groups are out of reach: its year is not found here.
        $this->actingAs($this->superAdmin());
        $this->expectException(ModelNotFoundException::class);
        $this->rollover(['session_year_id' => $this->schoolB->ids['session_year']]);
    }

    public function test_another_connection_cannot_start_a_rollover_while_source_and_destination_are_checked(): void
    {
        $this->group();
        // The competing connection needs committed synthetic fixtures. Keeping
        // the fixture transaction would itself hold the school lock and hide
        // the missing rollover lock this regression is meant to detect.
        DB::commit();
        $this->assertSame(0, DB::transactionLevel());
        config(['database.connections.season_lock_probe' => config('database.connections.'.config('database.default'))]);
        $probe = DB::connection('season_lock_probe');
        $probe->statement('SET SESSION innodb_lock_wait_timeout = 1');
        $attempted = $blocked = false;
        DB::listen(function (QueryExecuted $query) use ($probe, &$attempted, &$blocked) {
            if ($attempted || ! str_contains($query->sql, 'from `activity_groups`')) {
                return;
            }
            $attempted = true;
            try {
                $probe->transaction(fn () => $probe->table('schools')->where('id', 1)->lockForUpdate()->first());
            } catch (QueryException $error) {
                if ((int) ($error->errorInfo[1] ?? 0) !== 1205) {
                    throw $error;
                }
                $blocked = true;
            }
        });
        try {
            $this->assertSame(['created' => 1, 'skipped' => 0], $this->rollover());
            $this->assertTrue($attempted, 'A separate connection must compete during the group lookup');
            $this->assertTrue($blocked, 'Destination checks must hold the same school lock as activity writes');
            $this->assertSame(1, ActivityGroup::where('season', 'Winter')->count());
        } finally {
            DB::purge('season_lock_probe');
        }
    }
}
