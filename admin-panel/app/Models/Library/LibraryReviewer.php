<?php

declare(strict_types=1);

namespace App\Models\Library;

use Illuminate\Database\Eloquent\Model;

/** A platform staff member assigned to review one framework's subject (SA.16). */
class LibraryReviewer extends Model
{
    use PlatformLibraryRow;

    protected $table = 'library_reviewers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'library_framework_id' => 'integer'];
    }
}
