<?php

declare(strict_types=1);

namespace App\Models\Reading;

use Illuminate\Database\Eloquent\Model;

/**
 * The state of a book's one cached text (Action Plan 4.4). Written only by
 * ReadingFetcher, also during a learner's request, because a book is fetched
 * on first read; it holds no school or learner data.
 */
class ReadingBookText extends Model
{
    protected $table = 'reading_book_texts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['bytes' => 'integer', 'page_count' => 'integer', 'word_count' => 'integer', 'removed_lines' => 'integer',
            'fetched_at' => 'immutable_datetime', 'last_failed_at' => 'immutable_datetime'];
    }
}
