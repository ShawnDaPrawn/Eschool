<?php

declare(strict_types=1);

namespace App\Models\Growth;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** An open gap: the family-visible level on a topic was assessed below secure. Action Plan 3.13, engineering/specs/learning/practice-motivation.md. */
class MasteryTopicGap extends Model
{
    use BelongsToSchool;

    protected $table = 'mastery_topic_gaps';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['learner_id' => 'integer', 'topic_id' => 'integer', 'opened_at' => 'immutable_datetime'];
    }
}
