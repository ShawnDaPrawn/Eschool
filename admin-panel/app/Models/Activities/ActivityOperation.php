<?php

declare(strict_types=1);

namespace App\Models\Activities;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ActivityOperation extends Model
{
    use BelongsToSchool;

    protected $table = 'activity_operations';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['receipt' => 'encrypted:array'];
    }
}
