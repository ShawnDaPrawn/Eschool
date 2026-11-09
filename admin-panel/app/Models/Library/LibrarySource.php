<?php

declare(strict_types=1);

namespace App\Models\Library;

use Illuminate\Database\Eloquent\Model;

/**
 * Where library content comes from and under what licence (Action Plan 3.3).
 * A teacher's shared question has its own source (kind `contributed`, Action
 * Plan 5.4) naming the school, the teacher, the credit they chose and the
 * date, so the published item can say who shared it, as they chose.
 */
class LibrarySource extends Model
{
    use PlatformLibraryRow;

    protected $table = 'library_sources';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['contributor_school_id' => 'integer', 'contributor_user_id' => 'integer', 'contributed_at' => 'datetime'];
    }
}
