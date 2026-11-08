<?php

declare(strict_types=1);

namespace App\Models\Learning\Tests;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One learner's timed sitting of a blueprint. The deadline is held here, on the server. */
class PracticeTest extends Model
{
    use BelongsToSchool;

    protected $table = 'practice_tests';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['started_at' => UtcInstant::class, 'deadline_at' => UtcInstant::class, 'submitted_at' => UtcInstant::class,
            'time_limit_seconds' => 'integer', 'correct_count' => 'integer', 'marked_count' => 'integer', 'learner_id' => 'integer',
            'user_id' => 'integer', 'teaching_group_id' => 'integer', 'practice_test_blueprint_id' => 'integer'];
    }

    /** @return BelongsTo<PracticeTestBlueprint, $this> */
    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(PracticeTestBlueprint::class, 'practice_test_blueprint_id');
    }

    /** @return HasMany<PracticeTestQuestion, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(PracticeTestQuestion::class, 'practice_test_id')->orderBy('position');
    }
}
