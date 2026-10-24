<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class TeachingGroupQueries
{
    public function groups(User $actor, array $filters): LengthAwarePaginator
    {
        $query = app(AcademicAuthority::class)->groups($actor);
        foreach (['subject_id', 'grade_level_id', 'session_year_id'] as $key) {
            if (isset($filters[$key])) {
                $query->where($key, $filters[$key]);
            }
        }

        return $query->with('subject', 'gradeLevel', 'year')->orderBy('name')->paginate((int) ($filters['per_page'] ?? 30), ['*'], $filters['page_name'] ?? 'page');
    }

    public function rows(User $actor, string $kind, array $filters, ?int $group = null): LengthAwarePaginator
    {
        if ($group !== null) {
            app(AcademicAuthority::class)->group($actor, $group);
        } else {
            app(AcademicAuthority::class)->manage($actor);
        }
        $class = TeachingGroups::ROWS[$kind];
        $query = $class::query();
        if ($group !== null) {
            $query->where('teaching_group_id', $group);
        } elseif (isset($filters['session_year_id'])) {
            $query->where('session_year_id', $filters['session_year_id']);
        }
        if (isset($filters['on'])) {
            $query->whereDate('starts_on', '<=', $filters['on'])->whereDate('ends_on', '>=', $filters['on']);
        }
        $relation = match ($kind) {
            'memberships' => 'learner.user', 'teachers', 'homerooms' => 'teacher.user', 'leadership' => 'user'
        };

        return $query->with($relation)->orderByDesc('starts_on')->orderByDesc('id')->paginate((int) ($filters['per_page'] ?? 30), ['*'], $filters['page_name'] ?? 'page');
    }

    public function rowData(Model $row, string $kind): array
    {
        $person = match ($kind) {
            'memberships' => $row->learner?->user, 'teachers', 'homerooms' => $row->teacher?->user, 'leadership' => $row->user
        };

        return app(TeachingGroups::class)->datedData($row) + ['display_name' => $person === null ? '' : trim($person->first_name.' '.$person->last_name)];
    }
}
