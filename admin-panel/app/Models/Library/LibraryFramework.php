<?php

declare(strict_types=1);

namespace App\Models\Library;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A curriculum framework of the platform library, such as CAPS (Action Plan 3.3). */
class LibraryFramework extends Model
{
    use PlatformLibraryRow;

    protected $table = 'library_frameworks';

    protected $guarded = ['id'];

    public function topics(): HasMany
    {
        return $this->hasMany(LibraryTopic::class);
    }
}
