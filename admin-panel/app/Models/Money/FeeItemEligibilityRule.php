<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** @property array $applies_to
 * @property int $revision
 * @property int|null $target_id
 * @property int|null $activity_group_id
 * @property int|null $teaching_group_id
 * @property int|null $subject_id
 */
class FeeItemEligibilityRule extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['applies_to' => 'array', 'revision' => 'integer', 'target_id' => 'integer', 'activity_group_id' => 'integer', 'teaching_group_id' => 'integer', 'subject_id' => 'integer', 'created_at' => UtcInstant::class, 'updated_at' => UtcInstant::class];
    }
}
