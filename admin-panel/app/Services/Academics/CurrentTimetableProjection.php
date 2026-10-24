<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/** A stale projection cannot show a cancelled, expired or alternate-week lesson. */
class CurrentTimetableProjection implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (app(CurrentSchool::class)->isMigrating()) {
            return;
        }
        $monday = CarbonImmutable::parse(app(AcademicAuthority::class)->today())->startOfWeek();
        $builder->where(function ($q) use ($monday) {
            $q->whereNull('timetables.timetable_lesson_id')->orWhereExists(function ($sub) use ($monday) {
                $sub->selectRaw('1')->from('timetable_lessons as projected_lesson')->whereColumn('projected_lesson.id', 'timetables.timetable_lesson_id')
                    ->whereColumn('projected_lesson.school_id', 'timetables.school_id')->where('projected_lesson.active', true)->whereNull('projected_lesson.deleted_at')
                    ->whereExists(function ($group) {
                        $group->selectRaw('1')->from('teaching_groups as current_lesson_group')->whereColumn('current_lesson_group.id', 'projected_lesson.teaching_group_id')
                            ->whereColumn('current_lesson_group.school_id', 'projected_lesson.school_id')->where('current_lesson_group.active', true)->whereNull('current_lesson_group.deleted_at');
                    })
                    ->where(function ($dates) use ($monday) {
                        for ($day = 1; $day <= 7; $day++) {
                            $date = $monday->addDays($day - 1);
                            $dates->orWhere(fn ($on) => $on->where('projected_lesson.weekday', $day)->whereDate('projected_lesson.starts_on', '<=', $date->toDateString())
                                ->whereDate('projected_lesson.ends_on', '>=', $date->toDateString())
                                ->where(fn ($specific) => $specific->whereNull('projected_lesson.occurs_on')->orWhereDate('projected_lesson.occurs_on', $date->toDateString()))
                                ->whereIn('projected_lesson.week_pattern', ['all', $date->isoWeek() % 2 === 0 ? 'even' : 'odd'])
                                ->whereExists(fn ($grant) => $grant->selectRaw('1')->from('teaching_allocations as current_lesson_grant')
                                    ->whereColumn('current_lesson_grant.teaching_group_id', 'projected_lesson.teaching_group_id')->whereColumn('current_lesson_grant.teacher_id', 'projected_lesson.teacher_id')
                                    ->whereColumn('current_lesson_grant.school_id', 'projected_lesson.school_id')->whereNull('current_lesson_grant.deleted_at')
                                    ->whereDate('current_lesson_grant.starts_on', '<=', $date->toDateString())->whereDate('current_lesson_grant.ends_on', '>=', $date->toDateString())));
                        }
                    });
            });
        });
    }
}
