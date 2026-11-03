<?php

declare(strict_types=1);

namespace App\Models\EarlyYears;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * An early concern raised from an observation (Action Plan 4.3): only the
 * link to the intervention it was referred as. The words of the concern live
 * encrypted in that intervention, never here.
 *
 * @property int $id
 * @property int $observation_id
 * @property int $learner_id
 * @property int $intervention_id
 * @property int $raised_by
 * @property Carbon|null $created_at
 */
class EarlyConcern extends Model
{
    use BelongsToSchool;

    protected $table = 'early_years_concerns';

    protected $guarded = ['id', 'school_id'];
}
