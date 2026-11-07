<?php

declare(strict_types=1);

namespace App\Models\Reading;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** A learner's answer to one comprehension question, marked on the server. School-owned. */
class ReadingAnswer extends Model
{
    use BelongsToSchool;

    protected $table = 'reading_answers';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['learner_id' => 'integer', 'reading_question_id' => 'integer', 'choice' => 'integer', 'correct' => 'boolean'];
    }
}
