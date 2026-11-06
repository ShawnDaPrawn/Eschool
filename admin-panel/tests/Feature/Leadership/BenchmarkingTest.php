<?php

declare(strict_types=1);

namespace Tests\Feature\Leadership;

use App\Models\Academics\AttendanceEntry;
use App\Models\Academics\AttendanceRegister;
use App\Models\Academics\GradeLevel;
use App\Models\ClassSection;
use App\Models\Leadership\BenchmarkParticipation;
use App\Models\Leadership\BenchmarkRelease;
use App\Models\Leadership\BenchmarkSchoolValue;
use App\Models\People\Enrolment;
use App\Models\Platform\AuditLog;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\Platform\SchoolAccount;
use App\Models\Platform\SchoolHealthSnapshot;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Academics\TeachingGroups;
use App\Services\Leadership\Benchmarking\BenchmarkMetrics;
use App\Services\Leadership\Benchmarking\BenchmarkSnapshots;
use App\Services\Leadership\Benchmarking\PlatformBenchmarks;
use App\Services\Leadership\Benchmarking\SchoolBenchmarks;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\PassesPlatformTwoFactor;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * Anonymous benchmarking (Action Plan 5.5): opt-in and reciprocity, the
 * k-anonymity threshold, the differencing guard, no school identifiable,
 * roles and school isolation. engineering/specs/leadership/benchmarking.md.
 */
class BenchmarkingTest extends TestCase
{
    use PassesPlatformTwoFactor;
    use UsesTwoSchools;

    /** Every synthetic comparable school's name carries it. */
    private const MARKER = 'Mopane';

    private const GROUP = 'public:junior:0';

    private User $principal;

    private int $grade;

    /** @var array<int, array{group_key: ?string, values: array<string, ?int>}> */
    private array $figures = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(CarbonImmutable::parse('2026-02-10 10:00:00', 'Africa/Johannesburg'));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        config(['modules.leadership_dashboard' => ['always_on' => true], 'benchmarking.min_group_size' => 5, 'benchmarking.min_membership_change' => 3,
            'benchmarking.min_attendance_learner_days' => 2, 'benchmarking.min_reach_learners' => 5]);
        $year = $this->schoolA->ids['session_year'];
        SessionYear::whereKey($year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $this->grade = (int) GradeLevel::where('code', '5')->value('id');
        ClassSection::with('class')->findOrFail($this->schoolA->ids['section_a'])->class->forceFill(['grade_level_id' => $this->grade])->save();
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => null, 'legacy_dates' => false]);
        $this->principal = User::findOrFail($this->schoolA->ids['tara_user']);
        app(TeachingGroups::class)->dated($this->superAdmin(), 'leadership', ['user_id' => $this->principal->id, 'session_year_id' => $year, 'kind' => 'principal', 'grade_level_id' => null, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic benchmarking appointment']);
        SchoolAccount::query()->create(['stage' => 'live', 'school_type' => 'public']);
        foreach (range(3, 9) as $id) {
            School::query()->forceCreate(['id' => $id, 'name' => self::MARKER.' Synthetic '.$id, 'slug' => 'mopane-'.$id, 'timezone' => 'Africa/Johannesburg']);
        }
        // Other schools' figures are given; School A's are worked out from its own records.
        $figures = &$this->figures;
        app()->instance(BenchmarkMetrics::class, new class($figures) extends BenchmarkMetrics
        {
            public function __construct(private array &$figures) {}

            public function forSchool(string $periodEnd): array
            {
                return $this->figures[app(CurrentSchool::class)->id()] ?? parent::forSchool($periodEnd);
            }
        });
    }

    private function join(int $school): void
    {
        app(CurrentSchool::class)->run($school, fn () => BenchmarkParticipation::query()->updateOrCreate([], ['opted_in' => true, 'changed_at' => now()->subDay(), 'left_at' => null]));
    }

    private function leave(int $school): void
    {
        app(CurrentSchool::class)->run($school, fn () => BenchmarkParticipation::query()->update(['opted_in' => false, 'changed_at' => now(), 'left_at' => now()]));
    }

    /** A synthetic comparable school with the given attendance and reach (basis points). */
    private function other(int $school, ?int $attendance, ?int $reach = null, ?int $results = null, string $group = self::GROUP): void
    {
        $this->figures[$school] = ['group_key' => $group, 'values' => ['attendance' => $attendance, 'results_movement' => $results, 'reach' => $reach]];
        $this->join($school);
    }

    /** School A's own records: one learner, a present and an absent day, and 7 of 10 learners reachable. */
    private function schoolARecords(): void
    {
        foreach (['2026-02-05' => 'present', '2026-02-06' => 'absent'] as $date => $state) {
            $register = AttendanceRegister::create(['session_year_id' => $this->schoolA->ids['session_year'], 'class_section_id' => $this->schoolA->ids['section_a'], 'on_date' => $date, 'timetable_lesson_id' => null, 'grade_level_id' => $this->grade, 'scope_key' => 'homeroom:'.$this->schoolA->ids['section_a'], 'label' => 'Synthetic homeroom', 'revision' => 1]);
            AttendanceEntry::create(['attendance_register_id' => $register->id, 'learner_id' => $this->schoolA->ids['asha'], 'state' => $state, 'source' => 'manual', 'captured_by' => $this->principal->id, 'revision' => 1]);
        }
        SchoolHealthSnapshot::query()->create(['snapshot_date' => '2026-02-07', 'guardians' => 10, 'active_guardians' => 4, 'learners' => 10, 'reached_learners' => 7,
            'flag_count' => 0, 'metrics' => [], 'flags' => [], 'computed_at' => now()]);
    }

    private function snapshot(string $period = '2026-02-08'): array
    {
        return app(BenchmarkSnapshots::class)->run($period);
    }

    private function read(): array
    {
        return app(SchoolBenchmarks::class)->read($this->principal);
    }

    private function release(string $metric, string $period = '2026-02-08'): ?BenchmarkRelease
    {
        return BenchmarkRelease::query()->whereDate('period_end', $period)->where('group_key', self::GROUP)->where('metric', $metric)->first();
    }

    private function hq(string $role): User
    {
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => ucfirst($role), 'last_name' => 'Operator', 'email' => $role.'@platform.example', 'mobile' => '', 'password' => 'Synthetic-benchmark-password-417!', 'status' => 1]));
        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => $role, 'expires_at' => now()->addHour()]);

        return $user;
    }

    private function refused(callable $call, int $status = 403): void
    {
        try {
            $call();
            $this->fail('Expected a refusal');
        } catch (HttpException $e) {
            $this->assertSame($status, $e->getStatusCode());
        }
    }

    public function test_a_participating_school_sees_its_own_figures_next_to_the_median_and_quartiles_of_comparable_schools(): void
    {
        $this->schoolARecords();
        $this->assertSame(['group_key' => self::GROUP, 'values' => ['attendance' => 5000, 'results_movement' => null, 'reach' => 7000]],
            app(BenchmarkMetrics::class)->forSchool('2026-02-08'));
        foreach ([3 => [9000, 6000, 4000], 4 => [8500, 6500, 4100], 5 => [9300, 8000, 4200], 6 => [8800, 9000, 4300]] as $school => [$attendance, $reach, $results]) {
            $this->other($school, $attendance, $reach, $results);
        }
        $this->actingAs($this->principal)->post('/leadership-dashboard/benchmarking', ['participate' => '1'])->assertRedirect();
        $this->assertTrue(BenchmarkParticipation::query()->sole()->opted_in);
        $this->assertSame(1, AuditLog::query()->where('action', 'benchmarking_joined')->count());
        $this->assertSame(['participating' => true, 'rejoin_on' => null, 'minimum_group_size' => 5, 'period_end' => null, 'group' => null, 'metrics' => []], $this->read());

        $this->travelTo(CarbonImmutable::parse('2026-02-10 11:00:00', 'Africa/Johannesburg'));
        $this->assertSame(['period_end' => '2026-02-08', 'schools' => 5, 'failed' => 0, 'released' => 2, 'held' => 0, 'withheld' => 1], $this->snapshot());
        $data = $this->read();
        $this->assertSame('2026-02-08', $data['period_end']);
        $this->assertSame(['sector' => 'public', 'stages' => ['junior'], 'learners_from' => 1, 'learners_to' => 299], $data['group']);
        // Sorted attendance 5000, 8500, 8800, 9000, 9300: quartiles 85%, 88%, 90%.
        $this->assertSame(['own_bp' => 5000, 'comparison' => ['lower_quartile_bp' => 8500, 'median_bp' => 8800, 'upper_quartile_bp' => 9000, 'as_of' => '2026-02-08', 'held' => false],
            'position' => 'lower_quarter'], $data['metrics']['attendance']);
        $this->assertSame(['own_bp' => 7000, 'comparison' => ['lower_quartile_bp' => 6500, 'median_bp' => 7000, 'upper_quartile_bp' => 8000, 'as_of' => '2026-02-08', 'held' => false],
            'position' => 'middle_half'], $data['metrics']['reach']);
        // School A has no results movement, so only four schools have one: below k, nothing is shown.
        $this->assertSame(['own_bp' => null, 'comparison' => null, 'position' => null], $data['metrics']['results_movement']);
        $this->assertNull($this->release('results_movement'));

        $page = $this->get('/leadership-dashboard')->assertOk()->assertSee('Comparable schools')->assertSee('88%')->assertSee('Lower quarter')
            ->assertSee('Not enough comparable schools yet')->assertSee('Stop taking part');
        $page->assertDontSee(self::MARKER)->assertDontSee(self::SCHOOL_B_MARKER);

        $event = collect(app(Schedule::class)->events())->first(fn ($event) => $event->description === 'benchmark-snapshots');
        $this->assertNotNull($event);
        $this->assertStringContainsString('benchmarking:snapshot', $event->command);
        $this->assertSame('10 5 * * 1', $event->expression);

        // Running the week again gives the same figures.
        $before = BenchmarkRelease::query()->orderBy('id')->get(['group_key', 'metric', 'lower_quartile_bp', 'median_bp', 'upper_quartile_bp', 'status'])->toArray();
        $this->artisan('benchmarking:snapshot', ['--period' => '2026-02-08'])->assertSuccessful();
        $this->assertSame($before, BenchmarkRelease::query()->orderBy('id')->get(['group_key', 'metric', 'lower_quartile_bp', 'median_bp', 'upper_quartile_bp', 'status'])->toArray());
        $this->artisan('benchmarking:snapshot', ['--period' => '2026-02-07'])->assertExitCode(2);

        // HQ sees the same aggregates and nothing else.
        $owner = $this->hq('owner');
        $hq = $this->actingAsHq($owner)->get('/saas/benchmarks')->assertOk()->assertSee('88%')->assertSee('85%')->assertSee('90%');
        $hq->assertDontSee(self::MARKER)->assertDontSee('Jacaranda')->assertDontSee(self::SCHOOL_B_MARKER)->assertDontSee('50%');
        $overview = app(PlatformBenchmarks::class)->overview($owner);
        $this->assertSame(['lower_quartile_bp' => 8500, 'median_bp' => 8800, 'upper_quartile_bp' => 9000, 'as_of' => '2026-02-08', 'held' => false], $overview['groups'][0]['metrics']['attendance']);
    }

    public function test_teachers_parents_and_other_hq_roles_are_refused_and_nothing_is_written(): void
    {
        foreach (['theo_user', 'farid_user'] as $person) {
            $user = User::findOrFail($this->schoolA->ids[$person]);
            // The panel turns a parent away before the page (a redirect); a teacher is forbidden.
            $status = $this->actingAs($user)->post('/leadership-dashboard/benchmarking', ['participate' => '1'])->getStatusCode();
            $this->assertSame($person === 'theo_user' ? 403 : 302, $status);
            $this->refused(fn () => app(SchoolBenchmarks::class)->read($user));
            $this->refused(fn () => app(SchoolBenchmarks::class)->choose($user, true));
        }
        $this->actingAs(User::findOrFail($this->schoolA->ids['theo_user']))->get('/leadership-dashboard')->assertForbidden();
        $this->assertSame(0, BenchmarkParticipation::query()->count());
        $this->actingAs($this->principal)->post('/leadership-dashboard/benchmarking', ['participate' => ['1']])->assertSessionHasErrors('participate');
        $this->principal->forceFill(['password_change_required' => true])->save();
        $this->refused(fn () => app(SchoolBenchmarks::class)->choose($this->principal, true));
        $this->principal->forceFill(['password_change_required' => false])->save();
        PlatformStaffIdentity::query()->create(['user_id' => $this->principal->id]);
        $this->refused(fn () => app(SchoolBenchmarks::class)->choose($this->principal, true));
        $this->assertSame(0, BenchmarkParticipation::query()->count());

        $this->actingAsHq($this->hq('sales'))->get('/saas/benchmarks')->assertForbidden();
        $this->refused(fn () => app(PlatformBenchmarks::class)->overview($this->hq('content')));
        $this->newBrowserSession()->actingAs($this->superAdmin())->get('/saas/benchmarks')->assertForbidden();
    }

    public function test_a_school_that_does_not_take_part_sees_nothing_and_is_never_counted(): void
    {
        $this->schoolARecords();
        foreach (range(3, 7) as $school) {
            $this->other($school, 8000 + $school * 100, 7000);
        }
        $this->snapshot();
        $this->assertNotNull($this->release('attendance'));
        $this->assertSame(['participating' => false, 'rejoin_on' => null, 'minimum_group_size' => 5, 'period_end' => null, 'group' => null, 'metrics' => []], $this->read());
        $this->assertSame(0, BenchmarkSchoolValue::query()->count(), 'School A is not worked out unless it takes part');
        $this->actingAs($this->principal)->get('/leadership-dashboard')->assertOk()->assertSee('Take part in benchmarking')
            ->assertSee('does not take part')->assertDontSee('Median');

        // Taking part and then leaving: nothing is shown again at once, and joining again waits.
        app(SchoolBenchmarks::class)->choose($this->principal, true);
        $this->travelTo(CarbonImmutable::parse('2026-02-16 10:00:00', 'Africa/Johannesburg'));
        $this->snapshot('2026-02-15');
        $this->assertNotNull($this->read()['metrics']['attendance']['comparison']);
        $this->actingAs($this->principal)->post('/leadership-dashboard/benchmarking', ['participate' => '0'])->assertRedirect();
        $this->assertSame([], $this->read()['metrics']);
        $this->assertSame('2026-05-17', $this->read()['rejoin_on']);
        try {
            app(SchoolBenchmarks::class)->choose($this->principal, true);
            $this->fail('Joining again must wait');
        } catch (ValidationException) {
        }
        $this->actingAs($this->principal)->post('/leadership-dashboard/benchmarking', ['participate' => '1'])->assertSessionHasErrors('participate');
        $this->assertFalse(BenchmarkParticipation::query()->sole()->opted_in);
        $this->travelTo(CarbonImmutable::parse('2026-05-18 10:00:00', 'Africa/Johannesburg'));
        $this->assertTrue(app(SchoolBenchmarks::class)->choose($this->principal, true)->opted_in);
        // Old figures from before joining again are not shown.
        $this->assertNull($this->read()['period_end']);
    }

    public function test_a_group_smaller_than_k_shows_nothing_rather_than_a_smaller_group(): void
    {
        $this->schoolARecords();
        app(SchoolBenchmarks::class)->choose($this->principal, true);
        foreach (range(3, 5) as $school) {
            $this->other($school, 9000, 8000);
        }
        $this->travelTo(CarbonImmutable::parse('2026-02-10 11:00:00', 'Africa/Johannesburg'));
        $this->assertSame(0, $this->snapshot()['released']);
        $this->assertSame(0, BenchmarkRelease::query()->count());
        $data = $this->read();
        $this->assertSame(5000, $data['metrics']['attendance']['own_bp']);
        $this->assertNull($data['metrics']['attendance']['comparison']);
        $this->assertNull($data['metrics']['reach']['comparison']);
        $this->actingAs($this->principal)->get('/leadership-dashboard')->assertOk()->assertSee('Not enough comparable schools yet')->assertDontSee('90%');
        $this->assertSame([], app(PlatformBenchmarks::class)->overview($this->hq('owner'))['groups']);

        // A configured k below three is never used.
        config(['benchmarking.min_group_size' => 1]);
        $this->assertSame(3, BenchmarkSnapshots::minimumGroupSize());
        config(['benchmarking.min_group_size' => 5]);

        // A school in another group never fills this one.
        $this->other(6, 9000, 8000, null, 'independent:junior:0');
        $this->snapshot();
        $this->assertNull($this->release('attendance'));

        // A group that was published and then shrinks below k shows nothing, not its older figures.
        $this->other(7, 9100, 8100);
        $this->snapshot();
        $this->assertNotNull($this->release('attendance'));
        $this->leave(7);
        $this->travelTo(CarbonImmutable::parse('2026-02-17 10:00:00', 'Africa/Johannesburg'));
        $this->snapshot('2026-02-15');
        $this->assertNull($this->release('attendance', '2026-02-15'));
        $this->assertNull($this->read()['metrics']['attendance']['comparison']);
    }

    public function test_no_other_school_can_be_identified_from_what_is_published(): void
    {
        $this->schoolARecords();
        app(SchoolBenchmarks::class)->choose($this->principal, true);
        foreach ([3 => 8137, 4 => 8263, 5 => 9391, 6 => 7749] as $school => $attendance) {
            $this->other($school, $attendance, 7000);
        }
        $this->travelTo(CarbonImmutable::parse('2026-02-10 11:00:00', 'Africa/Johannesburg'));
        $this->snapshot();
        // The platform table has no school column and only the three aggregates.
        $this->assertFalse(Schema::hasColumn('benchmark_releases', 'school_id'));
        $this->assertSame(['id', 'period_end', 'group_key', 'metric', 'status', 'basis_period_end', 'lower_quartile_bp', 'median_bp', 'upper_quartile_bp', 'computed_at', 'created_at', 'updated_at'],
            Schema::getColumnListing('benchmark_releases'));
        // Sorted 5000, 7749, 8137, 8263, 9391: published to whole percentage points only.
        $release = $this->release('attendance');
        $this->assertSame([7700, 8100, 8300], [$release->lower_quartile_bp, $release->median_bp, $release->upper_quartile_bp]);
        $data = $this->read();
        $this->assertSame(['lower_quartile_bp', 'median_bp', 'upper_quartile_bp', 'as_of', 'held'], array_keys($data['metrics']['attendance']['comparison']));
        $json = json_encode($data);
        foreach (['8137', '8263', '9391', '7749', '"school_id"', '"members"', '"count"'] as $hidden) {
            $this->assertStringNotContainsString($hidden, $json);
        }
        $page = $this->actingAs($this->principal)->get('/leadership-dashboard')->assertOk();
        foreach (['81.37', '82.63', '93.91', '77.49', '94%', self::MARKER, self::SCHOOL_B_MARKER] as $hidden) {
            $page->assertDontSee($hidden);
        }
        // School A's own rows are its own; the others' rows are never readable in School A.
        $this->assertSame([1], BenchmarkSchoolValue::query()->distinct()->pluck('school_id')->map(fn ($id) => (int) $id)->all());
        $hq = $this->actingAsHq($this->hq('operations'))->get('/saas/benchmarks')->assertOk();
        foreach (['81.37', '93%', '94%', '50%', self::MARKER, 'Jacaranda', self::SCHOOL_B_MARKER] as $hidden) {
            $hq->assertDontSee($hidden);
        }
    }

    public function test_the_differencing_guard_keeps_the_last_figures_until_enough_schools_have_changed(): void
    {
        app(SchoolBenchmarks::class)->choose($this->principal, true);
        // School A's figures are fixed here, so only membership changes between weeks.
        $this->figures[1] = ['group_key' => self::GROUP, 'values' => ['attendance' => 5000, 'results_movement' => null, 'reach' => 7000]];
        foreach ([3 => 8000, 4 => 8200, 5 => 8400, 6 => 8600] as $school => $attendance) {
            $this->other($school, $attendance, 7000);
        }
        $this->travelTo(CarbonImmutable::parse('2026-02-10 11:00:00', 'Africa/Johannesburg'));
        $this->snapshot();
        // Sorted 5000, 8000, 8200, 8400, 8600.
        $first = [8000, 8200, 8400];
        $released = fn (string $period) => (fn (BenchmarkRelease $r) => [$r->status, $r->basis_period_end->toDateString(), [$r->lower_quartile_bp, $r->median_bp, $r->upper_quartile_bp]])($this->release('attendance', $period));
        $this->assertSame(['released', '2026-02-08', $first], $released('2026-02-08'));

        // One school joins with an extreme figure: one change is fewer than three, so the figures are kept.
        $this->other(7, 10000, 7000);
        $this->travelTo(CarbonImmutable::parse('2026-02-17 10:00:00', 'Africa/Johannesburg'));
        $this->snapshot('2026-02-15');
        $this->assertSame(['held', '2026-02-08', $first], $released('2026-02-15'));
        $this->assertSame(['lower_quartile_bp' => 8000, 'median_bp' => 8200, 'upper_quartile_bp' => 8400, 'as_of' => '2026-02-08', 'held' => true], $this->read()['metrics']['attendance']['comparison']);
        $this->actingAs($this->principal)->get('/leadership-dashboard')->assertOk()->assertSee('Kept from the week ending 2026-02-08');
        $this->assertFalse(app(CurrentSchool::class)->run(7, fn () => BenchmarkSchoolValue::query()->where('metric', 'attendance')->whereDate('period_end', '2026-02-15')->sole()->contributed));

        // A school leaves as well: two changes against the published week, still kept.
        $this->leave(3);
        $this->travelTo(CarbonImmutable::parse('2026-02-24 10:00:00', 'Africa/Johannesburg'));
        $this->snapshot('2026-02-22');
        $this->assertSame(['held', '2026-02-08', $first], $released('2026-02-22'));

        // Two more join: four changes since the published week, so new figures from every current school.
        $this->other(8, 6000, 7000);
        $this->other(9, 9000, 7000);
        $this->travelTo(CarbonImmutable::parse('2026-03-03 10:00:00', 'Africa/Johannesburg'));
        $this->snapshot('2026-03-01');
        // Sorted 5000, 6000, 8200, 8400, 8600, 9000, 10000: positions 1.5, 3 and 4.5.
        $this->assertSame(['released', '2026-03-01', [7100, 8400, 8800]], $released('2026-03-01'));

        // The same schools with new figures publish at once: no single school changed the group.
        $this->figures[4]['values']['attendance'] = 8300;
        $this->travelTo(CarbonImmutable::parse('2026-03-10 10:00:00', 'Africa/Johannesburg'));
        $this->snapshot('2026-03-08');
        $this->assertSame('released', $released('2026-03-08')[0]);
        // A minimum change below two is never used.
        config(['benchmarking.min_membership_change' => 1]);
        $this->assertSame(2, BenchmarkSnapshots::minimumChange());
    }

    public function test_each_school_decides_and_reads_only_its_own_and_another_schools_context_is_refused(): void
    {
        $this->schoolARecords();
        app(CurrentSchool::class)->run(2, fn () => BenchmarkParticipation::query()->create(['opted_in' => true, 'changed_at' => now()->subDay()]));
        app(CurrentSchool::class)->run(2, fn () => SchoolAccount::query()->create(['stage' => 'live', 'school_type' => 'public']));
        $this->assertSame(['participating' => false, 'rejoin_on' => null, 'minimum_group_size' => 5, 'period_end' => null, 'group' => null, 'metrics' => []], $this->read());
        app(SchoolBenchmarks::class)->choose($this->principal, true);
        foreach (range(3, 6) as $school) {
            $this->other($school, 9000, 8000);
        }
        $this->travelTo(CarbonImmutable::parse('2026-02-10 11:00:00', 'Africa/Johannesburg'));
        $this->snapshot();
        // School B is worked out from its own records only, in its own school.
        $b = app(CurrentSchool::class)->run(2, fn () => BenchmarkSchoolValue::query()->whereDate('period_end', '2026-02-08')->get()->keyBy('metric'));
        $this->assertCount(3, $b);
        $this->assertNotSame(5000, $b['attendance']->value_bp);
        $this->assertSame([1], BenchmarkSchoolValue::query()->distinct()->pluck('school_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame(5000, $this->read()['metrics']['attendance']['own_bp']);

        // School A leaving changes only School A.
        app(SchoolBenchmarks::class)->choose($this->principal, false);
        $this->assertTrue(app(CurrentSchool::class)->run(2, fn () => BenchmarkParticipation::query()->sole()->opted_in));
        $this->assertFalse(BenchmarkParticipation::query()->sole()->opted_in);

        // School A's principal has no authority in School B.
        app(CurrentSchool::class)->run(2, function (): void {
            try {
                app(SchoolBenchmarks::class)->read($this->principal);
                $this->fail('No School B appointment');
            } catch (HttpException|ModelNotFoundException $e) {
                $this->assertTrue($e instanceof ModelNotFoundException || in_array($e->getStatusCode(), [403, 404], true));
            }
            $this->assertTrue(BenchmarkParticipation::query()->sole()->opted_in);
        });
        $this->refused(fn () => app(CurrentSchool::class)->acrossSchools(fn () => app(SchoolBenchmarks::class)->read($this->principal)));
    }
}
