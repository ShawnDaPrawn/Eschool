<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class PracticeOperation extends Model
{
    use BelongsToSchool;

    protected $table = 'practice_operations';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['result' => 'encrypted:array'];
    }
}
