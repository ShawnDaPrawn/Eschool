<?php

declare(strict_types=1);

namespace App\Services\Assistance;

use App\Models\Academics\AcademicLeadership;
use App\Models\Assistance\AssistanceNoticeIntent;
use App\Models\Assistance\SupportMessage;
use App\Models\Assistance\SupportThread;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Notifications\PrivateNoticeDelivery;
use App\Services\Platform\Notifications\PrivateNoticeScope;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Notice payloads contain only an opaque intent, never a source/learner/body. */
class AssistanceNotices implements PrivateNoticeDelivery, PrivateNoticeScope
{
    public const PARENT = 'school_assistance.parent_update';

    public const OFFICE = 'school_assistance.office_update';

    public const LINK = '/ask-school';

    private function officeRecipients(): array
    {
        $today = app(AcademicAuthority::class)->today();
        $principals = AcademicLeadership::where('kind', 'principal')->where('session_year_id', (int) schoolSetting('session_year'))
            ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->pluck('user_id');
        $ids = SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())->whereIn('role', ['Admin', 'Super Admin'])->pluck('user_id');

        return User::whereIn('id', $ids->merge($principals)->unique())->where('status', 1)->orderBy('id')->limit(100)->get()
            ->filter(fn ($user) => app(AssistanceQueries::class)->available($user))->pluck('id')->all();
    }

    /** Called inside the exact domain operation transaction; outbox waits for commit. */
    public function changed(SupportThread $thread, User $sender, ?string $message = null): void
    {
        $thread = SupportThread::findOrFail($thread->id);
        $access = app(AssistanceAccess::class);
        if ((int) $sender->id === (int) $thread->parent_user_id) {
            $access->family($sender, (int) $thread->learner_id);
        } else {
            $access->office($sender);
        }
        $recipients = [(int) $thread->parent_user_id => self::PARENT];
        foreach ($thread->assigned_user_id === null ? $this->officeRecipients() : [(int) $thread->assigned_user_id] as $id) {
            $recipients[(int) $id] = self::OFFICE;
        }
        unset($recipients[(int) $sender->id]);
        foreach ($recipients as $id => $type) {
            $actor = User::find($id);
            if ($actor === null) {
                continue;
            }
            $key = $thread->id.':'.$thread->revision.':'.$id.':'.$type;
            $intent = AssistanceNoticeIntent::firstOrCreate(['source_key' => $key], ['id' => (string) Str::uuid(),
                'recipient_user_id' => $id, 'thread_id' => $thread->id, 'message_id' => $message, 'type' => $type,
                'queue_only' => $type === self::OFFICE && $thread->assigned_user_id === null,
                'expires_at' => min($thread->expires_at, now()->utc()->addDays(30))]);
            if (! $this->permitted($actor, $intent)) {
                if ($intent->wasRecentlyCreated) {
                    $intent->delete();
                }

                continue;
            }
            if ($intent->notification_id === null) {
                $notice = app(Notifier::class)->makeNotice($type, __('School support update'), __('Open Ask the school to check your available school-support updates.'), self::LINK);
                $notice->source_key = 'school-assistance:'.$intent->id;
                $notice->delivery_payload = ['link' => self::LINK, 'assistance_intent_id' => $intent->id];
                $notice->save();
                $intent->update(['notification_id' => $notice->id]);
                app(Notifier::class)->assign($notice, [$id]);
            }
            // Notices are a bounded transient inbox, not the conversation ledger.
            $old = AssistanceNoticeIntent::where('recipient_user_id', $id)->orderByDesc('created_at')->orderByDesc('id')->skip(500)->take(500)->pluck('id');
            AssistanceNoticeIntent::whereIn('id', $old)->delete();
        }
    }

    private function permitted(User $actor, AssistanceNoticeIntent $intent): bool
    {
        if ((int) $intent->recipient_user_id !== (int) $actor->id || ! $intent->expires_at->isFuture()) {
            return false;
        }
        try {
            $access = app(AssistanceAccess::class);
            if ($intent->type === self::PARENT) {
                $thread = $access->thread($actor, $intent->thread_id);
            } elseif ($intent->type === self::OFFICE && $intent->queue_only) {
                $access->office($actor);
                $thread = SupportThread::findOrFail($intent->thread_id);
                if ($thread->assigned_user_id !== null || $thread->state !== 'open' || $thread->purged_at !== null || ! $thread->expires_at->isFuture()) {
                    return false;
                }
                $access->family(User::findOrFail($thread->parent_user_id), (int) $thread->learner_id);
            } elseif ($intent->type === self::OFFICE) {
                $thread = $access->thread($actor, $intent->thread_id, false, true);
            } else {
                return false;
            }

            return $intent->message_id === null || SupportMessage::whereKey($intent->message_id)->where('thread_id', $thread->id)->whereNull('purged_at')->exists();
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    public function allows(User $actor, string $type, array $payload): bool
    {
        if (! in_array($type, [self::PARENT, self::OFFICE], true) || ($payload['link'] ?? null) !== self::LINK
            || ! is_string($payload['assistance_intent_id'] ?? null) || ! Str::isUuid($payload['assistance_intent_id'])) {
            return false;
        }
        try {
            app(AssistanceAccess::class)->actor($actor);
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
        $intent = AssistanceNoticeIntent::whereKey($payload['assistance_intent_id'])->where('type', $type)->first();

        return $intent !== null && $this->permitted($actor, $intent);
    }

    public function apply(Builder $notifications, User $actor): void
    {
        if (! app(ModuleGate::class)->allows('ask_school')) {
            $notifications->whereRaw('1 = 0');

            return;
        }
        $allowed = AssistanceNoticeIntent::where('recipient_user_id', $actor->id)->where('expires_at', '>', now()->utc())
            ->orderByDesc('created_at')->orderByDesc('id')->limit(500)->get()
            ->filter(fn ($intent) => $this->permitted($actor, $intent))->pluck('id')->all();
        $payload = $notifications->qualifyColumn('delivery_payload');
        $safe = "IF(JSON_VALID($payload), $payload, '{}')";
        $intents = AssistanceNoticeIntent::whereIn('id', $allowed)->where('recipient_user_id', $actor->id)->where('expires_at', '>', now()->utc())
            ->whereColumn('notification_id', $notifications->qualifyColumn('id'))
            ->whereColumn('type', $notifications->qualifyColumn('type'))
            ->whereRaw("school_assistance_notice_intents.id = JSON_UNQUOTE(JSON_EXTRACT($safe, '$.assistance_intent_id'))");
        $notifications->whereIn('type', [self::PARENT, self::OFFICE])
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT($safe, '$.link')) = ?", [self::LINK])
            ->whereExists($intents->selectRaw('1')->toBase());
    }

    public function prune(): int
    {
        return AssistanceNoticeIntent::where('expires_at', '<=', now()->utc())->delete();
    }
}
