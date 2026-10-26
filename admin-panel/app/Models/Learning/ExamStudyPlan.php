<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ExamStudyPlan extends Model
{
    use BelongsToSchool;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['school_id'];

    protected $hidden = ['definition'];

    protected function casts(): array
    {
        return ['definition' => 'encrypted:array'];
    }
}
