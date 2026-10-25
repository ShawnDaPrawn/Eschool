<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Policies\Growth\LearnerNotesPolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Request;

/** Runs before historical-year draft reflection as well as normal validation. */
class ProgressPanelScope
{
    public function authorize(Request $r): bool
    {
        abort_if(! $r->isMethodSafe() && $r->query->count() > 0, 422, 'Invalid save request. Reload and try again.');
        $configuration = app(ProgressQueries::class)->configuration($r->user());
        $op = substr((string) $r->route()->getName(), strlen('learner-notes.'));
        if (! $r->isMethodSafe()) {
            abort_unless((int) $r->input('expected_account_id') === (int) $r->user()->id
                && (int) $r->input('expected_school_id') === app(CurrentSchool::class)->id(), 403);
        }
        if ($op === 'configure') {
            app(AcademicAuthority::class)->manage($r->user());

            return true;
        }
        if (in_array($op, ['assign', 'revoke-assignment'], true) || $r->input('mode') === 'assignments') {
            $policy = app(LearnerNotesPolicy::class);
            $policy->principal($r->user());
            $learner = $r->route('learner') ?? $r->input('learner_id');
            if (is_scalar($learner) && (int) $learner > 0) {
                $policy->assign($r->user(), (int) $learner);
            }

            return true;
        }
        $noteId = $r->route('note') ?? $r->input('note');
        if (is_scalar($noteId) && (int) $noteId > 0) {
            app(LearnerNotes::class)->authorize($r->user(), (int) $noteId, ! $r->isMethodSafe());
        }
        $learnerId = $r->route('learner') ?? $r->input('learner_id');
        if (! is_scalar($learnerId) || (int) $learnerId < 1) {
            return true;
        }
        if (in_array($op, ['assign', 'revoke-assignment'], true) || $r->input('mode') === 'assignments') {
            app(LearnerNotesPolicy::class)->assign($r->user(), (int) $learnerId);

            return true;
        }
        $clinical = $r->input('mode') === 'counselling';
        $subject = $r->input('subject_id');
        $year = $r->input('session_year_id');
        $visibility = $clinical ? 'counselling' : $r->input('visibility', 'staff');
        app(LearnerNotesPolicy::class)->scope($r->user(), (int) $learnerId,
            is_scalar($subject) && (int) $subject > 0 ? (int) $subject : null,
            is_scalar($year) && (int) $year > 0 ? (int) $year : $configuration['session_year_id'],
            is_string($visibility) ? $visibility : 'staff');

        return true;
    }
}
