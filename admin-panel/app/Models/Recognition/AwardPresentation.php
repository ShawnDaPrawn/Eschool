<?php

declare(strict_types=1);

namespace App\Models\Recognition;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AwardPresentation extends Model
{
    use BelongsToSchool;

    protected $table = 'award_presentations';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['presented_on' => 'date', 'void_reason' => 'encrypted', 'voided_at' => 'datetime', 'revision' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(function ($row): void {
            abort_if(array_diff(array_keys($row->getDirty()), ['state', 'revision', 'dedupe_key', 'voided_by', 'void_reason', 'voided_at', 'updated_at']) !== [], 409, 'Permanent award details are immutable.');
        });
        static::deleting(fn () => abort(409, 'Permanent awards must be retained.'));
    }

    protected $hidden = ['void_reason'];
}
