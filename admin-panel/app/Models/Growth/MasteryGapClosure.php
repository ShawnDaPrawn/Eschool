<?php

declare(strict_types=1);

namespace App\Models\Growth;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** Ledger: a gap closed, celebrated at most once per learner, topic and term. Action Plan 3.13, engineering/specs/learning/practice-motivation.md. */
class MasteryGapClosure extends Model
{
    use BelongsToSchool;

    protected $table = 'mastery_gap_closures';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['learner_id' => 'integer', 'topic_id' => 'integer', 'term_id' => 'integer', 'session_year_id' => 'integer', 'closed_at' => 'immutable_datetime', 'notification_id' => 'integer'];
    }
}
