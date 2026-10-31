<?php

declare(strict_types=1);

namespace App\Services\Platform\Saas;

use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Navigate Ed's breach and incident register (Action Plan SA.12). Platform
 * staff record each security incident, the schools it affects and when each
 * school was told, whether the Information Regulator must be notified (POPIA
 * section 22; the schools are the responsible parties and Navigate Ed their
 * operator, section 21), what was done, and how it closed. An incident that
 * may involve personal information cannot close until every affected school
 * has been told and the Regulator decision is recorded.
 *
 * The register describes incidents, never the people in them: forms ask staff
 * not to write names, and the audit log records ids and states only, never
 * the free text. engineering/specs/platform/hq-incidents.md.
 */
class PlatformIncidents
{
    /** HQ works in South African time; rows are stored in the app's timezone. */
    public const TIMEZONE = 'Africa/Johannesburg';

    public const CATEGORIES = [
        'breach' => 'Personal information reached by someone not authorised',
        'security' => 'Security event, no personal information known to be reached',
        'availability' => 'Outage or loss of service',
        'integrity' => 'Data damaged or lost',
        'other' => 'Other',
    ];

    public const SEVERITIES = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'];

    public const STATUSES = ['open' => 'Open', 'contained' => 'Contained', 'closed' => 'Closed'];

    public const PERSONAL = ['yes' => 'Yes', 'unknown' => 'Not known yet', 'no' => 'No'];

    public const REGULATOR = ['required' => 'Required', 'not_required' => 'Not required'];

    public const NOTIFIED_HOW = ['email' => 'Email', 'phone' => 'Phone call', 'meeting' => 'Meeting', 'letter' => 'Letter'];

    public const TIMELINE_PER_PAGE = 100;

    public const ENTRY_KINDS = ['note' => 'Note', 'action' => 'Action taken'];

    public function __construct(private readonly CurrentSchool $currentSchool) {}

    public static function openRules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:191'],
            'category' => ['required', Rule::in(array_keys(self::CATEGORIES))],
            'severity' => ['required', Rule::in(array_keys(self::SEVERITIES))],
            'personal_information' => ['required', Rule::in(array_keys(self::PERSONAL))],
            'detected_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'occurred_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'summary' => ['required', 'string', 'min:10', 'max:4000'],
            'schools' => ['nullable', 'array', 'max:500'],
            'schools.*' => ['integer', 'distinct', 'exists:schools,id'],
        ];
    }

    public static function updateRules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:191'],
            'category' => ['required', Rule::in(array_keys(self::CATEGORIES))],
            'severity' => ['required', Rule::in(array_keys(self::SEVERITIES))],
            'status' => ['required', Rule::in(array_keys(self::STATUSES))],
            'personal_information' => ['required', Rule::in(array_keys(self::PERSONAL))],
            'occurred_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'summary' => ['required', 'string', 'min:10', 'max:4000'],
            'regulator' => ['nullable', Rule::in(array_keys(self::REGULATOR))],
            'regulator_reason' => ['nullable', 'required_with:regulator', 'string', 'min:5', 'max:500'],
            'regulator_notified_on' => ['nullable', 'date_format:Y-m-d'],
            'regulator_reference' => ['nullable', 'string', 'max:64'],
            'closure_summary' => ['nullable', 'required_if:status,closed', 'string', 'min:10', 'max:4000'],
            'revision' => ['required', 'integer', 'min:1'],
        ];
    }

    public static function schoolRules(): array
    {
        return ['school_id' => ['required', 'integer', 'exists:schools,id']];
    }

    public static function notifiedRules(): array
    {
        return [
            'notified_on' => ['required', 'date_format:Y-m-d'],
            'notified_how' => ['required', Rule::in(array_keys(self::NOTIFIED_HOW))],
            'notified_contact' => ['required', 'string', 'min:2', 'max:191'],
        ];
    }

    public static function entryRules(): array
    {
        return ['kind' => ['required', Rule::in(array_keys(self::ENTRY_KINDS))], 'body' => ['required', 'string', 'min:3', 'max:1000']];
    }

    /**
     * Open incidents first (oldest detection first), then the latest closed.
     *
     * @return array{open: list<array<string, mixed>>, closed: list<array<string, mixed>>}
     */
    public function list(): array
    {
        return $this->currentSchool->acrossSchools(function (): array {
            $open = DB::table('platform_incidents')->where('status', '!=', 'closed')->orderBy('detected_at')->limit(200)->get();
            $closed = DB::table('platform_incidents')->where('status', 'closed')->orderByDesc('closed_at')->limit(50)->get();
            $schools = DB::table('platform_incident_schools')->whereIn('incident_id', $open->pluck('id')->merge($closed->pluck('id')))
                ->get(['incident_id', 'notified_on'])->groupBy('incident_id');

            $present = fn (object $row): array => $this->summaryRow($row, $schools[$row->id] ?? collect());

            return ['open' => $open->map($present)->values()->all(), 'closed' => $closed->map($present)->values()->all()];
        });
    }

    /** One incident with its schools, timeline and what still stops it closing. */
    public function show(int $id, int $timelinePage = 1): array
    {
        return $this->currentSchool->acrossSchools(function () use ($id, $timelinePage): array {
            $row = DB::table('platform_incidents')->where('id', $id)->first();
            abort_if($row === null, 404);
            $schools = DB::table('platform_incident_schools')->join('schools', 'schools.id', '=', 'platform_incident_schools.school_id')
                ->where('incident_id', $id)->orderBy('schools.name')
                ->get(['platform_incident_schools.*', 'schools.name as school_name']);
            // The school's Information Officer and principal from HQ's own customer record (SA.4): business contacts only.
            $contacts = DB::table('school_contacts')->whereIn('school_id', $schools->pluck('school_id'))
                ->whereIn('role', ['information_officer', 'principal'])->orderByRaw("role = 'information_officer' desc")->orderBy('name')
                ->get(['school_id', 'role', 'name', 'email', 'phone'])->groupBy('school_id');
            // Newest first, a page at a time: the whole history stays reachable however long it grows.
            $entries = DB::table('platform_incident_entries')->where('incident_id', $id)->orderByDesc('created_at')->orderByDesc('id')
                ->paginate(self::TIMELINE_PER_PAGE, ['*'], 'timeline', max(1, $timelinePage))->withPath(route('saas.incidents.show', $id))->fragment('timeline');
            $actors = $this->actorNames(collect($entries->items())->pluck('actor_id')->push($row->opened_by, $row->closed_by)->merge($schools->pluck('notified_by')));

            return [
                'incident' => $this->summaryRow($row, $schools) + [
                    'summary' => (string) $row->summary, 'occurred_at' => $this->local($row->occurred_at),
                    'contained_at' => $this->local($row->contained_at), 'closed_at' => $this->local($row->closed_at),
                    'regulator' => $row->regulator, 'regulator_reason' => $row->regulator_reason,
                    'regulator_notified_on' => $row->regulator_notified_on, 'regulator_reference' => $row->regulator_reference,
                    'closure_summary' => $row->closure_summary, 'revision' => (int) $row->revision,
                    'opened_by' => $actors[$row->opened_by] ?? null, 'closed_by' => $actors[$row->closed_by] ?? null,
                ],
                'schools' => $schools->map(fn (object $school) => [
                    'id' => (int) $school->school_id, 'name' => (string) $school->school_name,
                    'notified_on' => $school->notified_on, 'notified_how' => $school->notified_how, 'notified_contact' => $school->notified_contact,
                    'notified_by' => $actors[$school->notified_by] ?? null,
                    'contacts' => ($contacts[$school->school_id] ?? collect())->map(fn (object $c) => (array) $c)->values()->all(),
                ])->values()->all(),
                'entries' => $entries->through(fn (object $entry) => [
                    'kind' => (string) $entry->kind, 'body' => (string) $entry->body,
                    'at' => $this->local($entry->created_at), 'actor' => $actors[$entry->actor_id] ?? null,
                ]),
                'blockers' => $this->blockers($row, $schools),
            ];
        });
    }

    /** @return array<int, string> school id => name, for the affected-school pickers */
    public function schoolChoices(): array
    {
        return $this->currentSchool->acrossSchools(fn (): array => DB::table('schools')->orderBy('name')->pluck('name', 'id')
            ->mapWithKeys(fn ($name, $id) => [(int) $id => (string) $name])->all());
    }

    public function open(array $input, User $actor): int
    {
        $data = Validator::make($input, self::openRules())->validate();
        $this->consistent($data);
        $detected = $this->parse($data['detected_at'], 'detected_at');
        $occurred = isset($data['occurred_at']) ? $this->parse($data['occurred_at'], 'occurred_at') : null;
        if ($occurred !== null && $occurred->greaterThan($detected)) {
            throw ValidationException::withMessages(['occurred_at' => __('An incident cannot happen after it was detected.')]);
        }
        $schoolIds = array_values(array_unique(array_map('intval', $data['schools'] ?? [])));

        return $this->currentSchool->acrossSchools(fn (): int => DB::transaction(function () use ($data, $detected, $occurred, $schoolIds, $actor): int {
            $id = (int) DB::table('platform_incidents')->insertGetId([
                'title' => $data['title'], 'category' => $data['category'], 'severity' => $data['severity'], 'status' => 'open',
                'personal_information' => $data['personal_information'], 'detected_at' => $detected, 'occurred_at' => $occurred,
                'summary' => $data['summary'], 'opened_by' => $actor->id, 'revision' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($schoolIds as $schoolId) {
                DB::table('platform_incident_schools')->insert(['incident_id' => $id, 'school_id' => $schoolId, 'created_at' => now(), 'updated_at' => now()]);
            }
            $this->entry($id, 'opened', trans_choice('{0} Incident recorded: :severity, no affected school known yet.|{1} Incident recorded: :severity, one affected school.|[2,*] Incident recorded: :severity, :count affected schools.', count($schoolIds), ['severity' => __(self::SEVERITIES[$data['severity']])]), $actor);
            Audit::record('platform_incident_opened', null, [], ['incident_id' => $id, 'category' => $data['category'], 'severity' => $data['severity'],
                'personal_information' => $data['personal_information'], 'schools' => $schoolIds, 'changed_by' => $actor->id]);

            return $id;
        }, 3));
    }

    public function update(int $id, array $input, User $actor): void
    {
        $data = Validator::make($input, self::updateRules())->validate();
        $this->consistent($data);
        $occurred = isset($data['occurred_at']) ? $this->parse($data['occurred_at'], 'occurred_at') : null;
        if (($data['regulator_notified_on'] ?? null) !== null && ($data['regulator'] ?? null) !== 'required') {
            throw ValidationException::withMessages(['regulator_notified_on' => __('Record that the Regulator must be told before the date it was told.')]);
        }
        if (($data['regulator_notified_on'] ?? null) !== null && $data['regulator_notified_on'] > $this->today()) {
            throw ValidationException::withMessages(['regulator_notified_on' => __('The Regulator cannot have been told on a future date.')]);
        }

        $this->currentSchool->acrossSchools(fn () => DB::transaction(function () use ($id, $data, $occurred, $actor): void {
            $row = DB::table('platform_incidents')->where('id', $id)->lockForUpdate()->first();
            abort_if($row === null, 404);
            abort_if((int) $row->revision !== (int) $data['revision'], 409, __('This incident changed. Reload before saving.'));
            abort_if($row->status === 'closed' && $data['status'] === 'closed', 409, __('Reopen the incident to change it.'));
            if ($occurred !== null && $occurred->greaterThan(CarbonImmutable::parse($row->detected_at))) {
                throw ValidationException::withMessages(['occurred_at' => __('An incident cannot happen after it was detected.')]);
            }

            $next = [
                'title' => $data['title'], 'category' => $data['category'], 'severity' => $data['severity'], 'status' => $data['status'],
                'personal_information' => $data['personal_information'], 'occurred_at' => $occurred, 'summary' => $data['summary'],
                'regulator' => $data['regulator'] ?? null, 'regulator_reason' => $data['regulator_reason'] ?? null,
                'regulator_notified_on' => $data['regulator_notified_on'] ?? null, 'regulator_reference' => $data['regulator_reference'] ?? null,
                'closure_summary' => $data['status'] === 'closed' ? $data['closure_summary'] : ($data['closure_summary'] ?? null),
            ];
            if ($data['status'] === 'closed') {
                $schools = DB::table('platform_incident_schools')->where('incident_id', $id)->get();
                $blockers = $this->blockers((object) array_merge((array) $row, $next), $schools);
                if ($blockers !== []) {
                    throw ValidationException::withMessages(['status' => $blockers]);
                }
            }

            $changes = [];
            foreach (['title', 'category', 'severity', 'status', 'personal_information', 'summary', 'regulator', 'regulator_reason', 'regulator_notified_on', 'regulator_reference', 'closure_summary'] as $field) {
                if ((string) ($row->{$field} ?? '') !== (string) ($next[$field] ?? '')) {
                    $changes[] = $field;
                }
            }
            if ($this->stored($row->occurred_at) !== $this->stored($occurred)) {
                $changes[] = 'occurred_at';
            }
            if ($changes === []) {
                return;
            }

            $write = $next + ['revision' => (int) $row->revision + 1, 'updated_at' => now()];
            if ($row->status !== $data['status']) {
                $write['contained_at'] = $data['status'] === 'open' ? null : ($row->contained_at ?? now());
                $write['closed_at'] = $data['status'] === 'closed' ? now() : null;
                $write['closed_by'] = $data['status'] === 'closed' ? $actor->id : null;
                $this->entry($id, 'status', __('Status changed from :from to :to.', ['from' => __(self::STATUSES[$row->status]), 'to' => __(self::STATUSES[$data['status']])]), $actor);
            }
            if ($row->severity !== $data['severity']) {
                $this->entry($id, 'severity', __('Severity changed from :from to :to.', ['from' => __(self::SEVERITIES[$row->severity]), 'to' => __(self::SEVERITIES[$data['severity']])]), $actor);
            }
            if ($row->personal_information !== $data['personal_information']) {
                $this->entry($id, 'note', __('Personal information involved: :answer.', ['answer' => __(self::PERSONAL[$data['personal_information']])]), $actor);
            }
            if ((string) $row->regulator !== (string) ($next['regulator'] ?? '') || (string) $row->regulator_notified_on !== (string) ($next['regulator_notified_on'] ?? '')) {
                $this->entry($id, 'regulator', $this->regulatorLine($next), $actor);
            }
            DB::table('platform_incidents')->where('id', $id)->update($write);
            Audit::record('platform_incident_updated', null, ['status' => $row->status, 'severity' => $row->severity, 'regulator' => $row->regulator],
                ['incident_id' => $id, 'status' => $data['status'], 'severity' => $data['severity'], 'regulator' => $next['regulator'], 'fields' => $changes, 'changed_by' => $actor->id]);
        }, 3));
    }

    public function addSchool(int $id, array $input, User $actor): void
    {
        $data = Validator::make($input, self::schoolRules())->validate();
        $schoolId = (int) $data['school_id'];
        $this->currentSchool->acrossSchools(fn () => DB::transaction(function () use ($id, $schoolId, $actor): void {
            $row = $this->lockOpen($id);
            if (DB::table('platform_incident_schools')->where('incident_id', $id)->where('school_id', $schoolId)->exists()) {
                throw ValidationException::withMessages(['school_id' => __('This school is already listed.')]);
            }
            DB::table('platform_incident_schools')->insert(['incident_id' => $id, 'school_id' => $schoolId, 'created_at' => now(), 'updated_at' => now()]);
            $this->bump($row);
            $this->entry($id, 'school', __(':school added as an affected school.', ['school' => $this->schoolName($schoolId)]), $actor);
            Audit::record('platform_incident_school_added', null, [], ['incident_id' => $id, 'changed_by' => $actor->id], $schoolId);
        }, 3));
    }

    /** Records when, how and whom at the school Navigate Ed told; a correction replaces it. */
    public function recordNotified(int $id, int $schoolId, array $input, User $actor): void
    {
        $data = Validator::make($input, self::notifiedRules())->validate();
        if ($data['notified_on'] > $this->today()) {
            throw ValidationException::withMessages(['notified_on' => __('The school cannot have been told on a future date.')]);
        }
        $this->currentSchool->acrossSchools(fn () => DB::transaction(function () use ($id, $schoolId, $data, $actor): void {
            $row = $this->lockOpen($id);
            $link = DB::table('platform_incident_schools')->where('incident_id', $id)->where('school_id', $schoolId)->lockForUpdate()->first();
            abort_if($link === null, 404);
            if ($data['notified_on'] < CarbonImmutable::parse($row->detected_at)->setTimezone(self::TIMEZONE)->toDateString()) {
                throw ValidationException::withMessages(['notified_on' => __('The school cannot have been told before the incident was detected.')]);
            }
            DB::table('platform_incident_schools')->where('id', $link->id)->update($data + ['notified_by' => $actor->id, 'updated_at' => now()]);
            $this->bump($row);
            $this->entry($id, 'school', __(':school told on :date by :how (:contact).', [
                'school' => $this->schoolName($schoolId), 'date' => $data['notified_on'], 'how' => mb_strtolower(__(self::NOTIFIED_HOW[$data['notified_how']])), 'contact' => $data['notified_contact']]), $actor);
            Audit::record('platform_incident_school_notified', null, ['notified_on' => $link->notified_on],
                ['incident_id' => $id, 'notified_on' => $data['notified_on'], 'notified_how' => $data['notified_how'], 'changed_by' => $actor->id], $schoolId);
        }, 3));
    }

    public function addEntry(int $id, array $input, User $actor): void
    {
        $data = Validator::make($input, self::entryRules())->validate();
        $this->currentSchool->acrossSchools(fn () => DB::transaction(function () use ($id, $data, $actor): void {
            $row = DB::table('platform_incidents')->where('id', $id)->lockForUpdate()->first();
            abort_if($row === null, 404);
            $this->entry($id, $data['kind'], $data['body'], $actor);
            DB::table('platform_incidents')->where('id', $id)->update(['updated_at' => now()]);
            Audit::record('platform_incident_entry_added', null, [], ['incident_id' => $id, 'kind' => $data['kind'], 'changed_by' => $actor->id]);
        }, 3));
    }

    public static function reference(int $id): string
    {
        return 'INC-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * What stops the incident closing. Personal information that was, or may
     * have been, reached means every affected school is told and the
     * Regulator decision recorded; a required notification needs its date.
     *
     * @param  iterable<object>  $schools
     * @return list<string>
     */
    private function blockers(object $row, iterable $schools): array
    {
        $blockers = [];
        if (($row->closure_summary ?? null) === null || trim((string) $row->closure_summary) === '') {
            $blockers[] = __('Write what happened, the cause and what fixed it.');
        }
        if ($row->personal_information !== 'no') {
            $untold = collect($schools)->whereNull('notified_on')->count();
            if ($untold > 0) {
                $blockers[] = trans_choice('{1} One affected school has not been told yet.|[2,*] :count affected schools have not been told yet.', $untold, ['count' => $untold]);
            }
            if ($row->regulator === null) {
                $blockers[] = __('Record whether the Information Regulator must be told.');
            }
        }
        if ($row->regulator === 'required' && $row->regulator_notified_on === null) {
            $blockers[] = __('Record the date the Information Regulator was told.');
        }

        return $blockers;
    }

    /** @param  iterable<object>  $schools */
    private function summaryRow(object $row, iterable $schools): array
    {
        $schools = collect($schools);
        $detected = CarbonImmutable::parse($row->detected_at);
        $attention = [];
        if ($row->status !== 'closed' && $row->personal_information !== 'no') {
            $untold = $schools->whereNull('notified_on')->count();
            if ($untold > 0) {
                $attention[] = trans_choice('{1} One school not told|[2,*] :count schools not told', $untold, ['count' => $untold]);
            }
            if ($row->regulator === null) {
                $attention[] = __('Regulator decision not recorded');
            } elseif ($row->regulator === 'required' && $row->regulator_notified_on === null) {
                $attention[] = __('Regulator not told yet');
            }
        }

        return [
            'id' => (int) $row->id, 'reference' => self::reference((int) $row->id), 'title' => (string) $row->title,
            'category' => (string) $row->category, 'severity' => (string) $row->severity, 'status' => (string) $row->status,
            'personal_information' => (string) $row->personal_information,
            'detected_at' => $this->local($row->detected_at),
            'days_open' => $row->status === 'closed' ? null : (int) $detected->diffInDays(now(), true),
            'schools' => $schools->count(), 'attention' => $attention,
        ];
    }

    private function regulatorLine(array $next): string
    {
        return match ($next['regulator']) {
            'required' => $next['regulator_notified_on'] !== null
                ? __('Information Regulator told on :date (reference :reference).', ['date' => $next['regulator_notified_on'], 'reference' => $next['regulator_reference'] ?? __('none')])
                : __('Information Regulator must be told: :reason', ['reason' => $next['regulator_reason']]),
            'not_required' => __('Information Regulator need not be told: :reason', ['reason' => $next['regulator_reason']]),
            default => __('Information Regulator decision cleared.'),
        };
    }

    /** A breach is, by definition, personal information reached by someone not authorised. */
    private function consistent(array $data): void
    {
        if ($data['category'] === 'breach' && $data['personal_information'] === 'no') {
            throw ValidationException::withMessages(['personal_information' => __('A breach involves personal information: choose yes or not known yet.')]);
        }
    }

    private function lockOpen(int $id): object
    {
        $row = DB::table('platform_incidents')->where('id', $id)->lockForUpdate()->first();
        abort_if($row === null, 404);
        abort_if($row->status === 'closed', 409, __('Reopen the incident to change it.'));

        return $row;
    }

    /** A change to the incident's schools is a change to the incident: an open edit form must reload. */
    private function bump(object $row): void
    {
        DB::table('platform_incidents')->where('id', $row->id)->update(['revision' => (int) $row->revision + 1, 'updated_at' => now()]);
    }

    private function entry(int $id, string $kind, string $body, User $actor): void
    {
        DB::table('platform_incident_entries')->insert(['incident_id' => $id, 'kind' => $kind, 'body' => mb_substr($body, 0, 1000), 'actor_id' => $actor->id, 'created_at' => now()]);
    }

    private function parse(string $value, string $field): CarbonImmutable
    {
        $at = CarbonImmutable::createFromFormat('Y-m-d\TH:i', $value, self::TIMEZONE);
        if ($at === null || $at->greaterThan(CarbonImmutable::now(self::TIMEZONE)->addMinutes(5))) {
            throw ValidationException::withMessages([$field => __('This time cannot be in the future.')]);
        }

        return $at->setTimezone((string) config('app.timezone'));
    }

    private function stored(mixed $value): ?string
    {
        return $value === null ? null : CarbonImmutable::parse($value)->format('Y-m-d H:i');
    }

    private function local(mixed $value): ?string
    {
        return $value === null ? null : CarbonImmutable::parse($value, (string) config('app.timezone'))->setTimezone(self::TIMEZONE)->format('Y-m-d H:i');
    }

    private function today(): string
    {
        return CarbonImmutable::now(self::TIMEZONE)->toDateString();
    }

    private function schoolName(int $schoolId): string
    {
        return (string) DB::table('schools')->where('id', $schoolId)->value('name');
    }

    /** @return array<int, string> */
    private function actorNames(iterable $ids): array
    {
        $ids = collect($ids)->filter()->map(fn ($id) => (int) $id)->unique()->values();

        return User::acrossSchools()->withTrashed()->whereIn('id', $ids)->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn (User $user) => [(int) $user->id => trim($user->first_name.' '.$user->last_name)])->all();
    }
}
