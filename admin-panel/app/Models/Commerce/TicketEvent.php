<?php

declare(strict_types=1);

namespace App\Models\Commerce;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $revision
 * @property int $changed_by
 * @property CarbonImmutable $sales_from
 * @property CarbonImmutable $sales_until
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 */
class TicketEvent extends Model
{
    use BelongsToSchool;

    protected $table = 'ticket_events';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = true;

    protected $hidden = [];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'changed_by' => 'integer', 'created_at' => UtcInstant::class, 'sales_from' => UtcInstant::class, 'sales_until' => UtcInstant::class, 'starts_at' => UtcInstant::class, 'ends_at' => UtcInstant::class];
    }
}
