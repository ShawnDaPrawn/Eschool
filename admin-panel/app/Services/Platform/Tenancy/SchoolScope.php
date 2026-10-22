<?php

declare(strict_types=1);

namespace App\Services\Platform\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Limits a school-owned model's queries to the current school (see
 * CurrentSchool for what happens when there is none).
 */
class SchoolScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $schoolId = app(CurrentSchool::class)->scopeId();
        if ($schoolId !== null) {
            $builder->where($model->qualifyColumn('school_id'), $schoolId);
        }
    }
}
