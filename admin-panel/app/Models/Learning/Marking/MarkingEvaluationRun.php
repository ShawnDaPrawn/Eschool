<?php

declare(strict_types=1);

namespace App\Models\Learning\Marking;

use Illuminate\Database\Eloquent\Model;

/**
 * One run of the marking engine over a subject's evaluation scripts (Action
 * Plan 3.18): its agreement with the teachers, at the engine and profile
 * versions measured, against the settings of the day. Never changed once
 * written. Platform-owned.
 *
 * @property int $id
 * @property string $subject
 * @property string $engine_version
 * @property string $profile_version
 * @property int $script_count
 * @property int $point_count
 * @property bool $passed
 * @property list<string> $failures
 * @property array<string, mixed> $metrics
 * @property list<array<string, mixed>> $point_stats
 * @property array<string, mixed> $settings
 */
class MarkingEvaluationRun extends Model
{
    use PlatformEvaluationRow;

    protected $table = 'marking_evaluation_runs';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['passed' => 'boolean', 'failures' => 'array', 'metrics' => 'array', 'point_stats' => 'array', 'settings' => 'array',
            'script_count' => 'integer', 'point_count' => 'integer', 'started_by' => 'integer'];
    }
}
