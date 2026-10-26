<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class PastPaper extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $attributes = ['revision' => 1, 'active' => true];

    protected $hidden = ['withdrawal_reason'];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'active' => 'boolean', 'withdrawal_reason' => 'encrypted', 'withdrawn_at' => UtcInstant::class];
    }
}
