<?php

declare(strict_types=1);

namespace App\Models\Reading;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** A learner's place in one book, and the time they have read it. School-owned. */
class ReadingProgress extends Model
{
    use BelongsToSchool;

    protected $table = 'reading_progress';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['learner_id' => 'integer', 'reading_book_id' => 'integer', 'page' => 'integer', 'furthest_page' => 'integer', 'seconds_read' => 'integer', 'finished_at' => 'immutable_datetime', 'revision' => 'integer'];
    }
}
