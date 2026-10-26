<?php

declare(strict_types=1);

namespace App\Models\Cards;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    use BelongsToSchool, HasUuids;

    protected $table = 'cards';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['issued_at' => UtcInstant::class, 'frozen_at' => UtcInstant::class, 'revision' => 'integer'];
    }
}
