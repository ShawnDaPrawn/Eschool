<?php

declare(strict_types=1);

namespace App\Models\Ai;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AiFlag extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = [];

    protected $attributes = ['review_revision' => 1, 'review_status' => 'open'];

    protected function casts(): array
    {
        return ['review_revision' => 'integer'];
    }
}
