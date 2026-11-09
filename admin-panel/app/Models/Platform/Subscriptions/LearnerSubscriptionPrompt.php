<?php

declare(strict_types=1);

namespace App\Models\Platform\Subscriptions;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** When a guardian was last told about learning plans for a child, and why (the notice never says why). */
class LearnerSubscriptionPrompt extends Model
{
    use BelongsToSchool;

    public const UPDATED_AT = null;

    protected $table = 'learner_subscription_prompts';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['guardian_user_id' => 'integer', 'learner_id' => 'integer', 'notification_id' => 'integer', 'sent_on' => 'date', 'created_at' => 'datetime'];
    }
}
