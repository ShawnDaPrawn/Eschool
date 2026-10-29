<?php

declare(strict_types=1);

namespace App\Models\Commerce;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $vendor_id
 * @property int $wallet_account_id
 * @property int $wallet_owner_id
 * @property int|null $learner_id
 * @property int $actor_id
 * @property int|null $ledger_entry_id
 * @property int $amount_cents
 * @property int $net_cents
 * @property int $vat_cents
 * @property array $seller_snapshot
 * @property string $private_qr_token
 * @property string $purchase_channel
 * @property string|null $wallet_intent_id
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $sealed_at
 */
class VendorOrder extends Model
{
    use BelongsToSchool;

    protected $table = 'vendor_orders';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['private_qr_token', 'qr_hash', 'seller_snapshot'];

    protected function casts(): array
    {
        return ['vendor_id' => 'integer', 'wallet_account_id' => 'integer', 'wallet_owner_id' => 'integer', 'learner_id' => 'integer', 'actor_id' => 'integer', 'ledger_entry_id' => 'integer', 'amount_cents' => 'integer', 'net_cents' => 'integer', 'vat_cents' => 'integer', 'seller_snapshot' => 'array', 'private_qr_token' => 'encrypted', 'created_at' => UtcInstant::class, 'sealed_at' => UtcInstant::class];
    }
}
