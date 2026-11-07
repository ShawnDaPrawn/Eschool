<?php

declare(strict_types=1);

namespace App\Models\Recognition;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class RecognitionBadgeSettings extends Model
{
    use BelongsToSchool;

    protected $table = 'recognition_badge_settings';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['practice_days_enabled' => 'boolean', 'practice_days_required' => 'integer', 'comeback_enabled' => 'boolean', 'comeback_below_percent' => 'integer', 'streak_enabled' => 'boolean', 'switched_on_by' => 'integer', 'revision' => 'integer'];
    }
}
