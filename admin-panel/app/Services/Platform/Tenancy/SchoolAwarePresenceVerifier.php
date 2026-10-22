<?php

declare(strict_types=1);

namespace App\Services\Platform\Tenancy;

use Illuminate\Validation\DatabasePresenceVerifier;

/**
 * The exists and unique validation rules, limited to the current school on
 * school-owned tables: "unique:session_years,name" means unique within the
 * school (another school may have a year with the same name), and
 * "exists:class_sections,id" refuses another school's homeroom. Without a
 * school (console, across schools) the rules see every row, as before.
 */
class SchoolAwarePresenceVerifier extends DatabasePresenceVerifier
{
    protected function table($table)
    {
        $query = parent::table($table);

        $name = str_contains($table, '.') ? substr($table, strrpos($table, '.') + 1) : $table;
        if (SchoolOwnedTables::isSchoolOwned($name)) {
            $schoolId = app(CurrentSchool::class)->scopeId();
            if ($schoolId !== null) {
                $query->where($name.'.school_id', $schoolId);
            }
        }

        return $query;
    }
}
