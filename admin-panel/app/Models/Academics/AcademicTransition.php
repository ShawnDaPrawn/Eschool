<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AcademicTransition extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['reason', 'payload', 'result', 'request_hash'];

    protected function casts(): array
    {
        return ['effective_on' => 'date', 'reason' => 'encrypted', 'payload' => 'encrypted:array', 'result' => 'encrypted:array'];
    }

    protected static function booted(): void
    {
        static::updating(function (AcademicTransition $row) {
            abort_unless($row->getRawOriginal('result') === null && array_diff(array_keys($row->getDirty()), ['result', 'updated_at']) === [], 409, __('Dated changes are immutable. Record a new correction.'));
        });
        static::deleting(fn () => abort(409, __('Dated change history must be retained.')));
    }
}
