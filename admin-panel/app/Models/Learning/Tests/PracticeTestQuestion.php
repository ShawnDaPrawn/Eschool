<?php

declare(strict_types=1);

namespace App\Models\Learning\Tests;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** A question in a sitting: the immutable version it came from and the learner's encrypted answer. */
class PracticeTestQuestion extends Model
{
    use BelongsToSchool;

    protected $table = 'practice_test_questions';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['answer'];

    protected function casts(): array
    {
        return ['answer' => 'encrypted', 'answered_at' => UtcInstant::class, 'correct' => 'boolean', 'position' => 'integer',
            'practice_item_version_id' => 'integer', 'library_item_version_id' => 'integer', 'topic_id' => 'integer'];
    }
}
