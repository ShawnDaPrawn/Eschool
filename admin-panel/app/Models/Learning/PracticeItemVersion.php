<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class PracticeItemVersion extends Model
{
    use BelongsToSchool;

    protected $table = 'practice_item_versions';

    protected $guarded = ['id', 'school_id'];

    protected static function booted(): void
    {
        static::updating(fn () => abort(409, 'Approved practice versions are immutable.'));
        static::deleting(fn () => abort(409, 'Retain approved practice version history.'));
    }

    protected function casts(): array
    {
        return ['definition' => 'encrypted:array', 'revision' => 'integer', 'estimated_seconds' => 'integer'];
    }
}
