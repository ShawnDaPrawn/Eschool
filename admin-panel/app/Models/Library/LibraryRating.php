<?php

declare(strict_types=1);

namespace App\Models\Library;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * One teacher's rating (1 to 5) and optional short comment of a shared
 * library item (Action Plan 5.4). School-owned: who rated what stays in the
 * rater's school; other schools see only an average and a count, and
 * comments without any name.
 */
class LibraryRating extends Model
{
    use BelongsToSchool;

    protected $table = 'library_ratings';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['library_item_id' => 'integer', 'user_id' => 'integer', 'stars' => 'integer'];
    }
}
