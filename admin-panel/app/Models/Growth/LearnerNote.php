<?php

declare(strict_types=1);

namespace App\Models\Growth;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class LearnerNote extends Model
{
    use BelongsToSchool;

    protected $table = 'learner_notes';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['body'];

    protected $attributes = ['revision' => 1];

    protected function casts(): array
    {
        return ['body' => 'encrypted', 'revision' => 'integer', 'archived_at' => 'datetime'];
    }
}
