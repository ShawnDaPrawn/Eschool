<?php

declare(strict_types=1);

namespace App\Models\Recognition;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AwardDefinition extends Model
{
    use BelongsToSchool;

    protected $table = 'award_definitions';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'revision' => 'integer'];
    }
}
