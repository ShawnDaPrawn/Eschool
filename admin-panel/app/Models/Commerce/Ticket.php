<?php

declare(strict_types=1);

namespace App\Models\Commerce;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $type_id
 * @property int $order_line_id
 * @property int $ordinal
 * @property int $original_holder_id
 * @property int|null $named_learner_id
 * @property array $snapshot
 * @property CarbonImmutable $created_at
 * @property string $private_qr_token
 * @property string $private_number
 */
class Ticket extends Model
{
    use BelongsToSchool;

    protected $table = 'tickets';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['private_qr_token', 'private_number', 'qr_hash', 'number_hash'];

    protected function casts(): array
    {
        return ['type_id' => 'integer', 'order_line_id' => 'integer', 'ordinal' => 'integer', 'original_holder_id' => 'integer', 'named_learner_id' => 'integer', 'snapshot' => 'array', 'created_at' => UtcInstant::class, 'private_qr_token' => 'encrypted', 'private_number' => 'encrypted'];
    }
}
