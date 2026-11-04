<?php

declare(strict_types=1);

namespace App\Services\Passport;

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\PeerTutoring\ServiceHour;
use App\Models\People\Enrolment;
use App\Models\Recognition\AwardPresentation;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Transfers\TransferReceipt;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Identity\LearnerIdentifiers;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Transfers\TransferVault;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * The learner passport v1 (Action Plan 4.10): a read-only record of a learner
 * across the schools they attended, headed by their LURITS number.
 * Specification: engineering/specs/people/learner-passport.md.
 *
 * Where each part comes from:
 * - each school's own awards (permanent presentations, badges included),
 *   verified peer tutoring service minutes and activity group memberships;
 * - results carried in a signed transfer package the school received
 *   (reports and released marks, after the signature is checked);
 * - reading: an empty seam until the reading library (Action Plan 4.4) lands.
 *
 * Who sees which schools:
 * - the learner and their approving guardians: every NavigateEd school the
 *   learner attended, found by the learner's own account (never by LURITS);
 *   these are the learner's own records;
 * - staff: this school only, i.e. what it holds plus what arrived in a signed
 *   package addressed to it. A staff view never switches to, or reads across,
 *   another school.
 *
 * Never included, by construction (only the columns below are read): health
 * and clinical records, counselling and wellbeing, discipline, support plans,
 * safeguarding, house points and their private reasons, private reasons and
 * notes of any kind (award withdrawals, memberships, tutoring corrections),
 * documents from transfer packages, guardians' names, other learners, staff
 * names and the learner's date of birth.
 */
class LearnerPassport
{
    /** Bounds per school, so one learner's passport stays a page. */
    private const LIMIT = 300;

    public function __construct(private readonly PassportAuthority $authority) {}

    public function forLearner(User $actor, int $learnerId): array
    {
        return $this->build($this->authority->resolve($actor, $learnerId));
    }

    public function mine(User $actor): array
    {
        return $this->forLearner($actor, $this->authority->ownLearnerId($actor));
    }

    /**
     * The panel's finder: this school's learners by name only. There is no
     * search by LURITS number, so the passport is never a lookup tool.
     *
     * @return list<array{id: int, name: string, admission_no: string}>
     */
    public function learners(User $actor, string $name): array
    {
        $this->authority->staff($actor);
        $name = trim($name);
        if (mb_strlen($name) < 2) {
            return [];
        }
        $like = '%'.addcslashes($name, '\\%_').'%';

        return Students::query()->with('user')
            ->whereHas('user', fn ($q) => $q->where(fn ($u) => $u->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)))
            ->orderBy('id')->limit(25)->get()
            ->map(fn (Students $row) => ['id' => (int) $row->id, 'name' => (string) $row->user?->full_name, 'admission_no' => (string) $row->admission_no])->all();
    }

    /**
     * @param  array{actor: User, learner: Students, viewer: 'learner'|'guardian'|'staff'}  $access
     */
    public function build(array $access): array
    {
        $schools = app(CurrentSchool::class);
        $here = $schools->id();
        $learner = $access['learner'];
        $viewer = $access['viewer'];
        $places = $viewer === 'staff' ? [$learner] : $this->placesOf((int) $learner->user_id);
        $sections = [];
        $transfers = [];
        foreach ($places as $place) {
            $read = fn () => $this->place($place, (int) $place->school_id === $here);
            $part = (int) $place->school_id === $here ? $read() : $schools->run((int) $place->school_id, $read);
            $transfers = [...$transfers, ...$part['transfers']];
            unset($part['transfers']);
            $sections[] = $part;
        }
        // Reading another school's records must never leave the request there.
        abort_unless($schools->id() === $here && ! $schools->isAcrossSchools(), 403);
        usort($sections, fn (array $a, array $b) => [$a['current'], $a['attended_from'] ?? '9999'] <=> [$b['current'], $b['attended_from'] ?? '9999']);
        usort($transfers, fn (array $a, array $b) => $a['starts_on'] <=> $b['starts_on']);

        return [
            'learner' => ['name' => (string) $learner->user?->full_name, 'primary_identifier' => app(LearnerIdentifiers::class)->forLearner($access['actor'], $learner)],
            'viewer' => $viewer,
            'scope' => $viewer === 'staff' ? 'this_school' : 'all_schools',
            'schools' => $sections,
            'transfers' => $transfers,
            'reading' => $this->reading($learner),
            'export' => $viewer === 'learner' ? app(PassportExports::class)->availability($access['actor']) : null,
        ];
    }

    /** An empty, clearly unavailable section until a reading source is bound. */
    private function reading(Students $learner): array
    {
        $source = app(ReadingRecords::class);

        return ['available' => ! $source instanceof NoReadingRecords, 'items' => $source->forLearner($learner)];
    }

    /**
     * The learner's rows at every school, by their own account: the one
     * deliberate read across schools, only ever for the learner and their
     * approving guardians.
     *
     * @return list<Students>
     */
    private function placesOf(int $userId): array
    {
        return Students::acrossSchools()->where('user_id', $userId)->orderBy('id')->get(['id', 'school_id', 'user_id'])->all();
    }

    /** What one school holds, read inside that school. */
    private function place(Students $place, bool $current): array
    {
        $id = (int) $place->id;
        $gate = app(ModuleGate::class);
        $enrolments = Enrolment::query()->where('learner_id', $id)->get(['starts_on', 'ends_on']);
        $today = app(AcademicAuthority::class)->today();
        $open = $enrolments->contains(fn ($row) => $row->ends_on === null || $row->ends_on->toDateString() >= $today);

        return [
            'name' => (string) app(CurrentSchool::class)->school()?->name,
            'current' => $current,
            'attended_from' => $enrolments->min(fn ($row) => $row->starts_on->toDateString()),
            'attended_to' => $open ? null : $enrolments->max(fn ($row) => $row->ends_on?->toDateString()),
            'awards' => $gate->allows('awards') ? $this->awards($id) : [],
            'activities' => $gate->allows('groups') ? $this->activities($id) : [],
            'service' => $gate->allows('peer_tutoring') ? $this->service($id) : null,
            'transfers' => $this->transfers($id),
        ];
    }

    private function awards(int $learnerId): array
    {
        return AwardPresentation::query()->where('learner_id', $learnerId)->where('state', 'active')
            ->orderByDesc('presented_on')->orderByDesc('id')->limit(self::LIMIT)
            ->get(['title', 'criteria', 'presented_on', 'event', 'outside_organisation', 'source_kind'])
            ->map(fn (AwardPresentation $row) => ['title' => (string) $row->title, 'criteria' => (string) $row->criteria, 'presented_on' => $row->presented_on->toDateString(),
                'event' => $row->event, 'outside_organisation' => $row->outside_organisation, 'kind' => in_array($row->source_kind, ['catalogue', 'outside', 'badge'], true) ? $row->source_kind : 'catalogue'])
            ->all();
    }

    private function activities(int $learnerId): array
    {
        $memberships = ActivityMembership::query()->where('learner_id', $learnerId)->where('active', true)
            ->orderByDesc('starts_on')->limit(self::LIMIT)->get(['activity_group_id', 'role', 'starts_on', 'ends_on']);
        $groups = ActivityGroup::query()->whereIn('id', $memberships->pluck('activity_group_id'))->get(['id', 'session_year_id', 'name', 'discipline', 'season'])->keyBy('id');
        $years = SessionYear::query()->whereIn('id', $groups->pluck('session_year_id'))->pluck('name', 'id');
        $rows = [];
        foreach ($memberships as $membership) {
            $group = $groups->get($membership->activity_group_id);
            if ($group === null) {
                continue; // a removed group is not part of the record
            }
            $rows[] = ['name' => (string) $group->name, 'discipline' => (string) $group->discipline, 'season' => (string) $group->season,
                'school_year' => isset($years[$group->session_year_id]) ? (string) $years[$group->session_year_id] : null,
                'role' => $membership->role === 'captain' ? 'captain' : 'member',
                'starts_on' => $membership->starts_on->toDateString(), 'ends_on' => $membership->ends_on->toDateString()];
        }

        return $rows;
    }

    /** Verified supervised tutoring time, net of corrections; never the partner or notes. */
    private function service(int $learnerId): ?array
    {
        $rows = ServiceHour::query()->where('learner_id', $learnerId)->get(['kind', 'minutes']);
        $minutes = (int) $rows->sum('minutes');
        $sessions = $rows->where('kind', 'earned')->count() - $rows->where('kind', 'reversed')->count();

        return $minutes > 0 || $sessions > 0 ? ['minutes' => max(0, $minutes), 'sessions' => max(0, $sessions)] : null;
    }

    /**
     * Results that arrived with a signed transfer addressed to this school.
     * A package whose signature or addressing does not verify is left out.
     */
    private function transfers(int $learnerId): array
    {
        $rows = [];
        foreach (TransferReceipt::query()->where('learner_id', $learnerId)->orderBy('id')->get() as $receipt) {
            try {
                app(TransferVault::class)->verifyReceipt($receipt);
            } catch (HttpExceptionInterface) {
                continue;
            }
            $c = $receipt->contents;
            $rows[] = [
                'from_school_name' => (string) ($c['from_school_name'] ?? ''),
                'to_school_name' => (string) ($c['to_school_name'] ?? ''),
                'last_attended_on' => (string) ($c['last_attended_on'] ?? ''),
                'starts_on' => (string) ($c['starts_on'] ?? ''),
                'reports' => array_map(fn (array $r) => ['year_name' => (string) ($r['year_name'] ?? ''), 'term_name' => (string) ($r['term_name'] ?? '')], array_values((array) ($c['reports'] ?? []))),
                'results' => array_map(fn (array $m) => [
                    'subject_name' => (string) ($m['subject_name'] ?? ''),
                    'mode' => ($m['mode'] ?? null) === 'running' ? 'running' : 'official',
                    'percentage_bp' => isset($m['percentage_bp']) ? (int) $m['percentage_bp'] : null,
                    'rating_level' => isset($m['rating_level']) ? (int) $m['rating_level'] : null,
                    'released_on' => isset($m['released_at']) ? substr((string) $m['released_at'], 0, 10) : null,
                ], array_values((array) ($c['released_marks'] ?? []))),
            ];
        }

        return $rows;
    }
}
