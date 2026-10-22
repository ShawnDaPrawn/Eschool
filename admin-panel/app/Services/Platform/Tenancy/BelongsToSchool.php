<?php

declare(strict_types=1);

namespace App\Services\Platform\Tenancy;

use App\Models\Platform\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A school-owned model (the Build Bible, section 5.4): its queries are
 * limited to the current school (SchoolScope) and a new row gets the current
 * school's id, also through bulk inserts (SchoolBuilder). Reading across
 * schools is explicit: Model::acrossSchools() or CurrentSchool::acrossSchools().
 *
 * The table needs a school_id column and a place in SchoolOwnedTables.
 *
 * @mixin Model
 */
trait BelongsToSchool
{
    public static function bootBelongsToSchool(): void
    {
        static::addGlobalScope(new SchoolScope);

        static::creating(function (Model $model) {
            $currentSchool = app(CurrentSchool::class);
            if ($model->getAttribute('school_id') === null && ! ($currentSchool->isMigrating() && $currentSchool->idOrNull() === null)) {
                $model->setAttribute('school_id', $currentSchool->id());
            }
        });
    }

    /**
     * school_id stays out of arrays and JSON: v1 answers keep their exact
     * shape, and a client never needs it (the school is the request's).
     */
    public function initializeBelongsToSchool(): void
    {
        $this->hidden[] = 'school_id';
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** Every school's rows: for platform code only (say why where you call it). */
    public static function acrossSchools(): Builder
    {
        return static::withoutGlobalScope(SchoolScope::class);
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    public function newEloquentBuilder($query): SchoolBuilder
    {
        return new SchoolBuilder($query);
    }
}
