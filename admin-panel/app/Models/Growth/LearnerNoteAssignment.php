<?php

declare(strict_types=1);

namespace App\Models\Growth;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class LearnerNoteAssignment extends Model
{
    use BelongsToSchool;

    protected $table = 'learner_note_assignments';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date', 'revision' => 'integer'];
    }
}
