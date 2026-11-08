<?php

declare(strict_types=1);

namespace App\Models\Platform\Subscriptions;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * A learner the school named for one of a sponsor's places. Removed, never deleted.
 *
 * @property int $id
 * @property int $subscription_id
 * @property int $learner_id
 * @property Carbon $added_at
 * @property Carbon|null $removed_at
 */
class LearnerSubscriptionMember extends Model
{
    use BelongsToSchool;

    public $timestamps = false;

    protected $table = 'learner_subscription_members';

    protected $guarded = ['id', 'school_id', 'active_learner'];

    protected static function booted(): void
    {
        static::deleting(function (): void {
            throw new LogicException('Sponsored places are removed, never deleted.');
        });
    }

    protected function casts(): array
    {
        return ['subscription_id' => 'integer', 'learner_id' => 'integer', 'added_by' => 'integer', 'removed_by' => 'integer', 'added_at' => 'datetime', 'removed_at' => 'datetime'];
    }
}
