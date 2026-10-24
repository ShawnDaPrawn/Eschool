<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/** Expired canonical grants cannot survive as stale legacy projection rows. */
class DatedAcademicProjection implements Scope
{
    public function __construct(private readonly string $kind) {}

    public function apply(Builder $builder, Model $model): void
    {
        if (app(CurrentSchool::class)->isMigrating()) {
            return;
        }
        $table = $model->getTable();
        $today = app(AcademicAuthority::class)->today();
        $source = $this->kind === 'subject' ? 'teaching_allocation_id' : 'homeroom_allocation_id';
        $builder->where(function ($q) use ($table, $today, $source) {
            $q->whereNull($table.'.'.$source)->orWhereExists(function ($sub) use ($table, $today, $source) {
                $canonical = $this->kind === 'subject' ? 'teaching_allocations' : 'homeroom_allocations';
                $sub->selectRaw('1')->from($canonical.' as dated_grant')
                    ->whereColumn('dated_grant.id', $table.'.'.$source)->whereColumn('dated_grant.school_id', $table.'.school_id')
                    ->whereColumn('dated_grant.teacher_id', $table.'.'.($this->kind === 'subject' ? 'teacher_id' : 'class_teacher_id'))
                    ->whereNull('dated_grant.deleted_at')->whereDate('dated_grant.starts_on', '<=', $today)->whereDate('dated_grant.ends_on', '>=', $today);
                if ($this->kind === 'subject') {
                    $sub->join('teaching_groups as dated_group', 'dated_group.id', '=', 'dated_grant.teaching_group_id')
                        ->whereColumn('dated_group.school_id', $table.'.school_id')->whereColumn('dated_group.subject_id', $table.'.subject_id')
                        ->whereColumn('dated_group.session_year_id', $table.'.session_year_id')->where('dated_group.active', true)->whereNull('dated_group.deleted_at');
                } else {
                    $sub->whereColumn('dated_grant.class_section_id', $table.'.class_section_id')->whereColumn('dated_grant.session_year_id', $table.'.session_year_id');
                }
            });
        });
    }
}
