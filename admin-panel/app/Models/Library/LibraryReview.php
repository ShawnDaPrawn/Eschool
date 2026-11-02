<?php

declare(strict_types=1);

namespace App\Models\Library;

use Illuminate\Database\Eloquent\Model;

/** A reviewer's decision on one library item version; kept as written. */
class LibraryReview extends Model
{
    use PlatformLibraryRow;

    protected $table = 'library_reviews';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::updating(fn () => abort(409, 'Library reviews are kept as written.'));
        static::deleting(fn () => abort(409, 'Library reviews are kept as written.'));
    }
}
