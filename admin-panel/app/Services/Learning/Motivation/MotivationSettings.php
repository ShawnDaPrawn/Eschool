<?php

declare(strict_types=1);

namespace App\Services\Learning\Motivation;

use App\Models\Learning\PracticeMotivationSettings;
use App\Models\User;
use App\Services\Learning\PracticeOperations;
use App\Services\Platform\Audit\Audit;
use Illuminate\Support\Facades\Validator;

/** The school's gentle practice nudges (Action Plan 3.13). Both start off. */
class MotivationSettings
{
    /** @return array{guardian_nudge_enabled: bool, learner_nudge_enabled: bool, nudge_after_school_days: int, revision: int} */
    public function current(): array
    {
        $row = PracticeMotivationSettings::first();

        return ['guardian_nudge_enabled' => (bool) ($row->guardian_nudge_enabled ?? false), 'learner_nudge_enabled' => (bool) ($row->learner_nudge_enabled ?? false),
            'nudge_after_school_days' => (int) ($row->nudge_after_school_days ?? config('motivation.nudges.default_days', 3)), 'revision' => (int) ($row->revision ?? 0)];
    }

    public static function rules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'guardian_nudge_enabled' => ['required', 'boolean'],
            'learner_nudge_enabled' => ['required', 'boolean'],
            'nudge_after_school_days' => ['required', 'integer', 'between:'.config('motivation.nudges.min_days', 2).','.config('motivation.nudges.max_days', 10)]];
    }

    public function configure(User $actor, array $input): array
    {
        $data = Validator::make($input, self::rules())->validate();
        $data = ['operation_id' => $data['operation_id'], 'revision' => (int) $data['revision'], 'guardian_nudge_enabled' => (bool) $data['guardian_nudge_enabled'],
            'learner_nudge_enabled' => (bool) $data['learner_nudge_enabled'], 'nudge_after_school_days' => (int) $data['nudge_after_school_days']];
        $authorize = fn () => app(MotivationAuthority::class)->manager($actor, true);
        $authorize();

        return app(PracticeOperations::class)->run($actor, $data + ['kind' => 'practice_motivation_settings'], $authorize, function () use ($actor, $data): array {
            $row = PracticeMotivationSettings::lockForUpdate()->first() ?? new PracticeMotivationSettings(['revision' => 0]);
            abort_unless((int) $row->revision === $data['revision'], 409, 'The practice reminder settings changed. Compare the current settings.');
            $before = $this->current();
            $row->fill(array_diff_key($data, ['operation_id' => true, 'revision' => true]) + ['changed_by' => (int) $actor->id, 'revision' => (int) $row->revision + 1])->save();
            Audit::record('practice.motivation_configured', $row, array_diff_key($before, ['revision' => true]), array_diff_key($this->current(), ['revision' => true]) + ['revision' => (int) $row->revision]);

            return $this->current();
        });
    }
}
