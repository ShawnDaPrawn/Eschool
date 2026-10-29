<?php

declare(strict_types=1);

namespace App\Models\Commerce;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $event_id
 * @property int $product_id
 * @property int $capacity
 * @property int $revision
 * @property int $changed_by
 */
class TicketType extends Model
{
    use BelongsToSchool;

    protected $table = 'ticket_types';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = true;

    protected $hidden = [];

    protected function casts(): array
    {
        return ['event_id' => 'integer', 'product_id' => 'integer', 'capacity' => 'integer', 'revision' => 'integer', 'changed_by' => 'integer', 'created_at' => UtcInstant::class];
    }
}
