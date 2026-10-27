<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class FeeAccountingSetting extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['mappings' => 'array', 'revision' => 'integer', 'created_at' => UtcInstant::class, 'updated_at' => UtcInstant::class];
    }
}
