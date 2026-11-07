<?php

declare(strict_types=1);

namespace App\Models\Learning\Tests;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * What a practice test is made of (Action Plan 3.17): its kind, group, topics
 * and their shares, question count, time limit and window. A quick quiz is a
 * learner's own; a cycle test is set by a teacher for a group.
 */
class PracticeTestBlueprint extends Model
{
    use BelongsToSchool;

    protected $table = 'practice_test_blueprints';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['topic_ids' => 'array', 'paper_version_ids' => 'array', 'topic_shares' => 'array', 'question_count' => 'integer',
            'time_limit_seconds' => 'integer', 'opens_at' => UtcInstant::class, 'closes_at' => UtcInstant::class, 'active' => 'boolean',
            'revision' => 'integer', 'teaching_group_id' => 'integer', 'subject_id' => 'integer', 'grade_level_id' => 'integer',
            'session_year_id' => 'integer', 'created_by' => 'integer', 'learner_id' => 'integer'];
    }
}
