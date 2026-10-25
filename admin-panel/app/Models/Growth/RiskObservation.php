<?php

declare(strict_types=1);

namespace App\Models\Growth;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class RiskObservation extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['evidence'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['as_of' => 'date', 'window_start' => 'date', 'score' => 'integer', 'signal_count' => 'integer', 'evidence' => 'encrypted:array', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Retain immutable early-warning history.'));
        static::deleting(fn () => throw new \LogicException('Retain immutable early-warning history.'));
    }
}
