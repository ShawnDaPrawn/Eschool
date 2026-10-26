<?php

declare(strict_types=1);

namespace App\Services\FamilyForms;

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\FamilyForms\ConsentRecipient;
use App\Models\FamilyForms\ConsentRequest;
use App\Models\FamilyForms\ConsentResponse;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Activities\CommunityAuthority;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Consents
{
    public static function rules(string $action): array
    {
        return match ($action) {
            'create' => ['operation_id' => ['required', 'uuid'], 'activity_group_id' => ['required', 'integer', 'min:1'],
                'title' => ['required', 'string', 'max:191'], 'wording' => ['required', 'string', 'max:20000'],
                'event_starts_at' => ['required', 'date', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})$/D'], 'event_ends_at' => ['required', 'date', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})$/D', 'after:event_starts_at'],
                'respond_by' => ['required', 'date', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})$/D', 'before_or_equal:event_starts_at'],
                'learner_ids' => ['required', 'array', 'min:1', 'max:500'], 'learner_ids.*' => ['integer', 'min:1', 'distinct']],
            'respond' => ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'],
                'fingerprint' => ['required', 'string', 'size:64'], 'decision' => ['required', 'in:yes,no'], 'acknowledged' => ['required', 'accepted'], 'signed_name' => ['required', 'string', 'max:191']],
            'cancel' => ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1']],
        };
    }

    private function fingerprint(ConsentRequest $request, ?array $ids = null): string
    {
        $ids ??= ConsentRecipient::where('request_id', $request->id)->pluck('learner_id')->map(fn ($id) => (int) $id)->all();
        sort($ids);

        return hash('sha256', json_encode([app(CurrentSchool::class)->id(), (int) $request->session_year_id, (int) $request->activity_group_id,
            $request->title, $request->wording, $request->event_starts_at->toIso8601String(), $request->event_ends_at->toIso8601String(),
            $request->respond_by->toIso8601String(), $ids], JSON_THROW_ON_ERROR));
    }

    private function validSignature(ConsentRequest $request, ConsentResponse $response, int $learner): bool
    {
        $payload = json_encode([$request->id, $learner, (int) $response->guardian_user_id, $response->request_fingerprint, $response->decision,
            $response->signed_name, $response->revision, $response->signed_at->toIso8601String()], JSON_THROW_ON_ERROR);
        $matches = false;
        foreach (array_unique([(string) config('app.key'), ...config('app.previous_keys', [])]) as $key) {
            $matches = hash_equals(hash_hmac('sha256', $payload, $key), $response->signature) || $matches;
        }

        return hash_equals($request->fingerprint, $response->request_fingerprint) && $matches;

    }

    private function dates(ConsentRequest $request): array
    {
        $zone = app(CurrentSchool::class)->school()->timezone;

        return [$request->event_starts_at->setTimezone($zone)->toDateString(), $request->event_ends_at->setTimezone($zone)->toDateString()];
    }

    private function staff(User $actor, ConsentRequest $request, bool $write = false): void
    {
        app(FamilyFormAccess::class)->actor($actor, $write);
        [$from, $to] = $this->dates($request);
        foreach ([$from, $to] as $date) {
            $group = app(CommunityAuthority::class)->groupStaff($actor, (int) $request->activity_group_id, $write, $date);
            if ($write) {
                foreach (app(CommunityAuthority::class)->lineage($group) as $id) {
                    abort_unless(ActivityGroup::whereKey($id)->where('active', true)->whereDate('starts_on', '<=', $from)->whereDate('ends_on', '>=', $to)->exists(), 422, 'Choose an event within the active group season.');
                }
            }
        }
        if ($write) {
            $future = SchoolMembership::where('school_id', app(CurrentSchool::class)->id())->where('user_id', $actor->id)->where('status', 'active')
                ->whereDate('starts_on', '<=', $from)->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $to))->whereNotIn('role', ['Parent', 'Student'])->pluck('role')->all();
            abort_unless($future !== [], 403);
            $manager = $actor->roles()->whereIn('roles.name', $future)->whereHas('permissions', fn ($q) => $q->where('name', 'activity-groups-manage'))->exists()
                || $actor->getDirectPermissions()->contains('name', 'activity-groups-manage');
            if (! $manager) {
                abort_unless(array_intersect($future, ['Teacher', 'Facilitator']) !== []
                    && app(CommunityAuthority::class)->assigned($actor, $group, $from) && app(CommunityAuthority::class)->assigned($actor, $group, $to), 403);
            }
        }
        abort_if($write && (int) $request->session_year_id !== (int) schoolSetting('session_year'), 423, 'New consent requests require the current school year.');
    }

    private function audience(ConsentRequest $request, int $learner): void
    {
        [$from, $to] = $this->dates($request);
        app(FamilyFormAccess::class)->eventLearner($learner, (int) $request->activity_group_id, (int) $request->session_year_id, $from, $to);
    }

    public function managedRequest(User $actor, string $id): ConsentRequest
    {
        app(FamilyFormAccess::class)->actor($actor);
        $request = ConsentRequest::findOrFail($id);
        $this->staff($actor, $request);
        abort_unless(hash_equals($request->fingerprint, $this->fingerprint($request)), 409, 'This consent wording could not be verified.');

        return $request;
    }

    /** Server-authorized candidates for the actual event span; never offer stale retained rows. */
    public function eligible(User $actor, array $input): array
    {
        app(FamilyFormAccess::class)->actor($actor, true);
        $rules = self::rules('create');
        foreach (['title', 'wording', 'learner_ids', 'learner_ids.*'] as $key) {
            unset($rules[$key]);
        }
        $data = Validator::make($input, $rules)->validate();
        $group = app(CommunityAuthority::class)->groupStaff($actor, (int) $data['activity_group_id'], true);
        $request = new ConsentRequest(['session_year_id' => $group->session_year_id, 'activity_group_id' => $group->id,
            'event_starts_at' => CarbonImmutable::parse($data['event_starts_at'])->utc(), 'event_ends_at' => CarbonImmutable::parse($data['event_ends_at'])->utc(),
            'respond_by' => CarbonImmutable::parse($data['respond_by'])->utc()]);
        abort_unless($request->respond_by->isFuture() && $request->event_starts_at->isFuture(), 422, 'Choose a future event and response deadline.');
        $this->staff($actor, $request, true);
        [$from, $to] = $this->dates($request);
        $ids = ActivityMembership::where('activity_group_id', $group->id)->where('active', true)->whereDate('starts_on', '<=', $from)->whereDate('ends_on', '>=', $to)->distinct()->orderBy('learner_id')->limit(501)->pluck('learner_id');
        abort_if($ids->count() > 500, 422, 'Narrow this activity before publishing consent.');
        $items = [];
        foreach ($ids as $id) {
            try {
                $learner = app(FamilyFormAccess::class)->eventLearner((int) $id, (int) $group->id, (int) $group->session_year_id, $from, $to);
                $items[] = ['id' => (int) $learner->id, 'person_id' => (int) $learner->user_id, 'name' => $learner->user->full_name];
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                // A departed/withdrawn participant must not invalidate all valid classmates.
            }
        }
        $this->staff($actor, $request, true);

        return $items;
    }

    public function create(User $actor, array $input): array
    {
        app(FamilyFormAccess::class)->actor($actor, true);
        $data = Validator::make($input, self::rules('create'))->validate();
        $group = app(CommunityAuthority::class)->groupStaff($actor, (int) $data['activity_group_id'], true);
        $request = new ConsentRequest(['session_year_id' => $group->session_year_id, 'activity_group_id' => $group->id,
            'title' => $data['title'], 'wording' => $data['wording'], 'event_starts_at' => CarbonImmutable::parse($data['event_starts_at'])->utc(),
            'event_ends_at' => CarbonImmutable::parse($data['event_ends_at'])->utc(), 'respond_by' => CarbonImmutable::parse($data['respond_by'])->utc()]);
        abort_unless($request->respond_by->isFuture() && $request->event_starts_at->isFuture(), 422, 'Choose a future event and response deadline.');
        $authorize = function () use ($actor, $request, $data) {
            $this->staff($actor, $request, true);
            foreach ($data['learner_ids'] as $learner) {
                $this->audience($request, (int) $learner);
            }
        };

        return app(FamilyFormOperations::class)->once($actor, 'consent.create', $data, $authorize, function () use ($actor, $request, $data) {
            $ids = array_map('intval', $data['learner_ids']);
            sort($ids);
            $request->created_by = $actor->id;
            $request->fingerprint = $this->fingerprint($request, $ids);
            $request->save();
            foreach ($ids as $learner) {
                ConsentRecipient::create(['request_id' => $request->id, 'learner_id' => $learner]);
            }
            Audit::record('consent.created', $request, [], ['fingerprint' => $request->fingerprint, 'recipient_count' => count($ids)], app(CurrentSchool::class)->id());

            return ['id' => $request->id, 'fingerprint' => $request->fingerprint, 'revision' => 1];
        });
    }

    public function read(User $actor, string $id, int $learner): array
    {
        app(FamilyFormAccess::class)->guardian($actor, $learner);
        $request = ConsentRequest::findOrFail($id);
        $recipient = ConsentRecipient::where('request_id', $id)->where('learner_id', $learner)->firstOrFail();
        abort_unless(hash_equals($request->fingerprint, $this->fingerprint($request)), 409, 'This consent wording could not be verified.');
        $response = ConsentResponse::where('recipient_id', $recipient->id)->where('revision', $recipient->revision)->first();
        abort_if($response !== null && ! $this->validSignature($request, $response, $learner), 409, 'The recorded consent attestation could not be verified.');
        app(FamilyFormAccess::class)->guardian($actor, $learner);

        return ['id' => $request->id, 'learner_id' => $learner, 'title' => $request->title, 'wording' => $request->wording,
            'fingerprint' => $request->fingerprint, 'status' => $request->status, 'revision' => $recipient->revision,
            'event_starts_at' => $request->event_starts_at->toIso8601String(), 'event_ends_at' => $request->event_ends_at->toIso8601String(), 'respond_by' => $request->respond_by->toIso8601String(),
            'decision' => $response?->decision, 'signed_at' => $response?->signed_at?->toIso8601String()];
    }

    public function respond(User $actor, string $id, int $learner, array $input): array
    {
        app(FamilyFormAccess::class)->actor($actor, revocation: true);
        $data = Validator::make($input, self::rules('respond'))->validate();
        $negative = $data['decision'] === 'no';
        $authorize = function () use ($actor, $id, $learner, $data, $negative) {
            app(FamilyFormAccess::class)->guardian($actor, $learner, revocation: $negative, attest: true);
            $request = ConsentRequest::findOrFail($id);
            ConsentRecipient::where('request_id', $id)->where('learner_id', $learner)->firstOrFail();
            abort_unless(hash_equals($request->fingerprint, $this->fingerprint($request)) && hash_equals($request->fingerprint, $data['fingerprint']), 409, 'Read the exact current consent wording.');

        };
        $bound = ['request_id' => $id, 'learner_id' => $learner] + $data;

        return app(FamilyFormOperations::class)->once($actor, 'consent.respond', $bound, $authorize, function () use ($actor, $id, $learner, $data, $negative) {
            $fresh = app(FamilyFormAccess::class)->guardian($actor, $learner, true, $negative);
            abort_unless(mb_strtolower(trim($fresh->full_name)) === mb_strtolower(trim($data['signed_name'])), 422, 'Sign using the name on your guardian account.');
            if (! $negative) {
                $request = ConsentRequest::findOrFail($id);
                abort_unless($request->status === 'open' && $request->respond_by->isFuture(), 409, 'The consent response period has closed.');
                $this->audience($request, $learner);
            }
            $recipient = ConsentRecipient::where('request_id', $id)->where('learner_id', $learner)->lockForUpdate()->firstOrFail();
            abort_unless($recipient->revision === (int) $data['revision'], 409, 'Another guardian changed this response. Refresh before signing.');
            $signed = now()->utc();
            $revision = $recipient->revision + 1;
            $signature = hash_hmac('sha256', json_encode([$id, $learner, $actor->id, $data['fingerprint'], $data['decision'], trim($data['signed_name']), $revision, $signed->toIso8601String()], JSON_THROW_ON_ERROR), (string) config('app.key'));
            $response = ConsentResponse::create(['recipient_id' => $recipient->id, 'guardian_user_id' => $actor->id, 'revision' => $revision,
                'decision' => $data['decision'], 'signed_name' => trim($data['signed_name']), 'request_fingerprint' => $data['fingerprint'], 'signature' => $signature, 'signed_at' => $signed]);
            $recipient->update(['revision' => $revision]);
            Audit::record('consent.responded', $response, [], ['request_id' => $id, 'revision' => $revision], app(CurrentSchool::class)->id());

            return ['id' => $response->id, 'request_id' => $id, 'learner_id' => $learner, 'decision' => $response->decision, 'revision' => $revision, 'signed_at' => $signed->toIso8601String()];
        });
    }

    public function cancel(User $actor, string $id, array $input): array
    {
        app(FamilyFormAccess::class)->actor($actor, revocation: true);
        $data = Validator::make($input, self::rules('cancel'))->validate();
        $authorize = function () use ($actor, $id) {
            $request = ConsentRequest::findOrFail($id);
            $this->staff($actor, $request);
        };

        return app(FamilyFormOperations::class)->once($actor, 'consent.cancel', ['request_id' => $id] + $data, $authorize, function () use ($id, $data) {
            $request = ConsentRequest::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless($request->revision === (int) $data['revision'], 409, 'This consent request changed.');
            $request->update(['status' => 'cancelled', 'cancelled_at' => now()->utc(), 'revision' => $request->revision + 1]);
            Audit::record('consent.cancelled', $request, [], ['revision' => $request->revision], app(CurrentSchool::class)->id());

            return ['id' => $id, 'status' => $request->status, 'revision' => $request->revision];
        });
    }

    /** Operational permission derives from current sources, never from a retained yes row. */
    public function effective(User $actor, string $id, int $learner): bool
    {
        app(FamilyFormAccess::class)->actor($actor);
        $request = ConsentRequest::findOrFail($id);
        $this->staff($actor, $request);
        $recipient = ConsentRecipient::where('request_id', $id)->where('learner_id', $learner)->firstOrFail();
        if ($request->status !== 'open' || $request->event_ends_at->isPast() || ! hash_equals($request->fingerprint, $this->fingerprint($request))) {
            return false;
        }
        $response = ConsentResponse::where('recipient_id', $recipient->id)->where('revision', $recipient->revision)->first();
        if ($response === null || $response->decision !== 'yes') {
            return false;
        }
        try {
            $this->audience($request, $learner);
            app(FamilyFormAccess::class)->guardian(User::findOrFail($response->guardian_user_id), $learner, attest: true);
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }

        return $this->validSignature($request, $response, $learner);
    }
}
