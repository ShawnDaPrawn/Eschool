<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class MarkSnapshot extends Model
{
    use BelongsToSchool;

    protected $table = 'mark_snapshots';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['version' => 'integer', 'percentage_bp' => 'integer', 'running_bp' => 'integer', 'coverage_bp' => 'integer', 'rating_level' => 'integer', 'complete' => 'boolean', 'inputs' => 'array', 'result' => 'array', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Released academic evidence is immutable.'));
        static::deleting(fn () => throw new \LogicException('Released academic evidence is immutable.'));
    }
}
