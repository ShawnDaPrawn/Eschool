<?php

declare(strict_types=1);

namespace App\Models\Reading;

use Illuminate\Database\Eloquent\Model;

/**
 * An HQ-written comprehension question on a book. The answer and explanation
 * reach a learner only after they have answered. Platform-owned.
 */
class ReadingQuestion extends Model
{
    use PlatformReadingRow;

    protected $table = 'reading_questions';

    protected $guarded = ['id'];

    protected $hidden = ['answer', 'explanation'];

    protected function casts(): array
    {
        return ['after_page' => 'integer', 'options' => 'array', 'answer' => 'integer', 'active' => 'boolean'];
    }
}
