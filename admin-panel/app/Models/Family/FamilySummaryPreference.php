<?php

declare(strict_types=1);

namespace App\Models\Family;

use Illuminate\Database\Eloquent\Model;

/**
 * A guardian's own daily family summary choice (Action Plan 5.11): one row
 * per person, for every school, like their quiet hours. Global, not school-owned.
 *
 * @property int $user_id
 * @property bool $enabled
 * @property string $send_at
 * @property string $timezone
 * @property int $revision
 */
class FamilySummaryPreference extends Model
{
    protected $table = 'family_summary_preferences';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'enabled' => 'boolean', 'revision' => 'integer'];
    }
}
