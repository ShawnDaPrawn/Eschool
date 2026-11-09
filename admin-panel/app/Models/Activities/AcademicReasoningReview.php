<?php

declare(strict_types=1);

namespace App\Models\Activities;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * AI feedback on the reasoning in one speech or written argument, asked for
 * by an adjudicator. Advice to the adjudicator only: it is never a result or
 * a score. The text is stored redacted and cleared once the feedback is
 * written; what the adjudicator chooses to share with a learner is their own.
 */
class AcademicReasoningReview extends Model
{
    use BelongsToSchool;

    protected $table = 'academic_reasoning_reviews';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['input', 'feedback', 'shared_feedback'];

    protected function casts(): array
    {
        return ['shared_fixture_id' => 'integer', 'requested_by' => 'integer', 'input' => 'encrypted', 'feedback' => 'encrypted:array', 'shared_learner_id' => 'integer',
            'shared_feedback' => 'encrypted', 'shared_by' => 'integer', 'shared_at' => 'datetime', 'quarantine_purged_at' => 'datetime'];
    }
}
