<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class MarkRelease extends Model
{
    use BelongsToSchool;

    protected $table = 'mark_releases';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['version' => 'integer', 'created_at' => 'datetime'];
    }

    public function entries()
    {
        return $this->hasMany(MarkReleaseEntry::class);
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Released academic evidence is immutable.'));
        static::deleting(fn () => throw new \LogicException('Released academic evidence is immutable.'));
    }
}
