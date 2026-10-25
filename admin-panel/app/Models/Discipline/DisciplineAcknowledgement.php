<?php

declare(strict_types=1);

namespace App\Models\Discipline;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class DisciplineAcknowledgement extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['acknowledged_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Retain immutable discipline history.'));
        static::deleting(fn () => throw new \LogicException('Retain immutable discipline history.'));
    }
}
