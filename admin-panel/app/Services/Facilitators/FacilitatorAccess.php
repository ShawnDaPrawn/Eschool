<?php

declare(strict_types=1);

namespace App\Services\Facilitators;

use App\Models\Platform\School;
use App\Models\User;
use App\Policies\Growth\LearnerNotesPolicy;
use App\Policies\People\ProfilePolicy;
use App\Services\Consultations\ConsultationAccess;
use App\Services\Discipline\DisciplineQueries;
use App\Services\Growth\RiskQueries;
use App\Services\Learning\PracticeChoices;
use App\Services\ParentPolicyService;
use App\Services\Pastoral\SensitiveRecordsAccess;
use App\Services\Pastoral\SupportAuthority;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Identity\PortalSession;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\StaffPrivateNotices;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Recognition\RecognitionAuthority;
use App\Services\Wellbeing\WellbeingAccess;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class FacilitatorAccess
{
    public const ROLES = ['Teacher', 'Facilitator', 'Counsellor', 'Safeguarding'];

    public function actor(User $actor): User
    {
        abort_unless(app(CurrentSchool::class)->school()?->isActive(), 403);
        $policy = app(ProfilePolicy::class);
        $fresh = $policy->actor($actor);
        abort_unless(array_intersect($policy->roles($fresh), self::ROLES) !== [], 403);
        app(ApprovalService::class)->assertAccess($fresh);

        return $fresh;
    }

    public function ready(User $actor): User
    {
        $fresh = $this->actor($actor);
        abort_if($fresh->password_change_required || app(ParentPolicyService::class)->needsAcceptance($fresh), 403, 'Complete facilitator sign-in setup for this school.');

        return $fresh;
    }

    public function ownsSession(Request $request): bool
    {
        $token = $request->user()?->currentAccessToken();

        return ($token instanceof PersonalAccessToken && $token->name === AppTokens::FACILITATOR)
            || (PortalSession::isPortal($request) && $request->session()->get('portal_app') === AppTokens::FACILITATOR);
    }

    public function enforceProfile(Request $request): void
    {
        if ($this->ownsSession($request)) {
            if (PortalSession::isPortal($request)) {
                app(ModuleGate::class)->forget();
                abort_unless(app(ModuleGate::class)->allows('web_facilitator'), 404);
                abort_if(! $request->isMethodSafe() && ! app(ModuleGate::class)->writable('web_facilitator'), 403);
            }
            $this->ready($request->user());
        }
    }

    public function profile(User $actor): array
    {
        $fresh = $this->ready($actor);
        $school = app(CurrentSchool::class)->school();
        $schools = app(MembershipService::class)->active($fresh)->whereIn('role', self::ROLES)->pluck('school_id')->unique();
        $choices = School::whereIn('id', $schools)->orderBy('name')->get();
        app(ModuleGate::class)->forget();
        $roles = app(ProfilePolicy::class)->roles($fresh);
        $groups = array_intersect($roles, ['Teacher', 'Facilitator', 'Parent', 'Student']) !== [] || app(ProfilePolicy::class)->permission($fresh, 'activity-groups-manage');

        return ['user_id' => (int) $fresh->id, 'school_id' => (int) $school->id, 'name' => trim($fresh->first_name.' '.$fresh->last_name),
            'roles' => array_values(array_intersect(app(ProfilePolicy::class)->roles($fresh), self::ROLES)),
            'schools' => $choices->map(fn ($row) => ['id' => (int) $row->id, 'name' => $row->name, 'slug' => $row->slug])->all(),
            'features' => ['daily_practice' => app(PracticeChoices::class)->availableStaff($fresh), 'consultations' => $this->consultationsAvailable($fresh), 'private_notices' => app(StaffPrivateNotices::class)->available($fresh), 'discipline' => app(DisciplineQueries::class)->available($fresh), 'groups' => $groups && app(ModuleGate::class)->allows('groups'), 'messages' => app(ModuleGate::class)->allows('communication'), 'counselling' => app(WellbeingAccess::class)->professionalAvailable($fresh),
                'support_plans' => app(SupportAuthority::class)->available($fresh), 'learner_notes' => $this->notesAvailable($fresh), 'risk_interventions' => $this->riskAvailable($fresh), 'house_points' => $this->recognitionAvailable($fresh, 'house_points'), 'awards' => $this->recognitionAvailable($fresh, 'awards')]];
    }

    private function consultationsAvailable(User $actor): bool
    {
        try {
            app(ConsultationAccess::class)->actor($actor);

            return true;
        } catch (ModelNotFoundException) {
            return false;
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [403, 404], true)) {
                throw $error;
            }

            return false;
        }
    }

    private function recognitionAvailable(User $actor, string $module): bool
    {
        try {
            app(RecognitionAuthority::class)->staff($actor, $module);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException $error) {
            return false;
        }
    }

    private function notesAvailable(User $actor): bool
    {
        try {
            app(LearnerNotesPolicy::class)->enabled();
            app(SensitiveRecordsAccess::class)->actor($actor);

            return true;
        } catch (HttpException|ModelNotFoundException $error) {
            return false;
        }
    }

    private function riskAvailable(User $actor): bool
    {
        try {
            app(RiskQueries::class)->configuration($actor);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException $error) {
            return false;
        }
    }
}
