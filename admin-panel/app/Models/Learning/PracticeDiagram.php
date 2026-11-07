<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** A school's diagram picture for labelling questions (Action Plan 3.11), encrypted in the school's private storage. */
class PracticeDiagram extends Model
{
    use BelongsToSchool;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'practice_diagrams';

    protected $guarded = ['school_id'];

    protected $hidden = ['path'];

    protected static function booted(): void
    {
        static::updating(fn () => abort(409, 'Approved diagram pictures are immutable.'));
    }

    protected function casts(): array
    {
        return ['bytes' => 'integer', 'width' => 'integer', 'height' => 'integer'];
    }
}
