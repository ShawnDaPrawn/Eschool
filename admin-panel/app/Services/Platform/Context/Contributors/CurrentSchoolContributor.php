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
        return [
            'current_school' => new CurrentSchoolResource($scope->profile, $this->sessionYear($scope)),
        ];
    }

    private function sessionYear(ContextScope $scope): ?SessionYear
    {
        if ($scope->profile->sessionYearId === null) {
            return null;
        }
        // One calendar read for /me/context. Keep the year's tenant/soft-delete
        // scopes, and put term filters in the join so an empty year still exists.
        $fields = ['id', 'school_id', 'session_year_id', 'name', 'start_date', 'end_date', 'grade_from', 'grade_to', 'marks_lock_on', 'reports_on'];
        $rows = SessionYear::query()->whereKey($scope->profile->sessionYearId)
            ->where('session_years.school_id', $scope->school->id)
            ->leftJoin('terms as context_terms', function (JoinClause $join): void {
                $join->on('context_terms.session_year_id', '=', 'session_years.id')
                    ->on('context_terms.school_id', '=', 'session_years.school_id')
                    ->whereNull('context_terms.deleted_at');
            })
            ->orderBy('context_terms.start_date')->orderBy('context_terms.id')
            ->get(['session_years.*', ...array_map(fn (string $field): string => 'context_terms.'.$field.' as context_term_'.$field, $fields)]);
        $year = $rows->first();
        if ($year === null) {
            return null;
        }
        $terms = Term::hydrate($rows->whereNotNull('context_term_id')->map(function (SessionYear $row) use ($fields): array {
            $attributes = [];
            foreach ($fields as $field) {
                $attributes[$field] = $row->getAttribute('context_term_'.$field);
            }

            return $attributes;
        })->all());
        foreach ($fields as $field) {
            $year->offsetUnset('context_term_'.$field);
        }

        return $year->setRelation('terms', $terms);
    }
}
