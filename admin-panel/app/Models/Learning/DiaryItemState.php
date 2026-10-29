<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** The learner's own layer on a school item: done, a reminder and an encrypted private note. */
class DiaryItemState extends Model
{
    use BelongsToSchool;

    protected $guarded = ['school_id'];

    protected $hidden = ['note'];

    protected function casts(): array
    {
        return ['source_id' => 'integer', 'remind_at' => 'datetime', 'done_at' => 'datetime', 'user_id' => 'integer'];
    }
}
