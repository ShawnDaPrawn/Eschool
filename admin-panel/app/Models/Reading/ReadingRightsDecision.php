<?php

declare(strict_types=1);

namespace App\Models\Reading;

use Illuminate\Database\Eloquent\Model;

/** One recorded copyright decision on a book: who, when, and on what basis. Platform-owned, never changed. */
class ReadingRightsDecision extends Model
{
    use PlatformReadingRow;

    protected $table = 'reading_rights_decisions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['author_death_year' => 'integer', 'translator_checked' => 'boolean', 'decided_at' => 'immutable_datetime', 'decided_by' => 'integer'];
    }
}
