<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Jobs\Growth\RecomputeMastery;
use App\Models\Academics\TeachingGroup;
use App\Models\Growth\MasterySettings;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Academics\AcademicAccess;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/** Which mastery score model a school reads (Action Plan 3.8). recent20-v1 is always kept current. */
class MasteryModels
{
    public const VERSIONS = [MasteryScore::VERSION, MasteryScoreV2::VERSION];

    /** The current school's model; a missing or unknown choice reads as the default. */
    public function active(): string
    {
        return app(TopicGraph::class)->settings()['policy_version'];
    }

    /** Versions to write: v1 always (so switching back is instant and history stays comparable), plus the active one. */
    public function computed(): array
    {
        return array_values(array_unique([MasteryScore::VERSION, $this->active()]));
    }

    public static function label(string $version): string
    {
        return match ($version) {
            MasteryScore::VERSION => __('Mastery model v1: the last 20 answers, marks added up'),
            MasteryScoreV2::VERSION => __('Mastery model v2: weighted by recency, difficulty and forgetting'),
            default => $version,
        };
    }

    public static function rules(): array
    {
        return ['model_version' => ['required', 'string', Rule::in(self::VERSIONS)], 'expected_revision' => ['required', 'integer', 'min:0']];
    }

    public function change(User $actor, array $input): array
    {
        $this->authorize($actor);
        $data = Validator::make($input, self::rules())->validate();

        return DB::transaction(function () use ($actor, $data) {
            School::query()->lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $this->authorize($actor);
            $setting = MasterySettings::query()->lockForUpdate()->first() ?? new MasterySettings(['family_marks_enabled' => false, 'revision' => 0]);
            abort_unless((int) $setting->revision === (int) $data['expected_revision'], 409, 'Mastery configuration changed; reload before saving.');
            $before = $this->active();
            if ($before === $data['model_version']) {
                return ['model_version' => $before, 'revision' => (int) $setting->revision, 'queued_groups' => 0];
            }
            $setting->fill(['model_version' => $data['model_version'], 'revision' => (int) $setting->revision + 1])->save();
            // recent20-v1 records are always current; any other model needs its records built,
            // for the same groups as `mastery:rebuild` (every active group).
            $queued = 0;
            if ($data['model_version'] !== MasteryScore::VERSION) {
                $school = app(CurrentSchool::class)->id();
                TeachingGroup::query()->where('active', true)->select('id')
                    ->chunkById(200, function ($groups) use ($school, &$queued) {
                        foreach ($groups as $group) {
                            RecomputeMastery::dispatch($school, (int) $group->id);
                            $queued++;
                        }
                    });
            }
            Audit::record('growth.mastery_model_changed', $setting, ['model_version' => $before], ['model_version' => $setting->model_version,
                'revision' => $setting->revision, 'queued_groups' => $queued]);

            return ['model_version' => $setting->model_version, 'revision' => (int) $setting->revision, 'queued_groups' => $queued];
        });
    }

    private function authorize(User $actor): void
    {
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($gate->writable('mastery'), 403, 'The mastery module is disabled.');
        app(AcademicAccess::class)->actor($actor, 'curriculum-publish');
    }
}
