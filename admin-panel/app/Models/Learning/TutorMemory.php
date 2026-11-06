<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * A learner's "clear my tutor memory" mark at one school; holds no chat.
 * cleared_at is written with now() like tutor_messages.created_at, so the two
 * compare in the same school-local clock.
 */
class TutorMemory extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['learner_id' => 'integer', 'cleared_by' => 'integer', 'cleared_at' => 'datetime'];
    }
}
