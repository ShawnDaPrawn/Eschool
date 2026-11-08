<?php

declare(strict_types=1);

namespace App\Models\Reading;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** An HQ-curated shelf for a range of grades (R = 0 to 12). Platform-owned. */
class ReadingShelf extends Model
{
    use PlatformReadingRow;

    protected $table = 'reading_shelves';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['grade_from' => 'integer', 'grade_to' => 'integer', 'active' => 'boolean', 'revision' => 'integer'];
    }

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(ReadingBook::class, 'reading_shelf_books')->withPivot('position')->orderByPivot('position');
    }
}
