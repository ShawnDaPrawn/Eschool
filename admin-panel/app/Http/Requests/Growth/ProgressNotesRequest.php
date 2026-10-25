<?php

declare(strict_types=1);

namespace App\Http\Requests\Growth;

use App\Models\Academics\TeachingGroupMembership;
use App\Policies\Growth\LearnerNotesPolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\ReleasedMarks;
use App\Services\Growth\LearnerNotes;
use App\Services\Growth\ProgressQueries;
use App\Services\Pastoral\SensitiveRecordsAccess;
use App\Services\Platform\Identity\ApprovalService;
use Illuminate\Foundation\Http\FormRequest;

class ProgressNotesRequest extends FormRequest
{
    public function operation(): string
    {
        return substr((string) $this->route()->getName(), strlen('api.v2.progress.'));
    }

    public function authorize(): bool
    {
        abort_if(! $this->isMethodSafe() && $this->query->count() > 0, 422, 'Invalid save request. Reload and try again.');
        $policy = app(LearnerNotesPolicy::class);
        $write = ! $this->isMethodSafe();
        $policy->enabled($write);
        $op = $this->operation();
        if (in_array($op, ['family', 'periods'], true)) {
            $learner = app(ReleasedMarks::class)->learner($this->user(), (int) $this->route('learner'));
            abort_unless(app(ApprovalService::class)->approved($learner), 403, 'Guardian approval required.');
        } elseif ($op === 'assignment-learners') {
            $policy->principal($this->user());
        } elseif ($op === 'caseload') {
            app(SensitiveRecordsAccess::class)->actor($this->user());
        } elseif (in_array($op, ['roster', 'staff'], true)) {
            $group = app(ProgressQueries::class)->group($this->user(), (int) $this->route('group'));
            if ($op === 'staff') {
                app(LearnerNotesPolicy::class)->academic($this->user(), (int) $this->route('learner'), (int) $group->subject_id, (int) $group->session_year_id);
                abort_unless(TeachingGroupMembership::query()->where('teaching_group_id', $group->id)->where('learner_id', (int) $this->route('learner'))->exists(), 404);
            }
        } elseif (in_array($op, ['note', 'history', 'update'], true)) {
            app(LearnerNotes::class)->authorize($this->user(), (int) $this->route('note'), $write);
        } elseif (in_array($op, ['assignments', 'assign', 'revoke-assignment'], true)) {
            $policy->assign($this->user(), (int) $this->route('learner'));
        } elseif (in_array($op, ['notes', 'create'], true)) {
            app(AcademicAuthority::class)->staff($this->user());
            $subject = $this->input('subject_id');
            $year = $this->input('session_year_id');
            $visibility = $this->input('visibility');
            $policy->year(is_scalar($year) && (int) $year > 0 ? (int) $year : (int) schoolSetting('session_year'), $write);
            $policy->scope($this->user(), (int) $this->route('learner'), is_scalar($subject) && (int) $subject > 0 ? (int) $subject : null,
                is_scalar($year) && (int) $year > 0 ? (int) $year : (int) schoolSetting('session_year'), is_string($visibility) ? $visibility : 'staff');
        } else {
            app(AcademicAuthority::class)->staff($this->user());
            if ($op === 'configure') {
                app(AcademicAuthority::class)->manage($this->user());
            }
        }

        return true;
    }

    public function rules(): array
    {
        $id = ['required', 'integer', 'min:1'];
        $op = $this->operation();
        if (in_array($op, ['notes', 'create'], true)) {
            return ['session_year_id' => $id, 'subject_id' => ['sometimes', 'nullable', 'integer', 'min:1'], 'visibility' => ['required', 'in:tutor,staff,counselling']] +
                ($op === 'create' ? ['body' => ['required', 'string', 'max:20000'], 'operation_id' => ['required', 'uuid']] : ['after' => ['sometimes', 'integer', 'min:0']]);
        }
        if ($op === 'update') {
            return ['body' => ['required', 'string', 'max:20000'], 'expected_revision' => ['required', 'integer', 'min:1'],
                'archive' => ['sometimes', 'boolean'], 'operation_id' => ['required', 'uuid'],
                'visibility' => ['prohibited'], 'learner_id' => ['prohibited'], 'subject_id' => ['prohibited'],
                'session_year_id' => ['prohibited'], 'author_id' => ['prohibited']];
        }
        if ($op === 'revoke-assignment') {
            return ['expected_revision' => ['required', 'integer', 'min:1'], 'operation_id' => ['required', 'uuid']];
        }
        if ($op === 'assign') {
            return ['starts_on' => ['required', 'date_format:Y-m-d'], 'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
                'expected_revision' => ['required', 'integer', 'min:0'], 'operation_id' => ['required', 'uuid']];
        }
        if ($op === 'configure') {
            return ['high_bp' => ['required', 'integer', 'between:1,10000'], 'secure_bp' => ['required', 'integer', 'between:0,9999', 'lt:high_bp'],
                'minimum_coverage_bp' => ['required', 'integer', 'between:1,10000'], 'expected_revision' => ['required', 'integer', 'min:0'], 'operation_id' => ['required', 'uuid']];
        }
        if ($op === 'family') {
            return ['session_year_id' => $id, 'term_id' => ['sometimes', 'nullable', 'integer', 'min:1']];
        }
        if ($op === 'staff') {
            return ['term_id' => $id];
        }
        if (in_array($op, ['roster', 'caseload', 'assignment-learners'], true)) {
            return ['page' => ['sometimes', 'integer', 'min:1']];
        }

        return [];
    }
}
