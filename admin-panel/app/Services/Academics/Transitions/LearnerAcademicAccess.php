<?php

declare(strict_types=1);

namespace App\Services\Academics\Transitions;

use App\Models\Academics\AcademicTransition;
use App\Models\People\Enrolment;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Identity\ApprovalService;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/** Dated learner access; an affiliation role never overrides a declared departure. */
class LearnerAcademicAccess
{
    public function hasChanges(int $learner): bool
    {
        return AcademicTransition::where('learner_id', $learner)->whereIn('kind', ['join', 'leave'])->exists();
    }

    public function allows(Students $learner, ?string $date = null): bool
    {
        if (! $this->hasChanges((int) $learner->id)) {
            return true; // Existing installations retain their session/approval contract.
        }
        $date ??= app(AcademicAuthority::class)->today();
        $latest = AcademicTransition::where('learner_id', $learner->id)->whereIn('kind', ['join', 'leave'])
            ->whereDate('effective_on', '<=', $date)->orderByDesc('effective_on')->orderByDesc('id')->first();
        if ($latest?->kind === 'leave' && $latest->effective_on->toDateString() < $date) {
            return false;
        }

        return Enrolment::where('learner_id', $learner->id)->onDate($date)->exists();
    }

    /** Departure closes learner entry, not a current guardian's retained records. */
    public function departed(Students $learner, ?string $date = null): bool
    {
        $date ??= app(AcademicAuthority::class)->today();
        $latest = AcademicTransition::where('learner_id', $learner->id)->whereIn('kind', ['join', 'leave'])
            ->whereDate('effective_on', '<=', $date)->orderByDesc('effective_on')->orderByDesc('id')->first();

        return $latest?->kind === 'leave' && $latest->effective_on->toDateString() < $date;
    }

    public function assertUser(User $user): void
    {
        $learner = Students::where('user_id', $user->id)->first();
        if ($learner !== null) {
            abort_unless($this->allows($learner), 403, __('Learner access has ended for this school. Contact the school office.'));
        }
    }

    /** A student app token/session must not turn into another profile after leaving. */
    public function assertRequest(Request $request): void
    {
        $user = $request->user();
        if ($user === null || ! $user->hasRole('Student')) {
            return;
        }
        $token = $user->currentAccessToken();
        $namedStudent = $token instanceof PersonalAccessToken && $token->name === 'student-app';
        $portalStudent = $request->hasSession() && $request->session()->get('portal_app') === 'student-app';
        $onlyStudent = $user->getRoleNames()->diff(['Student'])->isEmpty();
        if ($namedStudent || $portalStudent || $onlyStudent) {
            $this->assertUser($user);
        }
    }

    /** Projection only: request-time date checks remain authoritative. */
    public function refreshProjection(int $learnerId, ?int $yearId = null): void
    {
        if (! $this->hasChanges($learnerId)) {
            return;
        }
        $learner = Students::findOrFail($learnerId);
        $today = app(AcademicAuthority::class)->today();
        $approved = app(ApprovalService::class)->approved($learner);
        $allowed = $this->allows($learner, $today) && $approved;
        $sessions = StudentSessions::where('student_id', $learnerId)->when($yearId !== null, fn ($q) => $q->where('session_year_id', $yearId))->get();
        foreach ($sessions as $session) {
            $active = $allowed && Enrolment::where('learner_id', $learnerId)->where('session_year_id', $session->session_year_id)->onDate($today)->exists();
            StudentSessions::withoutEvents(fn () => $session->forceFill(['status' => $active ? 1 : 0])->save());
        }
    }
}
