<?php

declare(strict_types=1);

namespace App\Services\Recognition;

use App\Models\Recognition\AwardCategory;
use App\Models\Recognition\AwardDefinition;
use App\Models\Recognition\AwardNomination;
use App\Models\Recognition\AwardPresentation;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Audit\Audit;
use Illuminate\Support\Facades\Validator;

class AwardPresentations
{
    private function category(User $actor, int $category, bool $write = true): AwardCategory
    {
        $a = app(RecognitionAuthority::class);
        $a->staff($actor, 'awards', $write);
        $row = AwardCategory::findOrFail($category);
        abort_unless($a->categoryAdmin($actor, $row, $write), 403);

        return $row;
    }

    public function present(User $actor, array $input): AwardPresentation
    {
        $a = app(RecognitionAuthority::class);
        $a->staff($actor, 'awards', true);
        $d = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'learner_id' => ['required', 'integer', 'min:1'], 'session_year_id' => ['required', 'integer', 'min:1'], 'award_definition_id' => ['required', 'integer', 'min:1'], 'award_nomination_id' => ['present', 'nullable', 'integer', 'min:1'], 'presented_on' => ['required', 'date_format:Y-m-d'], 'event' => ['present', 'nullable', 'string', 'max:191']])->validate();
        foreach (['learner_id', 'session_year_id', 'award_definition_id'] as $key) {
            $d[$key] = (int) $d[$key];
        }$d['award_nomination_id'] = $d['award_nomination_id'] === null ? null : (int) $d['award_nomination_id'];
        $authority = function () use ($actor, $d): void {
            $definition = AwardDefinition::findOrFail($d['award_definition_id']);
            $this->category($actor, (int) $definition->award_category_id);
            if (app(RecognitionCatalog::class)->settings()->principal_approval_required && $d['award_nomination_id'] === null) {
                abort_unless(app(AcademicAuthority::class)->currentLeadership($actor, 'principal', 0), 403, 'This school requires principal approval.');
            }
        };

        return app(RecognitionOperations::class)->once($actor, 'award.present', $d, $authority, function () use ($actor, $a, $d) {
            $year = $a->currentYear($d['session_year_id']);
            abort_unless($d['presented_on'] >= substr((string) $year->start_date, 0, 10) && $d['presented_on'] <= $a->today(), 422, 'Presentation dates must be in the current year and not in the future.');
            $place = $a->placement($d['learner_id'], (int) $year->id);
            $dated = $a->placement($d['learner_id'], (int) $year->id, $d['presented_on']);
            abort_unless((int) $dated->homeroom->class->grade_level_id === (int) $place->homeroom->class->grade_level_id, 409, 'The presentation date belongs to a different grade placement.');
            $learner = Students::findOrFail($d['learner_id']);
            $definition = AwardDefinition::where('active', true)->findOrFail($d['award_definition_id']);
            $category = AwardCategory::findOrFail($definition->award_category_id);
            $a->eligible($category, $learner, $place);
            if ($d['award_nomination_id'] !== null) {
                $n = AwardNomination::lockForUpdate()->findOrFail($d['award_nomination_id']);
                abort_unless($n->state === 'approved' && (int) $n->learner_id === $d['learner_id'] && (int) $n->session_year_id === $d['session_year_id'] && (int) $n->award_definition_id === (int) $definition->id && (int) $n->grade_level_id === (int) $place->homeroom->class->grade_level_id, 409, 'Use the approved nomination for this learner, award and year.');
                abort_unless((int) $n->definition_revision === (int) $definition->revision, 409, 'The definition changed after this nomination was reviewed.');
                if (app(RecognitionCatalog::class)->settings()->principal_approval_required) {
                    $reviewer = User::findOrFail($n->reviewed_by);
                    $a->staff($reviewer, 'awards');
                    abort_unless(app(AcademicAuthority::class)->currentLeadership($reviewer, 'principal', 0), 409, 'A current principal must approve this nomination.');
                }
                abort_if(AwardPresentation::where('award_nomination_id', $n->id)->exists(), 409, 'This nomination was already presented.');
            }
            $dedupe = $definition->frequency === 'annual' ? hash('sha256', implode(':', [$d['learner_id'], $d['session_year_id'], $definition->id])) : null;
            abort_if($dedupe !== null && AwardPresentation::where('dedupe_key', $dedupe)->exists(), 409, 'This annual award is already on the learner profile.');
            $row = AwardPresentation::create(array_diff_key($d, ['operation_id' => true]) + ['grade_level_id' => $place->homeroom->class->grade_level_id, 'award_category_id' => $category->id, 'presented_by' => $actor->id, 'title' => $definition->name, 'criteria' => $definition->criteria, 'source_kind' => 'catalogue', 'state' => 'active', 'revision' => 1, 'dedupe_key' => $dedupe]);
            $this->audit($row);

            return $row;
        }, fn (int $id) => AwardPresentation::findOrFail($id));
    }

    public function outside(User $actor, array $input): AwardPresentation
    {
        $a = app(RecognitionAuthority::class);
        $a->manage($actor, 'awards');
        $authority = function () use ($a, $actor): void {
            $a->manage($actor, 'awards');
            if (app(RecognitionCatalog::class)->settings()->principal_approval_required) {
                abort_unless(app(AcademicAuthority::class)->currentLeadership($actor, 'principal', 0), 403);
            }
        };
        $d = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'learner_id' => ['required', 'integer', 'min:1'], 'session_year_id' => ['required', 'integer', 'min:1'], 'title' => ['required', 'string', 'max:160'], 'criteria' => ['required', 'string', 'max:4000'], 'outside_organisation' => ['required', 'string', 'max:160'], 'presented_on' => ['required', 'date_format:Y-m-d'], 'event' => ['present', 'nullable', 'string', 'max:191']])->validate();
        $d['learner_id'] = (int) $d['learner_id'];
        $d['session_year_id'] = (int) $d['session_year_id'];

        return app(RecognitionOperations::class)->once($actor, 'award.outside', $d, $authority, function () use ($actor, $a, $d) {
            $year = SessionYear::findOrFail($d['session_year_id']);
            $place = $a->placement($d['learner_id'], (int) $year->id, $d['presented_on']);
            abort_unless($year->start_date !== null && $year->end_date !== null && $d['presented_on'] >= substr((string) $year->start_date, 0, 10) && $d['presented_on'] <= min($a->today(), substr((string) $year->end_date, 0, 10)), 422);
            $row = AwardPresentation::create(array_diff_key($d, ['operation_id' => true]) + ['grade_level_id' => $place->homeroom->class->grade_level_id, 'presented_by' => $actor->id, 'source_kind' => 'outside', 'state' => 'active', 'revision' => 1]);
            $this->audit($row);

            return $row;
        }, fn (int $id) => AwardPresentation::findOrFail($id));
    }

    public function void(User $actor, int $id, array $input): AwardPresentation
    {
        $a = app(RecognitionAuthority::class);
        $a->staff($actor, 'awards', true);
        $row = AwardPresentation::findOrFail($id);
        $authority = function () use ($actor, $a, $row): void {
            if ($row->award_category_id === null) {
                $a->manage($actor, 'awards');
            } else {
                $this->category($actor, (int) $row->award_category_id);
            }
        };
        $authority();
        $d = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'max:4000']])->validate();
        $d['revision'] = (int) $d['revision'];
        $d['presentation_id'] = $id;

        return app(RecognitionOperations::class)->once($actor, 'award.void', $d, $authority, function () use ($id, $d, $actor) {
            $row = AwardPresentation::lockForUpdate()->findOrFail($id);
            app(RecognitionCatalog::class)->revision($row, $d);
            abort_unless($row->state === 'active', 409, 'This award already has a retained correction.');
            $row->update(['state' => 'void', 'revision' => $row->revision + 1, 'dedupe_key' => null, 'voided_by' => $actor->id, 'void_reason' => $d['reason'], 'voided_at' => now()]);
            $this->audit($row);

            return $row;
        }, fn (int $id) => AwardPresentation::findOrFail($id));
    }

    /** This permanent profile intentionally queries presentations only. */
    public function profile(User $actor, int $learner, bool $family = false, int $before = 0): array
    {
        $a = app(RecognitionAuthority::class);
        if ($family) {
            $a->family($actor, $learner, 'awards');
        } else {
            $a->learner($actor, $learner, 'awards');
        }
        $rows = AwardPresentation::where('learner_id', $learner)->where('state', 'active')->when($before > 0, fn ($q) => $q->where('id', '<', $before))->orderByDesc('id')->limit(51)->get();
        $more = $rows->count() > 50;
        $rows = $rows->take(50);

        return ['items' => $rows->map(fn ($p) => ['id' => (int) $p->id, 'session_year_id' => (int) $p->session_year_id, 'title' => $p->title, 'criteria' => $p->criteria, 'presented_on' => $p->presented_on->toDateString(), 'event' => $p->event, 'outside_organisation' => $p->outside_organisation, 'source_kind' => $p->source_kind])->all(), 'next_before' => $more ? (int) $rows->last()->id : null];
    }

    private function audit(AwardPresentation $row): void
    {
        Audit::record('recognition.award_presentation_changed', $row, [], ['learner_id' => (int) $row->learner_id, 'session_year_id' => (int) $row->session_year_id, 'revision' => $row->revision, 'state' => $row->state]);
    }
}
