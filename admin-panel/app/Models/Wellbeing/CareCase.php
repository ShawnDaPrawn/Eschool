<?php

declare(strict_types=1);

namespace App\Models\Wellbeing;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class CareCase extends Model
{
    use BelongsToSchool;

    protected $table = 'support_cases';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['opened_at' => 'immutable_datetime', 'revision' => 'integer'];
    }
}
