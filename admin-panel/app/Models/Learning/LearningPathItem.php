<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * One question a learning path gave a learner, for practice or for the
 * server-graded check (Action Plan 3.9). The approved answer stays on the
 * server; the learner's own answer is encrypted, and the first answer is final.
 */
class LearningPathItem extends Model
{
    use BelongsToSchool;

    public const PURPOSES = ['practice', 'check'];

    protected $table = 'learning_path_items';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['answer'];

    protected function casts(): array
    {
        return ['answer' => 'encrypted', 'correct' => 'boolean', 'schedule' => 'array', 'answered_at' => 'datetime', 'learning_path_id' => 'integer',
            'learner_id' => 'integer', 'topic_id' => 'integer', 'cycle' => 'integer', 'position' => 'integer',
            'practice_item_version_id' => 'integer', 'library_item_version_id' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(function (self $row) {
            abort_unless($row->getRawOriginal('answered_at') === null
                && array_diff(array_keys($row->getDirty()), ['answer_operation_id', 'answer', 'correct', 'schedule', 'answered_at', 'updated_at']) === [], 409, 'An answered path question cannot change.');
        });
        static::deleting(fn () => abort(409, 'Learning path answers are kept.'));
    }
}
