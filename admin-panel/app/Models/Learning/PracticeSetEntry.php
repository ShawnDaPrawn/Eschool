<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class PracticeSetEntry extends Model
{
    use BelongsToSchool;

    protected $table = 'practice_set_entries';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }
}
