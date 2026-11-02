<?php

declare(strict_types=1);

namespace App\Models\EarlyYears;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One learner's early years daily diary for one day (Action Plan 4.3).
 *
 * @property int $id
 * @property int $learner_id
 * @property int $class_section_id
 * @property int $session_year_id
 * @property Carbon $entry_date
 * @property string|null $meals
 * @property string|null $rest
 * @property string|null $mood
 * @property string|null $toileting
 * @property string|null $note
 * @property int $recorded_by
 * @property int $updated_by
 * @property int $revision
 */
class DiaryEntry extends Model
{
    use BelongsToSchool;

    protected $table = 'early_years_diary_entries';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['note'];

    protected function casts(): array
    {
        return ['entry_date' => 'date', 'note' => 'encrypted', 'revision' => 'integer'];
    }
}
