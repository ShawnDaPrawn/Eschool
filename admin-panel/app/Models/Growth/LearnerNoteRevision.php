<?php

declare(strict_types=1);

namespace App\Models\Growth;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class LearnerNoteRevision extends Model
{
    use BelongsToSchool;

    protected $table = 'learner_note_revisions';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected $hidden = ['body'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Note revision history is immutable.'));
        static::deleting(fn () => throw new \LogicException('Note revision history is immutable.'));
    }

    protected function casts(): array
    {
        return ['body' => 'encrypted', 'revision' => 'integer', 'archived' => 'boolean', 'created_at' => 'datetime'];
    }
}
