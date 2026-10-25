<?php

declare(strict_types=1);

namespace App\Models\Recognition;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class HousePointLimit extends Model
{
    use BelongsToSchool;

    protected $table = 'house_point_limits';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['on_date' => 'date', 'spent' => 'integer'];
    }
}
