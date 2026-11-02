<?php

declare(strict_types=1);

namespace App\Services\Platform\Context\Contributors;

use App\Http\Resources\Platform\CurrentSchoolResource;
use App\Models\SessionYear;
use App\Models\Term;
use App\Services\Platform\Context\ContextContributor;
use App\Services\Platform\Context\ContextScope;
use Illuminate\Database\Query\JoinClause;

/**
 * current_school: the school the request works in, with its name, crest,
 * colours, timezone and current year with its terms (stream T1; openapi.yaml
 * CurrentSchool). Stream T3 adds the school's branding inside it.
 */
class CurrentSchoolContributor implements ContextContributor
{
    public function contribute(ContextScope $scope): array
    {
        $sessionYear = $this->calendar($scope);

        return [
            'current_school' => new CurrentSchoolResource($scope->profile, $sessionYear),
        ];
    }

    /** Read the year and its ordered terms together, retaining both school boundaries. */
    private function calendar(ContextScope $scope): ?SessionYear
    {
        if ($scope->profile->sessionYearId === null) {
            return null;
        }
        $fields = ['id', 'name', 'session_year_id', 'start_date', 'end_date', 'grade_from', 'grade_to', 'marks_lock_on', 'reports_on'];
        $rows = SessionYear::query()->whereKey($scope->profile->sessionYearId)
            ->where('session_years.school_id', $scope->school->id)
            ->leftJoin('terms as context_terms', function (JoinClause $join) use ($scope): void {
                $join->on('context_terms.session_year_id', '=', 'session_years.id')
                    ->where('context_terms.school_id', $scope->school->id)->whereNull('context_terms.deleted_at');
            })
            ->select('session_years.*')
            ->addSelect(array_map(fn ($field) => 'context_terms.'.$field.' as calendar_term_'.$field, $fields))
            ->orderBy('context_terms.start_date')->orderBy('context_terms.id')->get();
        $year = $rows->first();
        if ($year === null) {
            return null;
        }
        $terms = $rows->filter(fn ($row) => $row->getAttribute('calendar_term_id') !== null)
            ->map(function ($row) use ($fields): array {
                return array_combine($fields, array_map(fn ($field) => $row->getAttribute('calendar_term_'.$field), $fields));
            })->values()->all();
        $year->setRelation('terms', Term::hydrate($terms));

        return $year;
    }
}
