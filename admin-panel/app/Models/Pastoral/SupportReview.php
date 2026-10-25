<?php

declare(strict_types=1);

namespace App\Models\Pastoral;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class SupportReview extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['outcome'];

    protected function casts(): array
    {
        return ['reviewed_on' => 'date', 'next_review_on' => 'date', 'outcome' => 'encrypted'];
    }
}
