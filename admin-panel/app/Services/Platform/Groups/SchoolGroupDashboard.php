<?php

declare(strict_types=1);

namespace App\Services\Platform\Groups;

use App\Models\Platform\School;
use App\Models\Platform\SchoolGroup;
use App\Models\Platform\SchoolGroupMember;
use App\Models\SessionYear;
use App\Services\Leadership\LeadershipDashboard;
use App\Services\Platform\Saas\PlatformSchoolHealth;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * A school group's figures across its current schools (school-groups.md):
 * per school and for the group, counts and shares only, computed now from
 * each school's own rows inside that school.
 *
 *  - Only schools whose membership is active now (agreed by the school, not
 *    ended) and that are active on the platform are read. A school that left
 *    is not read from the moment it left; a school never in the group is
 *    never read.
 *  - The measures are those HQ school health and the leadership dashboard
 *    already compute: learners, weekly active families, guardian reach,
 *    learners active in the app this week and recorded attendance.
 *  - Small numbers are suppressed (SmallNumbers), per school and in the
 *    group's totals.
 *  - Nothing names or lists a learner, guardian or staff member, and there
 *    is no way through here to any record.
 *
 * The caller authorises: a group administrator (SchoolGroupAccess) or HQ
 * staff with groups.view.
 */
class SchoolGroupDashboard
{
    public function __construct(private readonly CurrentSchool $currentSchool) {}

    /**
     * @return array{group: array{id: int, name: string}, k: int, computed_at: string, attendance_days: int, schools: list<array<string, mixed>>, totals: array<string, mixed>}
     */
    public function read(int $groupId): array
    {
        $group = SchoolGroup::query()->find($groupId);
        abort_if($group === null, 404);
        $suppress = SmallNumbers::fromConfig();
        $raw = [];
        foreach ($this->currentSchools($groupId) as $school) {
            $raw[] = ['id' => (int) $school->id, 'name' => (string) $school->name] + $this->currentSchool->run((int) $school->id, fn (): array => $this->figures((int) $school->id));
        }
        $rows = array_map(fn (array $r): array => $this->present($r, $suppress), $raw);

        return [
            'group' => ['id' => (int) $group->id, 'name' => (string) $group->name],
            'k' => $suppress->k(),
            'computed_at' => CarbonImmutable::now('Africa/Johannesburg')->format('Y-m-d H:i'),
            'attendance_days' => $this->days(),
            'schools' => $rows,
            'totals' => $this->totals($raw, $rows, $suppress),
        ];
    }

    /** How a figure reads on a page: a count, a share with what it is of, or why it is hidden. */
    public static function display(array $cell, int $k): string
    {
        if (array_key_exists('available', $cell) && ! $cell['available'] && ! $cell['hidden']) {
            return __('No attendance recorded');
        }
        if ($cell['hidden']) {
            return array_key_exists('percent_bp', $cell) ? __('Hidden: fewer than :k people', ['k' => $k]) : __('Fewer than :k', ['k' => $k]);
        }
        if (! array_key_exists('percent_bp', $cell)) {
            return (string) $cell['value'];
        }
        $percent = $cell['percent_bp'] === null ? '–' : intdiv($cell['percent_bp'], 100).'.'.intdiv($cell['percent_bp'] % 100, 10).'%';

        return array_key_exists('count', $cell) && $cell['count'] !== null && $cell['of'] !== null
            ? __(':percent (:count of :of)', ['percent' => $percent, 'count' => $cell['count'], 'of' => $cell['of']]) : $percent;
    }

    /**
     * The schools the group may see now: an active membership and an active
     * school. Read across schools on purpose (the group's own list), with
     * the membership's status checked in the same query.
     *
     * @return list<object{id: int, name: string}>
     */
    public function currentSchools(int $groupId): array
    {
        return $this->currentSchool->acrossSchools(fn (): array => DB::table('school_group_members as m')->join('schools as s', 's.id', '=', 'm.school_id')
            ->where('m.school_group_id', $groupId)->where('m.status', SchoolGroupMember::ACTIVE)->whereNotNull('m.open_key')
            ->where('s.status', School::ACTIVE)->orderBy('s.name')->orderBy('s.id')->get(['s.id', 's.name'])->all());
    }

    private function days(): int
    {
        return max(1, min(90, (int) config('school_groups.attendance_days', 28)));
    }

    /** One school's raw figures, in that school. @return array<string, int|bool> */
    private function figures(int $schoolId): array
    {
        $family = app(PlatformSchoolHealth::class)->familyFigures($schoolId);
        $attendance = ['learners' => 0, 'recorded_days' => 0, 'present_half_days' => 0, 'available' => false];
        $year = SessionYear::query()->find((int) schoolSetting('session_year'));
        if ($year !== null && $year->start_date && $year->end_date) {
            $timezone = (string) (DB::table('schools')->where('id', $schoolId)->value('timezone') ?: 'Africa/Johannesburg');
            $today = CarbonImmutable::now($timezone)->toDateString();
            $end = min($today, CarbonImmutable::parse($year->end_date)->toDateString());
            $start = max(CarbonImmutable::parse($end)->subDays($this->days() - 1)->toDateString(), CarbonImmutable::parse($year->start_date)->toDateString());
            if ($start <= $end) {
                $attendance = app(LeadershipDashboard::class)->attendanceTotals((int) $year->id, $start, $end) + ['available' => true];
            }
        }

        return [
            'learners' => (int) $family['reach']['learners'], 'reached' => (int) $family['reach']['reached'],
            'guardians' => (int) $family['families']['guardians'], 'active_guardians' => (int) $family['families']['active'],
            'active_learners' => (int) $family['learners_active'],
            'attendance_learners' => (int) $attendance['learners'], 'recorded_days' => (int) $attendance['recorded_days'],
            'present_half_days' => (int) $attendance['present_half_days'], 'attendance_available' => (bool) $attendance['available'],
        ];
    }

    /** @param  array<string, mixed>  $r */
    private function present(array $r, SmallNumbers $s, bool $force = false): array
    {
        $out = [
            'learners' => $s->count((int) $r['learners'], $force),
            'weekly_active_families' => $s->share((int) $r['active_guardians'], (int) $r['guardians'], $force),
            'guardian_reach' => $s->share((int) $r['reached'], (int) $r['learners'], $force),
            'active_learners' => $s->share(min((int) $r['active_learners'], (int) $r['learners']), (int) $r['learners'], $force),
            'attendance' => $s->rate((int) $r['present_half_days'], 2 * (int) $r['recorded_days'], (int) $r['attendance_learners'], $force)
                + ['available' => (bool) $r['attendance_available'] && (int) $r['recorded_days'] > 0],
        ];

        return isset($r['id']) ? ['id' => (int) $r['id'], 'name' => (string) $r['name']] + $out : $out;
    }

    /**
     * The group's totals: each measure summed over its schools, then
     * suppressed like a school's figure, and hidden outright when any
     * school's figure for that measure is hidden.
     *
     * @param  list<array<string, mixed>>  $raw
     * @param  list<array<string, mixed>>  $rows
     */
    private function totals(array $raw, array $rows, SmallNumbers $s): array
    {
        $sum = fn (string $key): int => array_sum(array_map(fn (array $r): int => (int) $r[$key], $raw));
        $total = [];
        foreach (['learners', 'reached', 'guardians', 'active_guardians', 'attendance_learners', 'recorded_days', 'present_half_days'] as $key) {
            $total[$key] = $sum($key);
        }
        $total['active_learners'] = array_sum(array_map(fn (array $r): int => min((int) $r['active_learners'], (int) $r['learners']), $raw));
        $total['attendance_available'] = in_array(true, array_column($raw, 'attendance_available'), true);
        $presented = $this->present($total, $s);
        foreach (array_keys($presented) as $measure) {
            if (in_array(true, array_map(fn (array $row): bool => (bool) $row[$measure]['hidden'], $rows), true)) {
                $presented[$measure] = $this->present($total, $s, true)[$measure];
            }
        }

        return ['schools' => count($rows)] + $presented;
    }
}
