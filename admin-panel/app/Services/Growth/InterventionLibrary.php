<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\Growth\InterventionLibraryAction;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * The school's library of reusable, non-clinical intervention actions (Action
 * Plan 4.11). Anyone who can use the intervention workbench reads it; only a
 * dated principal changes it. A library action is guidance, never a diagnosis,
 * and an intervention made from one keeps its own copy of the plan.
 * engineering/specs/growth/check-ins-and-intervention-library.md.
 */
class InterventionLibrary
{
    public const AREAS = ['attendance', 'learning', 'engagement', 'behaviour', 'relationships', 'wellbeing'];

    public const MAX_STEPS = 8;

    /** Suggested actions a school can adopt and then edit; nothing is added until it does. */
    public const DEFAULTS = [
        'named_adult' => ['name' => 'Daily check-in with a named adult', 'concern_area' => 'engagement', 'suggested_weeks' => 3,
            'steps' => ['Agree with the learner which adult they will see each day', 'Meet briefly at the start of each school day', 'Note what helped and what got in the way', 'Review with the learner after two weeks'],
            'progress_measure' => 'School days the learner arrived and checked in, out of the days in the plan'],
        'attendance_plan' => ['name' => 'Attendance follow-up plan', 'concern_area' => 'attendance', 'suggested_weeks' => 4,
            'steps' => ['Talk with the family about what makes arriving at school hard', 'Agree a simple plan for getting to school on time', 'Recognise each improved week with the learner', 'Check the attendance register every week'],
            'progress_measure' => 'Recorded attendance compared with the four weeks before the plan'],
        'catch_up' => ['name' => 'Catch-up support for missed work', 'concern_area' => 'learning', 'suggested_weeks' => 4,
            'steps' => ['List the missed topics with the subject teacher', 'Set two short catch-up sessions a week', 'Check the completed catch-up work together', 'Look at the next assessment for those topics'],
            'progress_measure' => 'Catch-up tasks completed and the next assessment mark for those topics'],
        'study_routine' => ['name' => 'Organisation and homework routine', 'concern_area' => 'learning', 'suggested_weeks' => 3,
            'steps' => ['Set up a homework diary with the learner', 'Check the diary together twice a week', 'Agree a regular time and place for homework', 'Share the routine with the family'],
            'progress_measure' => 'Homework handed in on time, out of the homework set'],
        'peer_buddy' => ['name' => 'Peer buddy and belonging', 'concern_area' => 'relationships', 'suggested_weeks' => 4,
            'steps' => ['Pair the learner with a willing peer buddy', 'Invite the learner to join one club or activity', 'Ask each week how breaks and free time are going', 'Review the pairing with both learners'],
            'progress_measure' => 'The learner\'s own weekly view of breaks, and activity attendance'],
        'behaviour_card' => ['name' => 'Positive behaviour goals card', 'concern_area' => 'behaviour', 'suggested_weeks' => 3,
            'steps' => ['Agree two clear, positive goals with the learner', 'Teachers note on the card whether each goal was met in the lesson', 'Celebrate goals met at the end of each day', 'Share a weekly summary with the family'],
            'progress_measure' => 'Share of lessons in which both goals were met'],
    ];

    /** For the panel: every active action, and archived ones for whoever manages the library. */
    public function list(User $actor): array
    {
        app(RiskAuthority::class)->enabled();
        app(RiskQueries::class)->configuration($actor);
        $manage = $this->canManage($actor);
        $rows = InterventionLibraryAction::query()->when(! $manage, fn ($q) => $q->whereNull('archived_at'))
            ->orderByRaw('archived_at IS NULL DESC')->orderBy('concern_area')->orderBy('name')->limit(200)->get();

        return ['can_manage' => $manage, 'actions' => $rows->map(fn ($row) => $this->present($row))->all(),
            'adoptable' => $manage ? array_values(array_diff(array_keys(self::DEFAULTS), InterventionLibraryAction::query()->whereNotNull('default_key')->pluck('default_key')->all())) : []];
    }

    /** Bounded active choices for the create-intervention form. */
    public function choices(): array
    {
        return InterventionLibraryAction::query()->whereNull('archived_at')->orderBy('concern_area')->orderBy('name')->limit(200)
            ->get(['id', 'name', 'concern_area', 'suggested_weeks'])
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => $row->name, 'concern_area' => $row->concern_area, 'suggested_weeks' => $row->suggested_weeks])->all();
    }

    public function canManage(User $actor): bool
    {
        try {
            app(RiskAuthority::class)->principal($actor);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    /** Adds the suggested actions the school does not have yet; an archived one stays archived. */
    public function adopt(User $actor): int
    {
        app(RiskAuthority::class)->principal($actor, true);

        return DB::transaction(function () use ($actor) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $fresh = app(RiskAuthority::class)->principal($actor, true);
            $have = InterventionLibraryAction::query()->whereNotNull('default_key')->pluck('default_key')->all();
            $added = 0;
            foreach (self::DEFAULTS as $key => $action) {
                if (in_array($key, $have, true)) {
                    continue;
                }
                $row = InterventionLibraryAction::create($action + ['default_key' => $key, 'created_by' => $fresh->id, 'revision' => 1]);
                Audit::record('growth.intervention_library_added', $row, [], ['default_key' => $key, 'revision' => 1]);
                $added++;
            }

            return $added;
        });
    }

    public function save(User $actor, ?int $id, array $input): array
    {
        app(RiskAuthority::class)->principal($actor, true);
        $data = Validator::make($input, $this->rules($id !== null))->validate();

        return DB::transaction(function () use ($actor, $id, $data) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $fresh = app(RiskAuthority::class)->principal($actor, true);
            $fields = ['name' => $data['name'], 'concern_area' => $data['concern_area'], 'steps' => array_values($data['steps']),
                'suggested_weeks' => (int) $data['suggested_weeks'], 'progress_measure' => $data['progress_measure']];
            if ($id === null) {
                $row = InterventionLibraryAction::create($fields + ['created_by' => $fresh->id, 'revision' => 1]);
                Audit::record('growth.intervention_library_added', $row, [], ['revision' => 1]);

                return $this->present($row);
            }
            $row = InterventionLibraryAction::lockForUpdate()->findOrFail($id);
            abort_unless($row->revision === (int) $data['expected_revision'], 409, 'This library action changed. Reload before saving.');
            $row->fill($fields + ['revision' => $row->revision + 1])->save();
            Audit::record('growth.intervention_library_changed', $row, ['revision' => $row->revision - 1], ['revision' => $row->revision]);

            return $this->present($row);
        });
    }

    /** Archived actions are no longer offered; plans already made from them keep their copy. */
    public function archive(User $actor, int $id, int $revision, bool $archived): array
    {
        app(RiskAuthority::class)->principal($actor, true);

        return DB::transaction(function () use ($actor, $id, $revision, $archived) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            app(RiskAuthority::class)->principal($actor, true);
            $row = InterventionLibraryAction::lockForUpdate()->findOrFail($id);
            abort_unless($row->revision === $revision, 409, 'This library action changed. Reload before saving.');
            $row->forceFill(['archived_at' => $archived ? now() : null, 'revision' => $row->revision + 1])->save();
            Audit::record($archived ? 'growth.intervention_library_archived' : 'growth.intervention_library_restored', $row, ['revision' => $revision], ['revision' => $row->revision]);

            return $this->present($row);
        });
    }

    /** The copy an intervention keeps: later library edits never change it. */
    public function plan(int $id): array
    {
        $row = InterventionLibraryAction::query()->whereNull('archived_at')->findOrFail($id);

        return ['library_action_id' => (int) $row->id, 'name' => $row->name, 'concern_area' => $row->concern_area, 'steps' => array_values($row->steps),
            'suggested_weeks' => $row->suggested_weeks, 'progress_measure' => $row->progress_measure];
    }

    /** The action text an intervention gets when its author leaves it blank. */
    public function actionText(array $plan): string
    {
        $lines = [$plan['name']];
        foreach ($plan['steps'] as $i => $step) {
            $lines[] = ($i + 1).'. '.$step;
        }
        $lines[] = __('Measure of progress').': '.$plan['progress_measure'];

        return implode("\n", $lines);
    }

    private function rules(bool $update): array
    {
        return ['name' => ['required', 'string', 'min:3', 'max:120'], 'concern_area' => ['required', 'in:'.implode(',', self::AREAS)],
            'steps' => ['required', 'array', 'min:1', 'max:'.self::MAX_STEPS], 'steps.*' => ['required', 'string', 'min:3', 'max:300'],
            'suggested_weeks' => ['required', 'integer', 'between:1,26'], 'progress_measure' => ['required', 'string', 'min:3', 'max:500']]
            + ($update ? ['expected_revision' => ['required', 'integer', 'min:1']] : []);
    }

    private function present(InterventionLibraryAction $row): array
    {
        return ['id' => (int) $row->id, 'name' => $row->name, 'concern_area' => $row->concern_area, 'steps' => array_values($row->steps ?? []),
            'suggested_weeks' => $row->suggested_weeks, 'progress_measure' => $row->progress_measure, 'default_key' => $row->default_key,
            'archived' => $row->archived_at !== null, 'revision' => $row->revision];
    }
}
