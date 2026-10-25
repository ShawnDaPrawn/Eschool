<?php

declare(strict_types=1);

namespace App\Models\Ai;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AiFlagReview extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['private_reason', 'intent_hash'];

    protected function casts(): array
    {
        return ['private_reason' => 'encrypted', 'revision' => 'integer'];
    }
}
