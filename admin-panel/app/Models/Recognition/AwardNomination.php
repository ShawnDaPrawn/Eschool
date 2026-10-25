<?php

declare(strict_types=1);

namespace App\Models\Recognition;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AwardNomination extends Model
{
    use BelongsToSchool;

    protected $table = 'award_nominations';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['reason' => 'encrypted', 'review_reason' => 'encrypted', 'definition_revision' => 'integer', 'revision' => 'integer'];
    }

    protected $hidden = ['reason', 'review_reason'];
}
