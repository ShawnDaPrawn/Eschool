<?php

declare(strict_types=1);

namespace App\Services\Learning\Library;

use App\Models\Library\LibraryFramework;
use App\Models\Library\LibraryItemVersion;
use App\Models\Library\LibraryReviewer;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Saas\PlatformStaffAccess;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Library reviewers and their subjects (SA.16). Content staff assign a
 * platform staff member whose role holds library.review to one framework's
 * subject code; only an assigned reviewer may approve or reject a version of
 * an item in that subject, and never a version they wrote or submitted.
 */
class LibraryReviewers
{
    public static function assignRules(): array
    {
        return ['user_id' => ['required', 'integer', 'min:1'], 'library_framework_id' => ['required', 'integer', 'min:1'], 'subject_code' => ['required', 'string', 'max:32']];
    }

    public function assign(User $actor, array $input): LibraryReviewer
    {
        $actor = app(LibraryAccess::class)->actor($actor, LibraryAccess::MANAGE);
        $data = Validator::make($input, self::assignRules())->validate();
        $framework = LibraryFramework::query()->find($data['library_framework_id']);
        if ($framework === null) {
            throw ValidationException::withMessages(['library_framework_id' => 'Choose a library framework.']);
        }
        if (! in_array($data['subject_code'], app(LibraryCatalogue::class)->subjectCodes($framework), true)) {
            throw ValidationException::withMessages(['subject_code' => 'Choose a subject code from the '.$framework->code.' subject catalogue.']);
        }
        $reviewer = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->find($data['user_id']));
        if ($reviewer === null || ! PlatformStaffIdentity::query()->where('user_id', $reviewer->id)->where('active', true)->exists()
            || ! app(PlatformStaffAccess::class)->can($reviewer, LibraryAccess::REVIEW)) {
            throw ValidationException::withMessages(['user_id' => 'Choose platform staff whose role reviews library content.']);
        }

        return DB::transaction(function () use ($actor, $framework, $reviewer, $data) {
            $row = LibraryReviewer::query()->firstOrCreate(['user_id' => $reviewer->id, 'library_framework_id' => $framework->id, 'subject_code' => $data['subject_code']],
                ['assigned_by' => $actor->id]);
            if ($row->wasRecentlyCreated) {
                Audit::record('library_reviewer_assigned', $row, [], ['user_id' => $reviewer->id, 'framework' => $framework->code, 'subject_code' => $row->subject_code, 'changed_by' => $actor->id]);
            }

            return $row;
        });
    }

    public function remove(User $actor, int $assignmentId): void
    {
        $actor = app(LibraryAccess::class)->actor($actor, LibraryAccess::MANAGE);
        DB::transaction(function () use ($actor, $assignmentId) {
            $row = LibraryReviewer::query()->lockForUpdate()->findOrFail($assignmentId);
            $before = ['user_id' => $row->user_id, 'library_framework_id' => $row->library_framework_id, 'subject_code' => $row->subject_code];
            $row->delete();
            Audit::record('library_reviewer_removed', null, $before, ['assignment_id' => $assignmentId, 'changed_by' => $actor->id]);
        });
    }

    /** Whether this reviewer is assigned to the subject of the version's item. */
    public function assigned(User $reviewer, LibraryItemVersion $version): bool
    {
        $topic = $version->item->topic;

        return LibraryReviewer::query()->where('user_id', $reviewer->id)->where('library_framework_id', $topic->library_framework_id)
            ->where('subject_code', $topic->subject_code)->exists();
    }

    /** Why this reviewer may not decide this version, or null when they may. */
    public function refusal(User $reviewer, LibraryItemVersion $version): ?string
    {
        if (! app(LibraryAccess::class)->can($reviewer, LibraryAccess::REVIEW)) {
            return 'Your role does not review library content.';
        }
        if (! $this->assigned($reviewer, $version)) {
            return 'You are not a reviewer for this subject.';
        }
        if ((int) $version->created_by === (int) $reviewer->id || (int) $version->submitted_by === (int) $reviewer->id) {
            return 'Someone else must review a version you wrote or submitted.';
        }

        return null;
    }

    /** Platform staff who could be assigned: active, with a role that reviews. */
    public function candidates(): array
    {
        return app(CurrentSchool::class)->acrossSchools(function (): array {
            $ids = PlatformStaffIdentity::query()->where('active', true)->pluck('user_id');

            return User::query()->whereIn('id', $ids)->where('status', 1)->orderBy('first_name')->orderBy('last_name')->limit(500)->get()
                ->filter(fn (User $user) => app(PlatformStaffAccess::class)->can($user, LibraryAccess::REVIEW))
                ->mapWithKeys(fn (User $user) => [(int) $user->id => trim($user->first_name.' '.$user->last_name)])->all();
        });
    }
}
