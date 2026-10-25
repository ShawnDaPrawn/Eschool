<?php

declare(strict_types=1);

namespace App\Models\Growth;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class MasterySettings extends Model
{
    use BelongsToSchool;

    protected $table = 'mastery_settings';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['family_marks_enabled' => 'boolean', 'revision' => 'integer'];
    }
}
