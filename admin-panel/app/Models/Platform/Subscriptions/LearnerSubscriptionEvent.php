<?php

declare(strict_types=1);

namespace App\Models\Platform\Subscriptions;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/** The subscription's own audit trail: who changed it, how, to what. Immutable; names no one. */
class LearnerSubscriptionEvent extends Model
{
    use BelongsToSchool;

    public const UPDATED_AT = null;

    protected $table = 'learner_subscription_events';

    protected $guarded = ['id', 'school_id'];

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('The subscription audit trail is never edited.');
        });
        static::deleting(function (): void {
            throw new LogicException('The subscription audit trail is never deleted.');
        });
    }

    protected function casts(): array
    {
        return ['subscription_id' => 'integer', 'actor_user_id' => 'integer', 'amount_cents' => 'integer', 'detail' => 'array', 'created_at' => 'datetime'];
    }
}
