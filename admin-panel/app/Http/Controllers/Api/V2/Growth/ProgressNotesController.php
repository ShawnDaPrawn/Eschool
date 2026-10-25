<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Growth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Growth\ProgressNotesRequest;
use App\Policies\Growth\LearnerNotesPolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\ReleasedMarks;
use App\Services\Growth\LearnerNoteOperations;
use App\Services\Growth\LearnerNotes;
use App\Services\Growth\ProgressBands;
use App\Services\Growth\ProgressQueries;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class ProgressNotesController extends Controller
{
    public function configuration(ProgressNotesRequest $r): JsonResponse
    {
        return $this->response(app(ProgressQueries::class)->configuration($r->user()));
    }

    public function roster(ProgressNotesRequest $r, int $group): JsonResponse
    {
        return $this->response(app(ProgressQueries::class)->roster($r->user(), $group, (int) $r->validated('page', 1)));
    }

    public function assignmentLearners(ProgressNotesRequest $r): JsonResponse
    {
        return $this->response(app(ProgressQueries::class)->assignmentLearners($r->user(), (int) $r->validated('page', 1)));
    }

    public function caseload(ProgressNotesRequest $r): JsonResponse
    {
        return $this->response(app(ProgressQueries::class)->caseload($r->user(), (int) $r->validated('page', 1)));
    }

    public function notes(ProgressNotesRequest $r, int $learner): JsonResponse
    {
        return $this->response(app(LearnerNotes::class)->listing($r->user(), $learner, $this->subject($r), (int) $r->validated('session_year_id'),
            $r->validated('visibility'), (int) $r->validated('after', 0)));
    }

    public function create(ProgressNotesRequest $r, int $learner): JsonResponse
    {
        $data = $r->validated() + ['learner_id' => $learner];
        $authority = fn () => app(LearnerNotesPolicy::class)->scope($r->user(), $learner, $this->subject($r), (int) $data['session_year_id'], $data['visibility']);
        $result = app(LearnerNoteOperations::class)->once($r->user(), 'note.create', $data, $authority,
            fn () => app(LearnerNotes::class)->create($r->user(), $learner, $this->subject($r), (int) $data['session_year_id'], $data['visibility'], $data['body']));

        return $this->response($result);
    }

    public function note(ProgressNotesRequest $r, int $note): JsonResponse
    {
        return $this->response(app(LearnerNotes::class)->read($r->user(), $note));
    }

    public function history(ProgressNotesRequest $r, int $note): JsonResponse
    {
        return $this->response(['note_id' => $note, 'revisions' => app(LearnerNotes::class)->history($r->user(), $note)]);
    }

    public function update(ProgressNotesRequest $r, int $note): JsonResponse
    {
        $data = $r->validated() + ['note_id' => $note];
        $result = app(LearnerNoteOperations::class)->once($r->user(), 'note.update', $data,
            fn () => app(LearnerNotes::class)->authorize($r->user(), $note, true),
            fn () => app(LearnerNotes::class)->update($r->user(), $note, (int) $data['expected_revision'], $data['body'], (bool) ($data['archive'] ?? false)));

        return $this->response($result);
    }

    public function assignments(ProgressNotesRequest $r, int $learner): JsonResponse
    {
        return $this->response(['learner_id' => $learner, 'assignments' => app(LearnerNotes::class)->assignments($r->user(), $learner)]);
    }

    public function assign(ProgressNotesRequest $r, int $learner, int $staff): JsonResponse
    {
        $data = $r->validated() + ['learner_id' => $learner, 'staff_user_id' => $staff];
        $result = app(LearnerNoteOperations::class)->once($r->user(), 'note.assign', $data,
            fn () => app(LearnerNotesPolicy::class)->assign($r->user(), $learner),
            fn () => app(LearnerNotes::class)->assign($r->user(), $learner, $staff, $data));

        return $this->response($result);
    }

    public function configure(ProgressNotesRequest $r): JsonResponse
    {
        $data = $r->validated();
        $result = app(LearnerNoteOperations::class)->once($r->user(), 'bands.configure', $data,
            fn () => app(AcademicAuthority::class)->manage($r->user()), fn () => app(ProgressBands::class)->configure($r->user(), $data));

        return $this->response($result);
    }

    public function staff(ProgressNotesRequest $r, int $group, int $learner): JsonResponse
    {
        app(ProgressQueries::class)->group($r->user(), $group);

        return $this->response(app(ProgressBands::class)->staff($r->user(), $group, $learner, (int) $r->validated('term_id')));
    }

    public function periods(ProgressNotesRequest $r, int $learner): JsonResponse
    {
        return $this->response(app(ReleasedMarks::class)->periods($r->user(), $learner));
    }

    public function family(ProgressNotesRequest $r, int $learner): JsonResponse
    {
        $term = $r->validated('term_id');

        return $this->response(app(ProgressBands::class)->family($r->user(), $learner, (int) $r->validated('session_year_id'), $term === null ? null : (int) $term));
    }

    private function subject(ProgressNotesRequest $r): ?int
    {
        return $r->validated('subject_id') === null ? null : (int) $r->validated('subject_id');
    }

    private function response(array $data): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', $data);
    }
}
