<?php

declare(strict_types=1);

namespace App\Services\Platform\Identity;

use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\Transitions\LearnerAcademicAccess;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalService
{
    public function approved(Students $learner): bool
    {
        return $this->activeApprovals($learner)->exists();
    }

    public function state(Students $learner): array
    {
        $count = $this->activeApprovals($learner)->count();

        return [
            'status' => $count > 0 ? 'approved' : (LearnerApproval::query()->where('learner_id', $learner->id)->exists() ? 'withdrawn' : 'pending'),
            'approved_by_count' => $count,
        ];
    }

    public function assertAccess(User $user): void
    {
        if (! config('identity.approval_enforced') || ! $user->hasRole('Student')) {
            return;
        }
        $learner = Students::query()->where('user_id', $user->id)->first();
        abort_unless($learner !== null && $this->approved($learner), 403, 'Guardian approval required.');
    }

    public function decide(User $guardian, Students $learner, string $decision, string $version): array
    {
        abort_unless((int) $guardian->status === 1 && $guardian->email_verified_at !== null, 403);
        abort_unless(GuardianLink::query()->where('learner_id', $learner->id)
            ->where('guardian_user_id', $guardian->id)->where('status', 'active')->where('can_approve', true)->exists(), 404);
        if ($version !== config('identity.consent_version')) {
            throw ValidationException::withMessages(['consent_version' => 'Read and accept the current consent text.']);
        }

        return DB::transaction(function () use ($guardian, $learner, $decision, $version) {
            // Serialise approval and withdrawal for all guardians of this learner.
            Students::query()->whereKey($learner->id)->lockForUpdate()->firstOrFail();
            if ($decision === 'approve') {
                if (! LearnerApproval::query()->where('learner_id', $learner->id)->where('guardian_user_id', $guardian->id)->whereNull('withdrawn_at')->exists()) {
                    LearnerApproval::query()->create([
                        'learner_id' => $learner->id, 'guardian_user_id' => $guardian->id,
                        'consent_version' => $version, 'consented_at' => now(),
                    ]);
                }
            } else {
                // Any authorised guardian can stop access, even if another approved.
                LearnerApproval::query()->where('learner_id', $learner->id)->whereNull('withdrawn_at')->get()
                    ->each(fn (LearnerApproval $approval) => $approval->update(['withdrawn_at' => now()]));
                $user = $learner->user;
                if ($user !== null) {
                    $user->tokens()->delete();
                    DB::table('sessions')->where('user_id', $user->id)->delete();
                }
            }
            app(LearnerAcademicAccess::class)->refreshProjection((int) $learner->id);
            Audit::record('learner.approval.'.$decision, $learner, [], ['guardian_user_id' => $guardian->id, 'consent_version' => $version]);

            return $this->state($learner);
        });
    }

    /** How many of the school's learners hold a current guardian approval (for HQ onboarding). */
    public function approvedLearnerCount(): int
    {
        return $this->activeApprovalsQuery()->whereIn('learner_id', Students::query()->select('id'))->distinct()->count('learner_id');
    }

    /** @return Builder<LearnerApproval> */
    private function activeApprovals(Students $learner): Builder
    {
        return $this->activeApprovalsQuery()->where('learner_id', $learner->id);
    }

    /** @return Builder<LearnerApproval> */
    private function activeApprovalsQuery(): Builder
    {
        $schoolId = app(CurrentSchool::class)->id();

        return LearnerApproval::query()->whereNull('withdrawn_at')
            ->whereExists(fn ($query) => $query->selectRaw('1')->from('guardian_links')
                ->join('users', 'users.id', '=', 'guardian_links.guardian_user_id')
                ->whereColumn('guardian_links.guardian_user_id', 'learner_approvals.guardian_user_id')
                ->whereColumn('guardian_links.learner_id', 'learner_approvals.learner_id')
                ->where('guardian_links.school_id', $schoolId)->where('guardian_links.status', 'active')
                ->where('guardian_links.can_approve', true)->whereNotNull('users.email_verified_at')
                ->where('users.status', 1)->whereNull('users.deleted_at'));
    }
}
