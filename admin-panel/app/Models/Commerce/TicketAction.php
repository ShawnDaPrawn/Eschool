<?php

declare(strict_types=1);

namespace App\Models\Commerce;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ticket_id
 * @property int $revision
 * @property int $actor_id
 * @property int $from_holder_id
 * @property int $to_holder_id
 * @property int|null $redemption_marker
 * @property CarbonImmutable $created_at
 * @property string|null $private_qr_token
 * @property string|null $private_number
 */
class TicketAction extends Model
{
    use BelongsToSchool;

    protected $table = 'ticket_actions';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['private_qr_token', 'private_number', 'qr_hash', 'number_hash'];

    protected function casts(): array
    {
        return ['ticket_id' => 'integer', 'revision' => 'integer', 'actor_id' => 'integer', 'from_holder_id' => 'integer', 'to_holder_id' => 'integer', 'redemption_marker' => 'integer', 'created_at' => UtcInstant::class, 'private_qr_token' => 'encrypted', 'private_number' => 'encrypted'];
    }
}
