<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class PracticeReview extends Model
{
    use BelongsToSchool;

    protected $table = 'practice_reviews';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['answer' => 'encrypted', 'correct' => 'boolean', 'schedule' => 'array', 'reviewed_on' => 'immutable_date'];
    }
}
