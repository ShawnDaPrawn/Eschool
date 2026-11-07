<?php

declare(strict_types=1);

namespace App\Models\Reading;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A book's place on a shelf. Platform-owned. */
class ReadingShelfBook extends Model
{
    use PlatformReadingRow;

    protected $table = 'reading_shelf_books';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['reading_shelf_id' => 'integer', 'reading_book_id' => 'integer', 'position' => 'integer'];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(ReadingBook::class, 'reading_book_id');
    }
}
