<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ExamAnswerReview extends Model
{
    use BelongsToSchool;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['school_id'];

    protected $hidden = ['reason'];

    protected function casts(): array
    {
        return ['reason' => 'encrypted'];
    }
}
