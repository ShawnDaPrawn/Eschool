<?php

declare(strict_types=1);

namespace App\Models\Assistance;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class SupportThread extends Model
{
    use BelongsToSchool;

    protected $table = 'school_support_threads';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['school_id'];

    protected $attributes = ['state' => 'open', 'revision' => 1];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'closed_at' => UtcInstant::class, 'expires_at' => UtcInstant::class, 'purged_at' => UtcInstant::class];
    }
}
