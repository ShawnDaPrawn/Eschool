<?php

declare(strict_types=1);

namespace App\Services\Ai\Operations;

use App\Models\Ai\AiSchoolLimit;
use App\Models\Ai\AiSettingsOperation;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AiSchoolControls
{
    public const KEYS = ['school_daily_tokens', 'actor_daily_tokens', 'learner_daily_tokens', 'school_daily_cents', 'actor_daily_cents', 'learner_daily_cents'];

    public function actor(User $actor): User
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating() && $school->school()?->refresh()?->isActive(), 403);
        abort_if(PlatformStaffIdentity::where('user_id', $actor->id)->where('active', true)->exists(), 403);
        $policy = app(ProfilePolicy::class);
        $fresh = $policy->actor(User::findOrFail($actor->id));
        abort_if($fresh->password_change_required, 403);
        abort_unless(array_intersect($policy->roles($fresh), ['Super Admin', 'School Admin', 'Principal']) !== [] && $policy->permission($fresh, 'ai-settings-manage'), 403);

        return $fresh;
    }

    public function available(User $actor): bool
    {
        try {
            $this->actor($actor);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    /** Trusted gateway hook: settings can only reduce the server-owned allowance. */
    public function apply(array $config): array
    {
        $row = AiSchoolLimit::first();
        if ($row?->paused) {
            throw new AiUnavailable('school_inference_paused');
        }
        foreach (self::KEYS as $key) {
            $value = $row?->limits[$key] ?? null;
            if (is_int($value) && isset($config[$key])) {
                $config[$key] = min($config[$key], $value);
            }
        }

        return $config;
    }

    public function data(User $actor): array
    {
        $this->actor($actor);
        $row = AiSchoolLimit::first();
        $features = [];
        foreach ((array) config('ai.inference.features', []) as $name => $settings) {
            if (! is_array($settings)) {
                continue;
            }
            $features[] = ['name' => $name, 'enabled_by_platform' => config('ai.inference.enabled') && ($settings['enabled'] ?? false), 'qualified' => config('ai.providers.'.($settings['provider'] ?? '').'.qualified') === true];
        }

        $this->actor($actor);

        return ['revision' => $row?->revision ?? 0, 'paused' => $row?->paused ?? false, 'limits' => $row?->limits ?? [], 'features' => $features];
    }

    public function save(User $actor, array $data): array
    {
        $this->actor($actor);
        $rules = ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'paused' => ['required', 'boolean'], 'limits' => ['present', 'array:'.implode(',', self::KEYS)]];
        foreach (self::KEYS as $key) {
            $rules['limits.'.$key] = ['nullable', 'integer', 'min:0', 'max:100000000'];
        }
        $data = Validator::make($data, $rules)->validate();
        $limits = [];
        foreach (self::KEYS as $key) {
            if (isset($data['limits'][$key])) {
                $limits[$key] = (int) $data['limits'][$key];
            }
        }
        $paused = (bool) $data['paused'];
        $revision = (int) $data['revision'];
        $hash = hash('sha256', json_encode([$revision, $paused, $limits], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($actor, $data, $limits, $paused, $revision, $hash): array {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $this->actor($actor);
            $prior = AiSettingsOperation::where('user_id', $actor->id)->where('operation_id', $data['operation_id'])->first();
            if ($prior !== null) {
                abort_unless(hash_equals($prior->intent_hash, $hash), 409);

                return ['revision' => $prior->revision];
            }
            $row = AiSchoolLimit::lockForUpdate()->first();
            abort_unless(($row?->revision ?? 0) === $revision, 409);
            $row ??= new AiSchoolLimit;
            $row->fill(['revision' => $revision + 1, 'paused' => $paused, 'limits' => $limits]);
            $row->save();
            AiSettingsOperation::create(['user_id' => $actor->id, 'operation_id' => $data['operation_id'], 'intent_hash' => $hash, 'revision' => $row->revision]);
            Audit::record('ai.school_controls_changed', $row, [], ['revision' => $row->revision, 'paused' => $paused, 'limits' => $limits]);
            $this->actor($actor);

            return ['revision' => $row->revision];
        });
    }
}
