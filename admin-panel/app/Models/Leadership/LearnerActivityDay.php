<?php

declare(strict_types=1);

namespace App\Models\Leadership;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** A daily school-attributed activity receipt; no request paths or content. */
class LearnerActivityDay extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['on_date' => 'date'];
    }
}
