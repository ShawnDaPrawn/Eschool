<?php

declare(strict_types=1);

namespace App\Models\Cards;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CardClassroomSession extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'card_classroom_sessions';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['secret_hash'];

    protected function casts(): array
    {
        return ['expires_at' => UtcInstant::class, 'revoked_at' => UtcInstant::class];
    }
}
