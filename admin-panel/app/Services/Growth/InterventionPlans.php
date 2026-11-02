<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\Growth\Intervention;
use App\Models\Growth\InterventionProgress;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * An intervention's plan steps and its dated progress entries (Action Plan
 * 4.11). Both are body records: only the intervention's current authorised
 * owner reads or adds them, exactly as for the action text. Entries are
 * append-only and their notes are encrypted.
 * engineering/specs/growth/check-ins-and-intervention-library.md.
 */
class InterventionPlans
{
    public const STATUSES = ['on_track', 'needs_review', 'completed'];

    public function read(User $actor, int $id): array
    {
        $meta = app(Interventions::class)->authorized($actor, $id);
        // Separate lookups after the owner check: a refused reader never selects them.
        $plan = Intervention::select('id', 'plan')->findOrFail($id)->plan;
        $entries = InterventionProgress::where('intervention_id', $id)->orderByDesc('recorded_on')->orderByDesc('id')->limit(100)->get()
            ->map(fn ($row) => ['id' => (int) $row->id, 'recorded_on' => $row->recorded_on->toDateString(), 'step' => $row->step,
                'status' => $row->status, 'note' => $row->note, 'actor_id' => (int) $row->actor_id])->all();
        app(Interventions::class)->authorized($actor, $id);

        return ['intervention_id' => (int) $meta->id, 'plan' => $plan, 'entries' => $entries];
    }

    public function record(User $actor, int $id, array $input): array
    {
        app(Interventions::class)->authorized($actor, $id, true);
        $data = Validator::make($input, ['recorded_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.app(AcademicAuthority::class)->today()],
            'step' => ['sometimes', 'nullable', 'integer', 'between:1,'.InterventionLibrary::MAX_STEPS], 'status' => ['required', 'in:'.implode(',', self::STATUSES)],
            'note' => ['sometimes', 'nullable', 'string', 'max:2000']])->validate();

        return DB::transaction(function () use ($actor, $id, $data) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $meta = app(Interventions::class)->authorized($actor, $id, true);
            abort_unless($meta->state === 'active', 409, 'Retained completed interventions are read-only.');
            if ($data['recorded_on'] < $meta->started_on->toDateString()) {
                throw ValidationException::withMessages(['recorded_on' => __('Progress cannot be dated before the intervention started.')]);
            }
            $step = isset($data['step']) ? (int) $data['step'] : null;
            $plan = Intervention::select('id', 'plan')->lockForUpdate()->findOrFail($id)->plan;
            if ($step !== null && ($plan === null || $step > count($plan['steps'] ?? []))) {
                throw ValidationException::withMessages(['step' => __('Choose a step from this plan.')]);
            }
            $row = InterventionProgress::create(['intervention_id' => $id, 'actor_id' => $actor->id, 'recorded_on' => $data['recorded_on'],
                'step' => $step, 'status' => $data['status'], 'note' => $data['note'] ?? null, 'created_at' => now()]);
            // Ids and status only: the note never enters the audit log.
            Audit::record('growth.intervention_progress_recorded', $row, [], ['intervention_id' => $id, 'step' => $step, 'status' => $data['status']]);

            return ['id' => (int) $row->id, 'intervention_id' => $id, 'learner_id' => (int) $meta->learner_id, 'session_year_id' => (int) $meta->session_year_id,
                'recorded_on' => $data['recorded_on'], 'step' => $step, 'status' => $data['status']];
        });
    }
}
