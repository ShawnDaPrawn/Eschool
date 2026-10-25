<?php

declare(strict_types=1);

namespace App\Models\Cards;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CardKey extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'card_keys';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['secret_key'];

    protected function casts(): array
    {
        return ['starts_at' => UtcInstant::class, 'sign_until' => UtcInstant::class, 'verify_until' => UtcInstant::class, 'revoked_at' => UtcInstant::class, 'secret_key' => 'encrypted'];
    }
}
