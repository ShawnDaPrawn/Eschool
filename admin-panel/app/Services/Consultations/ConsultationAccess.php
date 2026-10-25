<?php

declare(strict_types=1);

namespace App\Services\Consultations;

use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingGroup;
use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityStaff;
use App\Models\ClassSection;
use App\Models\Communication\Audience;
use App\Models\Consultations\Consultation;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\SessionYear;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Activities\CommunityAuthority;
use App\Services\Communication\CommunicationRecipients;
use App\Services\Communication\RecipientAccess;
use App\Services\ParentPolicyService;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ConsultationAccess
{
    public function actor(User $actor, bool $write = false): User
    {
        $context = app(CurrentSchool::class);
        $context->school()?->refresh();
        abort_unless($context->isSet() && ! $context->isAcrossSchools() && ! $context->isImpersonating() && $context->school()?->isActive(), 403);
        abort_if(PlatformStaffIdentity::where('user_id', $actor->id)->where('active', true)->exists(), 403);
        $fresh = app(ProfilePolicy::class)->actor($actor);
        abort_if($fresh->password_change_required, 403);
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($write ? $gate->writable('consultations') : $gate->allows('consultations'), 403);
        app(ApprovalService::class)->assertAccess($fresh);

        return $fresh;
    }

    public function manager(User $actor, bool $write = false): User
    {
        $fresh = $this->actor($actor, $write);
        $roles = app(ProfilePolicy::class)->roles($fresh);
        abort_if(array_diff($roles, ['Parent', 'Student']) === [] || app(CommunityAuthority::class)->facilitatorOnly($fresh), 403);
        abort_unless(app(ProfilePolicy::class)->permission($fresh, 'consultations-manage'), 403);

        return $fresh;
    }

    public function year(int $id, bool $write = false): SessionYear
    {
        $year = SessionYear::findOrFail($id);
        abort_if($write && (int) schoolSetting('session_year') !== $id, 409, 'Switch to the current school year before changing a consultation.');

        return $year;
    }

    public function audience(array $input, int $year): array
    {
        $this->year($year);
        $kind = $input['kind'];
        $target = (int) ($input['target_id'] ?? 0);
        $definition = ['kind' => $kind, 'target_id' => $kind === 'school' ? null : $target, 'session_year_id' => $year,
            'parents' => (bool) ($input['parents'] ?? false), 'include_staff' => (bool) ($input['include_staff'] ?? false)];
        match ($kind) {
            'school' => null,
            'grade' => GradeLevel::findOrFail($target),
            'homeroom' => ClassSection::findOrFail($target),
            'group' => TeachingGroup::where('session_year_id', $year)->where('active', true)->findOrFail($target),
            'activity_group' => ActivityGroup::where('session_year_id', $year)->where('active', true)->findOrFail($target),
            'custom' => null,
            default => abort(422),
        };
        if ($kind === 'custom') {
            $list = Audience::where('active', true)->findOrFail($target);
            $definition['user_ids'] = array_values(array_map('intval', $list->definition['user_ids']));
            $definition['parents'] = false;
        }

        return $definition;
    }

    /** Current communication sources, never a saved eligibility snapshot. */
    public function recipients(Consultation $row): array
    {
        $today = app(AcademicAuthority::class)->today();
        $definition = $row->audience;
        $resolver = app(CommunicationRecipients::class);
        if ($definition['kind'] === 'custom') {
            $all = $resolver->standard(['kind' => 'school', 'session_year_id' => $row->session_year_id, 'parents' => false], $today);
            foreach ($resolver->standard(['kind' => 'school', 'session_year_id' => $row->session_year_id, 'parents' => true], $today) as $id => $reason) {
                if (! isset($all[$id])) {
                    $all[$id] = $reason;
                } else {
                    $all[$id]['guardian_of'] = array_values(array_unique([...$all[$id]['guardian_of'], ...$reason['guardian_of']]));
                }
            }

            return array_intersect_key($all, array_flip($definition['user_ids']));
        }
        $result = $resolver->standard($definition, $today);
        if ($definition['kind'] === 'activity_group' && ($definition['include_staff'] ?? false)) {
            $group = ActivityGroup::where('active', true)->where('session_year_id', $row->session_year_id)->findOrFail($definition['target_id']);
            $ids = app(CommunityAuthority::class)->descendants($group);
            $staff = ActivityStaff::whereIn('activity_group_id', $ids)->where('active', true)
                ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->pluck('user_id');
            foreach ($staff as $id) {
                $result[(int) $id] ??= ['direct' => false, 'staff' => true, 'learner_ids' => [], 'guardian_of' => []];
            }
        }

        return $result;
    }

    public function voter(User $actor, Consultation $row, bool $write = false): User
    {
        $fresh = $this->actor($actor, $write);
        $this->year((int) $row->session_year_id, $write);
        abort_if(in_array($row->status, ['draft', 'cancelled'], true), 404);
        $reason = $this->recipients($row)[(int) $fresh->id] ?? null;
        abort_unless($reason !== null, 404);
        $roles = app(ProfilePolicy::class)->roles($fresh);
        $reason['learner_ids'] = in_array('Student', $roles, true) ? ($reason['learner_ids'] ?? []) : [];
        $reason['guardian_of'] = in_array('Parent', $roles, true) ? ($reason['guardian_of'] ?? []) : [];
        $reason['staff'] = ($reason['staff'] ?? false) && array_diff($roles, ['Student', 'Parent']) !== [];
        $reason['direct'] = false;
        if ($reason['staff'] && app(CommunityAuthority::class)->facilitatorOnly($fresh)) {
            $allowed = $row->audience['kind'] === 'activity_group' && ($row->audience['include_staff'] ?? false);
            if ($allowed) {
                try {
                    app(CommunityAuthority::class)->groupStaff($fresh, (int) $row->audience['target_id']);
                } catch (ModelNotFoundException) {
                    $allowed = false;
                } catch (HttpExceptionInterface $error) {
                    if (! in_array($error->getStatusCode(), [403, 404], true)) {
                        throw $error;
                    }
                    $allowed = false;
                }
            }
            $reason['staff'] = $allowed;
        }
        abort_unless(app(RecipientAccess::class)->allows($fresh, $reason), 404);
        if ($reason['guardian_of'] !== [] && ! $reason['staff'] && $reason['learner_ids'] === []) {
            abort_if(app(ParentPolicyService::class)->needsAcceptance($fresh), 403);
        }

        return $fresh;
    }

    public function eligible(User $actor, Consultation $row): bool
    {
        try {
            $this->voter($actor, $row);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }
}
