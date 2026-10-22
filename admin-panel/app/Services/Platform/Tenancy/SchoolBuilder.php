<?php

declare(strict_types=1);

namespace App\Services\Platform\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The query builder of school-owned models: bulk inserts and upserts
 * (Model::insert(), ::upsert() and friends skip model events) get the
 * current school's id on every row that has none, like a model saved one
 * at a time.
 *
 * @template TModel of Model
 *
 * @extends Builder<TModel>
 */
class SchoolBuilder extends Builder
{
    public function insert(array $values): bool
    {
        return $this->toBase()->insert($this->withSchool($values));
    }

    public function insertGetId(array $values, $sequence = null): int
    {
        return (int) $this->toBase()->insertGetId($this->withSchool($values), $sequence);
    }

    public function insertOrIgnore(array $values): int
    {
        return $this->toBase()->insertOrIgnore($this->withSchool($values));
    }

    public function upsert(array $values, $uniqueBy, $update = null)
    {
        return parent::upsert($this->withSchool($values), $uniqueBy, $update);
    }

    public function updateOrInsert(array $attributes, array|callable $values = []): bool
    {
        if (! (clone $this)->where($attributes)->exists()) {
            $values = is_callable($values) ? $values(false) : $values;

            return $this->insert(array_merge($attributes, $values));
        }
        $values = is_callable($values) ? $values(true) : $values;

        return $values === [] || (bool) (clone $this)->where($attributes)->limit(1)->update($values);
    }

    /**
     * @param  array<mixed>  $values
     * @return array<mixed>
     */
    protected function withSchool(array $values): array
    {
        return SchoolTable::withSchool($values);
    }
}
