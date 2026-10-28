<?php

declare(strict_types=1);

namespace App\Models\Commerce;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $vendor_id
 * @property int $actor_id
 * @property int $order_id
 * @property string $intent_id
 * @property string $operation_id
 * @property CarbonImmutable $created_at
 */
class WalletCounterPayment extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'wallet_counter_payments';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['private_quote', 'private_qr_token', 'qr_hash'];

    protected function casts(): array
    {
        return ['vendor_id' => 'integer', 'actor_id' => 'integer', 'order_id' => 'integer', 'created_at' => UtcInstant::class];
    }
}
