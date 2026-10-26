<?php

declare(strict_types=1);

namespace App\Services\FamilyForms;

use App\Models\FamilyForms\ConsentRecipient;
use App\Models\FamilyForms\ConsentRequest;
use App\Models\Notification;
use App\Models\Platform\School;
use App\Models\Students;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Notifications\PrivateNoticeDelivery;
use App\Services\Platform\Notifications\PrivateNoticeScope;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** No child IDs, names, wording, health fields or event details enter notices. */
class ConsentNotices implements PrivateNoticeDelivery, PrivateNoticeScope
{
    public const TYPE = 'consent.requested';

    public const LINK = '/family-forms';

    private function key(string $recipient, int $actor): string
    {
        return 'consent:'.$recipient.':'.$actor;
    }

    public function requested(string $id): void
    {
        $request = ConsentRequest::select(['id', 'status', 'revision', 'respond_by', 'session_year_id'])->findOrFail($id);
        if ($request->status !== 'open' || ! $request->respond_by->isFuture() || (int) $request->session_year_id !== (int) schoolSetting('session_year')) {
            return;
        }
        foreach (ConsentRecipient::where('request_id', $id)->where('revision', 0)->orderBy('id')->cursor() as $recipient) {
            $learner = Students::find($recipient->learner_id);
            if ($learner === null) {
                continue;
            }
            foreach (app(GuardianService::class)->guardianUserIds($learner)->unique() as $actorId) {
                $actor = User::find($actorId);
                if ($actor === null) {
                    continue;
                }
                try {
                    app(Consents::class)->noticeSource($actor, $recipient->id);
                } catch (HttpExceptionInterface|ModelNotFoundException) {
                    continue;
                }
                $notice = DB::transaction(function () use ($recipient, $actor, $request) {
                    School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
                    $key = $this->key($recipient->id, (int) $actor->id);
                    $existing = Notification::withTrashed()->where('source_key', $key)->first();
                    if ($existing !== null) {
                        return $existing->trashed() ? null : $existing;
                    }
                    $notice = app(Notifier::class)->makeNotice(self::TYPE, __('Activity permission requested'), __('Open Family consent and health to review a request.'), self::LINK);
                    $notice->source_key = $key;
                    $notice->delivery_payload = ['link' => self::LINK, 'consent_recipient_id' => $recipient->id, 'consent_notice_id' => (int) $notice->id,
                        'consent_revision' => $request->revision];
                    $notice->save();

                    return $notice;
                });
                if ($notice !== null && $this->allows($actor, self::TYPE, $notice->delivery_payload)) {
                    app(Notifier::class)->assign($notice, [(int) $actor->id]);
                }
            }
        }
    }

    public function allows(User $actor, string $type, array $payload): bool
    {
        if ($type !== self::TYPE || ($payload['link'] ?? null) !== self::LINK || ! is_string($payload['consent_recipient_id'] ?? null)
            || ! Str::isUuid($payload['consent_recipient_id']) || ! is_int($payload['consent_notice_id'] ?? null) || $payload['consent_notice_id'] < 1
            || ! is_int($payload['consent_revision'] ?? null) || $payload['consent_revision'] < 1) {
            return false;
        }
        try {
            // Fresh parent authority precedes even notice-reference metadata.
            $source = app(Consents::class)->noticeSource($actor, $payload['consent_recipient_id']);
            if ($source['request_revision'] !== $payload['consent_revision']) {
                return false;
            }

            return Notification::whereKey($payload['consent_notice_id'])->where('type', self::TYPE)
                ->where('source_key', $this->key($payload['consent_recipient_id'], (int) $actor->id))->exists();
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    public function apply(Builder $notifications, User $actor): void
    {
        // Most schools have this optional feature disabled; reuse the loaded
        // module map before any additional identity/source queries.
        if (! app(ModuleGate::class)->allows('family_forms')) {
            $notifications->whereRaw('1 = 0');

            return;
        }
        app(FamilyFormAccess::class)->parentActor($actor);
        $links = UserNotification::where('user_id', $actor->id)->select('notification_id');
        $allowed = [];
        // Metadata only and this actor's recipient links; never select notice text.
        $payload = $notifications->qualifyColumn('delivery_payload');
        $safe = "IF(JSON_VALID($payload), $payload, '{}')";
        $requests = ConsentRequest::whereColumn('consent_requests.id', 'consent_recipients.request_id')->where('status', 'open')
            ->where('session_year_id', (int) schoolSetting('session_year'))->where('respond_by', '>', now()->utc())
            ->whereRaw("CAST(consent_requests.revision AS CHAR) = JSON_UNQUOTE(JSON_EXTRACT($safe, '$.consent_revision'))")->selectRaw('1');
        $recipients = ConsentRecipient::where('revision', 0)
            ->whereRaw("consent_recipients.id = JSON_UNQUOTE(JSON_EXTRACT($safe, '$.consent_recipient_id'))")
            ->whereExists($requests->toBase())->selectRaw('1');
        foreach (Notification::where('type', self::TYPE)->whereIn('id', $links)->whereExists($recipients->toBase())
            ->whereRaw("source_key = CONCAT('consent:', JSON_UNQUOTE(JSON_EXTRACT($safe, '$.consent_recipient_id')), ':', ?)", [(int) $actor->id])
            ->select(['id', 'delivery_payload'])->cursor() as $row) {
            if ($this->allows($actor, self::TYPE, $row->delivery_payload ?? [])) {
                $allowed[] = (int) $row->id;
            }
        }
        $notifications->where($notifications->qualifyColumn('type'), self::TYPE)
            ->where($notifications->qualifyColumn('school_id'), app(CurrentSchool::class)->id())->whereIn($notifications->qualifyColumn('id'), $allowed);
    }
}
