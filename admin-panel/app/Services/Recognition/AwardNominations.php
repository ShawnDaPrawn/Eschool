<?php

declare(strict_types=1);

namespace App\Services\Recognition;

use App\Models\Academics\GradeLevel;
use App\Models\Recognition\AwardCategory;
use App\Models\Recognition\AwardDefinition;
use App\Models\Recognition\AwardNomination;
use App\Models\Recognition\AwardNominationVersion;
use App\Models\Recognition\AwardPresentation;
use App\Models\Recognition\AwardWindow;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Audit\Audit;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AwardNominations
{
    private function grade(User $actor, int $year, int $grade, bool $write = true): void
    {
        $a = app(RecognitionAuthority::class);
        $a->staff($actor, 'awards', $write);
        $a->currentYear($year);
        GradeLevel::findOrFail($grade);
        abort_unless($a->manages($actor, 'awards', $write) || app(AcademicAuthority::class)->leads($actor, 'grade_head', $grade, $year), 403);
    }

    public function catalogue(User $actor, int $year, int $grade): array
    {
        $this->grade($actor, $year, $grade, false);
        $a = app(RecognitionAuthority::class);
        $today = $a->today();
        $items = [];
        foreach (AwardWindow::where('session_year_id', $year)->where('status', 'open')->whereDate('opens_on', '<=', $today)->whereDate('closes_on', '>=', $today)->orderBy('id')->get() as $window) {
            $category = AwardCategory::where('active', true)->find($window->award_category_id);
            if ($category === null || ($category->scope_kind === 'grade' && (int) $category->grade_level_id !== $grade)) {
                continue;
            }
            try {
                $a->lineage($category);
            } catch (HttpException $e) {
                continue;
            }
            $items[] = ['category_id' => (int) $category->id, 'name' => $category->name, 'opens_on' => $window->opens_on->toDateString(), 'closes_on' => $window->closes_on->toDateString(), 'status' => $window->status, 'definitions' => AwardDefinition::where('award_category_id', $category->id)->where('active', true)->orderBy('name')->get()->map(fn ($d) => ['id' => (int) $d->id, 'name' => $d->name])->all()];
        }

        return ['items' => $items];
    }

    public function nominate(User $actor, array $input): AwardNomination
    {
        app(RecognitionAuthority::class)->staff($actor, 'awards', true);
        $d = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'session_year_id' => ['required', 'integer', 'min:1'], 'grade_level_id' => ['required', 'integer', 'min:1'], 'learner_id' => ['required', 'integer', 'min:1'], 'award_definition_id' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'max:4000']])->validate();
        foreach (['session_year_id', 'grade_level_id', 'learner_id', 'award_definition_id'] as $key) {
            $d[$key] = (int) $d[$key];
        }
        $authority = function () use ($actor, $d): void {
            $this->grade($actor, $d['session_year_id'], $d['grade_level_id']);
            $a = app(RecognitionAuthority::class);
            $place = $a->placement($d['learner_id'], $d['session_year_id']);
            abort_unless((int) $place->homeroom->class->grade_level_id === $d['grade_level_id'], 404);
            $definition = AwardDefinition::where('active', true)->findOrFail($d['award_definition_id']);
            $category = AwardCategory::findOrFail($definition->award_category_id);
            $a->eligible($category, Students::findOrFail($d['learner_id']), $place);
            $this->openWindow((int) $category->id, $d['session_year_id']);
        };

        return app(RecognitionOperations::class)->once($actor, 'award.nominate', $d, $authority, function () use ($actor, $d) {
            $definition = AwardDefinition::findOrFail($d['award_definition_id']);
            $window = $this->openWindow((int) $definition->award_category_id, $d['session_year_id']);
            abort_if(AwardNomination::where('learner_id', $d['learner_id'])->where('session_year_id', $d['session_year_id'])->where('award_definition_id', $definition->id)->whereIn('state', ['pending', 'approved'])->exists(), 409, 'This learner already has an active nomination for this award.');
            $row = AwardNomination::create(array_diff_key($d, ['operation_id' => true]) + ['award_category_id' => $definition->award_category_id, 'award_window_id' => $window->id, 'nominated_by' => $actor->id, 'state' => 'pending', 'scope_kind' => 'grade', 'scope_id' => $d['grade_level_id'], 'definition_revision' => $definition->revision, 'definition_name' => $definition->name, 'definition_criteria' => $definition->criteria, 'definition_frequency' => $definition->frequency, 'revision' => 1]);
            $this->version($actor, $row);

            return $row;
        }, fn (int $id) => AwardNomination::findOrFail($id));
    }

    private function openWindow(int $category, int $year): AwardWindow
    {
        $today = app(RecognitionAuthority::class)->today();

        return AwardWindow::where('award_category_id', $category)->where('session_year_id', $year)->where('status', 'open')->whereDate('opens_on', '<=', $today)->whereDate('closes_on', '>=', $today)->firstOrFail();
    }

    public function review(User $actor, int $id, array $input): AwardNomination
    {
        $a = app(RecognitionAuthority::class);
        $a->staff($actor, 'awards', true);
        $row = AwardNomination::findOrFail($id);
        $authority = function () use ($a, $actor, $row): void {
            $a->staff($actor, 'awards', true);
            abort_unless($a->categoryAdmin($actor, AwardCategory::findOrFail($row->award_category_id), true), 403);
            if (app(RecognitionCatalog::class)->settings()->principal_approval_required) {
                abort_unless(app(AcademicAuthority::class)->currentLeadership($actor, 'principal', 0), 403, 'This school requires a current principal to approve awards.');
            }
        };
        $authority();
        $d = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'decision' => ['required', 'in:approved,rejected'], 'reason' => ['required', 'string', 'max:4000']])->validate();
        $d['revision'] = (int) $d['revision'];
        $d['nomination_id'] = $id;

        return app(RecognitionOperations::class)->once($actor, 'award.review', $d, $authority, function () use ($id, $d, $actor, $a) {
            $row = AwardNomination::lockForUpdate()->findOrFail($id);
            app(RecognitionCatalog::class)->revision($row, $d);
            abort_unless($row->state === 'pending', 409, 'Only a pending nomination can be reviewed.');
            if ($d['decision'] === 'approved') {
                $a->currentYear((int) $row->session_year_id);
                $place = $a->placement((int) $row->learner_id, (int) $row->session_year_id);
                abort_unless((int) $place->homeroom->class->grade_level_id === (int) $row->grade_level_id, 409, 'The learner placement changed.');
                $definition = AwardDefinition::where('active', true)->findOrFail($row->award_definition_id);
                abort_unless((int) $definition->revision === (int) $row->definition_revision, 409, 'The award definition changed. Review a new nomination against its current criteria.');
                $a->eligible(AwardCategory::findOrFail($row->award_category_id), Students::findOrFail($row->learner_id), $place);
            }
            $row->update(['state' => $d['decision'], 'reviewed_by' => $actor->id, 'review_reason' => $d['reason'], 'revision' => $row->revision + 1]);
            $this->version($actor, $row);

            return $row;
        }, fn (int $id) => AwardNomination::findOrFail($id));
    }

    public function withdraw(User $actor, int $id, array $input): AwardNomination
    {
        $a = app(RecognitionAuthority::class);
        $a->staff($actor, 'awards', true);
        $row = AwardNomination::findOrFail($id);
        $authority = function () use ($a, $actor, $row): void {
            $a->staff($actor, 'awards', true);
            abort_unless((int) $row->nominated_by === (int) $actor->id || $a->categoryAdmin($actor, AwardCategory::findOrFail($row->award_category_id), true), 403);
            if ((int) $row->nominated_by === (int) $actor->id) {
                $this->grade($actor, (int) $row->session_year_id, (int) $row->grade_level_id);
            }
        };
        $authority();
        $d = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'max:4000']])->validate();
        $d['revision'] = (int) $d['revision'];
        $d['nomination_id'] = $id;

        return app(RecognitionOperations::class)->once($actor, 'award.withdraw', $d, $authority, function () use ($id, $d, $actor) {
            $row = AwardNomination::lockForUpdate()->findOrFail($id);
            app(RecognitionCatalog::class)->revision($row, $d);
            abort_unless(in_array($row->state, ['pending', 'approved'], true), 409);
            abort_if(AwardPresentation::where('award_nomination_id', $id)->exists(), 409, 'A presented award needs a retained correction.');
            $row->update(['state' => 'withdrawn', 'reviewed_by' => $actor->id, 'review_reason' => $d['reason'], 'revision' => $row->revision + 1]);
            $this->version($actor, $row);

            return $row;
        }, fn (int $id) => AwardNomination::findOrFail($id));
    }

    private function version(User $actor, AwardNomination $row): void
    {
        AwardNominationVersion::create(['award_nomination_id' => $row->id, 'actor_id' => $actor->id, 'revision' => $row->revision, 'snapshot' => array_replace($row->getAttributes(), ['reason' => $row->reason, 'review_reason' => $row->review_reason])]);
        Audit::record('recognition.award_nomination_changed', $row, [], ['revision' => $row->revision, 'state' => $row->state]);
    }
}
