<?php

declare(strict_types=1);

namespace App\Services\Platform\Saas;

use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\School;
use App\Models\Platform\SchoolAccountEvent;
use App\Models\Platform\SchoolContract;
use App\Models\Platform\SchoolJoinInvitation;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\StaffInvitation;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * A school's onboarding checklist in HQ (Action Plan SA.6), from live data:
 * contract, first administrator, homerooms, learners with LURITS numbers,
 * guardians linked and approved, staff invited and signed in, modules on,
 * and training sessions held. Counts only, never a person: HQ staff do not
 * see a child's personal information. Each step says what is blocking it.
 * engineering/specs/platform/hq-onboarding.md.
 */
class PlatformOnboarding
{
    public const DONE = 'done';

    public const STARTED = 'started';

    public const TODO = 'todo';

    public const LATER = 'later';

    /** Share of learners with an approving guardian before that step counts as done. */
    public const APPROVAL_TARGET = 80;

    public function __construct(private readonly CurrentSchool $currentSchool) {}

    /**
     * @return array{steps: list<array{key: string, title: string, status: string, detail: string, next: string|null}>, done: int, total: int}
     */
    public function checklist(int $schoolId): array
    {
        School::query()->findOrFail($schoolId);
        $steps = $this->currentSchool->run($schoolId, fn (): array => [
            $this->contract(),
            $this->administrator(),
            $this->sendingDomain(),
            $this->homerooms(),
            $this->learners(),
            $this->guardians(),
            $this->staff(),
            $this->modules(),
            $this->training(),
        ]);
        $counted = array_filter($steps, fn (array $step) => $step['status'] !== self::LATER);

        return ['steps' => $steps, 'done' => count(array_filter($counted, fn (array $step) => $step['status'] === self::DONE)), 'total' => count($counted)];
    }

    public static function trainingRules(): array
    {
        return [
            'held_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after:2020-01-01'],
            'topic' => ['required', 'string', 'min:3', 'max:200'],
            'attendees' => ['nullable', 'integer', 'between:1,1000'],
        ];
    }

    /** Records a training session on the school's HQ timeline. */
    public function recordTraining(int $schoolId, array $input, User $actor): void
    {
        $data = Validator::make($input, self::trainingRules())->validate();
        School::query()->findOrFail($schoolId);
        $body = ($data['attendees'] ?? null) !== null && $data['attendees'] !== ''
            ? __('Training on :date: :topic (:count people)', ['date' => $data['held_on'], 'topic' => $data['topic'], 'count' => $data['attendees']])
            : __('Training on :date: :topic', ['date' => $data['held_on'], 'topic' => $data['topic']]);
        $this->currentSchool->run($schoolId, fn () => DB::transaction(function () use ($body, $actor, $data) {
            $event = SchoolAccountEvent::query()->create(['kind' => 'training', 'body' => $body, 'actor_id' => $actor->id]);
            Audit::record('platform_school_training_recorded', $event, [], ['held_on' => $data['held_on']]);
        }));
    }

    private function contract(): array
    {
        $today = now()->toDateString();
        $current = SchoolContract::query()->whereDate('starts_on', '<=', $today)
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $today))->exists();
        $any = SchoolContract::query()->exists();

        return $this->step('contract', 'Contract and operator agreement', $current ? self::DONE : ($any ? self::STARTED : self::TODO),
            $current ? 'A current contract with a signed operator agreement is recorded.' : ($any ? 'The recorded contract is not current.' : 'No contract recorded.'),
            $current ? null : 'Record the contract on the school record.');
    }

    private function administrator(): array
    {
        $invitations = StaffInvitation::query()->where('purpose', StaffInvitation::SCHOOL_ADMIN);
        // A first administrator who already had an account joins instead (Action Plan 1.3).
        $joins = SchoolJoinInvitation::query()->where('purpose', SchoolJoinInvitation::SCHOOL_ADMIN);
        if ((clone $invitations)->whereNotNull('accepted_at')->exists()) {
            return $this->step('administrator', 'First administrator signed in', self::DONE, 'The first administrator accepted their invitation and chose a password.', null);
        }
        if ((clone $joins)->whereNotNull('accepted_at')->exists()) {
            return $this->step('administrator', 'First administrator signed in', self::DONE, 'The first administrator accepted their invitation with their existing account.', null);
        }
        $waiting = (clone $invitations)->whereNull('used_at')->whereNull('accepted_at')->where('expires_at', '>', now())->exists()
            || (clone $joins)->waiting()->exists();

        return $this->step('administrator', 'First administrator signed in', $waiting ? self::STARTED : self::TODO,
            $waiting ? 'The invitation is sent and not accepted yet.' : 'No invitation is waiting: it expired or was never sent.',
            'Send the invitation again from the school page.');
    }

    private function sendingDomain(): array
    {
        // Branded email from the school's own domain arrives with Action Plan 2.2.
        return $this->step('sending_domain', 'Email from the school\'s own domain', self::LATER, 'Available when branded email is released.', null);
    }

    private function homerooms(): array
    {
        $rooms = ClassSection::query()->count();
        $graded = ClassSection::query()->whereIn('class_id', ClassSchool::query()->whereNotNull('grade_level_id')->select('id'))->count();

        return $this->step('homerooms', 'Homerooms set up', $rooms === 0 ? self::TODO : ($graded === $rooms ? self::DONE : self::STARTED),
            __(':rooms homerooms, :graded with a grade.', ['rooms' => $rooms, 'graded' => $graded]),
            $rooms === 0 ? 'Import the homerooms (Import school data).' : ($graded < $rooms ? 'Set the grade of every homeroom\'s class.' : null));
    }

    private function learners(): array
    {
        $learners = Students::query()->count();
        $lurits = DB::table('learner_identifiers')->whereNotNull('lurits_hash')
            ->whereIn('user_id', Students::query()->select('user_id'))->count();

        return $this->step('learners', 'Learners imported with LURITS numbers', $learners === 0 ? self::TODO : ($lurits === $learners ? self::DONE : self::STARTED),
            __(':learners learners, :lurits with a LURITS number.', ['learners' => $learners, 'lurits' => $lurits]),
            $learners === 0 ? 'Import the learners (Import school data).' : ($lurits < $learners ? 'Add the missing LURITS numbers, for example from the SA-SAMS learner list.' : null));
    }

    private function guardians(): array
    {
        $learners = Students::query()->count();
        $linked = GuardianLink::query()->where('status', 'active')->where('can_approve', true)
            ->whereIn('learner_id', Students::query()->select('id'))->distinct()->count('learner_id');
        $approved = app(ApprovalService::class)->approvedLearnerCount();
        $share = $learners === 0 ? 0 : intdiv($approved * 100, $learners);
        $status = $learners === 0 || $linked === 0 ? self::TODO : ($share >= self::APPROVAL_TARGET ? self::DONE : self::STARTED);

        return $this->step('guardians', 'Guardians linked and approving', $status,
            __(':linked of :learners learners have a guardian who can approve; :approved (:share%) are approved.', ['linked' => $linked, 'learners' => $learners, 'approved' => $approved, 'share' => $share]),
            $status === self::DONE ? null : ($linked < $learners ? 'Link a guardian with an email address to every learner, then invite the guardians.' : 'Remind guardians to accept their invitations and approve their children.'));
    }

    private function staff(): array
    {
        $staffIds = SchoolMembership::query()->where('school_id', $this->currentSchool->id())->active()
            ->whereNotIn('role', ['Student', 'Parent'])->distinct()->pluck('user_id');
        $staff = $staffIds->count();
        $invited = StaffInvitation::query()->whereIn('user_id', $staffIds)->where('purpose', StaffInvitation::STAFF)->distinct()->count('user_id');
        $accepted = StaffInvitation::query()->whereIn('user_id', $staffIds)->whereNotNull('accepted_at')->distinct()->count('user_id');
        $status = $staff <= 1 ? self::TODO : ($accepted === $staff ? self::DONE : self::STARTED);

        return $this->step('staff', 'Staff invited and signed in', $status,
            __(':staff staff with a role; :invited invited, :accepted signed in.', ['staff' => $staff, 'invited' => $invited, 'accepted' => $accepted]),
            $status === self::DONE ? null : ($staff <= 1 ? 'Import the staff with their roles (Import school data).' : 'Invite the staff who have not signed in yet.'));
    }

    private function modules(): array
    {
        $gate = app(ModuleGate::class);
        $gate->forget();
        $on = count(array_filter(array_keys((array) config('modules')), fn (string $key) => $gate->allows($key)));

        return $this->step('modules', 'Modules switched on', $on > 0 ? self::DONE : self::TODO,
            __(':count modules on.', ['count' => $on]), $on > 0 ? null : 'Switch on the modules the school has agreed to start with.');
    }

    private function training(): array
    {
        $sessions = SchoolAccountEvent::query()->where('kind', 'training')->count();

        return $this->step('training', 'Training sessions held', $sessions > 0 ? self::DONE : self::TODO,
            __(':count training sessions recorded.', ['count' => $sessions]), $sessions > 0 ? null : 'Record each training session below.');
    }

    /** @return array{key: string, title: string, status: string, detail: string, next: string|null} */
    private function step(string $key, string $title, string $status, string $detail, ?string $next): array
    {
        return ['key' => $key, 'title' => __($title), 'status' => $status, 'detail' => __($detail), 'next' => $next === null ? null : __($next)];
    }
}
