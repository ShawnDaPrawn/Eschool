<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property bool $enabled
 * @property int $min_days_overdue
 * @property int $cadence_days
 * @property int $local_hour
 * @property int $revision
 */
class FeeReminderPolicy extends Model
{
    use BelongsToSchool;

    protected $table = 'fee_reminder_policies';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'min_days_overdue' => 'integer', 'cadence_days' => 'integer', 'local_hour' => 'integer', 'revision' => 'integer', 'created_at' => UtcInstant::class, 'updated_at' => UtcInstant::class];
    }
}
