<?php

declare(strict_types=1);

namespace App\Models\Cards;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class CardQrUse extends Model
{
    use BelongsToSchool;

    protected $table = 'card_qr_uses';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['nonce_hash'];

    protected function casts(): array
    {
        return ['expires_at' => UtcInstant::class, 'used_at' => UtcInstant::class];
    }
}
