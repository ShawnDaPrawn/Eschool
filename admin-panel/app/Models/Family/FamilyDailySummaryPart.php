<?php

declare(strict_types=1);

namespace App\Models\Family;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * One school's part of a guardian's daily family summary (Action Plan 5.11):
 * the structured facts it was written from and its wording, encrypted, kept
 * only until the summary expires.
 *
 * @property int $family_daily_summary_id
 * @property int $user_id
 * @property string $operation_id
 * @property array $facts
 * @property string $facts_hash
 * @property list<int> $learner_ids
 * @property string $text
 * @property string $wording ai or template
 * @property ?string $fallback_reason
 */
class FamilyDailySummaryPart extends Model
{
    use BelongsToSchool;

    protected $table = 'family_daily_summary_parts';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['facts', 'text', 'learner_ids'];

    protected function casts(): array
    {
        return ['family_daily_summary_id' => 'integer', 'user_id' => 'integer', 'facts' => 'encrypted:array', 'learner_ids' => 'encrypted:array',
            'text' => 'encrypted', 'expires_at' => 'immutable_datetime'];
    }
}
