<?php

declare(strict_types=1);

namespace App\Models\Reading;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** A public-domain book in the reading library catalogue (Action Plan 4.4). Platform-owned. */
class ReadingBook extends Model
{
    use PlatformReadingRow;

    public const CLEARED = 'public_domain_za';

    protected $table = 'reading_books';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['gutenberg_id' => 'integer', 'author_birth_year' => 'integer', 'author_death_year' => 'integer', 'subjects' => 'array',
            'word_count' => 'integer', 'rights_decision_id' => 'integer', 'revision' => 'integer'];
    }

    public function cleared(): bool
    {
        return $this->rights_status === self::CLEARED;
    }

    public function text(): HasOne
    {
        return $this->hasOne(ReadingBookText::class);
    }
}
