<?php

declare(strict_types=1);

namespace App\Models\Growth;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class MasteryScope extends Model
{
    use BelongsToSchool;

    protected $table = 'mastery_scopes';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['computed_at' => 'datetime'];
    }
}
