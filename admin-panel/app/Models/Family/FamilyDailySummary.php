<?php

declare(strict_types=1);

namespace App\Models\Family;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One guardian's summary for one local day, across their schools (Action
 * Plan 5.11). It holds no school content: each school's part is a
 * school-owned FamilyDailySummaryPart, read only inside that school.
 *
 * @property int $user_id
 * @property CarbonImmutable $summary_date
 * @property string $state composing, sent or empty
 * @property ?int $notice_school_id
 * @property ?int $notification_id
 */
class FamilyDailySummary extends Model
{
    public const COMPOSING = 'composing';

    public const SENT = 'sent';

    public const EMPTY = 'empty';

    protected $table = 'family_daily_summaries';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'summary_date' => 'immutable_date', 'notice_school_id' => 'integer', 'notification_id' => 'integer',
            'sent_at' => 'immutable_datetime', 'expires_at' => 'immutable_datetime'];
    }

    /** Parts of the current school only (the parts are school-owned). */
    public function parts(): HasMany
    {
        return $this->hasMany(FamilyDailySummaryPart::class, 'family_daily_summary_id');
    }
}
