<?php

declare(strict_types=1);

namespace App\Models\Activities;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ActivitySession extends Model
{
    use BelongsToSchool;

    protected $table = 'activity_sessions';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['on_date' => 'date', 'revision' => 'integer'];
    }
}
