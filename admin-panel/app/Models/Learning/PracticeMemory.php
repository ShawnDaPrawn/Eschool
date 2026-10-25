<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class PracticeMemory extends Model
{
    use BelongsToSchool;

    protected $table = 'practice_memories';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['state' => 'array', 'reviewed_on' => 'immutable_date', 'due_on' => 'immutable_date'];
    }
}
