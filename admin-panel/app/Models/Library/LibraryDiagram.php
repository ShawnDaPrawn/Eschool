<?php

declare(strict_types=1);

namespace App\Models\Library;

use Illuminate\Database\Eloquent\Model;

/**
 * A platform library diagram picture for labelling questions (Action Plan
 * 3.11). Written only from /saas/library; a school reads it only through a
 * published library question in a learner's own practice set.
 */
class LibraryDiagram extends Model
{
    use PlatformLibraryRow;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'library_diagrams';

    protected $guarded = [];

    protected $hidden = ['path'];

    protected static function booted(): void
    {
        static::updating(fn () => abort(409, 'Library diagram pictures are immutable.'));
        static::deleting(fn () => abort(409, 'Library diagram pictures are kept: published questions may point at them.'));
    }

    protected function casts(): array
    {
        return ['bytes' => 'integer', 'width' => 'integer', 'height' => 'integer'];
    }
}
