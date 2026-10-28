<?php

declare(strict_types=1);

namespace App\Models\Commerce;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $order_id
 * @property int $actor_id
 * @property int $ledger_entry_id
 * @property string $private_reason
 * @property CarbonImmutable $created_at
 */
class VendorOrderAction extends Model
{
    use BelongsToSchool;

    protected $table = 'vendor_order_actions';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['private_reason'];

    protected function casts(): array
    {
        return ['order_id' => 'integer', 'actor_id' => 'integer', 'ledger_entry_id' => 'integer', 'private_reason' => 'encrypted', 'created_at' => UtcInstant::class];
    }
}
