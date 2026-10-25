<?php

declare(strict_types=1);

namespace App\Models\Ai;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AiQuarantineSnapshot extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['private_content', 'content_hash'];

    protected function casts(): array
    {
        return ['private_content' => 'encrypted:array', 'expires_at' => 'immutable_datetime'];
    }
}
