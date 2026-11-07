<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** The FSRS review a learning path scheduled for a topic the learner passed (Action Plan 3.9). */
class LearningPathReview extends Model
{
    use BelongsToSchool;

    protected $table = 'learning_path_reviews';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['state' => 'array', 'reviewed_on' => 'immutable_date', 'due_on' => 'immutable_date', 'learning_path_id' => 'integer', 'topic_id' => 'integer'];
    }

    protected static function booted(): void
    {
        static::deleting(fn () => abort(409, 'Learning path reviews are kept.'));
    }
}
