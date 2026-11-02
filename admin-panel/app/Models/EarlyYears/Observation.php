<?php

declare(strict_types=1);

namespace App\Models\EarlyYears;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A note or photograph of one learner, tagged to a development area; together
 * they are the learner's portfolio (Action Plan 4.3). A photograph is a
 * private gallery photo whose family decides its tag.
 *
 * @property int $id
 * @property int $learner_id
 * @property int $class_section_id
 * @property int $session_year_id
 * @property int $development_area_id
 * @property Carbon $observed_on
 * @property string|null $note
 * @property int|null $gallery_photo_id
 * @property int $recorded_by
 * @property string $operation_id
 * @property string $request_hash
 * @property Carbon|null $withdrawn_at
 * @property int|null $withdrawn_by
 * @property int $revision
 * @property Carbon|null $created_at
 */
class Observation extends Model
{
    use BelongsToSchool;

    protected $table = 'early_years_observations';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['note', 'request_hash'];

    protected function casts(): array
    {
        return ['observed_on' => 'date', 'note' => 'encrypted', 'withdrawn_at' => 'datetime', 'revision' => 'integer'];
    }
}
