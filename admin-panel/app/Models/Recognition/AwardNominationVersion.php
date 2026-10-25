<?php

declare(strict_types=1);

namespace App\Models\Recognition;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AwardNominationVersion extends Model
{
    use BelongsToSchool;

    protected $table = 'award_nomination_versions';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['snapshot' => 'encrypted:array', 'revision' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => abort(409, 'Recognition history is immutable.'));
        static::deleting(fn () => abort(409, 'Recognition history must be retained.'));
    }
}
