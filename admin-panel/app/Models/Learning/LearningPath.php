<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * A learner's guided learning path in one learning group (Action Plan 3.9):
 * the topic it is working on and the step it is at. Only the server moves it
 * (LearningPathService). engineering/specs/learning/learning-path.md.
 */
class LearningPath extends Model
{
    use BelongsToSchool;

    public const STEPS = ['gap', 'teach', 'practise', 'check', 'review'];

    protected $table = 'learning_paths';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['learner_id' => 'integer', 'user_id' => 'integer', 'teaching_group_id' => 'integer', 'subject_id' => 'integer', 'session_year_id' => 'integer',
            'topic_id' => 'integer', 'rest_topic_id' => 'integer', 'cycle' => 'integer', 'failed_checks' => 'integer', 'revision' => 'integer', 'rest_until' => 'immutable_date'];
    }

    protected static function booted(): void
    {
        static::saving(fn (self $path) => abort_unless(in_array($path->step, self::STEPS, true), 409, 'Unknown learning path step.'));
        static::deleting(fn () => abort(409, 'Learning path history is kept.'));
    }
}
