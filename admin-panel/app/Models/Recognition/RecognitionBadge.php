<?php

declare(strict_types=1);

namespace App\Models\Recognition;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** Idempotency ledger: a badge is earned once per learner, kind, week (and topic), even after a correction. */
class RecognitionBadge extends Model
{
    use BelongsToSchool;

    protected $table = 'recognition_badges';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['week_start' => 'immutable_date', 'topic_id' => 'integer', 'award_presentation_id' => 'integer'];
    }
}
