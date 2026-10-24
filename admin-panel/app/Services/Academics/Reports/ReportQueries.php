<?php

declare(strict_types=1);

namespace App\Services\Academics\Reports;

use App\Models\Academics\HomeroomAllocation;
use App\Models\Academics\ReportCard;
use App\Models\Academics\ReportComment;
use App\Models\Academics\ReportCommentBank;
use App\Models\Academics\ReportTemplate;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\ClassSection;
use App\Models\People\Enrolment;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Policies\Academics\ReportPolicy;
use App\Services\Academics\AcademicAuthority;
use Carbon\CarbonImmutable;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ReportQueries
{
    public function configuration(User $actor, ?int $yearId = null): array
    {
        $authority = app(AcademicAuthority::class);
        $authority->staff($actor);
        $years = SessionYear::orderByDesc('default')->orderByDesc('id')->get();
        $year = $yearId === null ? $years->first() : $years->firstWhere('id', $yearId);
        abort_if($year === null, 404);
        $rooms = ClassSection::with('class.gradeLevel', 'section')->get()->filter(fn ($room) => $room->class?->grade_level_id !== null
            && ($authority->manages($actor) || app(ReportPolicy::class)->leads($actor, 'grade_head', (int) $room->class->grade_level_id)
                || app(ReportPolicy::class)->leads($actor, 'principal', (int) $room->class->grade_level_id)));

        return ['session_year_id' => (int) $year->id, 'school_years' => $years->map(fn ($y) => ['id' => (int) $y->id, 'name' => $y->name])->all(),
            'homerooms' => $rooms->map(fn ($room) => ['id' => (int) $room->id, 'grade_level_id' => (int) $room->class->grade_level_id, 'grade_order' => (int) $room->class->gradeLevel->sort_order, 'name' => trim($room->class->name.' '.$room->section->name)])->values()->all(),
            'terms' => Term::where('session_year_id', $year->id)->orderBy('start_date')->get()->map(fn ($term) => ['id' => (int) $term->id, 'name' => $term->name, 'grade_from' => $term->grade_from, 'grade_to' => $term->grade_to])->all(),
            'subjects' => Subject::orderBy('name')->get()->map(fn ($subject) => ['id' => (int) $subject->id, 'name' => $subject->name])->all(),
            'templates' => ReportTemplate::orderBy('name')->get()->map(fn ($t) => app(ReportDesigns::class)->data($t))->all(), 'can_design' => $authority->manages($actor),
            'comment_banks' => ReportCommentBank::orderBy('label')->get()->map(fn ($bank) => ['id' => (int) $bank->id, 'subject_id' => $bank->subject_id === null ? null : (int) $bank->subject_id, 'scope' => $bank->scope, 'label' => $bank->label, 'text' => $bank->text, 'active' => $bank->active, 'revision' => $bank->revision])->all()];
    }

    public function roster(User $actor, int $yearId, int $roomId, ?int $termId): array
    {
        $room = app(ReportPolicy::class)->scope($actor, $yearId, $roomId);
        $year = SessionYear::findOrFail($yearId);
        $term = $termId === null ? null : Term::where('session_year_id', $yearId)->forGrade((int) $room->class->gradeLevel->sort_order)->findOrFail($termId);
        abort_if(! $year->start_date || ! $year->end_date, 409, __('Set the school year dates before reporting.'));
        $start = CarbonImmutable::parse($term?->start_date ?? $year->start_date)->toDateString();
        $end = CarbonImmutable::parse($term?->end_date ?? $year->end_date)->toDateString();
        $ids = Enrolment::where('session_year_id', $yearId)->where('class_section_id', $roomId)->whereDate('starts_on', '<=', $end)
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $start))->pluck('learner_id');
        $learners = Students::with('user')->whereHas('user')->whereIn('id', $ids)->orderBy('id')->get();

        return ['session_year_id' => $yearId, 'term_id' => $termId, 'class_section_id' => $roomId, 'grade_level_id' => (int) $room->class->grade_level_id,
            'learners' => $learners->map(fn ($learner) => ['id' => (int) $learner->id, 'name' => trim($learner->user->first_name.' '.$learner->user->last_name)])->all()];
    }

    public function batches(User $actor, int $year, int $page = 1): array
    {
        app(AcademicAuthority::class)->staff($actor);
        SessionYear::findOrFail($year);
        $batches = app(ReportPolicy::class)->visibleBatches($actor)->where('session_year_id', $year)->orderByDesc('id')->paginate(50, ['*'], 'page', $page);

        return ['session_year_id' => $year, 'page' => $batches->currentPage(), 'last_page' => $batches->lastPage(), 'batches' => $batches->getCollection()->map(fn ($b) => ['id' => (int) $b->id, 'state' => $b->state, 'mode' => $b->mode, 'class_section_id' => (int) $b->class_section_id,
            'term_id' => $b->term_id === null ? null : (int) $b->term_id, 'learner_count' => count($b->selection['learner_ids']), 'created_at' => $b->created_at->toISOString(), 'released_at' => $b->released_at?->toISOString()])->all()];
    }

    public function show(User $actor, int $id): array
    {
        $batch = app(ReportPolicy::class)->batch($actor, $id);
        $data = app(ReportBatches::class)->data($batch);
        $authority = app(AcademicAuthority::class);
        $data['can_grade_sign'] = app(ReportPolicy::class)->leads($actor, 'grade_head', (int) $batch->grade_level_id);
        $data['can_principal_sign'] = app(ReportPolicy::class)->leads($actor, 'principal', (int) $batch->grade_level_id);
        foreach ($data['cards'] as &$card) {
            $card['comments'] = ReportComment::where('report_card_id', $card['id'])->orderBy('scope_key')->get()->map(fn ($c) => ['scope_key' => $c->scope_key, 'text' => $c->text, 'revision' => $c->revision])->all();
            $card['download_url'] = $card['pdf_ready'] ? app(ReportFamily::class)->url($actor, ReportCard::findOrFail($card['id']), true) : null;
        }

        return $data;
    }

    /** Only the teacher's own comment scopes, without another subject's marks. */
    public function comments(User $actor, int $year, int $page = 1): array
    {
        $authority = app(AcademicAuthority::class);
        $teachers = $authority->teachers($actor);
        SessionYear::findOrFail($year);
        $today = $authority->today();
        $rooms = HomeroomAllocation::where('session_year_id', $year)->whereIn('teacher_id', $teachers)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->pluck('class_section_id');
        $groups = TeachingAllocation::whereIn('teacher_id', $teachers)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)
            ->whereHas('group', fn ($q) => $q->where('active', true)->where('session_year_id', $year))->pluck('teaching_group_id');
        $learnerIds = TeachingGroupMembership::whereIn('teaching_group_id', $groups)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->pluck('learner_id');
        $cards = ReportCard::with('batch')->whereHas('batch', fn ($q) => $q->where('session_year_id', $year)->where('state', '!=', 'released'))
            ->where(fn ($q) => $q->whereIn('learner_id', $learnerIds)->orWhereHas('batch', fn ($b) => $b->whereIn('class_section_id', $rooms)))
            ->orderByDesc('id')->paginate(50, ['*'], 'page', $page);
        $rows = [];
        foreach ($cards as $card) {
            $payload = $card->payload;
            foreach ([null, ...array_column($payload['subjects'], 'subject_id')] as $subject) {
                try {
                    if (app(ReportPolicy::class)->comment($actor, $card, $subject)) {
                        // This list is allocated work only. Leadership corrections
                        // belong to batch review, where a reason is available.
                        continue;
                    }
                } catch (HttpExceptionInterface $error) {
                    if ($error->getStatusCode() === 403) {
                        continue;
                    } throw $error;
                }
                $scope = $subject === null ? 'homeroom' : 'subject:'.$subject;
                $comment = ReportComment::where('report_card_id', $card->id)->where('scope_key', $scope)->first();
                $label = $subject === null ? __('Homeroom') : collect($payload['subjects'])->firstWhere('subject_id', $subject)['subject_name'];
                $rows[] = ['card_id' => (int) $card->id, 'batch_id' => (int) $card->report_batch_id, 'batch_revision' => $card->batch->revision, 'learner_id' => (int) $card->learner_id,
                    'learner_name' => $payload['learner_name'], 'period_name' => $payload['year_name'].' '.($payload['term_name'] ?? __('Annual report')),
                    'subject_id' => $subject, 'scope_label' => $label, 'revision' => $comment?->revision ?? 0, 'text' => $comment?->text ?? ''];
            }
        }

        return ['session_year_id' => $year, 'page' => $cards->currentPage(), 'last_page' => $cards->lastPage(), 'rows' => $rows];
    }
}
