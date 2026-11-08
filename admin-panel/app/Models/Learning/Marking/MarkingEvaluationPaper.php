<?php

declare(strict_types=1);

namespace App\Models\Learning\Marking;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An evaluation paper (Action Plan 3.18): its questions and their mark points, as a structured memo stores them. Platform-owned.
 *
 * @property int $id
 * @property string $subject
 * @property string $code
 * @property array<int, array{number: string, max_units: int, points: list<array<string, mixed>>}> $questions
 * @property int $max_units
 * @property string $fingerprint
 */
class MarkingEvaluationPaper extends Model
{
    use PlatformEvaluationRow;

    protected $table = 'marking_evaluation_papers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['questions' => 'array', 'max_units' => 'integer', 'created_by' => 'integer'];
    }

    public function scripts(): HasMany
    {
        return $this->hasMany(MarkingEvaluationScript::class, 'marking_evaluation_paper_id');
    }
}
