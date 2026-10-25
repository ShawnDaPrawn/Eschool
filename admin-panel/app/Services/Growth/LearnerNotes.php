<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\Growth\LearnerNote;
use App\Models\Growth\LearnerNoteAssignment;
use App\Models\Growth\LearnerNoteRevision;
use App\Models\Platform\School;
use App\Models\User;
use App\Policies\Growth\LearnerNotesPolicy;
use App\Services\Pastoral\SensitiveRecordsAccess;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/** No body is loaded until fresh role, tenant and domain assignment checks succeed. */
class LearnerNotes
{
    private const META = ['id', 'school_id', 'learner_id', 'subject_id', 'session_year_id', 'author_id', 'visibility', 'revision', 'archived_at', 'created_at', 'updated_at'];

    public function create(User $actor, int $learnerId, ?int $subjectId, int $yearId, string $visibility, string $body): array
    {
        $policy = app(LearnerNotesPolicy::class);
        $policy->scope($actor, $learnerId, $subjectId, $yearId, $visibility);
        $body = $this->body($body);

        return DB::transaction(function () use ($actor, $learnerId, $subjectId, $yearId, $visibility, $body, $policy) {
            School::query()->lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $policy->year($yearId, true);
            $fresh = $policy->scope($actor, $learnerId, $subjectId, $yearId, $visibility);
            $note = LearnerNote::query()->create(['learner_id' => $learnerId, 'subject_id' => $subjectId, 'session_year_id' => $yearId,
                'author_id' => $fresh->id, 'visibility' => $visibility, 'body' => $body]);
            $this->revision($note, $fresh);
            Audit::record('growth.note_created', $note, [], ['revision' => $note->revision, 'author_id' => (int) $fresh->id]);

            return $this->data($note);
        });
    }

    public function authorize(User $actor, int $id, bool $write = false): void
    {
        $meta = $this->metadata($actor, $id);
        if ($write) {
            app(LearnerNotesPolicy::class)->year((int) $meta->session_year_id, true);
            abort_unless((int) $meta->author_id === (int) $actor->id, 403);
        }
    }

    public function read(User $actor, int $id): array
    {
        $meta = $this->metadata($actor, $id);
        $note = LearnerNote::query()->findOrFail($meta->id);
        Audit::record('growth.note_read', $meta, [], ['reader_id' => (int) $actor->id]);

        return $this->data($note);
    }

    public function listing(User $actor, int $learnerId, ?int $subjectId, int $yearId, string $visibility, int $after = 0): array
    {
        app(LearnerNotesPolicy::class)->scope($actor, $learnerId, $subjectId, $yearId, $visibility);
        $notes = LearnerNote::query()->where('learner_id', $learnerId)->where('session_year_id', $yearId)->where('visibility', $visibility)
            ->when($subjectId !== null || $visibility !== 'counselling', fn ($q) => $q->where('subject_id', $subjectId))->whereNull('archived_at')->where('id', '>', max(0, $after))->orderBy('id')->limit(101)->get(self::META);
        // List metadata only. Explicit detail reads apply policy before body load.
        $more = $notes->count() > 100;
        $rows = $notes->take(100)->map(fn ($n) => $this->data($n, false))->all();

        return ['notes' => $rows, 'next_after' => $more ? (int) $notes->take(100)->last()->id : null];
    }

    public function update(User $actor, int $id, int $expectedRevision, string $body, bool $archive = false): array
    {
        $meta = $this->metadata($actor, $id);
        abort_unless((int) $meta->author_id === (int) $actor->id, 403);
        $body = $this->body($body);

        return DB::transaction(function () use ($actor, $id, $expectedRevision, $body, $archive) {
            School::query()->lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $meta = $this->metadata($actor, $id);
            app(LearnerNotesPolicy::class)->year((int) $meta->session_year_id, true);
            abort_unless((int) $meta->author_id === (int) $actor->id, 403);
            abort_if($meta->archived_at !== null, 409, 'This note is archived.');
            $note = LearnerNote::query()->lockForUpdate()->findOrFail($id);
            abort_unless($note->revision === $expectedRevision, 409, 'This note changed. Reload and compare before saving.');
            $note->update(['body' => $body, 'archived_at' => $archive ? now() : null, 'revision' => $note->revision + 1]);
            $this->revision($note, $actor);
            Audit::record($archive ? 'growth.note_archived' : 'growth.note_updated', $note, [], ['revision' => $note->revision, 'author_id' => (int) $actor->id]);

            return $this->data($note);
        });
    }

    public function history(User $actor, int $id): array
    {
        $meta = $this->metadata($actor, $id);

        Audit::record('growth.note_history_read', $meta, [], ['reader_id' => (int) $actor->id]);

        return LearnerNoteRevision::query()->where('learner_note_id', $meta->id)->orderByDesc('revision')->limit(100)
            ->get()->map(fn ($r) => ['revision' => $r->revision, 'author_id' => (int) $r->author_id, 'author_name' => $this->authorName((int) $r->author_id), 'body' => $r->body,
                'archived' => $r->archived, 'created_at' => $r->created_at->toISOString()])->all();
    }

    public function assignments(User $actor, int $learnerId): array
    {
        app(LearnerNotesPolicy::class)->assign($actor, $learnerId);

        return LearnerNoteAssignment::query()->where('learner_id', $learnerId)->orderBy('id')->limit(100)->get()
            ->map(fn ($r) => $this->assignmentData($r))->all();
    }

    public function assign(User $actor, int $learnerId, int $staffId, array $input): array
    {
        app(LearnerNotesPolicy::class)->assign($actor, $learnerId);
        $data = Validator::make($input, ['starts_on' => ['required', 'date_format:Y-m-d'], 'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'expected_revision' => ['required', 'integer', 'min:0']])->validate();

        return DB::transaction(function () use ($actor, $learnerId, $staffId, $data) {
            School::query()->lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            app(LearnerNotesPolicy::class)->assign($actor, $learnerId);
            $staff = User::query()->findOrFail($staffId);
            app(SensitiveRecordsAccess::class)->actor($staff);
            $row = LearnerNoteAssignment::query()->where('learner_id', $learnerId)->where('staff_user_id', $staffId)->lockForUpdate()->first();
            abort_unless((int) ($row?->revision ?? 0) === (int) $data['expected_revision'], 409, 'This assignment changed. Reload before saving.');
            $row ??= new LearnerNoteAssignment(['learner_id' => $learnerId, 'staff_user_id' => $staffId]);
            $before = $row->exists ? $this->assignmentData($row) : [];
            $row->fill(['assigned_by' => $actor->id, 'starts_on' => $data['starts_on'], 'ends_on' => $data['ends_on'], 'revoked_at' => null, 'revision' => (int) ($row->revision ?? 0) + 1])->save();

            Audit::record('growth.note_assignment_changed', $row, array_diff_key($before, ['staff_name' => true]), array_diff_key($this->assignmentData($row), ['staff_name' => true]));

            return $this->assignmentData($row);
        });
    }

    public function revokeAssignment(User $actor, int $learnerId, int $staffId, int $expectedRevision): array
    {
        app(LearnerNotesPolicy::class)->assign($actor, $learnerId);

        return DB::transaction(function () use ($actor, $learnerId, $staffId, $expectedRevision) {
            School::query()->lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            app(LearnerNotesPolicy::class)->assign($actor, $learnerId);
            $row = LearnerNoteAssignment::query()->where('learner_id', $learnerId)->where('staff_user_id', $staffId)->lockForUpdate()->firstOrFail();
            abort_unless($row->revision === $expectedRevision, 409, 'This assignment changed. Reload before ending access.');
            abort_if($row->revoked_at !== null, 409, 'This note access has already ended.');
            $before = $this->assignmentData($row);
            $row->update(['revoked_at' => now(), 'revision' => $row->revision + 1]);
            Audit::record('growth.note_assignment_revoked', $row, array_diff_key($before, ['staff_name' => true]), array_diff_key($this->assignmentData($row), ['staff_name' => true]));

            return $this->assignmentData($row);
        });
    }

    private function assignmentData(LearnerNoteAssignment $row): array
    {
        return ['id' => (int) $row->id, 'learner_id' => (int) $row->learner_id, 'staff_user_id' => (int) $row->staff_user_id, 'staff_name' => $this->authorName((int) $row->staff_user_id),
            'starts_on' => $row->starts_on->toDateString(), 'ends_on' => $row->ends_on->toDateString(), 'revision' => $row->revision, 'revoked' => $row->revoked_at !== null];
    }

    private function metadata(User $actor, int $id): LearnerNote
    {
        app(LearnerNotesPolicy::class)->enabled();
        $meta = LearnerNote::query()->findOrFail($id, self::META);
        app(LearnerNotesPolicy::class)->scope($actor, (int) $meta->learner_id, $meta->subject_id === null ? null : (int) $meta->subject_id,
            (int) $meta->session_year_id, $meta->visibility);

        return $meta;
    }

    private function body(string $body): string
    {
        $data = Validator::make(['body' => $body], ['body' => ['required', 'string', 'max:20000']])->validate();
        abort_if(trim($data['body']) === '', 422, 'Write a note before saving.');

        return $data['body'];
    }

    private function revision(LearnerNote $note, User $actor): void
    {
        LearnerNoteRevision::query()->create(['learner_note_id' => $note->id, 'author_id' => $actor->id, 'revision' => $note->revision,
            'body' => $note->body, 'archived' => $note->archived_at !== null, 'created_at' => now()]);
    }

    private function authorName(int $id): string
    {
        $user = User::withTrashed()->find($id);

        return $user === null ? __('Archived staff member') : trim($user->first_name.' '.$user->last_name);
    }

    private function data(LearnerNote $note, bool $body = true): array
    {
        $data = ['id' => (int) $note->id, 'learner_id' => (int) $note->learner_id, 'subject_id' => $note->subject_id === null ? null : (int) $note->subject_id,
            'session_year_id' => (int) $note->session_year_id, 'author_id' => (int) $note->author_id, 'author_name' => $this->authorName((int) $note->author_id), 'visibility' => $note->visibility,
            'revision' => $note->revision, 'archived' => $note->archived_at !== null, 'updated_at' => $note->updated_at->toISOString()];
        if ($body) {
            $data['body'] = $note->body;
        }

        return $data;
    }
}
