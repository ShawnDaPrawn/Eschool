<?php

declare(strict_types=1);

namespace App\Services\Platform\Tenancy;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * DB::table() for a school-owned table, for the code that works without
 * models (the year copies in app/Actions/Transfers, bulk back-fills, test
 * fixtures): reads, updates and deletes are limited to the current school
 * like BelongsToSchool, and inserts get the current school's id on every
 * row that has none.
 *
 *     SchoolTable::query('timetables')->where('session_year_id', $id)->get();
 */
final class SchoolTable
{
    public static function query(string $table): Builder
    {
        $connection = DB::connection();
        $query = new class($connection, $connection->getQueryGrammar(), $connection->getPostProcessor()) extends Builder
        {
            public function insert(array $values)
            {
                return parent::insert(SchoolTable::withSchool($values));
            }

            public function insertGetId(array $values, $sequence = null)
            {
                return parent::insertGetId(SchoolTable::withSchool($values), $sequence);
            }

            public function insertOrIgnore(array $values)
            {
                return parent::insertOrIgnore(SchoolTable::withSchool($values));
            }

            public function upsert(array $values, $uniqueBy, $update = null)
            {
                return parent::upsert(SchoolTable::withSchool($values), $uniqueBy, $update);
            }

            public function updateOrInsert(array $attributes, array|callable $values = [])
            {
                if (! (clone $this)->where($attributes)->exists()) {
                    $values = is_callable($values) ? $values(false) : $values;

                    return $this->insert(array_merge($attributes, $values));
                }
                $values = is_callable($values) ? $values(true) : $values;

                return $values === [] || (bool) (clone $this)->where($attributes)->limit(1)->update($values);
            }
        };
        $query->from($table);

        $schoolId = app(CurrentSchool::class)->scopeId();
        if ($schoolId !== null) {
            $query->where($table.'.school_id', $schoolId);
        }

        return $query;
    }

    /**
     * @param  array<mixed>  $values  one row, or a list of rows
     * @return array<mixed>
     */
    public static function withSchool(array $values): array
    {
        if ($values === []) {
            return $values;
        }
        $currentSchool = app(CurrentSchool::class);
        if ($currentSchool->isMigrating() && $currentSchool->idOrNull() === null) {
            // An old migration, on a table that has no school_id yet.
            return $values;
        }
        $fill = fn (array $row): array => isset($row['school_id']) ? $row : ['school_id' => $currentSchool->id()] + $row;

        return array_is_list($values) && is_array(reset($values)) ? array_map($fill, $values) : $fill($values);
    }

    private function __construct() {}
}
