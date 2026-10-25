<?php

declare(strict_types=1);

namespace App\Services\Wellbeing;

use App\Models\User;
use App\Models\Wellbeing\Category;
use App\Models\Wellbeing\IncidentReport;
use App\Models\Wellbeing\IncidentThread;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class IncidentReporting
{
    public function __construct(private WellbeingAccess $access, private WellbeingOperations $operations) {}

    private function secret(string $secret): string
    {
        Validator::make(['secret' => $secret], ['secret' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/D']])->validate();

        return $this->operations->digest('incident:'.app(CurrentSchool::class)->id().':'.$secret);
    }

    private function reporter(User $actor, ?int $learner, bool $write = true): User
    {
        $fresh = $this->access->actor($actor, $write);
        if ($learner !== null) {
            $child = $this->access->learner($learner);
            $roles = app(ProfilePolicy::class)->roles($fresh);
            $self = in_array('Student', $roles, true) && (int) $child->user_id === (int) $fresh->id;
            $guardian = in_array('Parent', $roles, true) && app(GuardianService::class)->guardianUserIds($child)->contains((int) $fresh->id);
            abort_unless($self || $guardian, 404);
        }

        return $fresh;
    }

    private function bySecret(string $secret, ?User $actor = null, bool $write = false): IncidentReport
    {
        $this->access->environment($write);
        $metadata = IncidentReport::select(['id', 'anonymous', 'reporter_user_id', 'learner_id'])->where('secret_digest', $this->secret($secret))->firstOrFail();
        if (! $metadata->anonymous) {
            abort_unless($actor !== null && (int) $actor->id === (int) $metadata->reporter_user_id, 403);
            $this->reporter($actor, $metadata->learner_id === null ? null : (int) $metadata->learner_id, $write);
        }

        return IncidentReport::findOrFail($metadata->id);
    }

    public function submit(array $input, ?User $identifiedActor = null): array
    {
        $this->access->environment(true);
        $data = Validator::make($input, ['secret' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/D'], 'category_id' => ['required', 'integer', 'min:1'], 'body' => ['required', 'string', 'max:20000'], 'urgency' => ['required', Rule::in(['routine', 'urgent'])], 'anonymous' => ['required', 'boolean'], 'acknowledged_guidance' => ['sometimes', 'boolean'], 'identify_consent' => ['sometimes', 'boolean'], 'learner_id' => ['nullable', 'integer', 'min:1']])->validate();
        abort_if($data['anonymous'] && (! empty($data['learner_id']) || ! empty($data['identify_consent'])), 422, 'Anonymous reports cannot carry an identity or learner reference.');
        $identity = null;
        if (! $data['anonymous']) {
            abort_unless($identifiedActor !== null && ($data['identify_consent'] ?? false), 422, 'Explicit identification consent and sign-in are required.');
            $identity = $this->reporter($identifiedActor, isset($data['learner_id']) ? (int) $data['learner_id'] : null);
        }
        $digest = $this->secret($data['secret']);
        // Never hash a plaintext body without a server secret: low-entropy content
        // must not be recoverable by dictionary attacks against retry metadata.
        $hash = $this->operations->digest(json_encode($data, JSON_THROW_ON_ERROR));

        return $this->operations->locked(function () use ($data, $identity, $digest, $hash) {
            $this->access->environment(true);
            Category::where('active', true)->findOrFail($data['category_id']);
            if ($identity !== null) {
                $this->reporter($identity, isset($data['learner_id']) ? (int) $data['learner_id'] : null);
            }
            $old = IncidentReport::select(['id', 'submission_hash'])->where('secret_digest', $digest)->first();
            if ($old !== null) {
                abort_unless(hash_equals($old->submission_hash, $hash), 409, 'This private follow-up code already belongs to another submission.');

                // A previously anonymous report may since have been identified.
                return $this->submissionReceipt($this->bySecret($data['secret'], $identity, true));
            }
            $row = IncidentReport::create(['category_id' => $data['category_id'], 'anonymous' => $data['anonymous'], 'reporter_user_id' => $identity?->id,
                'learner_id' => $identity === null ? null : ($data['learner_id'] ?? null), 'identified_at' => $identity === null ? null : now(),
                'secret_digest' => $digest, 'submission_hash' => $hash, 'body' => $data['body'], 'urgency' => $data['urgency'], 'status' => 'open', 'revision' => 1]);
            $this->operations->event($identity, 'wellbeing.report_submitted', $row);

            return $this->submissionReceipt($row);
        });
    }

    private function submissionReceipt(IncidentReport $row): array
    {
        return ['received' => true, 'submitted_at' => $row->created_at?->toISOString(), 'emergency_guidance' => WellbeingSettings::EMERGENCY_GUIDANCE];
    }

    private function publicView(IncidentReport $row, ?int $before = null): array
    {
        $query = IncidentThread::where('incident_report_id', $row->id)->where('internal', false)->when($before !== null, fn ($q) => $q->where('id', '<', $before))->orderByDesc('id');
        $rows = $query->limit(51)->get();
        $more = $rows->count() > 50;
        $rows = $rows->take(50);

        return ['anonymous' => (bool) $row->anonymous, 'body' => $row->body, 'urgency' => $row->urgency, 'status' => $row->status, 'revision' => $row->revision,
            'submitted_at' => $row->created_at?->toISOString(), 'emergency_guidance' => WellbeingSettings::EMERGENCY_GUIDANCE,
            'threads' => $rows->reverse()->map(fn ($thread) => ['author' => $thread->author_type === 'reporter' ? 'reporter' : 'school', 'body' => $thread->body, 'created_at' => $thread->created_at?->toISOString()])->values()->all(),
            'has_older' => $more, 'next_before' => $more ? $rows->last()?->id : null];
    }

    public function followUp(string $secret, ?User $actor = null, ?int $before = null): array
    {
        if ($before !== null) {
            Validator::make(['before' => $before], ['before' => ['integer', 'min:1']])->validate();
        }

        return $this->publicView($this->bySecret($secret, $actor), $before);
    }

    public function reply(string $secret, array $input, ?User $actor = null): array
    {
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'body' => ['required', 'string', 'max:20000']])->validate();
        $hash = $this->operations->digest(json_encode($data, JSON_THROW_ON_ERROR));

        return $this->operations->locked(function () use ($secret, $data, $hash, $actor) {
            $row = $this->bySecret($secret, $actor, true);
            $old = IncidentThread::where('incident_report_id', $row->id)->where('operation_id', $data['operation_id'])->first();
            if ($old !== null) {
                abort_unless($old->author_type === 'reporter' && hash_equals($old->request_hash, $hash), 409, 'This follow-up retry contains different text.');
            } else {
                $old = IncidentThread::create(['incident_report_id' => $row->id, 'staff_user_id' => null, 'author_type' => 'reporter', 'internal' => false, 'body' => $data['body'], 'operation_id' => $data['operation_id'], 'request_hash' => $hash]);
                $row->fill(['status' => 'open', 'revision' => $row->revision + 1])->save();
                // Even an authenticated cookie cannot identify an anonymous author.
                $this->operations->event(null, 'wellbeing.reporter_follow_up', $row);
            }

            // Acknowledgment is immutable; callers read fresh current report
            // state separately instead of replaying an old anonymity/status view.
            return ['posted' => true, 'operation_id' => $old->operation_id, 'created_at' => $old->created_at?->toISOString()];
        });
    }

    public function identify(User $actor, string $secret, array $input): array
    {
        $data = Validator::make($input, ['revision' => ['required', 'integer', 'min:1'], 'identify_consent' => ['required', 'accepted'], 'learner_id' => ['nullable', 'integer', 'min:1']])->validate();

        return $this->operations->locked(function () use ($actor, $secret, $data) {
            $fresh = $this->reporter($actor, isset($data['learner_id']) ? (int) $data['learner_id'] : null);
            $row = $this->bySecret($secret, $fresh, true);
            $this->operations->revision($row, $data);
            $row->fill(['anonymous' => false, 'reporter_user_id' => $fresh->id, 'learner_id' => $data['learner_id'] ?? null, 'identified_at' => now(), 'revision' => $row->revision + 1])->save();
            $this->operations->event($fresh, 'wellbeing.reporter_identified', $row);

            return $this->publicView($row);
        });
    }

    public function read(User $actor, int $id): array
    {
        $row = $this->access->report($actor, $id);

        return $this->staffView($row);
    }

    private function staffView(IncidentReport $row): array
    {
        return $row->only(['id', 'category_id', 'anonymous', 'reporter_user_id', 'learner_id', 'body', 'urgency', 'status', 'identified_at', 'revision', 'created_at']) +
            ['threads' => IncidentThread::where('incident_report_id', $row->id)->orderByDesc('id')->limit(100)->get()->map(fn ($thread) => $thread->only(['id', 'staff_user_id', 'author_type', 'internal', 'body', 'created_at']))->all()];
    }

    public function respond(User $actor, int $id, array $input): array
    {
        $this->access->report($actor, $id, true);
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'body' => ['required', 'string', 'max:20000'], 'internal' => ['required', 'boolean'], 'status' => ['required', Rule::in(['open', 'triaged', 'resolved'])]])->validate();

        return $this->operations->once($actor, 'incident-reply:'.$id, $data, fn () => $this->access->report($actor, $id, true), function () use ($actor, $id, $data) {
            $row = $this->access->report($actor, $id, true);
            $this->operations->revision($row, $data);
            $thread = IncidentThread::create(['incident_report_id' => $id, 'staff_user_id' => $actor->id, 'author_type' => 'staff', 'internal' => $data['internal'], 'body' => $data['body'], 'operation_id' => $data['operation_id'], 'request_hash' => $this->operations->digest(json_encode($data, JSON_THROW_ON_ERROR))]);
            $row->fill(['status' => $data['status'], 'revision' => $row->revision + 1])->save();
            $this->operations->event($actor, 'wellbeing.staff_follow_up', $row);

            return $row->only(['id', 'revision', 'status']) + ['thread_id' => $thread->id];
        });
    }

    public function listing(User $actor, int $page = 1): array
    {
        $ids = $this->access->teamIds($actor);
        Validator::make(['page' => $page], ['page' => ['integer', 'between:1,10000']])->validate();
        $rows = IncidentReport::whereIn('category_id', $ids)->orderByDesc('id')->paginate(30, ['id', 'category_id', 'anonymous', 'urgency', 'status', 'revision', 'created_at'], 'page', $page);

        return ['items' => $rows->items(), 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage()];
    }
}
