<?php

declare(strict_types=1);

namespace App\Models\Commerce;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $actor_id
 * @property string $intent_id
 * @property CarbonImmutable $created_at
 */
class WalletPaymentClosure extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'wallet_payment_closures';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['private_quote', 'private_qr_token', 'qr_hash'];

    protected function casts(): array
    {
        return ['actor_id' => 'integer', 'created_at' => UtcInstant::class];
    }
}
