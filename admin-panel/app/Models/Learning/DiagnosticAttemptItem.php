<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * One planned question of a diagnostic attempt: a published library practice
 * question, the school's mirror topic it counts towards, and the learner's
 * answer once given. A skipped question was not needed: its topic was clear.
 */
class DiagnosticAttemptItem extends Model
{
    use BelongsToSchool;

    public const WAITING = 'waiting';

    public const ANSWERED = 'answered';

    public const SKIPPED = 'skipped';

    protected $table = 'diagnostic_attempt_items';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['diagnostic_attempt_id' => 'integer', 'position' => 'integer', 'library_item_id' => 'integer', 'library_item_version_id' => 'integer',
            'library_topic_id' => 'integer', 'topic_id' => 'integer', 'answer' => 'encrypted', 'correct' => 'boolean', 'answered_at' => UtcInstant::class];
    }
}
