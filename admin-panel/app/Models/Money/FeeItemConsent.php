<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/** @property array $snapshot
 * @property bool $active
 * @property int $revision
 * @property int|null $learner_id
 * @property int $actor_id
 * @property int $target_id
 * @property CarbonImmutable $created_at
 */
class FeeItemConsent extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'active' => 'boolean', 'revision' => 'integer', 'learner_id' => 'integer', 'actor_id' => 'integer', 'target_id' => 'integer', 'created_at' => UtcInstant::class];
    }
}
