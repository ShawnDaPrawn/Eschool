<?php

declare(strict_types=1);

namespace App\Services\Platform\Saas;

use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\FeatureFlags;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * HQ's feature flags and staged rollout (Action Plan SA.14): create a flag,
 * roll it out (off, beta for chosen schools, everyone), switch single schools
 * on or off, and kill it everywhere with a reason. Every change is audited.
 * engineering/specs/platform/feature-flags.md.
 */
class PlatformFeatureFlags
{
    public function __construct(private readonly CurrentSchool $currentSchool) {}

    public static function createRules(): array
    {
        return [
            'key' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]{2,63}$/'],
            'description' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public static function updateRules(): array
    {
        return [
            'description' => ['required', 'string', 'min:5', 'max:500'],
            'rollout' => ['required', Rule::in(FeatureFlags::ROLLOUTS)],
            'killed' => ['required', 'boolean'],
            'kill_reason' => ['nullable', 'required_if:killed,1', 'string', 'min:5', 'max:500'],
            'revision' => ['required', 'integer', 'min:1'],
        ];
    }

    public static function schoolRules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            // on, off, or clear (follow the rollout).
            'switch' => ['required', Rule::in(['on', 'off', 'clear'])],
        ];
    }

    /** @return list<array<string, mixed>> */
    public function list(): array
    {
        return $this->currentSchool->acrossSchools(function (): array {
            $schools = DB::table('schools')->orderBy('name')->get(['id', 'name']);
            $switches = DB::table('platform_feature_flag_schools')->get()->groupBy('flag_id');

            return DB::table('platform_feature_flags')->orderBy('key')->get()->map(function (object $flag) use ($schools, $switches): array {
                $own = ($switches[$flag->id] ?? collect())->keyBy('school_id');
                $on = $schools->filter(fn (object $school) => FeatureFlags::value((string) $flag->rollout, (bool) $flag->killed, isset($own[$school->id]) ? (bool) $own[$school->id]->enabled : null));

                return [
                    'id' => (int) $flag->id, 'key' => (string) $flag->key, 'description' => (string) $flag->description,
                    'rollout' => (string) $flag->rollout, 'killed' => (bool) $flag->killed, 'kill_reason' => $flag->kill_reason,
                    'revision' => (int) $flag->revision, 'on_count' => $on->count(), 'school_count' => $schools->count(),
                    'switches' => $own->map(fn (object $row) => ['school_id' => (int) $row->school_id, 'enabled' => (bool) $row->enabled,
                        'name' => (string) ($schools->firstWhere('id', $row->school_id)->name ?? '')])->sortBy('name')->values()->all(),
                ];
            })->values()->all();
        });
    }

    /** @return array<int, string> */
    public function schoolChoices(): array
    {
        return $this->currentSchool->acrossSchools(fn (): array => DB::table('schools')->orderBy('name')->pluck('name', 'id')
            ->mapWithKeys(fn ($name, $id) => [(int) $id => (string) $name])->all());
    }

    public function create(array $input, User $actor): int
    {
        $data = Validator::make($input, self::createRules())->validate();

        return $this->currentSchool->acrossSchools(fn (): int => DB::transaction(function () use ($data, $actor): int {
            if (DB::table('platform_feature_flags')->where('key', $data['key'])->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['key' => __('A flag with this key already exists.')]);
            }
            $id = (int) DB::table('platform_feature_flags')->insertGetId(['key' => $data['key'], 'description' => $data['description'],
                'rollout' => 'off', 'killed' => false, 'revision' => 1, 'created_at' => now(), 'updated_at' => now()]);
            Audit::record('platform_feature_flag_created', null, [], ['flag' => $data['key'], 'rollout' => 'off', 'changed_by' => $actor->id]);

            return $id;
        }, 3));
    }

    public function update(int $id, array $input, User $actor): void
    {
        $data = Validator::make($input, self::updateRules())->validate();
        $this->currentSchool->acrossSchools(fn () => DB::transaction(function () use ($id, $data, $actor): void {
            $flag = DB::table('platform_feature_flags')->where('id', $id)->lockForUpdate()->first();
            abort_if($flag === null, 404);
            abort_if((int) $flag->revision !== (int) $data['revision'], 409, __('This flag changed. Reload before saving.'));
            $killed = (bool) $data['killed'];
            $next = ['description' => $data['description'], 'rollout' => $data['rollout'], 'killed' => $killed,
                'kill_reason' => $killed ? $data['kill_reason'] : null];
            DB::table('platform_feature_flags')->where('id', $id)->update($next + ['revision' => (int) $flag->revision + 1, 'updated_at' => now()]);
            Audit::record($killed && ! $flag->killed ? 'platform_feature_flag_killed' : 'platform_feature_flag_changed', null,
                ['rollout' => $flag->rollout, 'killed' => (bool) $flag->killed],
                ['flag' => $flag->key, 'rollout' => $data['rollout'], 'killed' => $killed, 'reason' => $next['kill_reason'], 'changed_by' => $actor->id]);
        }, 3));
    }

    /** Switches one school on or off by name, or clears it to follow the rollout. */
    public function switchSchool(int $id, array $input, User $actor): void
    {
        $data = Validator::make($input, self::schoolRules())->validate();
        $schoolId = (int) $data['school_id'];
        $this->currentSchool->acrossSchools(fn () => DB::transaction(function () use ($id, $schoolId, $data, $actor): void {
            $flag = DB::table('platform_feature_flags')->where('id', $id)->lockForUpdate()->first();
            abort_if($flag === null, 404);
            $before = DB::table('platform_feature_flag_schools')->where('flag_id', $id)->where('school_id', $schoolId)->value('enabled');
            if ($data['switch'] === 'clear') {
                DB::table('platform_feature_flag_schools')->where('flag_id', $id)->where('school_id', $schoolId)->delete();
            } else {
                DB::table('platform_feature_flag_schools')->updateOrInsert(['flag_id' => $id, 'school_id' => $schoolId],
                    ['enabled' => $data['switch'] === 'on', 'created_at' => now(), 'updated_at' => now()]);
            }
            DB::table('platform_feature_flags')->where('id', $id)->update(['revision' => (int) $flag->revision + 1, 'updated_at' => now()]);
            Audit::record('platform_feature_flag_school_switched', null, ['enabled' => $before === null ? null : (bool) $before],
                ['flag' => $flag->key, 'switch' => $data['switch'], 'changed_by' => $actor->id], $schoolId);
        }, 3));
    }
}
