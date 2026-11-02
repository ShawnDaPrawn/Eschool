<?php

declare(strict_types=1);

namespace App\Models\Library;

use Illuminate\Database\Eloquent\Model;

/** Where library content comes from and under what licence (Action Plan 3.3). */
class LibrarySource extends Model
{
    use PlatformLibraryRow;

    protected $table = 'library_sources';

    protected $guarded = ['id'];
}
