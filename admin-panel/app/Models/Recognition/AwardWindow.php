<?php

declare(strict_types=1);

namespace App\Models\Recognition;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AwardWindow extends Model
{
    use BelongsToSchool;

    protected $table = 'award_windows';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['opens_on' => 'date', 'closes_on' => 'date', 'revision' => 'integer', 'activity_group_id' => 'integer'];
    }
}
