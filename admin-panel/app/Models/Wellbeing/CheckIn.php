<?php

declare(strict_types=1);

namespace App\Models\Wellbeing;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** A short private wellbeing check-in; the answer is for authorised pastoral staff only. */
class CheckIn extends Model
{
    use BelongsToSchool;

    protected $table = 'wellbeing_check_ins';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['answer'];

    protected function casts(): array
    {
        return ['due_on' => 'date', 'answer' => 'encrypted:array', 'answered_at' => 'datetime', 'revision' => 'integer'];
    }
}
