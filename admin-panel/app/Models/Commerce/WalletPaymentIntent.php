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
 * @property int $wallet_account_id
 * @property int $amount_cents
 * @property int $net_cents
 * @property int $vat_cents
 * @property string|null $card_id
 * @property string|null $device_id
 * @property int|null $card_revision
 * @property int|null $portrait_revision
 * @property string|null $portrait_digest
 * @property string $fingerprint
 * @property string $currency
 * @property string $kind
 * @property array $private_quote
 * @property string $private_qr_token
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable $created_at
 */
class WalletPaymentIntent extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'wallet_payment_intents';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['private_quote', 'private_qr_token', 'qr_hash'];

    protected function casts(): array
    {
        return ['vendor_id' => 'integer', 'actor_id' => 'integer', 'wallet_account_id' => 'integer', 'amount_cents' => 'integer', 'net_cents' => 'integer', 'vat_cents' => 'integer', 'card_revision' => 'integer', 'portrait_revision' => 'integer', 'sun_counter' => 'integer', 'private_quote' => 'encrypted:array', 'private_qr_token' => 'encrypted', 'expires_at' => UtcInstant::class, 'created_at' => UtcInstant::class];
    }
}
