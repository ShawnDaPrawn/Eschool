<?php

declare(strict_types=1);

namespace App\Services\Academics\Transitions;

use App\Models\Academics\AcademicExitPackage;
use App\Models\Academics\AcademicTransition;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\ReportCard;
use App\Models\Academics\TeachingGroup;
use App\Models\ClassSection;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Policies\Academics\TransitionPolicy;
use App\Services\Academics\AcademicAuthority;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class TransitionQueries
{
    public function configuration(User $actor, ?int $year = null): array
    {
        app(AcademicAuthority::class)->manage($actor);
        $years = SessionYear::orderByDesc('default')->orderByDesc('id')->get();
        $selected = $year === null ? $years->first() : $years->firstWhere('id', $year);
        abort_if($selected === null, 404);

        return ['session_year_id' => (int) $selected->id,
            'school_years' => $years->map(fn ($y) => ['id' => (int) $y->id, 'name' => $y->name, 'starts_on' => $y->start_date ? substr((string) $y->start_date, 0, 10) : null, 'ends_on' => $y->end_date ? substr((string) $y->end_date, 0, 10) : null])->all(),
            'grades' => GradeLevel::orderBy('sort_order')->get()->map(fn ($g) => ['id' => (int) $g->id, 'name' => $g->name, 'policy' => app(TransitionPolicies::class)->grade((int) $g->id)])->all(),
            'homerooms' => ClassSection::with('class', 'section')->get()->map(fn ($r) => ['id' => (int) $r->id, 'name' => trim($r->class?->name.' '.$r->section?->name), 'grade_level_id' => $r->class?->grade_level_id === null ? null : (int) $r->class->grade_level_id])->all(),
            'groups' => TeachingGroup::with('subject')->where('session_year_id', $selected->id)->where('active', true)->orderBy('name')->get()->map(fn ($g) => ['id' => (int) $g->id, 'name' => $g->name, 'subject_name' => $g->subject?->name ?? __('Archived subject'), 'grade_level_id' => (int) $g->grade_level_id, 'class_section_id' => $g->class_section_id === null ? null : (int) $g->class_section_id])->all(),
            'retention' => app(TransitionPolicies::class)->retention()];
    }

    public function search(User $actor, string $search, int $page = 1): array
    {
        app(AcademicAuthority::class)->manage($actor);
        $term = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($search));
        $rows = Students::with('user')->whereHas('user', function ($q) use ($term) {
            if ($term !== '') {
                $q->where(fn ($names) => $names->whereRaw("first_name LIKE ? ESCAPE '!'", ['%'.$term.'%'])->orWhereRaw("last_name LIKE ? ESCAPE '!'", ['%'.$term.'%']));
            }
        })->orderBy('id')->paginate(50, ['*'], 'page', $page);

        return ['page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'learners' => $rows->getCollection()->map(fn ($l) => ['id' => (int) $l->id, 'name' => trim($l->user->first_name.' '.$l->user->last_name), 'student_number' => $l->student_number])->all()];
    }

    public function learner(User $actor, int $id, int $page = 1): array
    {
        $learner = app(TransitionPolicy::class)->learner($actor, $id);
        $rows = AcademicTransition::where('learner_id', $id)->orderByDesc('id')->paginate(50, ['*'], 'page', $page);
        $canExport = true;
        try {
            app(TransitionPolicy::class)->export($actor, $id);
        } catch (HttpExceptionInterface $e) {
            if ($e->getStatusCode() !== 403) {
                throw $e;
            } $canExport = false;
        }
        $reports = $canExport ? ReportCard::with('batch')->where('learner_id', $id)->whereHas('batch', fn ($q) => $q->where('state', 'released')->whereColumn('report_batches.fingerprint', 'report_cards.generated_fingerprint'))->orderByDesc('id')->limit(201)->get() : collect();

        return ['id' => $id, 'name' => trim($learner->user?->first_name.' '.$learner->user?->last_name), 'student_number' => $learner->student_number, 'state' => app(TransitionContext::class)->state($id),
            'page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'transitions' => $rows->getCollection()->map(fn ($r) => app(TransitionFlow::class)->data($r))->all(),
            'can_export' => $canExport, 'report_choices_truncated' => $reports->count() > 200,
            'report_choices' => $reports->take(200)->map(fn ($r) => ['id' => (int) $r->id, 'year_name' => $r->payload['year_name'], 'term_name' => $r->payload['term_name'], 'released_at' => $r->batch->released_at->toISOString()])->all(),
            'document_choices' => $canExport ? app(ExitEvidence::class)->documents($actor, $learner) : [],
            'exports' => $canExport ? $this->packages($actor, $id) : []];
    }

    /** A revoked historical file must not block unrelated office changes. */
    private function packages(User $actor, int $learner): array
    {
        $data = [];
        foreach (AcademicExitPackage::where('learner_id', $learner)->where('requested_by', $actor->id)->orderByDesc('id')->limit(50)->get() as $row) {
            try {
                $data[] = app(ExitPackages::class)->data($actor, $row);
            } catch (HttpExceptionInterface|ModelNotFoundException $e) {
                if ($e instanceof HttpExceptionInterface && ! in_array($e->getStatusCode(), [403, 404], true)) {
                    throw $e;
                }
            }
        }

        return $data;
    }
}
