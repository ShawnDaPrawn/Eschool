<?php

declare(strict_types=1);

namespace App\Models\Discipline;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class DisciplineRecordVersion extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['payload'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['payload' => 'encrypted:array', 'revision' => 'integer', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Retain immutable discipline history.'));
        static::deleting(fn () => throw new \LogicException('Retain immutable discipline history.'));
    }
}
