<?php

declare(strict_types=1);

namespace App\Services\SchoolBusiness;

use App\Models\SchoolBusiness\BursaryApplication;
use App\Models\SchoolBusiness\BursaryDocument;
use App\Models\SchoolBusiness\BursaryEvent;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\Money\FeeDiscounts;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Notifications\Notifier;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Bursary applications (Action Plan 5.10).
 *
 * A guardian who may act for the child applies with household details and
 * supporting documents. Only that guardian and staff holding the bursary
 * committee ability (bursaries-review) can see the application; another
 * guardian of the same child or another family gets 404. Household details
 * and committee reasons are encrypted at rest; documents are scanned and
 * stored encrypted (BursaryDocuments). A committee member who is also a
 * guardian of the child may not decide.
 *
 * An award becomes a draft bursary fee discount for the child, in integer
 * cents or basis points, through the existing discount mechanism: Finance
 * approves it (always a different person) and applies it to invoices as it
 * does any discount. BursaryRetention erases household details, reasons
 * and documents on schedule.
 */
class Bursaries
{
    public const MODULE = 'bursaries';

    public const PERMISSION = 'bursaries-review';

    public const NOTICE = 'bursary.updated';

    public const LINK = '/bursaries';

    public static function householdRules(): array
    {
        return ['household' => ['required', 'array:household_size,dependants,monthly_income_cents,monthly_expenses_cents,other_support,explanation'],
            'household.household_size' => ['required', 'integer', 'between:1,30'], 'household.dependants' => ['required', 'integer', 'between:0,30'],
            'household.monthly_income_cents' => ['required', 'integer', 'between:0,100000000000'], 'household.monthly_expenses_cents' => ['required', 'integer', 'between:0,100000000000'],
            'household.other_support' => ['nullable', 'string', 'max:2000'], 'household.explanation' => ['required', 'string', 'min:10', 'max:5000']];
    }

    public static function rules(string $action): array
    {
        return match ($action) {
            'create' => ['operation_id' => ['required', 'uuid'], 'learner_id' => ['required', 'integer', 'min:1'], 'session_year_id' => ['required', 'integer', 'min:1']] + self::householdRules(),
            'update' => ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1']] + self::householdRules(),
            'submit', 'withdraw' => ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'confirmed' => ['required', 'accepted']],
            'note' => ['operation_id' => ['required', 'uuid'], 'reasons' => ['required', 'string', 'min:5', 'max:5000']],
            'decide' => ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'outcome' => ['required', 'in:award,decline'],
                'reasons' => ['required', 'string', 'min:10', 'max:5000'], 'award_kind' => ['required_if:outcome,award', 'prohibited_if:outcome,decline', 'in:fixed,percent'],
                'award_value' => ['required_if:outcome,award', 'prohibited_if:outcome,decline', 'integer', 'min:1', 'max:100000000000'],
                'currency' => ['required_if:outcome,award', 'prohibited_if:outcome,decline', 'regex:/\A[A-Z]{3}\z/D']],
            'discount' => ['operation_id' => ['required', 'uuid']],
            'queue' => ['state' => ['sometimes', 'in:submitted,awarded,declined,withdrawn'], 'page' => ['sometimes', 'integer', 'between:1,100000']],
            default => throw new \InvalidArgumentException('Unknown action.'),
        };
    }

    private function auth(): BusinessAuthority
    {
        return app(BusinessAuthority::class);
    }

    private function currentYear(): SessionYear
    {
        return SessionYear::findOrFail((int) schoolSetting('session_year'));
    }

    /** This year, or a year that starts after this one ends. */
    private function eligibleYear(int $id): SessionYear
    {
        $year = SessionYear::lockForUpdate()->findOrFail($id);
        $current = $this->currentYear();
        abort_unless((int) $year->id === (int) $current->id || (string) $year->getRawOriginal('start_date') > (string) $current->getRawOriginal('end_date'), 422, 'Apply for this school year or next year.');

        return $year;
    }

    /** The applying guardian, still able to act for the child. */
    public function owned(User $actor, string $publicId, bool $write = false): BursaryApplication
    {
        abort_unless(Str::isUuid($publicId), 404);
        $application = BursaryApplication::where('public_id', $publicId);
        $application = ($write ? $application->lockForUpdate() : $application)->firstOrFail();
        abort_unless((int) $application->guardian_user_id === (int) $actor->id, 404);
        $this->auth()->guardian($actor, self::MODULE, $application->learner_id, $write);

        return $application;
    }

    public function staffApplication(User $actor, string $publicId, bool $write = false): BursaryApplication
    {
        $this->auth()->staff($actor, self::MODULE, self::PERMISSION, $write);
        abort_unless(Str::isUuid($publicId), 404);
        $application = BursaryApplication::where('public_id', $publicId);

        return ($write ? $application->lockForUpdate() : $application)->firstOrFail();
    }

    private function event(BursaryApplication $application, User $actor, string $action, ?string $reasons = null, ?string $kind = null, ?int $value = null): void
    {
        BursaryEvent::create(['application_id' => $application->id, 'actor_id' => $actor->id, 'action' => $action, 'reasons' => $reasons,
            'award_kind' => $kind, 'award_value' => $value, 'created_at' => now('UTC')]);
        // No household details, reasons or names enter the audit log.
        Audit::record('bursary.'.$action, $application, [], ['state' => $application->state, 'revision' => $application->revision]);
    }

    public function create(User $actor, array $input): array
    {
        $learner = (int) ($input['learner_id'] ?? 0);
        $this->auth()->guardian($actor, self::MODULE, $learner, true);
        $data = Validator::make($input, self::rules('create'))->validate();
        $data['session_year_id'] = (int) $data['session_year_id'];
        $data['learner_id'] = $learner;

        return app(BusinessOperations::class)->once($actor, 'bursary.create', $data, fn () => $this->auth()->guardian($actor, self::MODULE, $learner, true), function () use ($actor, $data): array {
            $year = $this->eligibleYear($data['session_year_id']);
            abort_if(BursaryApplication::where('learner_id', $data['learner_id'])->where('session_year_id', $year->id)->whereIn('state', ['draft', 'submitted'])->lockForUpdate()->exists(), 409,
                'An application for this child and year is already open.');
            $application = BursaryApplication::create(['public_id' => (string) Str::uuid(), 'learner_id' => $data['learner_id'], 'guardian_user_id' => $actor->id,
                'session_year_id' => $year->id, 'state' => 'draft', 'household' => $data['household'], 'revision' => 1]);
            $this->event($application, $actor, 'created');

            return ['id' => $application->public_id, 'state' => 'draft', 'revision' => 1];
        });
    }

    public function update(User $actor, string $publicId, array $input): array
    {
        $this->owned($actor, $publicId, true);
        $data = Validator::make($input, self::rules('update'))->validate() + ['application' => $publicId];

        return app(BusinessOperations::class)->once($actor, 'bursary.update', $data, fn () => $this->owned($actor, $publicId, true), function () use ($actor, $publicId, $data): array {
            $application = $this->owned($actor, $publicId, true);
            abort_unless($application->state === 'draft' && $application->revision === (int) $data['revision'], 409, 'This application changed. Refresh it first.');
            $application->update(['household' => $data['household'], 'revision' => $application->revision + 1]);
            $this->event($application, $actor, 'updated');

            return ['id' => $publicId, 'state' => 'draft', 'revision' => $application->revision];
        });
    }

    public function submit(User $actor, string $publicId, array $input): array
    {
        return $this->guardianTransition($actor, $publicId, $input, 'submit');
    }

    public function withdraw(User $actor, string $publicId, array $input): array
    {
        return $this->guardianTransition($actor, $publicId, $input, 'withdraw');
    }

    private function guardianTransition(User $actor, string $publicId, array $input, string $action): array
    {
        $this->owned($actor, $publicId, true);
        $data = Validator::make($input, self::rules($action))->validate() + ['application' => $publicId];

        return app(BusinessOperations::class)->once($actor, 'bursary.'.$action, $data, fn () => $this->owned($actor, $publicId, true), function () use ($actor, $publicId, $data, $action): array {
            $application = $this->owned($actor, $publicId, true);
            abort_unless($application->revision === (int) $data['revision'], 409, 'This application changed. Refresh it first.');
            if ($action === 'submit') {
                abort_unless($application->state === 'draft', 409, 'This application was already sent.');
                $this->eligibleYear($application->session_year_id);
                $application->update(['state' => 'submitted', 'submitted_at' => now('UTC'), 'revision' => $application->revision + 1]);
                $this->event($application, $actor, 'submitted');
            } else {
                abort_unless(in_array($application->state, ['draft', 'submitted'], true), 409, 'A decided application cannot be withdrawn.');
                $application->update(['state' => 'withdrawn', 'revision' => $application->revision + 1]);
                $this->event($application, $actor, 'withdrawn');
            }

            return ['id' => $publicId, 'state' => $application->state, 'revision' => $application->revision];
        });
    }

    /** The guardian's own applications only; never a co-guardian's. */
    public function mine(User $actor): array
    {
        $this->auth()->parent($actor, self::MODULE);
        $items = [];
        foreach (BursaryApplication::where('guardian_user_id', $actor->id)->orderByDesc('id')->limit(50)->get() as $application) {
            $learner = Students::find($application->learner_id);
            if ($learner === null || ! $this->auth()->approving($actor, $learner)) {
                continue;
            }
            $items[] = $this->summary($application) + ['learner_name' => BusinessAuthority::learnerName($application->learner_id)];
        }

        return ['items' => $items];
    }

    private function summary(BursaryApplication $application): array
    {
        return ['id' => $application->public_id, 'learner_id' => $application->learner_id, 'session_year_id' => $application->session_year_id,
            'session_year' => (string) SessionYear::whereKey($application->session_year_id)->value('name'), 'state' => $application->state, 'revision' => $application->revision,
            'submitted_at' => $application->submitted_at?->toIso8601String(), 'decided_at' => $application->decided_at?->toIso8601String(),
            'award_kind' => $application->award_kind, 'award_value' => $application->award_value, 'currency' => $application->currency,
            'erased' => $application->erased_at !== null];
    }

    private function documents(BursaryApplication $application): array
    {
        return BursaryDocument::where('application_id', $application->id)->orderBy('id')->get()
            ->map(fn (BursaryDocument $d) => ['id' => $d->handle, 'category' => $d->category, 'mime' => $d->mime, 'bytes' => $d->bytes])->values()->all();
    }

    public function read(User $actor, string $publicId): array
    {
        $application = $this->owned($actor, $publicId);
        $result = $this->summary($application) + ['household' => $application->household, 'documents' => $this->documents($application)];
        $this->owned($actor, $publicId);

        return $result;
    }

    /** Committee queue: metadata first; household details only on one application. */
    public function queue(User $actor, array $input): array
    {
        $this->auth()->staff($actor, self::MODULE, self::PERMISSION);
        $data = Validator::make($input, self::rules('queue'))->validate();
        $page = (int) ($data['page'] ?? 1);
        $query = BursaryApplication::where('state', $data['state'] ?? 'submitted')->orderBy('submitted_at')->orderBy('id');
        $total = (clone $query)->count();
        $rows = $query->forPage($page, 50)->get();
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->summary($row) + ['learner_name' => BusinessAuthority::learnerName($row->learner_id)];
        }

        return ['total' => $total, 'page' => $page, 'items' => $items];
    }

    public function review(User $actor, string $publicId): array
    {
        $application = $this->staffApplication($actor, $publicId);
        $result = $this->summary($application) + [
            'learner_name' => BusinessAuthority::learnerName($application->learner_id),
            'guardian_name' => BusinessAuthority::personName($application->guardian_user_id),
            'household' => $application->household, 'documents' => $this->documents($application),
            'events' => $this->events($application),
            'fee_discount_id' => $application->fee_discount_id,
        ];
        $this->staffApplication($actor, $publicId);

        return $result;
    }

    /** @return list<array<string, mixed>> */
    private function events(BursaryApplication $application): array
    {
        $events = [];
        foreach (BursaryEvent::where('application_id', $application->id)->orderBy('id')->get() as $e) {
            $events[] = ['action' => $e->action, 'actor_id' => $e->actor_id, 'actor_name' => BusinessAuthority::personName($e->actor_id), 'reasons' => $e->reasons,
                'award_kind' => $e->award_kind, 'award_value' => $e->award_value, 'at' => $e->created_at->toIso8601String()];
        }

        return $events;
    }

    /** A committee member's note on the record, before a decision. */
    public function note(User $actor, string $publicId, array $input): array
    {
        $this->staffApplication($actor, $publicId, true);
        $data = Validator::make($input, self::rules('note'))->validate() + ['application' => $publicId];

        return app(BusinessOperations::class)->once($actor, 'bursary.note', $data, fn () => $this->staffApplication($actor, $publicId, true), function () use ($actor, $publicId, $data): array {
            $application = $this->staffApplication($actor, $publicId, true);
            abort_unless($application->state === 'submitted', 409, 'Notes are added while the application waits for a decision.');
            $this->conflict($actor, $application);
            $this->event($application, $actor, 'review_note', $data['reasons']);

            return ['id' => $publicId, 'state' => $application->state, 'revision' => $application->revision];
        });
    }

    private function conflict(User $actor, BursaryApplication $application): void
    {
        $learner = Students::findOrFail($application->learner_id);
        abort_if((int) $application->guardian_user_id === (int) $actor->id || app(GuardianService::class)->guardianUserIds($learner)->contains((int) $actor->id), 403,
            'A guardian of this child cannot review the application.');
    }

    public function decide(User $actor, string $publicId, array $input): array
    {
        $this->staffApplication($actor, $publicId, true);
        $data = Validator::make($input, self::rules('decide'))->validate() + ['application' => $publicId];
        if ($data['outcome'] === 'award') {
            $data['award_value'] = (int) $data['award_value'];
            abort_unless($data['award_kind'] === 'fixed' || $data['award_value'] <= 10000, 422, 'A percentage award uses at most 10000 basis points.');
        }

        return app(BusinessOperations::class)->once($actor, 'bursary.decide', $data, fn () => $this->staffApplication($actor, $publicId, true), function () use ($actor, $publicId, $data): array {
            $application = $this->staffApplication($actor, $publicId, true);
            abort_unless($application->state === 'submitted' && $application->revision === (int) $data['revision'], 409, 'This application changed. Refresh it before deciding.');
            $this->conflict($actor, $application);
            $at = now('UTC');
            if ($data['outcome'] === 'decline') {
                $application->update(['state' => 'declined', 'decided_at' => $at, 'revision' => $application->revision + 1]);
                $this->event($application, $actor, 'declined', $data['reasons']);
            } else {
                $application->update(['state' => 'awarded', 'decided_at' => $at, 'award_kind' => $data['award_kind'], 'award_value' => $data['award_value'],
                    'currency' => $data['currency'], 'revision' => $application->revision + 1]);
                $this->event($application, $actor, 'awarded', $data['reasons'], $data['award_kind'], $data['award_value']);
                if ((int) $application->session_year_id === (int) $this->currentYear()->id) {
                    $this->draftDiscount($actor, $application);
                }
            }
            $this->notify($application);

            return ['id' => $publicId, 'state' => $application->state, 'revision' => $application->revision, 'fee_discount_id' => $application->fee_discount_id];
        });
    }

    /** For an award into next year: once that year is current, draft its discount. */
    public function issueDiscount(User $actor, string $publicId, array $input): array
    {
        $this->staffApplication($actor, $publicId, true);
        $data = Validator::make($input, self::rules('discount'))->validate() + ['application' => $publicId];

        return app(BusinessOperations::class)->once($actor, 'bursary.discount', $data, fn () => $this->staffApplication($actor, $publicId, true), function () use ($actor, $publicId): array {
            $application = $this->staffApplication($actor, $publicId, true);
            abort_unless($application->state === 'awarded' && $application->fee_discount_id === null, 409, 'This award already has its fee discount.');
            abort_unless((int) $application->session_year_id === (int) $this->currentYear()->id, 409, 'The award year is not the current school year yet.');
            $this->conflict($actor, $application);
            $this->draftDiscount($actor, $application);

            return ['id' => $publicId, 'state' => $application->state, 'revision' => $application->revision, 'fee_discount_id' => $application->fee_discount_id];
        });
    }

    private function draftDiscount(User $actor, BursaryApplication $application): void
    {
        $year = SessionYear::findOrFail($application->session_year_id);
        $rule = app(FeeDiscounts::class)->draftBursary($actor, ['operation_id' => (string) Str::uuid(), 'session_year_id' => $year->id,
            'name' => 'Bursary award '.substr($application->public_id, 0, 8), 'learner_id' => $application->learner_id, 'currency' => $application->currency,
            'starts_on' => substr((string) $year->getRawOriginal('start_date'), 0, 10), 'ends_on' => substr((string) $year->getRawOriginal('end_date'), 0, 10),
            'amount_kind' => $application->award_kind, 'amount_value' => $application->award_value]);
        $application->update(['fee_discount_id' => $rule->id, 'revision' => $application->revision + 1]);
        $this->event($application, $actor, 'discount_drafted');
    }

    /** A generic notice to the applying guardian; no amount, reason or child's name. */
    private function notify(BursaryApplication $application): void
    {
        $notice = app(Notifier::class)->makeNotice(self::NOTICE, __('Bursary application'), __('There is news about your bursary application. Open Bursaries to read it.'), self::LINK);
        $notice->source_key = 'bursary:'.$application->public_id.':'.$application->state;
        $notice->save();
        app(Notifier::class)->assign($notice, [$application->guardian_user_id]);
    }
}
