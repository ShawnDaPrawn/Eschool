<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class MarkReleaseEntry extends Model
{
    use BelongsToSchool;

    protected $table = 'mark_release_entries';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function snapshot()
    {
        return $this->belongsTo(MarkSnapshot::class, 'mark_snapshot_id');
    }

    public function release()
    {
        return $this->belongsTo(MarkRelease::class, 'mark_release_id');
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Released academic evidence is immutable.'));
        static::deleting(fn () => throw new \LogicException('Released academic evidence is immutable.'));
    }
}
