<?php

declare(strict_types=1);

namespace App\Models\Cards;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class CardEvent extends Model
{
    use BelongsToSchool;

    protected $table = 'card_events';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['reason'];

    protected function casts(): array
    {
        return ['occurred_at' => UtcInstant::class, 'reason' => 'encrypted', 'revision' => 'integer'];
    }
}
