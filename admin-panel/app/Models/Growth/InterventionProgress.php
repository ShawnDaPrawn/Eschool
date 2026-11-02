<?php

declare(strict_types=1);

namespace App\Models\Growth;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** A dated, append-only progress entry against an intervention plan's steps. */
class InterventionProgress extends Model
{
    use BelongsToSchool;

    protected $table = 'intervention_progress_entries';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['note'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['recorded_on' => 'date', 'step' => 'integer', 'note' => 'encrypted', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Retain intervention progress history.'));
        static::deleting(fn () => throw new \LogicException('Retain intervention progress history.'));
    }
}
